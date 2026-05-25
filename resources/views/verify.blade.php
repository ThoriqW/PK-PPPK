<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Verifikasi Perjanjian Kerja - BKPSDMD Kota Palu</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #0b5394 0%, #083f70 100%); color: #1f2937;
            display: flex; align-items: center; justify-content: center; padding: 24px;
        }
        .card {
            background: #fff; max-width: 520px; width: 100%; border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.18); overflow: hidden;
        }
        .header { background: #0b5394; color: white; padding: 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 18px; font-weight: 600; letter-spacing: 0.3px; }
        .header p  { margin: 4px 0 0; opacity: 0.85; font-size: 13px; }
        .body { padding: 28px 24px; }
        .row { display: flex; flex-direction: column; padding: 14px 0; border-bottom: 1px solid #f1f5f9; }
        .row:last-child { border-bottom: 0; }
        .label { color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.6px; }
        .value { font-size: 16px; font-weight: 600; margin-top: 4px; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .badge.aktif      { background: #dcfce7; color: #166534; }
        .badge.arsip      { background: #fef9c3; color: #854d0e; }
        .badge.dibatalkan { background: #fee2e2; color: #991b1b; }
        .badge.tidak      { background: #fee2e2; color: #991b1b; }
        .footer { padding: 14px 24px; background: #f8fafc; color: #475569; font-size: 12px; text-align: center; }
        .notfound { padding: 40px 24px; text-align: center; }
        .notfound h2 { margin: 0 0 8px; color: #991b1b; }
        .notfound p  { margin: 0; color: #64748b; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>Verifikasi Perjanjian Kerja</h1>
            <p>BKPSDMD Kota Palu</p>
        </div>

        @if ($agreement)
            <div class="body">
                <div class="row">
                    <span class="label">Nama Pegawai</span>
                    <span class="value">{{ $agreement->employee->full_name ?? '-' }}</span>
                </div>
                <div class="row">
                    <span class="label">Nomor Perjanjian</span>
                    <span class="value">{{ $agreement->agreement_number }}</span>
                </div>
                <div class="row">
                    <span class="label">Status</span>
                    <span class="value">
                        <span class="badge {{ strtolower($agreement->status) }}">{{ $agreement->status }}</span>
                    </span>
                </div>
            </div>
        @else
            <div class="notfound">
                <h2>Dokumen tidak ditemukan</h2>
                <p>Kode verifikasi tidak valid atau sudah tidak berlaku.</p>
            </div>
        @endif

        <div class="footer">
            Diverifikasi pada {{ $verifiedAt->translatedFormat('d F Y H:i') }} WITA
        </div>
    </div>
</body>
</html>
