<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class MercurePublisher
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $hubUrl = null,
        private readonly ?string $jwt = null,
    ) {
    }

    public function publish(string $topic, array $data): void
    {
        if (!$this->hubUrl) {
            return; // Hub not configured
        }

        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
        if ($this->jwt) {
            $headers['Authorization'] = 'Bearer ' . $this->jwt;
        }

        $payload = http_build_query([
            'topic' => $topic,
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ], '', '&');

        try {
            $this->httpClient->request('POST', $this->hubUrl, [
                'headers' => $headers,
                'body' => $payload,
                'timeout' => 2.0,
            ]);
        } catch (\Throwable) {
            // soft-fail for realtime
        }
    }
}

