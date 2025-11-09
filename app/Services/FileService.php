<?php

namespace App\Services;

use App\Models\FileVersion;
use App\Models\SystemConfig;
use App\Repositories\FileRepository;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;

class FileService
{
	protected FileRepository $fileRepository;

	public function __construct(FileRepository $fileRepository)
	{
		$this->fileRepository = $fileRepository;
	}

	/**
	 * Upload a file, save to storage and create File + FileVersion records
	 *
	 * @param \App\Models\User $user
	 * @param UploadedFile $uploadedFile
	 * @param int|null $folderId
	 * @param string|null $displayName
	 * @return \App\Models\File
	 */
	public function upload($user, UploadedFile $uploadedFile, ?int $folderId = null, ?string $displayName = null)
	{
		$size = $uploadedFile->getSize();
		$mime = $uploadedFile->getClientMimeType();
		$ext = $uploadedFile->getClientOriginalExtension();
		$originalName = $uploadedFile->getClientOriginalName();
		$display = $displayName ?: $originalName;

		return DB::transaction(function () use ($user, $uploadedFile, $folderId, $display, $size, $mime, $ext) {
			// Validate parent folder ownership if provided
			if ($folderId !== null) {
				$folder = \App\Models\Folder::where('id', $folderId)
					->where('user_id', $user->id)
					->first();
				if (! $folder) {
					throw new \App\Exceptions\DomainValidationException('Parent folder not found or not owned by user');
				}
			}

			// Check user storage quota – fall back to system default_storage_limit if user-specific limit not set/0
			// Read default storage limit (in bytes); user->storage_limit takes precedence if set
			$systemDefaultLimit = (int) SystemConfig::getBytes('default_storage_limit', 0);
			$limit = (int) ($user->storage_limit ?: $systemDefaultLimit);
			$used = (int) ($user->storage_used ?? 0);
			if ($limit > 0 && ($used + $size) > $limit) {
				throw new \App\Exceptions\DomainValidationException('Storage limit exceeded');
			}

			// Enforce per-file max upload size from system config (defensive server-side check)
			$maxUploadBytes = (int) SystemConfig::getBytes('max_upload_size', 0);
			if ($maxUploadBytes > 0 && $size > $maxUploadBytes) {
				throw new \App\Exceptions\DomainValidationException('File size exceeds max_upload_size');
			}

			// Create File record
			$file = $this->fileRepository->create([
				'folder_id' => $folderId,
				'user_id' => $user->id,
				'display_name' => $display,
				'file_size' => $size,
				'mime_type' => $mime,
				'file_extension' => $ext,
				'is_deleted' => false,
			]);

			// Create FileVersion v1
			$uuid = (string) Str::uuid();
			$version = FileVersion::create([
				'file_id' => $file->id,
				'user_id' => $user->id,
				'version_number' => 1,
				'uuid' => $uuid,
				'file_extension' => $ext,
				'mime_type' => $mime,
				'file_size' => $size,
				'action' => 'upload',
				'notes' => null,
			]);

			// Persist physical file into storage (private disk)
			$disk = Storage::disk(config('filesystems.default', 'local'));
			$path = "files/{$file->id}/v{$version->version_number}";
			$filename = $uuid . ($ext ? ".{$ext}" : '');
			$disk->putFileAs($path, $uploadedFile, $filename);

			// Update user's storage usage
			$user->increment('storage_used', $size);

			return $file->fresh();
		});
	}

	/**
	 * List files for the given user with optional filters and pagination.
	 */
	public function listFiles($user, ?int $folderId, ?string $search, ?string $extension, int $page, int $perPage): array
	{
		// Validate parent folder ownership if provided
		if ($folderId !== null) {
			$folder = \App\Models\Folder::where('id', $folderId)
				->where('user_id', $user->id)
				->first();
			if (! $folder) {
				throw new \App\Exceptions\DomainValidationException('Folder not found or not owned by user');
			}
		}

		$search = $search !== null ? trim($search) : null;
		$extension = $extension !== null ? strtolower(trim($extension)) : null;

		return $this->fileRepository->paginateForUser(
			$user->id,
			$folderId,
			$search,
			$extension,
			$page,
			$perPage
		);
	}

