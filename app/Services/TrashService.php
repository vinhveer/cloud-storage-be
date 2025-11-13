<?php

namespace App\Services;

use App\Models\Folder;
use App\Models\File as FileModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\FileVersionService;
use App\Exceptions\DomainValidationException;
use App\Models\FileVersion as FileVersionModel;
use App\Repositories\TrashRepository;

class TrashService
{
    private FileVersionService $versions;

    public function __construct(FileVersionService $versions)
    {
        $this->versions = $versions;
        // create repo via container to avoid breaking constructor signature for DI
        $this->trashRepo = app(TrashRepository::class);
    }
    
    /**
     * Permanently delete a trashed file and all its versions (physical + DB).
     * Enforces that the file is in trash and is a top-level node (folder_id === null).
     *
     * @param mixed $user
     * @param int $fileId
     * @throws DomainValidationException
     */
    public function permanentlyDeleteFile($user, int $fileId): void
    {
        DB::beginTransaction();
        try {
            $file = FileModel::onlyTrashed()->where('id', $fileId)->where('user_id', $user->id)->first();
            if (! $file) {
                throw new DomainValidationException('File not found in trash');
            }

            if (! ($file->is_deleted && $file->deleted_at)) {
                throw new DomainValidationException('Item is not in trash');
            }

            if ($file->folder_id !== null) {
                throw new DomainValidationException('Cannot delete child item directly. Please delete the top-level parent.');
            }

            // delegate actual deletion to non-transactional helper
            $this->performDeleteFileWithoutTransaction($file, $user);

            DB::commit();
            return;
        } catch (DomainValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw new DomainValidationException('Failed to delete file: ' . $e->getMessage());
        }
    }

    /**
     * Permanently delete a trashed folder and all its trashed children/files recursively.
     * Enforces that the folder is in trash and is a top-level node (fol_folder_id === null).
     *
     * @param mixed $user
     * @param int $folderId
     * @throws DomainValidationException
     */
    public function permanentlyDeleteFolder($user, int $folderId): void
    {
        DB::beginTransaction();
        try {
            $folder = Folder::onlyTrashed()->where('id', $folderId)->where('user_id', $user->id)->first();
            if (! $folder) {
                throw new DomainValidationException('Folder not found in trash');
            }

            if (! ($folder->is_deleted && $folder->deleted_at)) {
                throw new DomainValidationException('Item is not in trash');
            }

            if ($folder->fol_folder_id !== null) {
                throw new DomainValidationException('Cannot delete child item directly. Please delete the top-level parent.');
            }

            // perform deletion without opening nested transactions
            $this->performDeleteFolderWithoutTransaction($folder, $user);

            DB::commit();
            return;
        } catch (DomainValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw new DomainValidationException('Failed to delete folder: ' . $e->getMessage());
        }
    }

    /**
     * Perform file deletion without managing DB transactions. This allows callers
     * to wrap multiple deletions in a single transaction (e.g. emptyTrash).
     *
     * @param \App\Models\File $file
     * @param mixed $user
     * @throws DomainValidationException
     */
    private function performDeleteFileWithoutTransaction(FileModel $file, $user): void
    {
        $diskName = config('filesystems.default', 'local');
        $disk = Storage::disk($diskName);

        $totalFreed = 0;
        $versions = $file->versions()->orderBy('version_number')->get();
        foreach ($versions as $version) {
            $ext = $version->file_extension;
            $uuid = $version->uuid;
            $versionNumber = $version->version_number;
            $size = (int) ($version->file_size ?? 0);

            $path = "files/{$file->id}/v{$versionNumber}/" . $uuid . ($ext ? ".{$ext}" : '');

            if ($disk->exists($path)) {
                $deleted = $disk->delete($path);
                if (! $deleted) {
                    throw new DomainValidationException('Failed to delete file content');
                }
            }

            // remove DB record
            $version->delete();
            $totalFreed += $size;
        }

        // adjust owner's storage_used
        $owner = $file->user;
        if ($owner) {
            $used = (int) ($owner->storage_used ?? 0);
            $newUsed = $used - $totalFreed;
            $owner->storage_used = $newUsed < 0 ? 0 : $newUsed;
            $owner->save();
        }

        // clear file metadata
        $file->file_size = 0;
        $file->mime_type = null;
        $file->file_extension = null;
        $file->save();

        // remove empty directories
        try {
            $baseDir = "files/{$file->id}";
            $versionDirs = $disk->directories($baseDir) ?: [];
            foreach ($versionDirs as $vd) {
                $filesInDir = $disk->allFiles($vd) ?: [];
                if (empty($filesInDir)) {
                    $disk->deleteDirectory($vd);
                }
            }

            $remaining = $disk->allFiles($baseDir) ?: [];
            if (empty($remaining)) {
                $disk->deleteDirectory($baseDir);
            }
        } catch (\Throwable $_) {
            // ignore
        }

        // finally remove file record
        if ($file->exists) {
            $file->forceDelete();
        }
    }

