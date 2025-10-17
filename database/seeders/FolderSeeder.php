<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Folder;

class FolderSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            Folder::create([
                'user_id' => $user->id,
                'folder_name' => 'Root',
                'fol_folder_id' => null,
            ]);
        }
    }
}
