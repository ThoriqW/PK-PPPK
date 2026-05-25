<?php

namespace Database\Seeders;

use App\Models\NumberingConfig;
use Illuminate\Database\Seeder;

class NumberingConfigSeeder extends Seeder
{
    public function run(): void
    {
        NumberingConfig::updateOrCreate(
            ['name' => 'Perjanjian PPPK BKPSDMD'],
            [
                'format'         => '{seq}/PPPK/BKPSDMD/{roman_month}/{year}',
                'current_number' => 0,
                'padding'        => 3,
                'reset_policy'   => NumberingConfig::RESET_YEARLY,
                'is_active'      => true,
            ],
        );
    }
}
