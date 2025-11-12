<?php

namespace App\Repositories;

use App\Models\File;
use App\Models\Folder;
use App\Models\FileVersion;
use App\Models\SystemConfig;
use App\Exceptions\DomainValidationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\User;

class BulkRepository
{
    /**
     * Move files to trash for a given user. Returns detailed result arrays.
     * Only files that belong to the user and are not already deleted will be affected.
     *
     * @param array $fileIds
     * @param \App\Models\User $user
     * @return array{
     *   requested: array<int>,
     *   found: array<int>,
     *   not_found: array<int>,
     *   not_owned: array<int>,
     *   already_deleted: array<int>,
     *   deleted: array<int>,
     * }
     */
    public function moveFilesToTrash(array $fileIds, User $user): array
    {
        $requested = array_values(array_unique(array_map('intval', $fileIds)));

        if (count($requested) === 0) {
            return [
                'requested' => [],
                'found' => [],
                'not_found' => [],
                'not_owned' => [],
                'already_deleted' => [],
                'deleted' => [],
            ];
        }

        $records = File::whereIn('id', $requested)->get(['id', 'user_id', 'is_deleted']);
        $found = $records->pluck('id')->map(fn($v) => (int) $v)->all();
        $notFound = array_values(array_diff($requested, $found));

        $notOwned = $records->filter(fn($r) => $r->user_id !== $user->id)->pluck('id')->map(fn($v) => (int) $v)->all();
        $alreadyDeleted = $records->filter(fn($r) => (bool) $r->is_deleted)->pluck('id')->map(fn($v) => (int) $v)->all();

        $toDelete = $records->filter(fn($r) => $r->user_id === $user->id && ! $r->is_deleted)->pluck('id')->map(fn($v) => (int) $v)->all();

        $deleted = [];
        if (! empty($toDelete)) {
            $now = now();
            // update only owned and not-deleted files
            File::query()
                ->whereIn('id', $toDelete)
                ->where('user_id', $user->id)
                ->where('is_deleted', false)
                ->update(['is_deleted' => true, 'deleted_at' => $now]);

            // confirm updated ids
            $deleted = File::withTrashed()->whereIn('id', $toDelete)->where('user_id', $user->id)->pluck('id')->map(fn($v) => (int) $v)->all();
        }

        return [
            'requested' => $requested,
            'found' => $found,
            'not_found' => $notFound,
            'not_owned' => $notOwned,
            'already_deleted' => $alreadyDeleted,
            'deleted' => $deleted,
        ];
    }

    /**
     * Move folders (and their descendant folders + contained files) to trash for a given user.
     * Returns array of affected folder ids.
     *
     * @param array $folderIds
     * @param \App\Models\User $user
     * @return array{
     *   folders: array<int>,
     *   files: array<int>
     * }
     */
    public function moveFoldersToTrash(array $folderIds, User $user): array
    {
        $requested = array_values(array_unique(array_map('intval', $folderIds)));

        if (count($requested) === 0) {
            return [
                'requested' => [],
                'found' => [],
                'not_found' => [],
                'not_owned' => [],
                'already_deleted' => [],
                'deleted_folders' => [],
                'deleted_files' => [],
            ];
        }

        $records = Folder::whereIn('id', $requested)->get(['id', 'user_id', 'is_deleted']);
        $found = $records->pluck('id')->map(fn($v) => (int) $v)->all();
        $notFound = array_values(array_diff($requested, $found));

        $notOwned = $records->filter(fn($r) => $r->user_id !== $user->id)->pluck('id')->map(fn($v) => (int) $v)->all();
        $alreadyDeleted = $records->filter(fn($r) => (bool) $r->is_deleted)->pluck('id')->map(fn($v) => (int) $v)->all();

        // Only use roots that belong to user
        $ownedRootIds = $records->filter(fn($r) => $r->user_id === $user->id)->pluck('id')->map(fn($v) => (int) $v)->all();

        $allFolderIds = [];
        foreach ($ownedRootIds as $rid) {
            $ids = $this->collectDescendantFolderIds([$rid], $user->id);
            $allFolderIds = array_merge($allFolderIds, $ids);
        }

        $allFolderIds = array_values(array_unique($allFolderIds));

        $deletedFolders = [];
        $deletedFiles = [];

        if (! empty($allFolderIds)) {
            $now = now();
            // Update folders
            Folder::query()
                ->whereIn('id', $allFolderIds)
                ->where('user_id', $user->id)
                ->where('is_deleted', false)
                ->update(['is_deleted' => true, 'deleted_at' => $now]);

            // Update files under those folders
            File::query()
                ->whereIn('folder_id', $allFolderIds)
                ->where('user_id', $user->id)
                ->where('is_deleted', false)
                ->update(['is_deleted' => true, 'deleted_at' => $now]);

            $deletedFolders = Folder::withTrashed()->whereIn('id', $allFolderIds)->where('user_id', $user->id)->pluck('id')->map(fn($v) => (int) $v)->all();
            $deletedFiles = File::withTrashed()->whereIn('folder_id', $allFolderIds)->where('user_id', $user->id)->pluck('id')->map(fn($v) => (int) $v)->all();
        }

        return [
            'requested' => $requested,
            'found' => $found,
            'not_found' => $notFound,
            'not_owned' => $notOwned,
            'already_deleted' => $alreadyDeleted,
            'deleted_folders' => $deletedFolders,
            'deleted_files' => $deletedFiles,
        ];
    }