    /**
     * Perform folder deletion (and its trashed descendants) without managing DB transactions.
     * This will delete trashed files (and their versions) and trashed child folders recursively.
     *
     * @param \App\Models\Folder $folder
     * @param mixed $user
     * @return void
     * @throws DomainValidationException
     */
    private function performDeleteFolderWithoutTransaction(Folder $folder, $user): void
    {
        // delete trashed files in this folder
        $files = FileModel::onlyTrashed()->where('user_id', $user->id)->where('folder_id', $folder->id)->get();
        foreach ($files as $file) {
            if (! ($file->is_deleted && $file->deleted_at)) {
                continue;
            }
            // directly perform deletion without top-level check
            $this->performDeleteFileWithoutTransaction($file, $user);
        }

        // process children folders
        $children = Folder::onlyTrashed()->where('user_id', $user->id)->where('fol_folder_id', $folder->id)->get();
        foreach ($children as $child) {
            $this->performDeleteFolderWithoutTransaction($child, $user);
        }

        // finally delete this folder record
        if ($folder->exists) {
            $folder->forceDelete();
        }
    }
    /**
     * Return combined trashed items (folders + files) normalized and paginated.
     *
     * @param  \App\Models\User  $user
     * @param  string|null $search
     * @param  int $page
     * @param  int $perPage
     * @return array ['items' => array, 'pagination' => array]
     */
    public function getCombinedTrash($user, $search, int $page, int $perPage): array
    {
        // Counts
        $foldersCountQuery = DB::table('folders as f')
            ->leftJoin('folders as parent', 'f.fol_folder_id', '=', 'parent.id')
            ->whereNotNull('f.deleted_at')
            ->where('f.user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('f.fol_folder_id')
                  ->orWhereNull('parent.deleted_at');
            });
        if ($search) {
            $foldersCountQuery->where('f.folder_name', 'like', "%{$search}%");
        }
        $totalFolders = (int) $foldersCountQuery->count();

