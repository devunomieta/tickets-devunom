<?php

declare(strict_types=1);

namespace HiEvents\Services\Infrastructure\Payment;

use HiEvents\Models\AccountPaymentGateway;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaystackService
{
    private const BASE_URL = 'https://api.paystack.co';

    public function initializeTransaction(
        AccountPaymentGateway $gateway,
        float $amountMajor,
        string $currency,
        string $email,
        string $reference,
        string $callbackUrl,
        array $metadata = [],
    ): array {
        $secretKey = $gateway->getActiveSecretKey();
        if (empty($secretKey)) {
            throw new RuntimeException('Paystack secret key is not configured for the active mode.');
        }

        // Paystack expects integer amount in minor currency units (e.g. kobo/cents)
        $amountMinor = (int) round($amountMajor * 100);

        $response = Http::withToken($secretKey)
            ->acceptJson()
            ->timeout(20)
            ->post(self::BASE_URL . '/transaction/initialize', [
                'amount' => $amountMinor,
                'currency' => strtoupper($currency),
                'email' => $email,
                'reference' => $reference,
                'callback_url' => $callbackUrl,
                'metadata' => $metadata,
            ]);

        if (! $response->successful() || ! ($response->json('status') === true)) {
            $message = $response->json('message') ?? 'Failed to initialize Paystack transaction.';
            throw new RuntimeException($message);
        }

        return [
            'authorization_url' => $response->json('data.authorization_url'),
            'access_code' => $response->json('data.access_code'),
            'reference' => $response->json('data.reference'),
        ];
    }

    public function verifyTransaction(AccountPaymentGateway $gateway, string $reference): array
    {
        $secretKey = $gateway->getActiveSecretKey();
        if (empty($secretKey)) {
            throw new RuntimeException('Paystack secret key is not configured for the active mode.');
        }

        $response = Http::withToken($secretKey)
            ->acceptJson()
            ->timeout(20)
            ->get(self::BASE_URL . '/transaction/verify/' . urlencode($reference));

        if (! $response->successful() || ! ($response->json('status') === true)) {
            $message = $response->json('message') ?? 'Failed to verify Paystack transaction.';
            throw new RuntimeException($message);
        }

        return $response->json('data') ?? [];
    }

    public function verifyWebhookSignature(AccountPaymentGateway $gateway, string $rawPayload, ?string $signature): bool
    {
        if (empty($signature)) {
            return false;
        }

        foreach (array_filter([$gateway->live_secret_key, $gateway->test_secret_key]) as $secret) {
            $computed = hash_hmac('sha512', $rawPayload, $secret);
            if (hash_equals($computed, $signature)) {
                return true;
            }
        }

        return false;
    }
}
