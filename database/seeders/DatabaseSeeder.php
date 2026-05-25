<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            JabatanCategorySeeder::class,
            OpdSeeder::class,
            NumberingConfigSeeder::class,
            AgreementTemplateSeeder::class,
        ]);
    }
}
