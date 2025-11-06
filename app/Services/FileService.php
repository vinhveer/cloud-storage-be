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
			$systemDefaultLimit = (int) (SystemConfig::where('config_key', 'default_storage_limit')->value('config_value') ?? 0);
			$limit = (int) ($user->storage_limit ?: $systemDefaultLimit);
			$used = (int) ($user->storage_used ?? 0);
			if ($limit > 0 && ($used + $size) > $limit) {
				throw new \App\Exceptions\DomainValidationException('Storage limit exceeded');
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
}

