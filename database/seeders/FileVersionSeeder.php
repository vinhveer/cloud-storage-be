<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\File;
use App\Models\FileVersion;
use Illuminate\Support\Str;

class FileVersionSeeder extends Seeder
{
    public function run(): void
    {
        $files = File::all();

        foreach ($files as $file) {
            // Tạo 1–2 phiên bản mới cho 1 số file
            if (rand(0, 1)) {
                for ($v = 2; $v <= rand(2, 3); $v++) {
                    FileVersion::create([
                        'file_id' => $file->id,
                        'user_id' => $file->user_id,
                        'version_number' => $v,
                        'uuid' => Str::uuid(),
                        'file_extension' => $file->file_extension,
                        'mime_type' => $file->mime_type,
                        'file_size' => $file->file_size + rand(1000, 10000),
                        'action' => 'update',
                        'notes' => "Updated to version $v",
                    ]);
                }
            }
        }
    }
}
