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
    </style>
</head>
<body>
    <div class="doc-number">PERJANJIAN KERJA<br>NOMOR: {{ $agreement->agreement_number }}</div>
    <div class="body-content">
        {!! $body !!}
    </div>
</body>
</html>
