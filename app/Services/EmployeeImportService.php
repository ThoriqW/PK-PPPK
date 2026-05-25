<?php

namespace App\Services;

use App\Imports\RawSheetImport;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\JabatanCategory;
use App\Models\Opd;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Excel-driven employee import.
 *
 * Required columns (lowercase, exact match — published in the canonical .xlsx):
 *   nip, nik, nama_lengkap, tempat_lahir, tanggal_lahir, jenis_kelamin,
 *   pendidikan, jabatan, kategori_jabatan_kode, golongan, opd_kode,
 *   unit_kerja, tahun_pengangkatan, telepon, email
 *
 * Duplicate rule:
 *   - if nip is filled and already in DB -> DUPLICATE
 *   - else if nik is filled and already in DB -> DUPLICATE
 */
class EmployeeImportService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    /**
     * Synchronously process an uploaded file (queue-friendly).
     */
    public function process(ImportBatch $batch): ImportBatch
    {
        $batch->update([
            'status'     => ImportBatch::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        try {
            $absolutePath = storage_path('app/'.$batch->stored_path);
            if (! is_file($absolutePath)) {
                $batch->update([
                    'status'        => ImportBatch::STATUS_FAILED,
                    'finished_at'   => now(),
                    'error_summary' => "File yang diunggah tidak ditemukan di server: {$batch->stored_path}",
                ]);
                return $batch->fresh();
            }

            $importable = new RawSheetImport();
            // Maatwebsite\Excel 3.1 requires an Importable instance; passing null is
            // not supported. RawSheetImport collects the first sheet as a 2D array.
            Excel::import($importable, $absolutePath);
            $rows = $importable->rows;

            if (empty($rows)) {
                $batch->update([
                    'status'        => ImportBatch::STATUS_FAILED,
                    'finished_at'   => now(),
                    'error_summary' => 'File kosong atau tidak terbaca.',
                ]);
                return $batch->fresh();
            }

            $header = array_map(fn ($v) => strtolower(trim((string) $v)), $rows[0]);
            $dataRows = array_slice($rows, 1);

            // Build case-insensitive lookups so users can type opd_kode=bkpsdmd
            // or BKPSDMD interchangeably without confusion.
            $opdMap = Opd::pluck('id', 'code')->mapWithKeys(
                fn ($id, $code) => [strtoupper((string) $code) => $id]
            );
            $catMap = JabatanCategory::pluck('id', 'code')->mapWithKeys(
                fn ($id, $code) => [strtoupper((string) $code) => $id]
            );

            $inserted = 0;
            $skipped  = 0;
            $failed   = 0;

            foreach ($dataRows as $i => $raw) {
                $rowNumber = $i + 2; // human-friendly (header is row 1)
                $payload   = $this->mapRow($header, $raw);

                if ($this->isEmptyRow($payload)) {
                    continue; // silently skip blank lines
                }

                [$outcome, $error, $employeeId] = $this->persistRow($payload, $opdMap, $catMap);

                ImportBatchRow::create([
                    'import_batch_id' => $batch->id,
                    'row_number'      => $rowNumber,
                    'payload'         => $payload,
                    'outcome'         => $outcome,
                    'error_message'   => $error,
                    'employee_id'     => $employeeId,
                ]);

                match ($outcome) {
                    ImportBatchRow::OUTCOME_INSERTED  => $inserted++,
                    ImportBatchRow::OUTCOME_DUPLICATE => $skipped++,
                    default                           => $failed++,
                };
            }

            $finalStatus = $failed > 0
                ? ImportBatch::STATUS_PARTIAL
                : ImportBatch::STATUS_SUCCESS;

            $batch->update([
                'total_rows'    => count($dataRows),
                'inserted_rows' => $inserted,
                'skipped_rows'  => $skipped,
                'failed_rows'   => $failed,
                'status'        => $finalStatus,
                'finished_at'   => now(),
            ]);

            $this->audit->log(
                AuditLog::ACTION_IMPORT,
                "Import pegawai: {$inserted} disimpan, {$skipped} duplikat, {$failed} gagal",
                $batch,
                [
                    'inserted' => $inserted,
                    'skipped'  => $skipped,
                    'failed'   => $failed,
                ],
            );

            return $batch->fresh();
        } catch (\Throwable $e) {
            // Mark the batch as failed and return it. We deliberately do NOT
            // rethrow here so the controller can return a 200 with a clear
            // failure summary instead of a generic 500.
            $batch->update([
                'status'        => ImportBatch::STATUS_FAILED,
                'finished_at'   => now(),
                'error_summary' => substr(get_class($e).': '.$e->getMessage(), 0, 1000),
            ]);
            report($e);
            return $batch->fresh();
        }
    }

    private function mapRow(array $header, array $raw): array
    {
        $assoc = [];
        foreach ($header as $idx => $col) {
            $assoc[$col] = isset($raw[$idx]) ? trim((string) $raw[$idx]) : null;
        }
        return $assoc;
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $v) {
            if ($v !== null && $v !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * @return array{0: string, 1: ?string, 2: ?int}  [outcome, error_message, employee_id]
     */
    private function persistRow(array $row, Collection $opdMap, Collection $catMap): array
    {
        // Required fields
        $missing = [];
        foreach (['nama_lengkap', 'tanggal_lahir', 'jabatan', 'kategori_jabatan_kode', 'opd_kode', 'tahun_pengangkatan'] as $req) {
            if (empty($row[$req])) {
                $missing[] = $req;
            }
        }
        if (! empty($missing)) {
            return [ImportBatchRow::OUTCOME_INVALID, 'Kolom wajib kosong: '.implode(', ', $missing), null];
        }

        // Resolve foreign keys (case-insensitive on the code).
        $opdKey = strtoupper((string) $row['opd_kode']);
        $catKey = strtoupper((string) $row['kategori_jabatan_kode']);
        $opdId  = $opdMap[$opdKey] ?? null;
        if (! $opdId) {
            $known = $opdMap->keys()->implode(', ');
            return [ImportBatchRow::OUTCOME_INVALID, "OPD tidak dikenal: '{$row['opd_kode']}'. Kode tersedia: {$known}", null];
        }
        $catId = $catMap[$catKey] ?? null;
        if (! $catId) {
            $known = $catMap->keys()->implode(', ');
            return [ImportBatchRow::OUTCOME_INVALID, "Kategori jabatan tidak dikenal: '{$row['kategori_jabatan_kode']}'. Kode tersedia: {$known}", null];
        }

        // Date parsing
        try {
            $dob = $this->parseDate($row['tanggal_lahir']);
        } catch (\Throwable $e) {
            return [ImportBatchRow::OUTCOME_INVALID, "Tanggal lahir tidak valid: {$row['tanggal_lahir']}", null];
        }

        // Year validation
        $year = (int) $row['tahun_pengangkatan'];
        if ($year < 1950 || $year > 2100) {
            return [ImportBatchRow::OUTCOME_INVALID, "Tahun pengangkatan tidak valid: {$row['tahun_pengangkatan']}", null];
        }

        // Duplicate check
        $nip = $row['nip'] ?: null;
        $nik = $row['nik'] ?: null;
        if ($nip && Employee::query()->where('nip', $nip)->exists()) {
            return [ImportBatchRow::OUTCOME_DUPLICATE, "NIP sudah terdaftar: {$nip}", null];
        }
        if (! $nip && $nik && Employee::query()->where('nik', $nik)->exists()) {
            return [ImportBatchRow::OUTCOME_DUPLICATE, "NIK sudah terdaftar: {$nik}", null];
        }

        try {
            $employee = DB::transaction(fn () => Employee::create([
                'nip'                 => $nip,
                'nik'                 => $nik,
                'full_name'           => $row['nama_lengkap'],
                'place_of_birth'      => $row['tempat_lahir'] ?: null,
                'date_of_birth'       => $dob,
                'gender'              => in_array($row['jenis_kelamin'], ['L', 'P'], true) ? $row['jenis_kelamin'] : null,
                'education'           => $row['pendidikan'] ?: null,
                'jabatan'             => $row['jabatan'],
                'jabatan_category_id' => $catId,
                'golongan'            => $row['golongan'] ?: null,
                'opd_id'              => $opdId,
                'unit_kerja'          => $row['unit_kerja'] ?: null,
                'appointment_year'    => $year,
                'phone'               => $row['telepon'] ?: null,
                'email'               => $row['email']   ?: null,
                'status'              => Employee::STATUS_AKTIF,
            ]));
            return [ImportBatchRow::OUTCOME_INSERTED, null, $employee->id];
        } catch (\Throwable $e) {
            return [ImportBatchRow::OUTCOME_ERROR, substr($e->getMessage(), 0, 500), null];
        }
    }

    private function parseDate(string $raw): string
    {
        // Excel may provide a serial number, dd/mm/yyyy, or yyyy-mm-dd.
        if (is_numeric($raw)) {
            $unix = ((int) $raw - 25569) * 86400;
            return Carbon::createFromTimestampUTC($unix)->toDateString();
        }
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd/m/y'] as $format) {
            $d = \DateTime::createFromFormat($format, $raw);
            if ($d !== false) {
                return Carbon::instance($d)->toDateString();
            }
        }
        return Carbon::parse($raw)->toDateString();
    }
}
