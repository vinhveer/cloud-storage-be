<?php

namespace App\Repositories;

use App\Models\File;

class FileRepository
{
    /**
     * Create a new File record
     *
     * @param array $data
     * @return File
     */
    public function create(array $data): File
    {
        return File::create($data);
    }

    /**
     * Find file by id
     */
    public function find(int $id): ?File
    {
        return File::find($id);
    }

    /**
     * Find file by id including soft-deleted records
     */
    public function findWithTrashed(int $id): ?File
    {
        return File::withTrashed()->find($id);
    }

    /**
     * Paginate files belonging to a user with optional filters.
     *
     * @return array{items: \Illuminate\Support\Collection, total: int}
     */
    public function paginateForUser(
        int $userId,
        ?int $folderId,
        ?string $search,
        ?string $extension,
        int $page = 1,
        int $perPage = 15
    ): array {
        $query = File::query()
            ->where('user_id', $userId)
            ->where('is_deleted', false)
            ->when($folderId !== null, fn($q) => $q->where('folder_id', $folderId))
            ->when($search !== null && $search !== '', fn($q) => $q->where('display_name', 'like', "%".$search."%"))
            ->when($extension !== null && $extension !== '', fn($q) => $q->where('file_extension', strtolower($extension)))
            ->orderByDesc('id');

        $total = (clone $query)->count();
        $items = $query
            ->forPage($page, $perPage)
            ->get(['id', 'display_name', 'file_size', 'mime_type', 'file_extension', 'folder_id', 'user_id', 'is_deleted']);

        return [
            'items' => $items,
            'total' => (int) $total,
        ];
    }

    /**
     * Get a file with its latest version loaded (or null if not found).
     */
    public function getWithLatestVersion(int $id)
    {
        return File::where('id', $id)
            ->with(['versions' => function ($q) { $q->orderByDesc('version_number')->limit(1); }])
            ->first();
    }
}
