<?php

namespace App\Services;

use App\Models\User;
use App\Models\File as FileModel;
use App\Models\Folder as FolderModel;
use App\Models\PublicLink as PublicLinkModel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Exceptions\DomainValidationException;
use Throwable;

class AdminUserDeletionService
{
    private FileVersionService $versions;

    public function __construct(FileVersionService $versions)
    {
        $this->versions = $versions;
    }

    /**
     * Cleanup user's filesystem objects and related DB rows before deleting the user record.
     * This method attempts to remove physical file content (all versions) and file records
     * so that DB cascade does not leave orphaned objects on disk.
     *
     * Note: this runs synchronously and may be heavy for users with many files. Consider
     * dispatching to queue for large accounts.
     *
     * @param User $user
     * @param mixed $adminUser optional admin actor for audit tracking
     * @return void
     */
    public function cleanupUser(User $user, $adminUser = null): void
    {
        $diskName = config('filesystems.default', 'local');
        $disk = Storage::disk($diskName);

        // Process files in chunks to avoid memory spikes
        FileModel::where('user_id', $user->id)->orderBy('id')->chunkById(100, function ($files) use ($adminUser, $disk) {
            foreach ($files as $file) {
                // Load versions and delete each via FileVersionService which handles disk + DB accounting
                $versions = $file->versions()->orderBy('version_number')->get();
                foreach ($versions as $version) {
                    try {
                        // Delete physical file first (best-effort)
                        $ext = $version->file_extension;
                        $path = "files/{$file->id}/v{$version->version_number}/" . $version->uuid . ($ext ? ".{$ext}" : '');
                        if ($disk->exists($path)) {
                            $disk->delete($path);
                        }

                        // Then remove DB row for file_versions. Use query builder delete so DB triggers
                        // (storage accounting) fire exactly once.
                        DB::table('file_versions')->where('id', $version->id)->delete();
                    } catch (Throwable $e) {
                        Log::warning('AdminUserDeletionService: failed to delete version ' . $version->id . ' for file ' . $file->id . ': ' . $e->getMessage());
                    }
                }

                // Remove version folders and base directory if empty
                try {
                    $baseDir = "files/{$file->id}";
                    // deleteDirectory is safe even if directory missing
                    if ($disk->exists($baseDir) || !empty($disk->allFiles($baseDir))) {
                        $disk->deleteDirectory($baseDir);
                    }
                } catch (Throwable $e) {
                    Log::warning('AdminUserDeletionService: failed to cleanup directory for file ' . $file->id . ': ' . $e->getMessage());
                }

                // Ensure file DB record removed
                if ($file->exists) {
                    try {
                        $file->forceDelete();
                    } catch (Throwable $e) {
                        Log::warning('AdminUserDeletionService: failed to forceDelete file ' . $file->id . ': ' . $e->getMessage());
                    }
                }
            }
        });

        // Remove public links owned by user (best-effort)
        try {
            PublicLinkModel::where('user_id', $user->id)->delete();
        } catch (Throwable $e) {
            Log::warning('AdminUserDeletionService: failed to delete public links for user ' . $user->id . ': ' . $e->getMessage());
        }

        // Remove folders owned by user (force delete to remove DB rows)
        try {
            // use chunking to avoid locking too many rows
            FolderModel::where('user_id', $user->id)->orderBy('id')->chunkById(100, function ($folders) {
                foreach ($folders as $folder) {
                    try {
                        $folder->forceDelete();
                    } catch (Throwable $e) {
                        Log::warning('AdminUserDeletionService: failed to forceDelete folder ' . $folder->id . ': ' . $e->getMessage());
                    }
                }
            });
        } catch (Throwable $e) {
            Log::warning('AdminUserDeletionService: failed to remove folders for user ' . $user->id . ': ' . $e->getMessage());
        }
    }
}
