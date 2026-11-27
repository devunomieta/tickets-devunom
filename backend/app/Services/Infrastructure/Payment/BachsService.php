<?php

declare(strict_types=1);

namespace HiEvents\Services\Infrastructure\Payment;

use HiEvents\Models\AccountPaymentGateway;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BachsService
{
    private const SANDBOX_BASE_URL = 'https://sandbox-api.bachs.io';
    private const LIVE_BASE_URL = 'https://api.bachs.io';

    public function getBaseUrl(AccountPaymentGateway $gateway): string
    {
        return $gateway->isLive() ? self::LIVE_BASE_URL : self::SANDBOX_BASE_URL;
    }

    public function createCheckout(
        AccountPaymentGateway $gateway,
        float $amountMajor,
        string $currency,
        string $email,
        string $orderShortId,
        string $successUrl,
        string $cancelUrl,
        array $metadata = [],
    ): array {
        $secretKey = $gateway->getActiveSecretKey();
        if (empty($secretKey)) {
            throw new RuntimeException('Bachs.io secret key is not configured for the active mode.');
        }

        // Bachs.io requires money as a decimal string at currency precision (e.g. "5000.00")
        $amountDecimal = number_format($amountMajor, 2, '.', '');

        $response = Http::withToken($secretKey)
            ->withHeaders([
                'Accept' => 'application/json',
                'Idempotency-Key' => 'hievents_' . $orderShortId,
            ])
            ->timeout(20)
            ->post($this->getBaseUrl($gateway) . '/v1/checkouts', [
                'amount' => $amountDecimal,
                'currency' => strtoupper($currency),
                'email' => $email,
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => $metadata,
            ]);

        if (! $response->successful()) {
            $message = $response->json('message') ?? $response->json('error') ?? 'Failed to create Bachs.io checkout.';
            throw new RuntimeException(is_string($message) ? $message : 'Failed to create Bachs.io checkout.');
        }

        $data = $response->json('data') ?? $response->json();

        return [
            'checkout_url' => $data['checkout_url'] ?? null,
            'payment_id' => $data['payment_id'] ?? null,
            'reference' => $data['reference'] ?? null,
            'status' => $data['status'] ?? 'pending',
        ];
    }

    public function verifyWebhookSignature(AccountPaymentGateway $gateway, string $rawPayload, ?string $signature): bool
    {
        if (empty($signature)) {
            return false;
        }

        $secrets = array_filter([
            $gateway->live_webhook_secret,
            $gateway->test_webhook_secret,
        ]);

        // Support raw hex HMAC-SHA256 or "sha256=<hex>" / "v1=<hex>" formats
        $normalizedSig = preg_replace('/^(sha256=|v1=)/i', '', trim($signature));

        foreach ($secrets as $secret) {
            $computed = hash_hmac('sha256', $rawPayload, $secret);
            if (hash_equals($computed, $normalizedSig)) {
                return true;
            }
        }

        return false;
    }
}