    /**
     * Collect descendant folder ids for the given roots limited to a user's folders.
     * Returns unique ids including the original roots.
     *
     * @param array $rootIds
     * @param int $userId
     * @return array<int>
     */
    protected function collectDescendantFolderIds(array $rootIds, int $userId): array
    {
        $collected = [];
        $queue = array_values($rootIds);

        while (! empty($queue)) {
            $current = array_pop($queue);
            if (in_array($current, $collected, true)) {
                continue;
            }
            // Only include if folder exists and belongs to user
            $exists = Folder::query()
                ->where('id', $current)
                ->where('user_id', $userId)
                ->exists();

            if (! $exists) {
                continue;
            }

            $collected[] = (int) $current;

            $children = Folder::query()
                ->where('fol_folder_id', $current)
                ->where('user_id', $userId)
                ->pluck('id')
                ->map(fn($v) => (int) $v)
                ->all();

            foreach ($children as $c) {
                if (! in_array($c, $collected, true)) {
                    $queue[] = $c;
                }
            }
        }

        return array_values(array_unique($collected));
    }

    /**
     * Move files to a destination folder for a given user. Returns detailed result arrays.
     * Only files that belong to the user and are not deleted will be affected.
     *
     * @param array $fileIds
     * @param \App\Models\User $user
     * @param int $destinationFolderId
     * @return array{
     *   requested: array<int>,
     *   found: array<int>,
     *   not_found: array<int>,
     *   not_owned: array<int>,
     *   already_deleted: array<int>,
     *   moved: array<int>,
     * }
     */
    public function moveFilesToFolder(array $fileIds, User $user, ?int $destinationFolderId): array
    {
        $requested = array_values(array_unique(array_map('intval', $fileIds)));

        if (count($requested) === 0) {
            return [
                'requested' => [],
                'found' => [],
                'not_found' => [],
                'not_owned' => [],
                'already_deleted' => [],
                'moved' => [],
            ];
        }

        $records = File::whereIn('id', $requested)->get(['id', 'user_id', 'is_deleted', 'folder_id']);
        $found = $records->pluck('id')->map(fn($v) => (int) $v)->all();
        $notFound = array_values(array_diff($requested, $found));

        $notOwned = $records->filter(fn($r) => $r->user_id !== $user->id)->pluck('id')->map(fn($v) => (int) $v)->all();
        $alreadyDeleted = $records->filter(fn($r) => (bool) $r->is_deleted)->pluck('id')->map(fn($v) => (int) $v)->all();

        $toMove = $records->filter(fn($r) => $r->user_id === $user->id && ! $r->is_deleted)->pluck('id')->map(fn($v) => (int) $v)->all();

        $moved = [];
        if (! empty($toMove)) {
            // Move files individually so we can detect name collisions and apply "_copy" suffix when needed.
            $movedIds = [];
            $files = File::whereIn('id', $toMove)->where('user_id', $user->id)->where('is_deleted', false)->get();
            foreach ($files as $file) {
                $origDisplay = $file->display_name ?? '';
                $candidate = $origDisplay;
                $i = 0;
                while (true) {
                    $q = File::where('display_name', $candidate)->where('user_id', $user->id);
                    if ($destinationFolderId === null) {
                        $q = $q->whereNull('folder_id');
                    } else {
                        $q = $q->where('folder_id', $destinationFolderId);
                    }
                    // exclude the file itself if for some reason it already exists in destination (shouldn't)
                    $q = $q->where('id', '<>', $file->id);
                    if (! $q->exists()) {
                        break;
                    }
                    $i++;
                    $suffix = $i === 1 ? '_copy' : "_copy_{$i}";
                    // preserve extension if present in display name
                    $ext = $file->file_extension ?? null;
                    if ($ext && pathinfo($origDisplay, PATHINFO_EXTENSION) === $ext) {
                        $base = pathinfo($origDisplay, PATHINFO_FILENAME);
                        $candidate = $base . $suffix . ($ext ? ".{$ext}" : '');
                    } else {
                        $candidate = $origDisplay . $suffix . ($ext ? ".{$ext}" : '');
                    }
                }
                $newDisplay = $candidate;

                // perform update
                $file->folder_id = ($destinationFolderId === null ? null : $destinationFolderId);
                $file->display_name = $newDisplay;
                $file->save();

                $movedIds[] = (int) $file->id;
            }

            $moved = $movedIds;
        }

        return [
            'requested' => $requested,
            'found' => $found,
            'not_found' => $notFound,
            'not_owned' => $notOwned,
            'already_deleted' => $alreadyDeleted,
            'moved' => $moved,
        ];
    }

