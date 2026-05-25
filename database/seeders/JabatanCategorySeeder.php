<?php

namespace Database\Seeders;

use App\Models\JabatanCategory;
use Illuminate\Database\Seeder;

/**
 * Drives retirement age. The two business rules:
 *   - Fungsional Guru -> 60
 *   - Other PPPK categories -> 58
 */
class JabatanCategorySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['code' => JabatanCategory::CODE_FUNGSIONAL_GURU,         'name' => 'Jabatan Fungsional Guru',             'retirement_age' => 60],
            ['code' => JabatanCategory::CODE_FUNGSIONAL_AHLI_PERTAMA, 'name' => 'Jabatan Fungsional Ahli Pertama',     'retirement_age' => 58],
            ['code' => JabatanCategory::CODE_FUNGSIONAL_AHLI_MUDA,    'name' => 'Jabatan Fungsional Ahli Muda',        'retirement_age' => 58],
            ['code' => JabatanCategory::CODE_FUNGSIONAL_KETERAMPILAN, 'name' => 'Jabatan Fungsional Keterampilan',     'retirement_age' => 58],
            ['code' => JabatanCategory::CODE_PELAKSANA,               'name' => 'Jabatan Pelaksana',                   'retirement_age' => 58],
        ];

        foreach ($rows as $row) {
            JabatanCategory::updateOrCreate(['code' => $row['code']], array_merge($row, ['is_active' => true]));
        }
    }
}
