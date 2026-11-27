<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Common\Webhooks;

use HiEvents\DomainObjects\Enums\PaymentProviders;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Models\AccountPaymentGateway;
use HiEvents\Services\Infrastructure\Payment\BachsService;
use HiEvents\Services\Infrastructure\Payment\ExternalPaymentFulfillmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BachsIncomingWebhookAction extends BaseAction
{
    public function __construct(
        private readonly BachsService $bachsService,
        private readonly ExternalPaymentFulfillmentService $fulfillmentService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $signature = $request->header('X-Bachs-Signature')
            ?? $request->header('X-Webhook-Signature')
            ?? $request->header('X-Signature');
        $payload = $request->json()->all();

        $data = $payload['data'] ?? $payload;
        $accountId = $data['metadata']['account_id'] ?? null;
        $orderShortId = $data['metadata']['order_short_id'] ?? null;

        if (! $accountId || ! $orderShortId) {
            return $this->jsonResponse(['status' => 'ignored']);
        }

        $gateway = AccountPaymentGateway::query()
            ->where('account_id', $accountId)
            ->where('provider', AccountPaymentGateway::PROVIDER_BACHS)
            ->first();

        if (! $gateway || ! $this->bachsService->verifyWebhookSignature($gateway, $rawBody, $signature)) {
            return $this->errorResponse('Invalid Bachs.io signature.', Response::HTTP_UNAUTHORIZED);
        }

        $eventType = $payload['event'] ?? $payload['type'] ?? '';
        if (in_array($eventType, ['collection.succeeded', 'checkout.completed', 'payment.succeeded'], true)) {
            $this->fulfillmentService->markOrderPaid((string) $orderShortId, PaymentProviders::BACHS);
        }

        return $this->jsonResponse(['status' => 'ok']);
    }
}