    /**
     * Move folders to a destination folder for a given user.
     * Ensures only owned roots are moved. It will not re-parent descendants individually
     * (moving the root folder is sufficient as children follow the parent relationship).
     *
     * @param array $folderIds
     * @param \App\Models\User $user
     * @param int $destinationFolderId
     * @return array{
     *   requested: array<int>,
     *   found: array<int>,
     *   not_found: array<int>,
     *   not_owned: array<int>,
     *   already_deleted: array<int>,
     *   moved: array<int>,
     * }
     */
    public function moveFoldersToFolder(array $folderIds, User $user, ?int $destinationFolderId): array
    {
        $requested = array_values(array_unique(array_map('intval', $folderIds)));

        if (count($requested) === 0) {
            return [
                'requested' => [],
                'found' => [],
                'not_found' => [],
                'not_owned' => [],
                'already_deleted' => [],
                'moved' => [],
            ];
        }

        $records = Folder::whereIn('id', $requested)->get(['id', 'user_id', 'is_deleted']);
        $found = $records->pluck('id')->map(fn($v) => (int) $v)->all();
        $notFound = array_values(array_diff($requested, $found));

        $notOwned = $records->filter(fn($r) => $r->user_id !== $user->id)->pluck('id')->map(fn($v) => (int) $v)->all();
        $alreadyDeleted = $records->filter(fn($r) => (bool) $r->is_deleted)->pluck('id')->map(fn($v) => (int) $v)->all();

        $ownedRootIds = $records->filter(fn($r) => $r->user_id === $user->id)->pluck('id')->map(fn($v) => (int) $v)->all();

        $moved = [];
        if (! empty($ownedRootIds)) {
            // Move folders individually to allow name deduplication when destination contains same-name folder.
            $movedIds = [];
            $folders = Folder::whereIn('id', $ownedRootIds)->where('user_id', $user->id)->where('is_deleted', false)->get();
            foreach ($folders as $folder) {
                $origName = $folder->folder_name ?? '';
                $candidate = $origName;
                $k = 0;
                while (true) {
                    $fq = Folder::where('folder_name', $candidate)->where('user_id', $user->id);
                    if ($destinationFolderId === null) {
                        $fq = $fq->whereNull('fol_folder_id');
                    } else {
                        $fq = $fq->where('fol_folder_id', $destinationFolderId);
                    }
                    // exclude the folder itself
                    $fq = $fq->where('id', '<>', $folder->id);
                    if (! $fq->exists()) {
                        break;
                    }
                    $k++;
                    $fsuffix = $k === 1 ? '_copy' : "_copy_{$k}";
                    $candidate = $origName . $fsuffix;
                }
                $newName = $candidate;

                $folder->fol_folder_id = ($destinationFolderId === null ? null : $destinationFolderId);
                $folder->folder_name = $newName;
                $folder->save();

                $movedIds[] = (int) $folder->id;
            }

            $moved = $movedIds;
        }

        return [
            'requested' => $requested,
            'found' => $found,
            'not_found' => $notFound,
            'not_owned' => $notOwned,
            'already_deleted' => $alreadyDeleted,
            'moved' => $moved,
        ];
    }

