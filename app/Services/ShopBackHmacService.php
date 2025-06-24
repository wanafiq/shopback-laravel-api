<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ShopBackHmacService
{
    private string $accessKey;
    private string $accessKeySecret;

    public function __construct()
    {
        $this->accessKey = config('services.shopback.access_key');
        $this->accessKeySecret = config('services.shopback.access_key_secret');
    }

    public function generateSignature(
        string $method,
        string $path,
        array $body = [],
        string $contentType = 'application/json',
    ): array {
        $date = Carbon::now();
        $dateString = $date->format('Y-m-d\TH:i:s.v\Z');

        $contentDigest = $this->generateContentDigest($body);
        $stringToSign = $this->createStringToSign($method, $contentType, $dateString, $path, $contentDigest);
        $signature = $this->createHmacSignature($stringToSign);

        // Debug logging
        Log::info("String to sign:\n---\n" . $stringToSign . "\n---");
        Log::info("Signature: {$signature}\n");

        return [
            'authorization' => "SB1-HMAC-SHA256 {$this->accessKey}:{$signature}",
            'date' => $dateString,
            'content_digest' => $contentDigest
        ];
    }

    public function getAuthorizationHeader(
        string $method,
        string $path,
        array $body = [],
        string $contentType = 'application/json',
        ?Carbon $date = null
    ): string {
        $result = $this->generateSignature($method, $path, $body, $contentType, $date);
        return $result['authorization'];
    }

    private function generateContentDigest(array $body): string
    {
        if (empty($body)) {
            return '';
        }

        $sortedBody = $this->sortArrayRecursively($body);
        $jsonBody = json_encode($sortedBody, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash('sha256', $jsonBody);
    }

    private function sortArrayRecursively(array $array): array
    {
        ksort($array);

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->sortArrayRecursively($value);
            }
        }

        return $array;
    }

    private function createStringToSign(
        string $method,
        string $contentType,
        string $date,
        string $path,
        string $contentDigest
    ): string {
        return strtoupper($method) . "\n" . $contentType . "\n" . $date . "\n" . $path . "\n" . $contentDigest;
    }

    private function createHmacSignature(string $stringToSign): string
    {
        return hash_hmac('sha256', $stringToSign, $this->accessKeySecret);
    }

    public function validateSignature(
        string $providedSignature,
        string $method,
        string $path,
        array $body = [],
        string $contentType = 'application/json'
    ): bool {
        $expectedSignature = $this->generateSignature($method, $path, $body, $contentType);
        return hash_equals($providedSignature, $expectedSignature['authorization']);
    }
}
