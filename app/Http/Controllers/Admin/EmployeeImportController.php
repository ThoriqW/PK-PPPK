<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Models\JabatanCategory;
use App\Models\Opd;
use App\Services\EmployeeImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmployeeImportController extends Controller
{
    public function __construct(private readonly EmployeeImportService $importer)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $batches = ImportBatch::query()
            ->with('uploader:id,name,email')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 20));
        return response()->json($batches);
    }

    public function show(ImportBatch $batch): JsonResponse
    {
        return response()->json([
            'batch' => $batch->load('uploader:id,name,email'),
            'rows'  => $batch->rows()->orderBy('row_number')->limit(1000)->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            // Many browsers send xlsx as application/octet-stream so we accept
            // by extension rather than mime to avoid false negatives.
            'file' => ['required', 'file', 'max:10240', 'mimes:xlsx,xls'],
        ]);

        // Make sure the imports/ directory exists. storeAs will create it
        // automatically on the local disk, but we be explicit to avoid
        // silent failures on read-only filesystems.
        Storage::disk('local')->makeDirectory('imports');

        try {
            $stored = $request->file('file')->storeAs(
                'imports',
                sprintf('%s-%s.xlsx', now()->format('Ymd-His'), bin2hex(random_bytes(4))),
                'local',
            );

            if (! $stored) {
                return response()->json([
                    'error' => [
                        'code'    => 'STORAGE_FAILED',
                        'message' => 'Gagal menyimpan file di server. Periksa izin tulis pada storage/app/imports.',
                    ],
                ], 500);
            }

            $batch = ImportBatch::create([
                'filename'    => $request->file('file')->getClientOriginalName(),
                'stored_path' => $stored,
                'uploaded_by' => $request->user()?->id,
                'status'      => ImportBatch::STATUS_PENDING,
            ]);

            // Process synchronously for now. For large files, dispatch a queued
            // job (\App\Jobs\ProcessEmployeeImport) instead.
            $batch = $this->importer->process($batch);

            return response()->json(['batch' => $batch], 201);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'error' => [
                    'code'    => 'IMPORT_UNEXPECTED',
                    'message' => 'Terjadi kesalahan saat memproses impor: '.$e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Download a blank Excel template with the correct headers and seeded
     * reference codes the operator can fill in. Generated on the fly so it
     * always reflects the current OPDs and jabatan categories.
     */
    public function template(): BinaryFileResponse
    {
        $headers = [
            'nip', 'nik', 'nama_lengkap', 'tempat_lahir', 'tanggal_lahir',
            'jenis_kelamin', 'pendidikan', 'jabatan', 'kategori_jabatan_kode',
            'golongan', 'opd_kode', 'unit_kerja', 'tahun_pengangkatan',
            'telepon', 'email',
        ];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Pegawai');
        $sheet->fromArray($headers, null, 'A1');

        // Style the header row.
        $sheet->getStyle('A1:O1')->getFont()->setBold(true);
        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Reference sheet so operators know which codes are valid.
        $ref = $spreadsheet->createSheet();
        $ref->setTitle('Kode Referensi');
        $ref->setCellValue('A1', 'OPD - Kode');
        $ref->setCellValue('B1', 'OPD - Nama');
        $ref->setCellValue('D1', 'Kategori Jabatan - Kode');
        $ref->setCellValue('E1', 'Kategori Jabatan - Nama');
        $ref->setCellValue('F1', 'Usia Pensiun');

        $row = 2;
        foreach (Opd::orderBy('code')->get() as $opd) {
            $ref->setCellValue("A{$row}", $opd->code);
            $ref->setCellValue("B{$row}", $opd->name);
            $row++;
        }
        $row = 2;
        foreach (JabatanCategory::orderBy('code')->get() as $cat) {
            $ref->setCellValue("D{$row}", $cat->code);
            $ref->setCellValue("E{$row}", $cat->name);
            $ref->setCellValue("F{$row}", $cat->retirement_age);
            $row++;
        }
        $ref->getStyle('A1:F1')->getFont()->setBold(true);
        foreach (range('A', 'F') as $col) {
            $ref->getColumnDimension($col)->setAutoSize(true);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'tpl_pegawai_').'.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tmp);

        return response()->download($tmp, 'Template-Impor-Pegawai.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
