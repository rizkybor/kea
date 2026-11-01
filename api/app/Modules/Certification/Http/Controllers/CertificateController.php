<?php

namespace App\Modules\Certification\Http\Controllers;

use App\Modules\Certification\CertificatePdf;
use App\Modules\Certification\Models\Certificate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificateController
{
    public function verify(string $code): JsonResponse
    {
        $certificate = Certificate::where('code', strtoupper(trim($code)))->first();

        if (! $certificate) {
            return response()->json(['message' => 'Sertifikat tidak ditemukan.', 'code' => 'certificate_not_found', 'errors' => (object) []], 404);
        }

        return response()->json(['data' => $this->present($certificate)]);
    }

    public function mine(Request $request): JsonResponse
    {
        $certificates = Certificate::whereHas('enrollment', fn ($q) => $q->where('user_id', $request->user()->id))
            ->latest('issued_at')
            ->get();

        return response()->json(['data' => $certificates->map(fn (Certificate $c) => $this->present($c))]);
    }

    public function pdf(string $code, CertificatePdf $pdf): StreamedResponse
    {
        $certificate = Certificate::where('code', strtoupper($code))->whereNull('revoked_at')->firstOrFail();

        return Storage::disk('local')->download($pdf->path($certificate), "{$certificate->code}.pdf", [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Certificate $certificate): array
    {
        $verifyUrl = CertificatePdf::verifyUrl($certificate);

        return [
            'code' => $certificate->code,
            'is_valid' => $certificate->isValid(),
            'recipient_name' => $certificate->recipient_name,
            'program_title' => $certificate->program_title,
            'cohort_name' => $certificate->cohort_name,
            'final_score' => $certificate->final_score,
            'issued_at' => $certificate->issued_at->toIso8601String(),
            'verify_url' => $verifyUrl,
            'pdf_url' => route('certificates.pdf', $certificate->code, absolute: false),
            'linkedin_url' => 'https://www.linkedin.com/profile/add?'.http_build_query([
                'startTask' => 'CERTIFICATION_NAME',
                'name' => $certificate->program_title,
                'organizationName' => 'KEA by Jendela Cakra Digital',
                'issueYear' => $certificate->issued_at->year,
                'issueMonth' => $certificate->issued_at->month,
                'certUrl' => $verifyUrl,
                'certId' => $certificate->code,
            ]),
        ];
    }
}
