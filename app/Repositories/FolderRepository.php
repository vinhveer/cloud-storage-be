<?php

namespace App\Repositories;

use App\Models\Folder;
use App\Models\User;

class FolderRepository
{
    public function findByIdAndUser(int $folderId, int $userId): ?Folder
    {
        return Folder::where('id', $folderId)
            ->where('user_id', $userId)
            ->first();
    }

    public function paginateChildrenByUser(?int $parentId, int $userId, int $page = 1, int $perPage = 15): array
    {
        $query = Folder::query()
            ->where('user_id', $userId)
            ->where('is_deleted', false)
            ->when($parentId !== null, fn($q) => $q->where('fol_folder_id', $parentId), fn($q) => $q->whereNull('fol_folder_id'))
            ->orderByDesc('id');

        $total = (clone $query)->count();
        $items = $query->forPage($page, $perPage)->get(['id', 'folder_name', 'fol_folder_id', 'created_at']);

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    public function create(User $user, ?Folder $parent, string $folderName): Folder
    {
        return Folder::create([
            'user_id' => $user->id,
            'fol_folder_id' => $parent?->id,
            'folder_name' => $folderName,
            'is_deleted' => false,
        ]);
    }
}


