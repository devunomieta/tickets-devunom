<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Orders\Payment\External;

use HiEvents\Http\Actions\BaseAction;
use HiEvents\Models\AccountPaymentGateway;
use HiEvents\Models\Event;
use Illuminate\Http\JsonResponse;

class GetEventPaymentGatewaysActionPublic extends BaseAction
{
    public function __invoke(int $eventId): JsonResponse
    {
        $event = Event::query()->select(['id', 'account_id'])->find($eventId);
        if (! $event) {
            return $this->jsonResponse([
                'data' => [
                    'paystack' => ['enabled' => false, 'mode' => 'test'],
                    'bachs' => ['enabled' => false, 'mode' => 'test'],
                ],
            ]);
        }

        $gateways = AccountPaymentGateway::query()
            ->where('account_id', $event->account_id)
            ->get()
            ->keyBy('provider');

        // Fall back to platform-level (Super Admin) configured gateways in SaaS mode
        $paystack = $gateways->get(AccountPaymentGateway::PROVIDER_PAYSTACK);
        if (! $paystack || ! $paystack->isConfigured()) {
            $paystack = AccountPaymentGateway::query()
                ->where('provider', AccountPaymentGateway::PROVIDER_PAYSTACK)
                ->where('is_enabled', true)
                ->first();
        }

        $bachs = $gateways->get(AccountPaymentGateway::PROVIDER_BACHS);
        if (! $bachs || ! $bachs->isConfigured()) {
            $bachs = AccountPaymentGateway::query()
                ->where('provider', AccountPaymentGateway::PROVIDER_BACHS)
                ->where('is_enabled', true)
                ->first();
        }

        return $this->jsonResponse([
            'data' => [
                'paystack' => [
                    'enabled' => $paystack?->isConfigured() ?? false,
                    'mode' => $paystack?->mode ?? 'test',
                ],
                'bachs' => [
                    'enabled' => $bachs?->isConfigured() ?? false,
                    'mode' => $bachs?->mode ?? 'test',
                ],
            ],
        ]);
    }
}
