<?php

namespace Database\Seeders;

use App\Models\Opd;
use Illuminate\Database\Seeder;

class OpdSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['code' => 'BKPSDMD',  'name' => 'Badan Kepegawaian dan Pengembangan SDM Daerah Kota Palu'],
            ['code' => 'DISDIK',   'name' => 'Dinas Pendidikan Kota Palu'],
            ['code' => 'DINKES',   'name' => 'Dinas Kesehatan Kota Palu'],
            ['code' => 'DUKCAPIL', 'name' => 'Dinas Kependudukan dan Pencatatan Sipil Kota Palu'],
            ['code' => 'BAPPEDA',  'name' => 'Badan Perencanaan Pembangunan Daerah Kota Palu'],
        ];

        foreach ($rows as $row) {
            Opd::updateOrCreate(['code' => $row['code']], array_merge($row, ['is_active' => true]));
        }
    }
}
