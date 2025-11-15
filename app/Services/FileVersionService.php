<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use App\Exceptions\DomainValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\SystemConfig;
use App\Models\FileVersion as FileVersionModel;

class FileVersionService
{
    public function __construct(private readonly FileService $files) {}

    /**
     * Prepare download info for a specific file version ensuring user has access.
     *
     * @param mixed $user
     * @param int $fileId
     * @param int $versionId
     * @return array ['disk' => string, 'path' => string, 'download_name' => string, 'mime' => string]
     * @throws \App\Exceptions\DomainValidationException
     */
    public function prepareVersionDownloadForUser($user, int $fileId, int $versionId): array
    {
        // Require download permission (owner or share with download/edit)
        $file = $this->files->getFileForUser($user, $fileId, 'download');

        $version = $file->versions()->where('id', $versionId)->first();
        if (! $version) {
            throw new DomainValidationException('File version not found');
        }

        $ext = $version->file_extension;
        $uuid = $version->uuid;
        $versionNumber = $version->version_number;

        $path = "files/{$file->id}/v{$versionNumber}/" . $uuid . ($ext ? ".{$ext}" : '');
        $diskName = config('filesystems.default', 'local');

        $disk = Storage::disk($diskName);
        if (! $disk->exists($path)) {
            throw new DomainValidationException('File content not found');
        }

        // Determine download filename: prefer display_name, but include version number explicitly
        $display = $file->display_name ?? ($uuid . ($ext ? ".{$ext}" : ''));
        // Ensure extension present
        if ($ext && pathinfo($display, PATHINFO_EXTENSION) === '') {
            $display .= ".{$ext}";
        }

        // Insert version suffix before extension: report.docx -> report_v3.docx
        $downloadName = $display;
        $dotPos = strrpos($display, '.');
        if ($dotPos !== false) {
            $base = substr($display, 0, $dotPos);
            $extPart = substr($display, $dotPos); // includes the dot
            $downloadName = $base . '_v' . $versionNumber . $extPart;
        } else {
            $downloadName = $display . '_v' . $versionNumber;
        }

        return [
            'disk' => $diskName,
            'path' => $path,
            'download_name' => $downloadName,
            'mime' => $version->mime_type ?? $file->mime_type ?? 'application/octet-stream',
        ];
    }

    /**
     * Restore a historical version as a new current/latest version for the given file.
     *
     * This duplicates the physical object on disk into a new version folder, creates a
     * new FileVersion record with action='restore', updates the File's latest metadata
     * and increments the owner's storage accounting.
     *
     * @param mixed $user
     * @param int $fileId
     * @param int $versionId
     * @return \App\Models\FileVersion
     * @throws \App\Exceptions\DomainValidationException
     */
    public function restoreVersionForUser($user, int $fileId, int $versionId): FileVersionModel
    {
        // Require edit permission
        $file = $this->files->getFileForUser($user, $fileId, 'edit');

        $old = $file->versions()->where('id', $versionId)->first();
        if (! $old) {
            throw new DomainValidationException('File version not found');
        }

        $size = (int) $old->file_size;
        $mime = $old->mime_type;
        $ext = $old->file_extension;

        // Storage accounting belongs to the file owner — check owner's quota
        $owner = $file->user ?? $file->user()->first();
        if (! $owner) {
            throw new DomainValidationException('File owner not found');
        }
        $systemDefaultLimit = (int) SystemConfig::getBytes('default_storage_limit', 0);
        $limit = (int) ($owner->storage_limit ?: $systemDefaultLimit);
        $used = (int) ($owner->storage_used ?? 0);
        if ($limit > 0 && ($used + $size) > $limit) {
            throw new DomainValidationException('Storage limit exceeded');
        }

        $diskName = config('filesystems.default', 'local');
        $disk = Storage::disk($diskName);

        DB::beginTransaction();
        try {
            $newVersionNumber = (int) ($file->versions()->max('version_number') ?? 0) + 1;
            $newUuid = Str::uuid()->toString();

            $srcPath = "files/{$file->id}/v{$old->version_number}/" . $old->uuid . ($ext ? ".{$ext}" : '');
            $destPath = "files/{$file->id}/v{$newVersionNumber}/" . $newUuid . ($ext ? ".{$ext}" : '');

            if (! $disk->exists($srcPath)) {
                throw new DomainValidationException('File content not found');
            }

            // Attempt to copy on the same disk
            $copied = $disk->copy($srcPath, $destPath);
            if (! $copied) {
                throw new DomainValidationException('Failed to copy file content');
            }

            // create new FileVersion
            $version = new FileVersionModel();
            $version->file_id = $file->id;
            $version->user_id = $user->id;
            $version->version_number = $newVersionNumber;
            $version->uuid = $newUuid;
            $version->file_extension = $ext;
            $version->mime_type = $mime;
            $version->file_size = $size;
            $version->action = 'restore';
            $version->notes = null;
            $version->save();

            // Update file latest metadata
            $file->file_size = $size;
            $file->mime_type = $mime;
            $file->file_extension = $ext;
            $file->save();

            // Update owner's storage accounting (actor may be an editor)
            $owner->increment('storage_used', $size);

            DB::commit();

            return $version->fresh();
        } catch (DomainValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            // attempt best-effort cleanup of destination content
            try {
                if (isset($disk) && $disk->exists($destPath)) {
                    $disk->delete($destPath);
                }
            } catch (\Exception $_) {
                // ignore
            }

            throw new DomainValidationException('Failed to restore version: ' . $e->getMessage());
        }
    }

