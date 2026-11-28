<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Orders\Payment\External;

use HiEvents\Http\Actions\BaseAction;
use HiEvents\Models\AccountPaymentGateway;
use HiEvents\Models\Event;
use HiEvents\Repository\Interfaces\OrderRepositoryInterface;
use HiEvents\Services\Infrastructure\Payment\PaystackService;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class CreatePaystackCheckoutActionPublic extends BaseAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly PaystackService $paystackService,
    ) {}

    public function __invoke(int $eventId, string $orderShortId): JsonResponse
    {
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
            $gateway = AccountPaymentGateway::query()
                ->where('provider', AccountPaymentGateway::PROVIDER_PAYSTACK)
                ->where('is_enabled', true)
                ->first();
        }

        if (! $gateway || ! $gateway->isConfigured()) {
            return $this->errorResponse('Paystack is not enabled for this event.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $frontendUrl = rtrim((string) config('app.frontend_url', env('APP_FRONTEND_URL', '')), '/');
        $reference = 'he_' . $order->getShortId() . '_' . time();
        $callbackUrl = "{$frontendUrl}/checkout/{$eventId}/{$order->getShortId()}/summary?provider=paystack&reference={$reference}";

        try {
            $session = $this->paystackService->initializeTransaction(
                gateway: $gateway,
                amountMajor: (float) $order->getTotalGross(),
                currency: (string) $order->getCurrency(),
                email: (string) ($order->getEmail() ?: 'customer@example.com'),
                reference: $reference,
                callbackUrl: $callbackUrl,
                metadata: [
                    'order_short_id' => $order->getShortId(),
                    'event_id' => $eventId,
                    'account_id' => $event->account_id,
                ],
            );
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->jsonResponse(['data' => $session]);
    }
}