        $filesCountQuery = DB::table('files as fi')
            ->leftJoin('folders as pf', 'fi.folder_id', '=', 'pf.id')
            ->whereNotNull('fi.deleted_at')
            ->where('fi.user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('fi.folder_id')
                  ->orWhereNull('pf.deleted_at');
            });
        if ($search) {
            $filesCountQuery->where('fi.display_name', 'like', "%{$search}%");
        }
        $totalFiles = (int) $filesCountQuery->count();

        $totalItems = $totalFolders + $totalFiles;

        // Subqueries
        $foldersSub = DB::table('folders as f')
            ->leftJoin('folders as parent', 'f.fol_folder_id', '=', 'parent.id')
            ->selectRaw("f.id as item_id, 'folder' as item_type, f.folder_name as title, f.deleted_at, NULL as file_size, NULL as mime_type, NULL as file_extension, f.fol_folder_id as parent_id")
            ->whereNotNull('f.deleted_at')
            ->where('f.user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('f.fol_folder_id')
                  ->orWhereNull('parent.deleted_at');
            });
        if ($search) {
            $foldersSub->where('f.folder_name', 'like', "%{$search}%");
        }

        $filesSub = DB::table('files as fi')
            ->leftJoin('folders as pf', 'fi.folder_id', '=', 'pf.id')
            ->selectRaw("fi.id as item_id, 'file' as item_type, fi.display_name as title, fi.deleted_at, fi.file_size, fi.mime_type, fi.file_extension, fi.folder_id as parent_id")
            ->whereNotNull('fi.deleted_at')
            ->where('fi.user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('fi.folder_id')
                  ->orWhereNull('pf.deleted_at');
            });
        if ($search) {
            $filesSub->where('fi.display_name', 'like', "%{$search}%");
        }

        $union = $foldersSub->unionAll($filesSub);

        $offset = ($page - 1) * $perPage;
        $rows = DB::query()
            ->fromSub($union, 'trash_items')
            ->orderByRaw('deleted_at DESC, item_id DESC')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        $items = $rows->map(function ($r) {
            return [
                'id' => $r->item_id,
                'type' => $r->item_type,
                'title' => $r->title,
                'deleted_at' => $r->deleted_at,
                'file_size' => isset($r->file_size) ? (int) $r->file_size : null,
                'mime_type' => $r->mime_type ?? null,
                'file_extension' => $r->file_extension ?? null,
                'parent_id' => $r->parent_id ?? null,
            ];
        })->all();

        return [
            'items' => $items,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_pages' => (int) ceil($totalItems / max($perPage, 1)),
                'total_items' => $totalItems,
            ],
        ];
    }

    /**
     * Return immediate trashed children folders and trashed files inside a trashed folder.
     *
     * @param  \App\Models\User $user
     * @param  int $folderId
     * @param  string|null $search
     * @param  int $page
     * @param  int $perPage
     * @return array
     */
    public function getFolderContents($user, int $folderId, $search, int $page, int $perPage): array
    {
        $childrenQuery = Folder::onlyTrashed()->where('user_id', $user->id)->where('fol_folder_id', $folderId);
        if ($search) {
            $childrenQuery->where('folder_name', 'like', "%{$search}%");
        }
        $totalChildren = (int) $childrenQuery->count();
        $children = $childrenQuery->orderBy('deleted_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()->map(fn($f) => [
                'folder_id' => $f->id,
                'folder_name' => $f->folder_name,
                'deleted_at' => $f->deleted_at ? $f->deleted_at->toIso8601String() : null,
            ])->all();

        $filesQuery = FileModel::onlyTrashed()->where('user_id', $user->id)->where('folder_id', $folderId);
        if ($search) {
            $filesQuery->where('display_name', 'like', "%{$search}%");
        }
        $totalFiles = (int) $filesQuery->count();
        $files = $filesQuery->orderBy('deleted_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()->map(fn($f) => [
                'file_id' => $f->id,
                'display_name' => $f->display_name,
                'file_size' => (int) $f->file_size,
                'mime_type' => $f->mime_type,
                'file_extension' => $f->file_extension,
                'deleted_at' => $f->deleted_at ? $f->deleted_at->toIso8601String() : null,
            ])->all();

        return [
            'folders' => $children,
            'folders_pagination' => [
                'current_page' => $page,
                'total_pages' => (int) ceil($totalChildren / max($perPage, 1)),
                'total_items' => $totalChildren,
            ],
            'files' => $files,
            'files_pagination' => [
                'current_page' => $page,
                'total_pages' => (int) ceil($totalFiles / max($perPage, 1)),
                'total_items' => $totalFiles,
            ],
        ];
    }

    /**
     * Restore a trashed file. Only allowed for top-level files (folder_id === null).
     *
     * @param mixed $user
     * @param int $fileId
     * @return \App\Models\File
     * @throws DomainValidationException
     */
    public function restoreFile($user, int $fileId): FileModel
    {
        DB::beginTransaction();
        try {
            $file = FileModel::onlyTrashed()->where('id', $fileId)->where('user_id', $user->id)->first();
            if (! $file) {
                throw new DomainValidationException('File not found in trash');
            }

            if (! ($file->is_deleted && $file->deleted_at)) {
                throw new DomainValidationException('Item is not in trash');
            }

            if ($file->folder_id !== null) {
                throw new DomainValidationException('Cannot restore child item directly. Please restore the top-level parent.');
            }

            // clear soft-delete flag and restore
            $file->is_deleted = false;
            $file->save();
            $file->restore();

            DB::commit();
            return $file;
        } catch (DomainValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw new DomainValidationException('Failed to restore file: ' . $e->getMessage());
        }
    }

    /**
     * Restore a trashed folder and all its trashed descendants (folders + files).
     * Only allowed on top-level folders (fol_folder_id === null).
     *
     * @param mixed $user
     * @param int $folderId
     * @return \App\Models\Folder
     * @throws DomainValidationException
     */
    public function restoreFolder($user, int $folderId): Folder
    {
        DB::beginTransaction();
        try {
            $folder = Folder::onlyTrashed()->where('id', $folderId)->where('user_id', $user->id)->first();
            if (! $folder) {
                throw new DomainValidationException('Folder not found in trash');
            }

            if (! ($folder->is_deleted && $folder->deleted_at)) {
                throw new DomainValidationException('Item is not in trash');
            }

            if ($folder->fol_folder_id !== null) {
                throw new DomainValidationException('Cannot restore child item directly. Please restore the top-level parent.');
            }

            $self = $this;
            $recurse = function (Folder $f) use (&$recurse, $user, $self) {
                // restore trashed files in this folder
                $files = FileModel::onlyTrashed()->where('user_id', $user->id)->where('folder_id', $f->id)->get();
                foreach ($files as $file) {
                    if (! ($file->is_deleted && $file->deleted_at)) {
                        continue;
                    }
                    $file->is_deleted = false;
                    $file->save();
                    $file->restore();
                }

                // process children folders
                $children = Folder::onlyTrashed()->where('user_id', $user->id)->where('fol_folder_id', $f->id)->get();
                foreach ($children as $child) {
                    $recurse($child);
                }

                // finally restore this folder
                if ($f->exists) {
                    $f->is_deleted = false;
                    $f->save();
                    $f->restore();
                }
            };

            $recurse($folder);

            DB::commit();
            return $folder;
        } catch (DomainValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw new DomainValidationException('Failed to restore folder: ' . $e->getMessage());
        }
    }

    /**
     * Permanently empty the user's trash: delete all trashed top-level folders and files
     * and their trashed descendants/versions. Returns counts of deleted items.
     *
     * @param mixed $user
     * @return array ['files' => int, 'folders' => int]
     * @throws DomainValidationException
     */
    public function emptyTrash($user): array
    {
        DB::beginTransaction();
        try {
            $deletedFiles = 0;
            $deletedFolders = 0;

                        // Find trashed root folders via repository
                        $rootFolderIds = $this->trashRepo->getRootTrashedFolderIdsByUser($user->id);

            // For each root folder, count trashed descendants (folders + files) then delete
            foreach ($rootFolderIds as $fid) {
                $folder = Folder::onlyTrashed()->where('id', $fid)->where('user_id', $user->id)->first();
                if (! $folder) {
                    continue;
                }

                $folderCount = 0;
                $fileCount = 0;

                $collect = function (Folder $f) use (&$collect, $user, &$folderCount, &$fileCount) {
                    $folderCount++;

                        $files = $this->trashRepo->getTrashedFilesInFolder($user->id, $f->id);
                    $fileCount += $files->count();

                    $children = $this->trashRepo->getTrashedChildrenFolders($user->id, $f->id);
                    foreach ($children as $child) {
                        $collect($child);
                    }
                };

                $collect($folder);

                // perform deletion using non-transactional helper so we don't open nested transactions
                // and so child files/folders can be removed regardless of top-level checks
                $this->performDeleteFolderWithoutTransaction($folder, $user);

                $deletedFolders += $folderCount;
                $deletedFiles += $fileCount;
            }

                        // Find trashed root files via repository
                        $rootFileIds = $this->trashRepo->getRootTrashedFileIdsByUser($user->id);

            foreach ($rootFileIds as $fileId) {
                // ensure it's still trashed and top-level (service will validate)
                $file = $this->trashRepo->findTrashedFileByIdAndUser($fileId, $user->id);
                if (! $file) {
                    continue;
                }

                // delete file and its versions using non-transactional helper
                $this->performDeleteFileWithoutTransaction($file, $user);
                $deletedFiles++;
            }

            DB::commit();

            return ['files' => $deletedFiles, 'folders' => $deletedFolders];
        } catch (DomainValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw new DomainValidationException('Failed to empty trash: ' . $e->getMessage());
        }
    }
}
