<?php

namespace App\Services;

use App\Models\Agreement;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Generates QR tokens and renders QR images.
 *
 * Strategy:
 *   1. If GD is available (almost always true on XAMPP/Laragon/most PHP
 *      installs on Windows), produce a real PNG via endroid/qr-code.
 *      DomPDF embeds raster PNGs flawlessly.
 *   2. Otherwise fall back to pure-PHP SVG (via bacon/bacon-qr-code from
 *      simplesoftwareio/simple-qrcode). The XML prolog is stripped and an
 *      explicit width/height is injected so DomPDF can render the inline
 *      <svg> reliably.
 *
 * No imagick extension is ever required.
 */
class QrTokenService
{
    /**
     * Generate a URL-safe random token guaranteed unique against agreements.qr_token.
     */
    public function generateUniqueToken(): string
    {
        do {
            $token = Str::random(40);
        } while (Agreement::query()->where('qr_token', $token)->exists());

        return $token;
    }

    /**
     * Returns an HTML snippet ready to embed wherever a QR should appear in
     * the agreement template (the {{qr_code}} placeholder). DomPDF accepts
     * either a PNG <img> with a data URI or inline <svg>; this method picks
     * whichever path is supported by the current PHP environment.
     */
    public function inlineHtml(string $url, int $size = 200): string
    {
        if (function_exists('imagecreatetruecolor')) {
            $dataUri = $this->pngDataUri($url, $size);
            return '<img src="'.$dataUri.'" alt="QR Verifikasi" '
                .'style="width:'.$size.'px;height:'.$size.'px;" />';
        }

        $svg = $this->svg($url, $size);
        return '<div style="width:'.$size.'px;height:'.$size.'px;display:inline-block;">'
            .$svg
            .'</div>';
    }

    /**
     * Base-64 PNG via endroid/qr-code (GD). The result is suitable for both
     * <img src="..."> in HTML pages and PDFs.
     */
    public function pngDataUri(string $url, int $size = 200): string
    {
        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($url)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size($size)
            ->margin(8)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build();

        return $result->getDataUri();
    }

    /**
     * Raw SVG markup, prolog stripped, width/height enforced — safe to
     * inline inside DomPDF when GD is unavailable.
     */
    public function svg(string $url, int $size = 200): string
    {
        $svg = (string) QrCode::format('svg')
            ->size($size)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($url);

        $svg = preg_replace('/<\?xml[^?]*\?>\s*/u', '', $svg);
        $svg = preg_replace('/<!DOCTYPE[^>]*>\s*/u', '', $svg);

        if (! preg_match('/<svg[^>]*\swidth=/i', $svg)) {
            $svg = preg_replace(
                '/<svg\b/i',
                '<svg width="'.$size.'" height="'.$size.'"',
                $svg,
                1,
            );
        }

        return trim($svg);
    }

    /**
     * Returns a data URI suitable for <img src="..."> regardless of which
     * backend is available. PNG when GD is available, SVG otherwise.
     */
    public function dataUri(string $url, int $size = 200): string
    {
        if (function_exists('imagecreatetruecolor')) {
            return $this->pngDataUri($url, $size);
        }

        $svg = (string) QrCode::format('svg')
            ->size($size)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($url);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