    /**
     * Prepare version download info for public access (no user), used when token/public flows are handled separately.
     *
     * @param int $fileId
     * @param int $versionId
     * @return array
     * @throws \App\Exceptions\DomainValidationException
     */
    public function prepareVersionDownloadForPublic(int $fileId, int $versionId): array
    {
        $file = \App\Models\File::find($fileId);
        if (! $file) {
            throw new DomainValidationException('File not found');
        }

        $version = $file->versions()->where('id', $versionId)->first();
        if (! $version) {
            throw new DomainValidationException('File version not found');
        }

        $ext = $version->file_extension;
        $uuid = $version->uuid;
        $versionNumber = $version->version_number;

        $path = "files/{$file->id}/v{$versionNumber}/" . $uuid . ($ext ? ".{$ext}" : '');
        $diskName = config('filesystems.default', 'local');

        $disk = Storage::disk($diskName);
        if (! $disk->exists($path)) {
            throw new DomainValidationException('File content not found');
        }

        $display = $file->display_name ?? ($uuid . ($ext ? ".{$ext}" : ''));
        if ($ext && pathinfo($display, PATHINFO_EXTENSION) === '') {
            $display .= ".{$ext}";
        }

        $downloadName = $display;
        $dotPos = strrpos($display, '.');
        if ($dotPos !== false) {
            $base = substr($display, 0, $dotPos);
            $extPart = substr($display, $dotPos);
            $downloadName = $base . '_v' . $versionNumber . $extPart;
        } else {
            $downloadName = $display . '_v' . $versionNumber;
        }

        return [
            'disk' => $diskName,
            'path' => $path,
            'download_name' => $downloadName,
            'mime' => $version->mime_type ?? $file->mime_type ?? 'application/octet-stream',
        ];
    }

    /**
     * Delete a specific file version as an admin operation.
     *
     * - Removes the FileVersion record
     * - Deletes the physical object on disk if present
     * - Updates the owning user's storage_used accounting
     * - Updates File metadata if the deleted version was the latest
     *
     * @param mixed $adminUser  (unused except for audit/permission checks in future)
     * @param int $fileId
     * @param int $versionId
     * @throws \App\Exceptions\DomainValidationException
     */
    public function deleteVersionForAdmin($adminUser, int $fileId, int $versionId): void
    {
        $file = \App\Models\File::find($fileId);
        if (! $file) {
            throw new DomainValidationException('File not found');
        }

        $version = $file->versions()->where('id', $versionId)->first();
        if (! $version) {
            throw new DomainValidationException('File version not found');
        }

        $ext = $version->file_extension;
        $uuid = $version->uuid;
        $versionNumber = $version->version_number;
        $size = (int) ($version->file_size ?? 0);

        $path = "files/{$file->id}/v{$versionNumber}/" . $uuid . ($ext ? ".{$ext}" : '');
        $diskName = config('filesystems.default', 'local');
        $disk = Storage::disk($diskName);

        DB::beginTransaction();
        try {
            // Attempt to delete content on disk first (best-effort). If missing, continue but record error.
            if ($disk->exists($path)) {
                $deleted = $disk->delete($path);
                if (! $deleted) {
                    throw new DomainValidationException('Failed to delete file content');
                }
            }

            // Remove DB record for the version
            $version->delete();

            // Adjust owner's storage_used
            $owner = $file->user;
            if ($owner) {
                $used = (int) ($owner->storage_used ?? 0);
                $newUsed = $used - $size;
                $owner->storage_used = $newUsed < 0 ? 0 : $newUsed;
                $owner->save();
            }

            // If deleted version was the latest, update file metadata to the next latest (or clear)
            $latest = $file->versions()->orderByDesc('version_number')->first();
            if ($latest) {
                $file->file_size = (int) ($latest->file_size ?? 0);
                $file->mime_type = $latest->mime_type;
                $file->file_extension = $latest->file_extension;
            } else {
                // no versions left
                $file->file_size = 0;
                $file->mime_type = null;
                $file->file_extension = null;
            }
            $file->save();

            DB::commit();
            return;
        } catch (DomainValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            // best-effort: try to restore deleted content is not possible here; just surface an error
            throw new DomainValidationException('Failed to delete version: ' . $e->getMessage());
        }
    }
}