    /**
     * Copy files to a destination folder for a given user.
     * Duplicates File records and their FileVersion records. Returns mapping of original -> new ids.
     *
     * @param array $fileIds
     * @param \App\Models\User $user
     * @param int $destinationFolderId
     * @return array{
     *   requested: array<int>,
     *   found: array<int>,
     *   not_found: array<int>,
     *   not_owned: array<int>,
     *   already_deleted: array<int>,
     *   copied: array<array{original:int,new:int}>,
     * }
     */
    /**
     * @param array $fileIds
     * @param \App\Models\User $user
     * @param int|null $destinationFolderId
     */
    public function copyFilesToFolder(array $fileIds, User $user, ?int $destinationFolderId): array
    {
        $requested = array_values(array_unique(array_map('intval', $fileIds)));

        if (count($requested) === 0) {
            return [
                'requested' => [],
                'found' => [],
                'not_found' => [],
                'not_owned' => [],
                'already_deleted' => [],
                'copied' => [],
            ];
        }

        $records = File::whereIn('id', $requested)->get(['id', 'user_id', 'is_deleted', 'display_name', 'file_size', 'mime_type', 'file_extension']);
        $found = $records->pluck('id')->map(fn($v) => (int) $v)->all();
        $notFound = array_values(array_diff($requested, $found));

        $notOwned = $records->filter(fn($r) => $r->user_id !== $user->id)->pluck('id')->map(fn($v) => (int) $v)->all();
        $alreadyDeleted = $records->filter(fn($r) => (bool) $r->is_deleted)->pluck('id')->map(fn($v) => (int) $v)->all();

        $toCopy = $records->filter(fn($r) => $r->user_id === $user->id && ! $r->is_deleted)->all();

        $copied = [];
        $bytesToCopy = 0;
        $createdDestPaths = [];

        if (! empty($toCopy)) {
            // calculate total bytes required
            $toCopyIds = array_map(fn($o) => $o->id, $toCopy);
            $bytesToCopy = (int) FileVersion::whereIn('file_id', $toCopyIds)->sum('file_size');

            // check user storage quota
            $systemDefaultLimit = (int) SystemConfig::getBytes('default_storage_limit', 0);
            $limit = (int) ($user->storage_limit ?: $systemDefaultLimit);
            $used = (int) ($user->storage_used ?? 0);
            if ($limit > 0 && ($used + $bytesToCopy) > $limit) {
                throw new DomainValidationException('Storage limit exceeded');
            }

            $diskName = config('filesystems.default', 'local');
            $disk = Storage::disk($diskName);

            try {
                foreach ($toCopy as $orig) {
                        // build display name with deduplication similar to FileService
                        $origDisplay = $orig->display_name ?? '';
                        $latestExt = $orig->file_extension ?? null;
                        $candidateBase = $origDisplay;
                        $candidate = $candidateBase;
                        $i = 0;
                        // folder exists condition: when destination is null we check for NULL, otherwise exact match
                        while (true) {
                            $q = File::where('display_name', $candidate)->where('user_id', $user->id);
                            if ($destinationFolderId === null) {
                                $q = $q->whereNull('folder_id');
                            } else {
                                $q = $q->where('folder_id', $destinationFolderId);
                            }
                            if (! $q->exists()) {
                                break;
                            }
                            $i++;
                            $suffix = $i === 1 ? '_copy' : "_copy_{$i}";
                            if ($latestExt && pathinfo($origDisplay, PATHINFO_EXTENSION) === $latestExt) {
                                $base = pathinfo($origDisplay, PATHINFO_FILENAME);
                                $candidate = $base . $suffix . ($latestExt ? ".{$latestExt}" : '');
                            } else {
                                $candidate = $origDisplay . $suffix . ($latestExt ? ".{$latestExt}" : '');
                            }
                        }
                        $newDisplay = $candidate;

                        // create new file record
                        $new = File::create([
                            'folder_id' => ($destinationFolderId === null ? null : $destinationFolderId),
                            'user_id' => $user->id,
                            'display_name' => $newDisplay,
                            'file_size' => $orig->file_size,
                            'mime_type' => $orig->mime_type ?? null,
                            'file_extension' => $orig->file_extension ?? null,
                            'is_deleted' => false,
                        ]);

                    // copy versions
                    $versions = FileVersion::where('file_id', $orig->id)->get();
                    foreach ($versions as $ver) {
                        $newUuid = (string) Str::uuid();
                        $ext = $ver->file_extension;
                        $srcPath = "files/{$orig->id}/v{$ver->version_number}/" . $ver->uuid . ($ext ? ".{$ext}" : '');
                        $destPath = "files/{$new->id}/v{$ver->version_number}/" . $newUuid . ($ext ? ".{$ext}" : '');

                        if (! $disk->exists($srcPath)) {
                            throw new \Exception('Source file content not found for file id ' . $orig->id);
                        }

                        $copiedOk = $disk->copy($srcPath, $destPath);
                        if (! $copiedOk) {
                            throw new \Exception('Failed to copy file content for file id ' . $orig->id);
                        }

                        $createdDestPaths[] = [$diskName, $destPath];

                        $newVer = FileVersion::create([
                            'file_id' => $new->id,
                            'user_id' => $user->id,
                            'version_number' => $ver->version_number,
                            'uuid' => $newUuid,
                            'file_extension' => $ver->file_extension,
                            'mime_type' => $ver->mime_type,
                            'file_size' => $ver->file_size,
                            'action' => $ver->action,
                            'notes' => $ver->notes,
                        ]);
                    }

                    // update new file_size to latest version size if available
                    $latest = FileVersion::where('file_id', $new->id)->orderByDesc('version_number')->first();
                    if ($latest) {
                        $new->file_size = $latest->file_size;
                        $new->save();
                    }

                    $copied[] = ['original' => (int) $orig->id, 'new' => (int) $new->id];
                }

                // increment user storage accounting (sum of version sizes copied)
                if ($bytesToCopy > 0) {
                    $user->increment('storage_used', $bytesToCopy);
                }
            } catch (\Exception $e) {
                // attempt cleanup of any copied objects
                foreach ($createdDestPaths as $p) {
                    try {
                        [$dName, $pPath] = $p;
                        Storage::disk($dName)->delete($pPath);
                    } catch (\Exception $_) {
                        // ignore cleanup errors
                    }
                }
                // rethrow as domain exception to be handled by service
                throw new DomainValidationException('Failed to copy file content: ' . $e->getMessage());
            }
        }

        return [
            'requested' => $requested,
            'found' => $found,
            'not_found' => $notFound,
            'not_owned' => $notOwned,
            'already_deleted' => $alreadyDeleted,
            'copied' => $copied,
        ];
    }

