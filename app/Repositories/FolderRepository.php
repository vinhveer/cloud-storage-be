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


