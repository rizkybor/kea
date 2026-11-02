<?php

namespace App\Modules\Certification;

use App\Modules\Certification\Models\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Storage;

/**
 * Render PDF sertifikat (A4 landscape) dengan QR ke halaman verifikasi publik, lalu disimpan sekali di storage.
 */
class CertificatePdf
{
    public function path(Certificate $certificate): string
    {
        if ($certificate->pdf_path && Storage::disk('local')->exists($certificate->pdf_path)) {
            return $certificate->pdf_path;
        }

        $path = "certificates/{$certificate->code}.pdf";

        Storage::disk('local')->put($path, $this->render($certificate));
        $certificate->update(['pdf_path' => $path]);

        return $path;
    }

    public function render(Certificate $certificate): string
    {
        $qr = (new QRCode(new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'outputBase64' => true,
            'scale' => 5,
        ])))->render(self::verifyUrl($certificate));

        return Pdf::loadView('certificates.pdf', [
            'certificate' => $certificate,
            'qr' => $qr,
            'logo' => 'data:image/png;base64,'.base64_encode((string) file_get_contents(resource_path('brand/icon-192.png'))),
            'verifyUrl' => self::verifyUrl($certificate),
        ])->setPaper('a4', 'landscape')->setOption('isFontSubsettingEnabled', true)->output();
    }

    public static function verifyUrl(Certificate $certificate): string
    {
        return rtrim((string) config('app.frontend_url'), '/').'/verify/'.$certificate->code;
    }
}