    /**
     * Copy folders (and their descendant folders + contained files) to a destination folder for a given user.
     * Returns arrays of copied folder mappings and copied file mappings.
     *
     * @param array $folderIds
     * @param \App\Models\User $user
     * @param int $destinationFolderId
     * @return array{
     *   requested: array<int>,
     *   found: array<int>,
     *   not_found: array<int>,
     *   not_owned: array<int>,
     *   already_deleted: array<int>,
     *   copied_folders: array<array{original:int,new:int}>,
     *   copied_files: array<array{original:int,new:int}>,
     * }
     */
    /**
     * @param array $folderIds
     * @param \App\Models\User $user
     * @param int|null $destinationFolderId
     */
    public function copyFoldersToFolder(array $folderIds, User $user, ?int $destinationFolderId): array
    {
        $requested = array_values(array_unique(array_map('intval', $folderIds)));

        if (count($requested) === 0) {
            return [
                'requested' => [],
                'found' => [],
                'not_found' => [],
                'not_owned' => [],
                'already_deleted' => [],
                'copied_folders' => [],
                'copied_files' => [],
            ];
        }

        $records = Folder::whereIn('id', $requested)->get(['id', 'user_id', 'is_deleted', 'fol_folder_id', 'folder_name']);
        $found = $records->pluck('id')->map(fn($v) => (int) $v)->all();
        $notFound = array_values(array_diff($requested, $found));

        $notOwned = $records->filter(fn($r) => $r->user_id !== $user->id)->pluck('id')->map(fn($v) => (int) $v)->all();
        $alreadyDeleted = $records->filter(fn($r) => (bool) $r->is_deleted)->pluck('id')->map(fn($v) => (int) $v)->all();

        $ownedRootIds = $records->filter(fn($r) => $r->user_id === $user->id)->pluck('id')->map(fn($v) => (int) $v)->all();

        $copiedFolders = [];
        $copiedFiles = [];
        $bytesToCopy = 0;
        $createdDestPaths = [];

        if (! empty($ownedRootIds)) {
            // pre-collect all folder ids under each owned root so we can compute bytes to copy
            $allFolderIds = [];
            foreach ($ownedRootIds as $rid) {
                $ids = $this->collectDescendantFolderIds([$rid], $user->id);
                $allFolderIds = array_merge($allFolderIds, $ids);
            }
            $allFolderIds = array_values(array_unique($allFolderIds));

            // gather all file ids under those folders
            $fileIdsUnder = [];
            if (! empty($allFolderIds)) {
                $fileIdsUnder = File::whereIn('folder_id', $allFolderIds)->where('user_id', $user->id)->pluck('id')->map(fn($v) => (int) $v)->all();
            }

            if (! empty($fileIdsUnder)) {
                $bytesToCopy = (int) FileVersion::whereIn('file_id', $fileIdsUnder)->sum('file_size');
            }

            // check quota before making any physical copies
            $systemDefaultLimit = (int) SystemConfig::getBytes('default_storage_limit', 0);
            $limit = (int) ($user->storage_limit ?: $systemDefaultLimit);
            $used = (int) ($user->storage_used ?? 0);
            if ($limit > 0 && ($used + $bytesToCopy) > $limit) {
                throw new DomainValidationException('Storage limit exceeded');
            }

            // We'll perform a breadth-first copy preserving parent-child mapping
            $queue = $ownedRootIds;
            $oldToNew = []; // oldId => newId

            while (! empty($queue)) {
                $current = array_shift($queue);
                if (isset($oldToNew[$current])) {
                    // already copied
                    // still enqueue children
                }

                $orig = Folder::where('id', $current)->where('user_id', $user->id)->first();
                if (! $orig) {
                    // skip missing or not owned
                    continue;
                }

                // determine new parent id
                $origParent = $orig->fol_folder_id;
                if ($origParent && isset($oldToNew[$origParent])) {
                    $newParent = $oldToNew[$origParent];
                } else {
                    // if the original parent is outside copied set, attach to destination
                    $newParent = $destinationFolderId;
                }

                // build folder name with deduplication similar to FolderService
                $origFolderName = $orig->folder_name ?? '';
                $folderCandidate = $origFolderName;
                $k = 0;
                while (true) {
                    $fq = Folder::where('folder_name', $folderCandidate)->where('user_id', $user->id);
                    if ($newParent === null) {
                        $fq = $fq->whereNull('fol_folder_id');
                    } else {
                        $fq = $fq->where('fol_folder_id', $newParent);
                    }
                    if (! $fq->exists()) {
                        break;
                    }
                    $k++;
                    $fsuffix = $k === 1 ? '_copy' : "_copy_{$k}";
                    $folderCandidate = $origFolderName . $fsuffix;
                }
                $newFolderName = $folderCandidate;

                // create folder copy
                $newFolder = Folder::create([
                    'user_id' => $user->id,
                    'fol_folder_id' => ($newParent === null ? null : $newParent),
                    'folder_name' => $newFolderName,
                    'is_deleted' => false,
                ]);

                $oldToNew[$current] = $newFolder->id;
                $copiedFolders[] = ['original' => (int) $current, 'new' => (int) $newFolder->id];

                // copy files in this folder
                $files = File::where('folder_id', $current)->where('user_id', $user->id)->where('is_deleted', false)->get();
                foreach ($files as $origFile) {
                    // deduplicate file name in this new folder
                    $origDisplay = $origFile->display_name ?? '';
                    $latestExt = $origFile->file_extension ?? null;
                    $candidateBase = $origDisplay;
                    $candidate = $candidateBase;
                    $j = 0;
                    while (true) {
                        $fq = File::where('display_name', $candidate)->where('user_id', $user->id)->where('folder_id', $newFolder->id);
                        if (! $fq->exists()) {
                            break;
                        }
                        $j++;
                        $suffix = $j === 1 ? '_copy' : "_copy_{$j}";
                        if ($latestExt && pathinfo($origDisplay, PATHINFO_EXTENSION) === $latestExt) {
                            $base = pathinfo($origDisplay, PATHINFO_FILENAME);
                            $candidate = $base . $suffix . ($latestExt ? ".{$latestExt}" : '');
                        } else {
                            $candidate = $origDisplay . $suffix . ($latestExt ? ".{$latestExt}" : '');
                        }
                    }
                    $newDisplay = $candidate;

                    $newFile = File::create([
                        'folder_id' => $newFolder->id,
                        'user_id' => $user->id,
                        'display_name' => $newDisplay,
                        'file_size' => $origFile->file_size,
                        'mime_type' => $origFile->mime_type ?? null,
                        'file_extension' => $origFile->file_extension ?? null,
                        'is_deleted' => false,
                    ]);

                    // copy versions
                    $versions = FileVersion::where('file_id', $origFile->id)->get();
                    foreach ($versions as $ver) {
                        $newUuid = (string) Str::uuid();
                        $ext = $ver->file_extension;
                        $srcPath = "files/{$origFile->id}/v{$ver->version_number}/" . $ver->uuid . ($ext ? ".{$ext}" : '');
                        $destPath = "files/{$newFile->id}/v{$ver->version_number}/" . $newUuid . ($ext ? ".{$ext}" : '');

                        $diskName = config('filesystems.default', 'local');
                        $disk = Storage::disk($diskName);

                        if (! $disk->exists($srcPath)) {
                            throw new \Exception('Source file content not found for file id ' . $origFile->id);
                        }

                        $copiedOk = $disk->copy($srcPath, $destPath);
                        if (! $copiedOk) {
                            throw new \Exception('Failed to copy file content for file id ' . $origFile->id);
                        }

                        $createdDestPaths[] = [$diskName, $destPath];

                        $newVer = FileVersion::create([
                            'file_id' => $newFile->id,
                            'user_id' => $user->id,
                            'version_number' => $ver->version_number,
                            'uuid' => $newUuid,
                            'file_extension' => $ver->file_extension,
                            'mime_type' => $ver->mime_type,
                            'file_size' => $ver->file_size,
                            'action' => $ver->action,
                            'notes' => $ver->notes,
                        ]);

                        $bytesToCopy += (int) ($ver->file_size ?? 0);
                    }

                    // adjust file_size to latest version
                    $latest = FileVersion::where('file_id', $newFile->id)->orderByDesc('version_number')->first();
                    if ($latest) {
                        $newFile->file_size = $latest->file_size;
                        $newFile->save();
                    }

                    $copiedFiles[] = ['original' => (int) $origFile->id, 'new' => (int) $newFile->id];
                }

                // enqueue children
                $children = Folder::where('fol_folder_id', $current)->where('user_id', $user->id)->pluck('id')->map(fn($v) => (int) $v)->all();
                foreach ($children as $c) {
                    if (! in_array($c, $queue, true)) {
                        $queue[] = $c;
                    }
                }
            }

            // after processing all children/folders, check quota and increment storage
            $systemDefaultLimit = (int) SystemConfig::getBytes('default_storage_limit', 0);
            $limit = (int) ($user->storage_limit ?: $systemDefaultLimit);
            $used = (int) ($user->storage_used ?? 0);
            if ($limit > 0 && ($used + $bytesToCopy) > $limit) {
                // cleanup created objects
                foreach ($createdDestPaths as $p) {
                    try {
                        [$dName, $pPath] = $p;
                        Storage::disk($dName)->delete($pPath);
                    } catch (\Exception $_) {
                        // ignore
                    }
                }
                throw new DomainValidationException('Storage limit exceeded');
            }

            if ($bytesToCopy > 0) {
                $user->increment('storage_used', $bytesToCopy);
            }
        }

        return [
            'requested' => $requested,
            'found' => $found,
            'not_found' => $notFound,
            'not_owned' => $notOwned,
            'already_deleted' => $alreadyDeleted,
            'copied_folders' => $copiedFolders,
            'copied_files' => $copiedFiles,
        ];
    }
}
