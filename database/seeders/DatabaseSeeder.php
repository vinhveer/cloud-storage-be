<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,        
            SystemConfigSeeder::class,  
            FolderSeeder::class,     
            FileSeeder::class,          
            FileVersionSeeder::class,   
        ]);
    }
}
