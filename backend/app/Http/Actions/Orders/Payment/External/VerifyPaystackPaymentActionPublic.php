<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Orders\Payment\External;

use HiEvents\DomainObjects\Enums\PaymentProviders;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Models\AccountPaymentGateway;
use HiEvents\Models\Event;
use HiEvents\Repository\Interfaces\OrderRepositoryInterface;
use HiEvents\Services\Infrastructure\Payment\ExternalPaymentFulfillmentService;
use HiEvents\Services\Infrastructure\Payment\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class VerifyPaystackPaymentActionPublic extends BaseAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly PaystackService $paystackService,
        private readonly ExternalPaymentFulfillmentService $fulfillmentService,
    ) {}

    public function __invoke(Request $request, int $eventId, string $orderShortId): JsonResponse
    {
        $reference = (string) $request->query('reference', $request->input('reference', ''));
        if ($reference === '') {
            return $this->errorResponse('Payment reference is required.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $order = $this->orderRepository->findByShortId($orderShortId);
        $event = Event::query()->select(['id', 'account_id'])->find($eventId);

        if (! $order || ! $event || $order->getEventId() !== $eventId) {
            return $this->errorResponse('Order not found.', Response::HTTP_NOT_FOUND);
        }

        $gateway = AccountPaymentGateway::query()
            ->where('account_id', $event->account_id)
            ->where('provider', AccountPaymentGateway::PROVIDER_PAYSTACK)
            ->first();

        if (! $gateway || ! $gateway->isConfigured()) {
            return $this->errorResponse('Paystack is not configured.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $tx = $this->paystackService->verifyTransaction($gateway, $reference);
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $metaOrderShortId = $tx['metadata']['order_short_id'] ?? null;
        if (($tx['status'] ?? null) === 'success' && $metaOrderShortId === $orderShortId) {
            $this->fulfillmentService->markOrderPaid($orderShortId, PaymentProviders::PAYSTACK);

            return $this->jsonResponse(['data' => ['status' => 'completed', 'reference' => $reference]]);
        }

        return $this->jsonResponse(['data' => ['status' => $tx['status'] ?? 'pending', 'reference' => $reference]]);
    }
}
