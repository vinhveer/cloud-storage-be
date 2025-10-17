<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FileFactory extends Factory
{
    public function definition(): array
    {
        // Giả lập 1 số định dạng file phổ biến
        $extensions = [
            ['ext' => 'pdf', 'mime' => 'application/pdf'],
            ['ext' => 'jpg', 'mime' => 'image/jpeg'],
            ['ext' => 'png', 'mime' => 'image/png'],
            ['ext' => 'txt', 'mime' => 'text/plain'],
            ['ext' => 'mp4', 'mime' => 'video/mp4'],
        ];

        $file = $this->faker->randomElement($extensions);

        return [
            'display_name' => $this->faker->words(2, true) . '.' . $file['ext'],
            'file_size' => $this->faker->numberBetween(10000, 5000000), // 10KB - 5MB
            'mime_type' => $file['mime'],
            'file_extension' => $file['ext'],
            'is_deleted' => false,
            'deleted_at' => null,
            'last_opened_at' => now(),
        ];
    }
}
