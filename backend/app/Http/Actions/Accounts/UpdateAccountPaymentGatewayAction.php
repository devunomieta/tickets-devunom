<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Accounts;

use HiEvents\DomainObjects\AccountDomainObject;
use HiEvents\DomainObjects\Enums\Role;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Models\AccountPaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateAccountPaymentGatewayAction extends BaseAction
{
    public function __invoke(Request $request, int $accountId, string $provider): JsonResponse
    {
        $this->isActionAuthorized($accountId, AccountDomainObject::class, Role::ADMIN);

        $provider = strtolower($provider);
        if (! in_array($provider, [
            AccountPaymentGateway::PROVIDER_PAYSTACK,
            AccountPaymentGateway::PROVIDER_BACHS,
        ], true)) {
            return $this->errorResponse('Unsupported payment provider.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $request->validate([
            'is_enabled' => ['required', 'boolean'],
            'mode' => ['required', 'in:test,live'],
            'test_public_key' => ['nullable', 'string', 'max:255'],
            'test_secret_key' => ['nullable', 'string', 'max:255'],
            'test_webhook_secret' => ['nullable', 'string', 'max:255'],
            'live_public_key' => ['nullable', 'string', 'max:255'],
            'live_secret_key' => ['nullable', 'string', 'max:255'],
            'live_webhook_secret' => ['nullable', 'string', 'max:255'],
        ]);

        $gateway = AccountPaymentGateway::firstOrNew([
            'account_id' => $accountId,
            'provider' => $provider,
        ]);

        $gateway->is_enabled = (bool) $validated['is_enabled'];
        $gateway->mode = $validated['mode'];

        if (array_key_exists('test_public_key', $validated)) {
            $gateway->test_public_key = $validated['test_public_key'] ?: null;
        }
        if (array_key_exists('live_public_key', $validated)) {
            $gateway->live_public_key = $validated['live_public_key'] ?: null;
        }

        // Only overwrite encrypted secrets when a non-empty value is supplied
        foreach (['test_secret_key', 'test_webhook_secret', 'live_secret_key', 'live_webhook_secret'] as $secretField) {
            if (! empty($validated[$secretField])) {
                $gateway->{$secretField} = trim($validated[$secretField]);
            }
        }

        $gateway->save();

        return $this->jsonResponse(['data' => $gateway->toSafeArray()]);
    }
}
