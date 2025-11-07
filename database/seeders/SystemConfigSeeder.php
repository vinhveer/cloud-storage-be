<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemConfig;

class SystemConfigSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            ['config_key' => 'default_storage_limit', 'config_value' => '10737418240'], // 10GB
            ['config_key' => 'max_upload_size', 'config_value' => '52428800'], // 50MB
        ];

        foreach ($configs as $config) {
            SystemConfig::updateOrCreate(
                ['config_key' => $config['config_key']],
                ['config_value' => $config['config_value']]
            );
        }
    }
}
