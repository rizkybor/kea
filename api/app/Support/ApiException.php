<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Error bisnis yang dirender sebagai `{ message, code, errors }` (blueprint §4).
 */
class ApiException extends RuntimeException
{
    /**
     * @param  array<string, list<string>>  $errors
     */
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly int $status = 422,
        public readonly array $errors = [],
    ) {
        parent::__construct($message);
    }

    public static function quotaFull(): self
    {
        return new self('Kuota cohort sudah penuh.', 'cohort_full', 409);
    }

    public static function emailUnverified(): self
    {
        return new self('Verifikasi email dulu sebelum checkout.', 'email_unverified', 403);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode,
            'errors' => (object) $this->errors,
        ], $this->status);
    }
}
