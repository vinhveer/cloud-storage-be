<?php

namespace App\Services;

use App\Models\FileVersion;
use App\Models\SystemConfig;
use App\Repositories\FileRepository;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use App\Models\PublicLink;
use App\Exceptions\DomainValidationException;
use Carbon\Carbon;

class FileService
{
	protected FileRepository $fileRepository;

	public function __construct(FileRepository $fileRepository)
	{
		$this->fileRepository = $fileRepository;
	}

	/**
	 * Move a file (and its versions) to another folder belonging to same user.
	 *
	 * This operation updates the file's `folder_id`. Physical objects are stored
	 * under `files/{file_id}` so no filesystem copy is performed here. If the
	 * storage layout changes in future, this method should perform the necessary
	 * move of physical objects as well.
	 *
	 * @param mixed $user
	 * @param int $fileId
	 * @param int $destinationFolderId
	 * @return \App\Models\File
	 * @throws \App\Exceptions\DomainValidationException
	 */
	public function move($user, int $fileId, ?int $destinationFolderId)
	{
		$file = $this->fileRepository->find($fileId);
		if (! $file) {
			throw new \App\Exceptions\DomainValidationException('File not found');
		}

		// Allow owner or users with edit permission (via file or folder share)
		$isOwner = $file->user_id === $user->id;
		if (! $isOwner && ! $this->userHasSharePermissionForFile($user, $file, 'edit') && ! $this->userHasSharePermissionForFolder($user, $file->folder()->first(), 'edit')) {
			throw new \App\Exceptions\DomainValidationException('File not owned by user');
		}

		// Validate destination folder ownership if provided (null means root)
		if ($destinationFolderId !== null) {
			$folder = \App\Models\Folder::where('id', $destinationFolderId)->first();
			if (! $folder) {
				throw new \App\Exceptions\DomainValidationException('Destination folder not found');
			}
		}

		// If destination provided, enforce ownership rules.
		if ($destinationFolderId !== null) {
			// If actor is owner, destination must be owned by actor. If actor is editor (shared), only allow moving within the file owner's folders.
			if ($isOwner) {
				if ($folder->user_id !== $user->id) {
					throw new \App\Exceptions\DomainValidationException('Destination folder not found or not owned by user');
				}
			} else {
				if ($folder->user_id !== $file->user_id) {
					throw new \App\Exceptions\DomainValidationException('Destination folder not found or not owned by file owner');
				}
			}
		} else {
			// Moving to root (null). Only allow if actor is the owner; editors (shared users) cannot move files to root of the owner.
			if (! $isOwner) {
				throw new \App\Exceptions\DomainValidationException('Destination folder not found or not owned by file owner');
			}
		}

		// No-op if already in the target folder
		if ($file->folder_id === $destinationFolderId) {
			return $file->fresh();
		}

		// If moving to a different folder, deduplicate display_name in destination:
		$origDisplay = $file->display_name ?? '';
		$candidate = $origDisplay;
		$i = 0;
		while (true) {
			$q = \App\Models\File::where('display_name', $candidate)->where('user_id', $user->id);
			if ($destinationFolderId === null) {
				$q = $q->whereNull('folder_id');
			} else {
				$q = $q->where('folder_id', $destinationFolderId);
			}
			// exclude the file being moved
			$q = $q->where('id', '<>', $file->id);
			if (! $q->exists()) {
				break;
			}
			$i++;
			$suffix = $i === 1 ? '_copy' : "_copy_{$i}";
			$ext = $file->file_extension ?? null;
			if ($ext && pathinfo($origDisplay, PATHINFO_EXTENSION) === $ext) {
				$base = pathinfo($origDisplay, PATHINFO_FILENAME);
				$candidate = $base . $suffix . ($ext ? ".{$ext}" : '');
			} else {
				$candidate = $origDisplay . $suffix . ($ext ? ".{$ext}" : '');
			}
		}
		$newDisplay = $candidate;

		DB::beginTransaction();
		try {
			$file->folder_id = $destinationFolderId;
			// apply deduplicated display name when moving into destination
			$file->display_name = $newDisplay;
			$file->save();

			DB::commit();
			return $file->fresh();
		} catch (\Exception $e) {
			DB::rollBack();
			throw new \App\Exceptions\DomainValidationException($e->getMessage());
		}
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
	 * Create a new FileVersion for an existing File and store the uploaded content.
	 * Requires that $user has 'edit' permission on the file (owner or share with edit).
	 *
	 * @param mixed $user
	 * @param int $fileId
	 * @param UploadedFile $uploadedFile
	 * @param string $action one of upload|update|restore
	 * @param string|null $notes
	 * @return \App\Models\FileVersion
	 * @throws \App\Exceptions\DomainValidationException
	 */
	public function createVersion($user, int $fileId, UploadedFile $uploadedFile, string $action = 'upload', ?string $notes = null)
	{
		// Ensure user has edit permission (owner or shared with edit)
		$file = $this->getFileForUser($user, $fileId, 'edit');

		$size = $uploadedFile->getSize();
		$mime = $uploadedFile->getClientMimeType();
		$ext = $uploadedFile->getClientOriginalExtension();

		// Check user storage quota
		$systemDefaultLimit = (int) SystemConfig::getBytes('default_storage_limit', 0);
		$limit = (int) ($user->storage_limit ?: $systemDefaultLimit);
		$used = (int) ($user->storage_used ?? 0);
		if ($limit > 0 && ($used + $size) > $limit) {
			throw new \App\Exceptions\DomainValidationException('Storage limit exceeded');
		}

		// Enforce per-file max upload size from system config
		$maxUploadBytes = (int) SystemConfig::getBytes('max_upload_size', 0);
		if ($maxUploadBytes > 0 && $size > $maxUploadBytes) {
			throw new \App\Exceptions\DomainValidationException('File size exceeds max_upload_size');
		}

		return DB::transaction(function () use ($user, $file, $uploadedFile, $size, $mime, $ext, $action, $notes) {
			// Determine next version number
			$last = $file->versions()->orderByDesc('version_number')->first();
			$nextVersion = $last ? ($last->version_number + 1) : 1;

			$uuid = (string) Str::uuid();
			$version = \App\Models\FileVersion::create([
				'file_id' => $file->id,
				'user_id' => $user->id,
				'version_number' => $nextVersion,
				'uuid' => $uuid,
				'file_extension' => $ext,
				'mime_type' => $mime,
				'file_size' => $size,
				'action' => $action,
				'notes' => $notes,
			]);

			// Persist physical file into storage (private disk)
			$disk = Storage::disk(config('filesystems.default', 'local'));
			$path = "files/{$file->id}/v{$version->version_number}";
			$filename = $uuid . ($ext ? ".{$ext}" : '');
			$disk->putFileAs($path, $uploadedFile, $filename);

			// Update file metadata to reflect latest version
			$file->file_size = $size;
			$file->mime_type = $mime;
			$file->file_extension = $ext;
			$file->save();

			// Update user's storage usage
			$user->increment('storage_used', $size);

			return $version->fresh();
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
	/**
	 * Get a file by id and ensure the given user has at least the required permission.
	 *
	 * By default the required permission is 'view'. Owner always has full access.
	 * Non-owners may be granted access via `shares` / `receives_shares` (file or folder shares).
	 *
	 * @param mixed $user
	 * @param int $id
	 * @param string|null $requiredPermission one of 'view','download','edit' (defaults to 'view')
	 * @return \App\Models\File
	 * @throws \App\Exceptions\DomainValidationException
	 */
	public function getFileForUser($user, int $id, ?string $requiredPermission = 'view')
	{
		$file = $this->fileRepository->find($id);
		if (! $file) {
			throw new \App\Exceptions\DomainValidationException('File not found');
		}

		// Owner always allowed
		if ($file->user_id === $user->id) {
			return $file;
		}

		// Validate permission granted via a share on the file itself
		if ($this->userHasSharePermissionForFile($user, $file, $requiredPermission)) {
			return $file;
		}

		// Validate permission granted via a share on the folder (check parent chain)
		if ($file->folder_id !== null) {
			$folder = $file->folder()->first();
			while ($folder !== null) {
				if ($this->userHasSharePermissionForFolder($user, $folder, $requiredPermission)) {
					return $file;
				}
				$folder = $folder->parent()->first();
			}
		}

		throw new \App\Exceptions\DomainValidationException('File not owned by user');
	}

	/**
	 * Check if a user has a share that grants a permission on a specific file.
	 */
	private function userHasSharePermissionForFile($user, $file, string $requiredPermission): bool
	{
		$allowed = $this->allowedPermissionsFor($requiredPermission);

		$exists = \DB::table('shares')
			->join('receives_shares', 'shares.id', '=', 'receives_shares.share_id')
			->where('receives_shares.user_id', $user->id)
			->where('shares.shareable_type', 'file')
			->where('shares.file_id', $file->id)
			->whereIn('receives_shares.permission', $allowed)
			->exists();

		return (bool) $exists;
	}

	/**
	 * Check if a user has a share that grants a permission on a specific folder.
	 */
	private function userHasSharePermissionForFolder($user, $folder, string $requiredPermission): bool
	{
		$allowed = $this->allowedPermissionsFor($requiredPermission);

		$exists = \DB::table('shares')
			->join('receives_shares', 'shares.id', '=', 'receives_shares.share_id')
			->where('receives_shares.user_id', $user->id)
			->where('shares.shareable_type', 'folder')
			->where('shares.folder_id', $folder->id)
			->whereIn('receives_shares.permission', $allowed)
			->exists();

		return (bool) $exists;
	}

	/**
	 * Map a required permission to a list of granted permissions that satisfy it.
	 * e.g. required 'view' <- ['view','download','edit']
	 */
	private function allowedPermissionsFor(string $required): array
	{
		return match ($required) {
			'edit' => ['edit'],
			'download' => ['download', 'edit'],
			default => ['view', 'download', 'edit'],
		};
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
	// Require download permission (owner or share with download/edit)
	$file = $this->getFileForUser($user, $id, 'download');

		// get latest version
		$version = $file->versions()->orderByDesc('version_number')->first();
		if (! $version) {
			throw new \App\Exceptions\DomainValidationException('File version not found');
		}

		/**
		 * Check access for a file by owner, share (file or folder) or public link token.
		*/

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
		 * Check access for a file by owner, share (file or folder) or public link token.
		 * Returns the File model when access is granted or throws DomainValidationException.
		 *
		 * @param mixed $user|null
		 * @param int $fileId
		 * @param string|null $requiredPermission
		 * @param string|null $publicToken
		 * @return \App\Models\File
		 * @throws DomainValidationException
		 */
		public function checkAccessForFile($user, int $fileId, ?string $requiredPermission = 'view', ?string $publicToken = null)
		{
			$file = $this->fileRepository->find($fileId);
			if (! $file) {
				throw new DomainValidationException('File not found');
			}

			// Owner always allowed
			if ($user && $file->user_id === $user->id) {
				return $file;
			}

			// Permission via explicit file share
			if ($user && $this->userHasSharePermissionForFile($user, $file, $requiredPermission)) {
				return $file;
			}

			// Permission via share on folder (check direct parent)
			if ($user && $file->folder_id !== null) {
				$parent = $file->folder()->first();
				if ($parent && $this->userHasSharePermissionForFolder($user, $parent, $requiredPermission)) {
					return $file;
				}
			}

			// Public link token check: allow file-level or folder-level public link (including ancestor folders)
			if ($publicToken !== null) {
				$now = Carbon::now();
				$plQuery = PublicLink::where('token', $publicToken)
					->whereNull('revoked_at')
					->where(function ($q) use ($now) {
						$q->whereNull('expired_at')->orWhere('expired_at', '>', $now);
					});

				// Check direct file public link
				$pl = (clone $plQuery)->where('file_id', $file->id)->first();
				if ($pl && in_array($pl->permission, $this->allowedPermissionsFor($requiredPermission), true)) {
					return $file;
				}

				// Check folder public links: traverse parent chain
				$cursor = $file->folder()->first();
				while ($cursor) {
					$plf = (clone $plQuery)->where('folder_id', $cursor->id)->first();
					if ($plf && in_array($plf->permission, $this->allowedPermissionsFor($requiredPermission), true)) {
						return $file;
					}
					$cursor = $cursor->parent()->first();
				}
			}

			throw new DomainValidationException('File not accessible');
		}

	/**
	 * Public variant of prepareDownload: prepare download info for a file without a user.
	 * This is intended for public-link flows where authentication is performed elsewhere.
	 *
	 * @param int $id
	 * @return array
	 * @throws \App\Exceptions\DomainValidationException
	 */
	public function prepareDownloadForPublic(int $id): array
	{
		$file = $this->fileRepository->find($id);
		if (! $file) {
			throw new \App\Exceptions\DomainValidationException('File not found');
		}

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

		// Allow owner or users with edit permission
		$isOwner = $file->user_id === $user->id;
		if (! $isOwner && ! $this->userHasSharePermissionForFile($user, $file, 'edit') && ! $this->userHasSharePermissionForFolder($user, $file->folder()->first(), 'edit')) {
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
				$folder = \App\Models\Folder::where('id', $folderId)->first();
				if (! $folder) {
					throw new \App\Exceptions\DomainValidationException('Parent folder not found');
				}

				// If actor is owner require ownership on destination. If actor is editor, only allow moving within the file owner's folders.
				if ($isOwner) {
					if ($folder->user_id !== $user->id) {
						throw new \App\Exceptions\DomainValidationException('Parent folder not found or not owned by user');
					}
				} else {
					if ($folder->user_id !== $file->user_id) {
						throw new \App\Exceptions\DomainValidationException('Parent folder not found or not owned by file owner');
					}
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
	public function copy($user, int $fileId, ?int $destinationFolderId, bool $onlyLatest = false)
	{
		$file = $this->fileRepository->find($fileId);
		if (! $file) {
			throw new \App\Exceptions\DomainValidationException('File not found');
		}

		// Allow owner or users with download permission (download includes edit)
		$this->getFileForUser($user, $fileId, 'download');

		// Validate destination folder ownership if provided (null means root)
		if ($destinationFolderId !== null) {
			$folder = \App\Models\Folder::where('id', $destinationFolderId)
				->where('user_id', $user->id)
				->first();
			if (! $folder) {
				throw new \App\Exceptions\DomainValidationException('Destination folder not found or not owned by user');
			}
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

		// Build a display name for the copy. Use deduplication instead of appending "_copy".
		$latest = $allSourceVersions->last();
		$latestExt = $latest->file_extension;
		$latestSize = (int) ($latest->file_size ?? $file->file_size ?? 0);
		$origDisplay = $file->display_name ?? ($latest->uuid . ($latestExt ? ".{$latestExt}" : ''));

		// Candidate generation preserves extension if present on original display name.
		$candidateBase = $origDisplay;
		$candidate = $candidateBase;
		$i = 0;
		// Explicitly scope existence check to destination folder, handling root (null) correctly.
		while (true) {
			$q = \App\Models\File::where('display_name', $candidate);
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

	/**
	 * Return recent files for a user ordered by most-recent activity (last_opened_at or created_at).
	 *
	 * @param mixed $user
	 * @param int $limit
	 * @return \Illuminate\Support\Collection  collection of arrays with keys file_id, display_name, last_opened_at
	 */
	public function recent($user, int $limit = 20, bool $includeShared = true)
	{
		// Base condition: files owned by user
		$ownedQuery = \App\Models\File::query()
			->where('user_id', $user->id)
			->where('is_deleted', false)
			->select(['id', 'display_name', 'last_opened_at', 'created_at']);

		// If includeShared is enabled, collect file IDs shared directly to the user (file shares)
		$sharedIds = [];
		if ($includeShared) {
			$allowed = $this->allowedPermissionsFor('view');

			// Direct file shares
			$fileShareIds = DB::table('shares')
				->join('receives_shares', 'shares.id', '=', 'receives_shares.share_id')
				->where('receives_shares.user_id', $user->id)
				->where('shares.shareable_type', 'file')
				->whereIn('receives_shares.permission', $allowed)
				->pluck('shares.file_id')
				->filter()
				->unique()
				->values()
				->all();

			// Folder shares: include files in the shared folder and all descendant folders (BFS traversal)
			$sharedFolderIds = DB::table('shares')
				->join('receives_shares', 'shares.id', '=', 'receives_shares.share_id')
				->where('receives_shares.user_id', $user->id)
				->where('shares.shareable_type', 'folder')
				->whereIn('receives_shares.permission', $allowed)
				->pluck('shares.folder_id')
				->filter()
				->unique()
				->values()
				->all();

			$folderFileIds = [];
			if (! empty($sharedFolderIds)) {
				// BFS to collect descendant folder ids (safe, avoids raw recursive SQL)
				$visited = [];
				$queue = $sharedFolderIds;
				while (! empty($queue)) {
					$parentId = array_pop($queue);
					if (in_array($parentId, $visited, true)) {
						continue;
					}
					$visited[] = $parentId;

					$children = DB::table('folders')->where('fol_folder_id', $parentId)->pluck('id')->all();
					foreach ($children as $c) {
						if (! in_array($c, $visited, true)) {
							$queue[] = $c;
						}
					}
				}

				// Get files in any of the visited folders
				$folderFileIds = DB::table('files')
					->whereIn('folder_id', $visited)
					->where('is_deleted', false)
					->pluck('id')
					->filter()
					->unique()
					->values()
					->all();
			}

			$sharedIds = array_values(array_unique(array_merge($fileShareIds, $folderFileIds)));
		}

		// Build final query: owned OR shared (if any)
		$query = \App\Models\File::query()
			->where('is_deleted', false)
			->select(['id', 'display_name', 'last_opened_at', 'created_at']);

		$query->where(function ($q) use ($user, $sharedIds) {
			$q->where('user_id', $user->id);
			if (! empty($sharedIds)) {
				$q->orWhereIn('id', $sharedIds);
			}
		});

		// Order by the most recent of last_opened_at and created_at
		$query->orderByDesc(DB::raw("CASE WHEN last_opened_at IS NULL OR last_opened_at < created_at THEN created_at ELSE last_opened_at END"));

		$items = $query->limit($limit)->get();

		return $items->map(function ($f) {
			$dt = $f->last_opened_at ?? $f->created_at;
			return [
				'file_id' => $f->id,
				'display_name' => $f->display_name,
				'last_opened_at' => $dt ? ($dt instanceof \Illuminate\Support\Carbon ? $dt->toIso8601String() : (string) $dt) : null,
			];
		});
	}
}

