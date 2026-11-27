<?php

declare(strict_types=1);

namespace HiEvents\Models;

use Illuminate\Database\Eloquent\Model;

class AccountPaymentGateway extends Model
{
    public const PROVIDER_PAYSTACK = 'paystack';
    public const PROVIDER_BACHS = 'bachs';

    public const MODE_TEST = 'test';
    public const MODE_LIVE = 'live';

    protected $table = 'account_payment_gateways';

    protected $fillable = [
        'account_id',
        'provider',
        'is_enabled',
        'mode',
        'test_public_key',
        'test_secret_key',
        'test_webhook_secret',
        'live_public_key',
        'live_secret_key',
        'live_webhook_secret',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'test_secret_key' => 'encrypted',
        'test_webhook_secret' => 'encrypted',
        'live_secret_key' => 'encrypted',
        'live_webhook_secret' => 'encrypted',
    ];

    public function isLive(): bool
    {
        return $this->mode === self::MODE_LIVE;
    }

    public function getActivePublicKey(): ?string
    {
        return $this->isLive() ? $this->live_public_key : $this->test_public_key;
    }

    public function getActiveSecretKey(): ?string
    {
        return $this->isLive() ? $this->live_secret_key : $this->test_secret_key;
    }

    public function getActiveWebhookSecret(): ?string
    {
        return $this->isLive() ? $this->live_webhook_secret : $this->test_webhook_secret;
    }

    public function isConfigured(): bool
    {
        return $this->is_enabled && ! empty($this->getActiveSecretKey());
    }

    public function toSafeArray(): array
    {
        return [
            'provider' => $this->provider,
            'is_enabled' => (bool) $this->is_enabled,
            'mode' => $this->mode ?: self::MODE_TEST,
            'test_public_key' => $this->test_public_key ?? '',
            'has_test_secret_key' => ! empty($this->test_secret_key),
            'has_test_webhook_secret' => ! empty($this->test_webhook_secret),
            'live_public_key' => $this->live_public_key ?? '',
            'has_live_secret_key' => ! empty($this->live_secret_key),
            'has_live_webhook_secret' => ! empty($this->live_webhook_secret),
            'is_configured' => $this->isConfigured(),
        ];
    }
}