	/**
	 * Get a file by id and ensure it belongs to the given user.
	 *
	 * @param mixed $user
	 * @param int $id
	 * @return \App\Models\File
	 * @throws \App\Exceptions\DomainValidationException
	 */
	public function getFileForUser($user, int $id)
	{
		$file = $this->fileRepository->find($id);
		if (! $file) {
			throw new \App\Exceptions\DomainValidationException('File not found');
		}

		if ($file->user_id !== $user->id) {
			throw new \App\Exceptions\DomainValidationException('File not owned by user');
		}

		return $file;
	}

	/**
	 * Prepare download info for a file (latest version) ensuring user has access.
	 *
	 * @param mixed $user
	 * @param int $id
	 * @return array ['disk' => string, 'path' => string, 'download_name' => string, 'mime' => string]
	 * @throws \App\Exceptions\DomainValidationException
	 */
	public function prepareDownloadForUser($user, int $id): array
	{
		$file = $this->getFileForUser($user, $id);

		// get latest version
		$version = $file->versions()->orderByDesc('version_number')->first();
		if (! $version) {
			throw new \App\Exceptions\DomainValidationException('File version not found');
		}

        

		$ext = $version->file_extension;
		$uuid = $version->uuid;
		$versionNumber = $version->version_number;

		$path = "files/{$file->id}/v{$versionNumber}/" . $uuid . ($ext ? ".{$ext}" : '');
		$diskName = config('filesystems.default', 'local');

		$disk = Storage::disk($diskName);
		if (! $disk->exists($path)) {
			throw new \App\Exceptions\DomainValidationException('File content not found');
		}

		// Determine download filename: prefer display_name, ensure extension present
		$display = $file->display_name ?? ($uuid . ($ext ? ".{$ext}" : ''));
		// If display name doesn't have extension, append from version
		if ($ext && pathinfo($display, PATHINFO_EXTENSION) === '') {
			$display .= ".{$ext}";
		}

		return [
			'disk' => $diskName,
			'path' => $path,
			'download_name' => $display,
			'mime' => $version->mime_type ?? $file->mime_type ?? 'application/octet-stream',
		];
	}

	/**
	 * Update file information: display_name and/or folder_id (move)
	 *
	 * @param mixed $user
	 * @param int $fileId
	 * @param string|null $displayName
	 * @param int|null $folderId
	 * @return \App\Models\File
	 * @throws \App\Exceptions\DomainValidationException
	 */
	public function update($user, int $fileId, ?string $displayName = null, ?int $folderId = null)
	{
		// Try repository method that includes soft-deleted files
		$file = null;
		if (method_exists($this->fileRepository, 'findWithTrashed')) {
			$file = $this->fileRepository->findWithTrashed($fileId);
		}
		// Fallback directly to the model in case repository is not available or misbehaving
		if (! $file) {
			$file = \App\Models\File::withTrashed()->find($fileId);
		}
		if (! $file) {
			throw new \App\Exceptions\DomainValidationException('File not found');
		}

		if ($file->user_id !== $user->id) {
			throw new \App\Exceptions\DomainValidationException('File not owned by user');
		}

		if ($displayName === null && $folderId === null) {
			throw new \App\Exceptions\DomainValidationException('No data to update');
		}

		// If folderId provided, validate ownership
		if ($folderId !== null) {
			if ($folderId === $file->folder_id) {
				// same folder - allowed, no-op for folder
			} else {
				$folder = \App\Models\Folder::where('id', $folderId)
					->where('user_id', $user->id)
					->first();
				if (! $folder) {
					throw new \App\Exceptions\DomainValidationException('Parent folder not found or not owned by user');
				}
			}
		}

		$update = [];
		if ($displayName !== null) {
			$update['display_name'] = $displayName;
		}
		if ($folderId !== null) {
			$update['folder_id'] = $folderId;
		}

		// perform update and return fresh model
		$file->update($update);

		return $file->fresh();
	}

