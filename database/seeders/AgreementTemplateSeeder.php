<?php

namespace Database\Seeders;

use App\Models\AgreementTemplate;
use App\Models\AgreementTemplateVersion;
use Illuminate\Database\Seeder;

class AgreementTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $body = <<<'HTML'
<p>Pada hari ini, telah ditandatangani Perjanjian Kerja Pegawai Pemerintah dengan Perjanjian Kerja (PPPK) antara:</p>
<p><strong>PIHAK PERTAMA</strong>: {{penanda_tangan_nama}}, {{penanda_tangan_jabatan}}, bertindak untuk dan atas nama Pemerintah Kota Palu.</p>
<p><strong>PIHAK KEDUA</strong>: {{nama}} (NIP: {{nip}}), lahir di {{tempat_lahir}} pada {{tanggal_lahir}}, pendidikan {{pendidikan}}, jabatan {{jabatan}} ({{kategori_jabatan}}), golongan {{golongan}}, ditugaskan pada {{opd}} - {{unit_kerja}}.</p>
<p>Kedua belah pihak sepakat dengan ketentuan sebagai berikut:</p>
<ol>
    <li>Perjanjian kerja berlaku terhitung sejak <strong>{{tanggal_mulai}}</strong> sampai dengan <strong>{{tanggal_selesai}}</strong>.</li>
    <li>PIHAK KEDUA wajib melaksanakan tugas sesuai jabatan dan ketentuan yang berlaku.</li>
    <li>Perjanjian kerja dapat diperpanjang dan tidak melampaui batas usia pensiun ({{tanggal_pensiun}}).</li>
    <li>Hal-hal lain yang belum diatur dalam perjanjian ini akan diatur kemudian dengan kesepakatan kedua belah pihak.</li>
</ol>
<p>Demikian perjanjian kerja ini dibuat dan ditandatangani oleh kedua belah pihak.</p>
HTML;

        $template = AgreementTemplate::updateOrCreate(
            ['name' => 'Perjanjian Kerja PPPK Standar'],
            [
                'description' => 'Template default untuk perjanjian PPPK BKPSDMD Kota Palu.',
                'body_html'   => $body,
                'is_active'   => true,
                'version'     => 1,
            ],
        );

        AgreementTemplateVersion::updateOrCreate(
            ['template_id' => $template->id, 'version' => 1],
            ['body_html' => $body],
        );
    }
}
