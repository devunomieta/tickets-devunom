<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Common\Webhooks;

use HiEvents\DomainObjects\Enums\PaymentProviders;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Models\AccountPaymentGateway;
use HiEvents\Services\Infrastructure\Payment\ExternalPaymentFulfillmentService;
use HiEvents\Services\Infrastructure\Payment\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaystackIncomingWebhookAction extends BaseAction
{
    public function __construct(
        private readonly PaystackService $paystackService,
        private readonly ExternalPaymentFulfillmentService $fulfillmentService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $signature = $request->header('x-paystack-signature');
        $payload = $request->json()->all();

        $accountId = $payload['data']['metadata']['account_id'] ?? null;
        $orderShortId = $payload['data']['metadata']['order_short_id'] ?? null;

        if (! $accountId || ! $orderShortId) {
            return $this->jsonResponse(['status' => 'ignored']);
        }

        $gateway = AccountPaymentGateway::query()
            ->where('account_id', $accountId)
            ->where('provider', AccountPaymentGateway::PROVIDER_PAYSTACK)
            ->first();

        if (! $gateway || ! $this->paystackService->verifyWebhookSignature($gateway, $rawBody, $signature)) {
            return $this->errorResponse('Invalid Paystack signature.', Response::HTTP_UNAUTHORIZED);
        }

        if (($payload['event'] ?? null) === 'charge.success') {
            $this->fulfillmentService->markOrderPaid((string) $orderShortId, PaymentProviders::PAYSTACK);
        }

        return $this->jsonResponse(['status' => 'ok']);
    }
}
