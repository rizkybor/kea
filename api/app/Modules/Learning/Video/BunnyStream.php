<?php

namespace App\Modules\Learning\Video;

use Carbon\CarbonInterface;

/**
 * Signed embed URL Bunny Stream (token authentication), kedaluwarsa 2 jam.
 */
class BunnyStream
{
    public const TTL_MINUTES = 120;

    /**
     * @return array{url: ?string, expires_at: ?string}
     */
    public function playback(?string $videoId): array
    {
        $libraryId = config('services.bunny.library_id');
        $tokenKey = config('services.bunny.token_key');

        if (! $videoId || ! $libraryId || ! $tokenKey) {
            return ['url' => null, 'expires_at' => null];
        }

        $expiresAt = now()->addMinutes(self::TTL_MINUTES);

        return [
            'url' => $this->signedUrl($libraryId, $videoId, $tokenKey, $expiresAt),
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    private function signedUrl(string $libraryId, string $videoId, string $tokenKey, CarbonInterface $expiresAt): string
    {
        $expires = $expiresAt->getTimestamp();
        $token = hash('sha256', $tokenKey.$videoId.$expires);

        return "https://iframe.mediadelivery.net/embed/{$libraryId}/{$videoId}?token={$token}&expires={$expires}";
    }
}
