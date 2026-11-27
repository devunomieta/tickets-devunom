<?php

declare(strict_types=1);

namespace HiEvents\Services\Infrastructure\Payment;

use HiEvents\DomainObjects\Enums\PaymentProviders;
use HiEvents\DomainObjects\EventSettingDomainObject;
use HiEvents\DomainObjects\Generated\OrderDomainObjectAbstract;
use HiEvents\DomainObjects\OrderDomainObject;
use HiEvents\DomainObjects\OrderItemDomainObject;
use HiEvents\DomainObjects\Status\OrderPaymentStatus;
use HiEvents\DomainObjects\Status\OrderStatus;
use HiEvents\Events\OrderStatusChangedEvent;
use HiEvents\Repository\Interfaces\EventSettingsRepositoryInterface;
use HiEvents\Repository\Interfaces\OrderRepositoryInterface;
use HiEvents\Services\Domain\Product\ProductQuantityUpdateService;
use HiEvents\Services\Infrastructure\DomainEvents\DomainEventDispatcherService;
use HiEvents\Services\Infrastructure\DomainEvents\Enums\DomainEventType;
use HiEvents\Services\Infrastructure\DomainEvents\Events\OrderEvent;
use HiEvents\Services\Infrastructure\Lock\TransactionLockService;
use Illuminate\Database\DatabaseManager;

class ExternalPaymentFulfillmentService
{
    public function __construct(
        private readonly DatabaseManager $databaseManager,
        private readonly TransactionLockService $transactionLockService,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly EventSettingsRepositoryInterface $eventSettingsRepository,
        private readonly ProductQuantityUpdateService $productQuantityUpdateService,
        private readonly DomainEventDispatcherService $domainEventDispatcherService,
    ) {}

    public function markOrderPaid(string $orderShortId, PaymentProviders $provider): ?OrderDomainObject
    {
        return $this->databaseManager->transaction(function () use ($orderShortId, $provider) {
            $this->transactionLockService->lockOrder($orderShortId);

            /** @var OrderDomainObject|null $order */
            $order = $this->orderRepository
                ->loadRelation(OrderItemDomainObject::class)
                ->findByShortId($orderShortId);

            if ($order === null) {
                return null;
            }

            // Idempotent: already completed
            if ($order->getStatus() === OrderStatus::COMPLETED->name) {
                return $order;
            }

            $this->orderRepository->updateFromArray($order->getId(), [
                OrderDomainObjectAbstract::PAYMENT_STATUS => OrderPaymentStatus::PAYMENT_RECEIVED->name,
                OrderDomainObjectAbstract::STATUS => OrderStatus::COMPLETED->name,
                OrderDomainObjectAbstract::PAYMENT_PROVIDER => $provider->value,
            ]);

            $this->productQuantityUpdateService->updateQuantitiesFromOrder($order);

            /** @var OrderDomainObject $updatedOrder */
            $updatedOrder = $this->orderRepository
                ->loadRelation(OrderItemDomainObject::class)
                ->findById($order->getId());

            /** @var EventSettingDomainObject|null $eventSettings */
            $eventSettings = $this->eventSettingsRepository->findFirstWhere([
                'event_id' => $updatedOrder->getEventId(),
            ]);

            event(new OrderStatusChangedEvent(
                order: $updatedOrder,
                sendEmails: true,
                createInvoice: $eventSettings?->getEnableInvoicing() ?? false,
            ));

            $this->domainEventDispatcherService->dispatch(
                new OrderEvent(
                    type: DomainEventType::ORDER_CREATED,
                    orderId: $updatedOrder->getId(),
                ),
            );

            return $updatedOrder;
        });
    }
}
