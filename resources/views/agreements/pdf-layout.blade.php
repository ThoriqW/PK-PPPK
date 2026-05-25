<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Perjanjian Kerja {{ $agreement->agreement_number }}</title>
    <style>
        @page { margin: 28mm 22mm 28mm 22mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12pt; line-height: 1.55; color: #111; }
        .doc-number { text-align: center; font-weight: bold; margin-bottom: 18px; }
        .body-content { text-align: justify; }
        .footer-qr { position: fixed; bottom: 0; left: 0; right: 0; padding-top: 10px; border-top: 1px solid #d1d5db; font-size: 9pt; color: #4b5563; }
        .footer-qr table { width: 100%; }
        .footer-qr td.left { vertical-align: middle; padding-right: 12px; }
        .footer-qr td.right { vertical-align: middle; text-align: right; }
        .footer-qr svg, .footer-qr img { width: 80px; height: 80px; }
        .verify-text { font-size: 8pt; color: #6b7280; }
    </style>
</head>
<body>
    <div class="doc-number">PERJANJIAN KERJA<br>NOMOR: {{ $agreement->agreement_number }}</div>
    <div class="body-content">
        {!! $body !!}
    </div>

    <div class="footer-qr">
        <table>
            <tr>
                <td class="left verify-text">
                    Dokumen ini dilengkapi QR Code untuk verifikasi keabsahan.<br>
                    Pindai QR di samping atau buka:<br>
                    <strong>{{ $verifyUrl }}</strong>
                </td>
                <td class="right">
                    {!! $qrInline !!}
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