	/**
	 * Copy a file to another folder belonging to the same user.
	 *
	 * Behaviour & invariants:
	 * - This operation duplicates all versions of the source file. Each FileVersion is copied
	 *   (new uuid + same metadata) and the physical object for each version is copied on disk.
	 * - Accounting: the user's `storage_used` is incremented by the total bytes copied (sum of sizes of
	 *   all versions copied).
	 * - File metadata invariant: the new `File.file_size` is set to the size of the latest version only.
	 *   This keeps `file_size` consistent across APIs that expect it to represent the current/latest
	 *   version size. Historical/older versions keep their own `file_size` in `file_versions`.
	 * - Failure handling: the method attempts an atomic DB transaction and performs a best-effort
	 *   cleanup of copied files on disk if something fails.
	 *
	 * @param mixed $user
	 * @param int $fileId
	 * @param int $destinationFolderId
	 * @return \App\Models\File
	 * @throws \App\Exceptions\DomainValidationException
	 */
	public function copy($user, int $fileId, int $destinationFolderId, bool $onlyLatest = false)
	{
		$file = $this->fileRepository->find($fileId);
		if (! $file) {
			throw new \App\Exceptions\DomainValidationException('File not found');
		}

		if ($file->user_id !== $user->id) {
			throw new \App\Exceptions\DomainValidationException('File not owned by user');
		}

		// Validate destination folder ownership
		$folder = \App\Models\Folder::where('id', $destinationFolderId)
			->where('user_id', $user->id)
			->first();
		if (! $folder) {
			throw new \App\Exceptions\DomainValidationException('Destination folder not found or not owned by user');
		}

		// Get versions from source file. If onlyLatest is true, only copy the latest version.
		$allSourceVersions = $file->versions()->orderBy('version_number')->get();
		if ($allSourceVersions->isEmpty()) {
			throw new \App\Exceptions\DomainValidationException('File version not found');
		}

		if ($onlyLatest) {
			$sourceVersions = collect([$allSourceVersions->last()]);
		} else {
			$sourceVersions = $allSourceVersions;
		}

		// Total size across versions that will be copied (used for quota and accounting)
		$totalSize = $sourceVersions->sum(fn($v) => (int) ($v->file_size ?? 0));
		if ($totalSize <= 0) {
			// Fallback to file->file_size if versions don't have sizes
			$totalSize = (int) ($file->file_size ?? 0);
		}

		// Check storage quota
		$systemDefaultLimit = (int) SystemConfig::getBytes('default_storage_limit', 0);
		$limit = (int) ($user->storage_limit ?: $systemDefaultLimit);
		$used = (int) ($user->storage_used ?? 0);
		if ($limit > 0 && ($used + $totalSize) > $limit) {
			throw new \App\Exceptions\DomainValidationException('Storage limit exceeded');
		}

		$disk = Storage::disk(config('filesystems.default', 'local'));

		// Build a display name for the copy (append _copy before extension based on original latest version)
		$latest = $allSourceVersions->last();
		$latestExt = $latest->file_extension;
		$latestSize = (int) ($latest->file_size ?? $file->file_size ?? 0);
		$origDisplay = $file->display_name ?? ($latest->uuid . ($latestExt ? ".{$latestExt}" : ''));
		if ($latestExt && pathinfo($origDisplay, PATHINFO_EXTENSION) === $latestExt) {
			$base = pathinfo($origDisplay, PATHINFO_FILENAME);
			$newDisplay = $base . '_copy' . ($latestExt ? ".{$latestExt}" : '');
		} else {
			$newDisplay = $origDisplay . '_copy' . ($latestExt ? ".{$latestExt}" : '');
		}

		// Prepare bookkeeping for cleanup on failure
		// Prepare temporary copy batch (outside DB transaction). Copy source versions to a temp folder first.
		$batchId = (string) Str::uuid();
		$tempPaths = [];
		$index = 1;
		try {
			foreach ($sourceVersions as $srcVersion) {
				$srcExt = $srcVersion->file_extension;
				$srcPath = "files/{$file->id}/v{$srcVersion->version_number}/" . $srcVersion->uuid . ($srcExt ? ".{$srcExt}" : '');
				if (! $disk->exists($srcPath)) {
					throw new \App\Exceptions\DomainValidationException('File content not found for version ' . $srcVersion->version_number);
				}

				$tempPath = "tmp/copies/{$batchId}/v{$index}/" . $srcVersion->uuid . ($srcExt ? ".{$srcExt}" : '');
				$copied = $disk->copy($srcPath, $tempPath);
				if (! $copied) {
					throw new \App\Exceptions\DomainValidationException('Failed to copy to temp for version ' . $srcVersion->version_number);
				}
				$tempPaths[] = ['temp' => $tempPath, 'src' => $srcVersion, 'ext' => $srcExt, 'size' => (int) ($srcVersion->file_size ?? 0), 'mime' => $srcVersion->mime_type ?? $file->mime_type];
				$index++;
			}
		} catch (\Exception $e) {
			// Cleanup any temp files created
			try {
				if (! empty($tempPaths)) {
					$disk->delete(array_column($tempPaths, 'temp'));
				}
				$disk->deleteDirectory("tmp/copies/{$batchId}");
			} catch (\Exception $inner) {
				// ignore
			}
			throw $e;
		}

		$copiedTargetPaths = [];
		$createdFileId = null;

		DB::beginTransaction();
		try {
			// Create new File record
			$newFile = $this->fileRepository->create([
				'folder_id' => $destinationFolderId,
				'user_id' => $user->id,
				'display_name' => $newDisplay,
				'file_size' => $latestSize,
				'mime_type' => $file->mime_type,
				'file_extension' => $latestExt,
				'is_deleted' => false,
			]);
			$createdFileId = $newFile->id;

			// Move temp files into final locations and create FileVersion rows
			$newVersionNumber = 1;
			foreach ($tempPaths as $p) {
				$srcExt = $p['ext'];
				$srcMime = $p['mime'];
				$srcSize = $p['size'];
				$tempPath = $p['temp'];

				$newUuid = (string) Str::uuid();
				$version = FileVersion::create([
					'file_id' => $newFile->id,
					'user_id' => $user->id,
					'version_number' => $newVersionNumber,
					'uuid' => $newUuid,
					'file_extension' => $srcExt,
					'mime_type' => $srcMime,
					'file_size' => $srcSize,
					'action' => 'upload',
					'notes' => 'Copied from file ' . $file->id . ' version ' . $p['src']->version_number,
				]);

				$targetPath = "files/{$newFile->id}/v{$newVersionNumber}/" . $newUuid . ($srcExt ? ".{$srcExt}" : '');
				// Try to move (rename) temp -> target; if move not supported, fallback to copy+delete
				$moved = false;
				try {
					$moved = $disk->move($tempPath, $targetPath);
				} catch (\Throwable $moveEx) {
					// fallback
					$moved = $disk->copy($tempPath, $targetPath) && $disk->delete($tempPath);
				}
				if (! $moved) {
					throw new \App\Exceptions\DomainValidationException('Failed to move temp file to final location for version ' . $p['src']->version_number);
				}
				$copiedTargetPaths[] = $targetPath;
				$newVersionNumber++;
			}

			// Update user's storage usage
			if ($totalSize > 0) {
				$user->increment('storage_used', $totalSize);
			}

			DB::commit();
			// Cleanup any leftover temp dir
			try {
				$disk->deleteDirectory("tmp/copies/{$batchId}");
			} catch (\Exception $inner) {
				// ignore
			}

			return $newFile->fresh();
		} catch (\Exception $e) {
			DB::rollBack();
			// Attempt best-effort cleanup: remove created target files, versions and file, and remove temp copies
			try {
				if (! empty($copiedTargetPaths)) {
					$disk->delete($copiedTargetPaths);
				}
				if ($createdFileId) {
					FileVersion::where('file_id', $createdFileId)->delete();
					\App\Models\File::where('id', $createdFileId)->delete();
				}
				// cleanup temp copies
				if (! empty($tempPaths)) {
					$disk->delete(array_column($tempPaths, 'temp'));
					$disk->deleteDirectory("tmp/copies/{$batchId}");
				}
			} catch (\Exception $inner) {
				// ignore cleanup errors
			}

			throw new \App\Exceptions\DomainValidationException($e->getMessage());
		}
	}

	/**
	 * Soft-delete (move to trash) a file for the given user.
	 *
	 * @param mixed $user
	 * @param int $fileId
	 * @return \App\Models\File
	 * @throws \App\Exceptions\DomainValidationException
	 */
	public function moveToTrash($user, int $fileId)
	{
		$file = $this->fileRepository->find($fileId);
		if (! $file) {
			throw new \App\Exceptions\DomainValidationException('File not found');
		}

		if ($file->user_id !== $user->id) {
			throw new \App\Exceptions\DomainValidationException('File not owned by user');
		}

		if ($file->is_deleted) {
			throw new \App\Exceptions\DomainValidationException('File already deleted');
		}

		// Mark as deleted (logical trash). Also set deleted_at to allow soft-deletes queries if needed.
		$file->is_deleted = true;
		$file->deleted_at = now();
		$file->save();

		return $file->fresh();
	}
}

