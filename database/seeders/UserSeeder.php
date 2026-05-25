<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('SEED_ADMIN_EMAIL', 'admin@bkpsdmd.palu.go.id')],
            [
                'name'      => env('SEED_ADMIN_NAME', 'Administrator BKPSDMD'),
                'password'  => env('SEED_ADMIN_PASSWORD', 'ChangeMe!2026'),
                'is_active' => true,
            ],
        );
    }
}
