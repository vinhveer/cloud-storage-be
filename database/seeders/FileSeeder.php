<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\File;
use App\Models\Folder;
use App\Models\FileVersion;
use Illuminate\Support\Str;

class FileSeeder extends Seeder
{
    public function run(): void
    {
        $folders = Folder::all();

        foreach ($folders as $folder) {
            $files = File::factory()->count(3)->create([
                'user_id' => $folder->user_id,
                'folder_id' => $folder->id,
            ]);

            // Tạo version đầu tiên cho mỗi file (upload)
            foreach ($files as $file) {
                FileVersion::create([
                    'file_id' => $file->id,
                    'user_id' => $file->user_id,
                    'version_number' => 1,
                    'uuid' => Str::uuid(),
                    'file_extension' => $file->file_extension,
                    'mime_type' => $file->mime_type,
                    'file_size' => $file->file_size,
                    'action' => 'upload',
                    'notes' => 'Initial upload',
                ]);
            }
        }
    }
}
