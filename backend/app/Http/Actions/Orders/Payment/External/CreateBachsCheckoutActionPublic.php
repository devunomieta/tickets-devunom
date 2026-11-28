<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Orders\Payment\External;

use HiEvents\Http\Actions\BaseAction;
use HiEvents\Models\AccountPaymentGateway;
use HiEvents\Models\Event;
use HiEvents\Repository\Interfaces\OrderRepositoryInterface;
use HiEvents\Services\Infrastructure\Payment\BachsService;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class CreateBachsCheckoutActionPublic extends BaseAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly BachsService $bachsService,
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
            ->where('provider', AccountPaymentGateway::PROVIDER_BACHS)
            ->first();

        if (! $gateway || ! $gateway->isConfigured()) {
            $gateway = AccountPaymentGateway::query()
                ->where('provider', AccountPaymentGateway::PROVIDER_BACHS)
                ->where('is_enabled', true)
                ->first();
        }

        if (! $gateway || ! $gateway->isConfigured()) {
            return $this->errorResponse('Bachs.io is not enabled for this event.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $frontendUrl = rtrim((string) config('app.frontend_url', env('APP_FRONTEND_URL', '')), '/');
        $successUrl = "{$frontendUrl}/checkout/{$eventId}/{$order->getShortId()}/summary?provider=bachs";
        $cancelUrl = "{$frontendUrl}/checkout/{$eventId}/{$order->getShortId()}/payment";

        try {
            $session = $this->bachsService->createCheckout(
                gateway: $gateway,
                amountMajor: (float) $order->getTotalGross(),
                currency: (string) $order->getCurrency(),
                email: (string) ($order->getEmail() ?: 'customer@example.com'),
                orderShortId: $order->getShortId(),
                successUrl: $successUrl,
                cancelUrl: $cancelUrl,
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
