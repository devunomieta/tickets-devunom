<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Organizers\Wallet;

use HiEvents\DomainObjects\Enums\Role;
use HiEvents\DomainObjects\OrganizerDomainObject;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Models\Organizer;
use HiEvents\Models\OrganizerPayoutAccount;
use HiEvents\Models\OrganizerPayoutRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class GetOrganizerWalletAction extends BaseAction
{
    public function __invoke(int $organizerId): JsonResponse
    {
        $this->isActionAuthorized($organizerId, OrganizerDomainObject::class, Role::ORGANIZER);

        $organizer = Organizer::query()->findOrFail($organizerId);
        $feePercent = (float) config('app.saas_stripe_application_fee_percent', env('APP_SAAS_STRIPE_APPLICATION_FEE_PERCENT', 1.5));

        $totalSales = (float) DB::table('orders')
            ->join('events', 'events.id', '=', 'orders.event_id')
            ->where('events.organizer_id', $organizerId)
            ->where('orders.status', 'COMPLETED')
            ->where('orders.payment_status', 'PAYMENT_RECEIVED')
            ->whereNull('orders.deleted_at')
            ->sum('orders.total_gross');

        $platformFees = round($totalSales * ($feePercent / 100), 2);
        $netEarnings = max(0.0, round($totalSales - $platformFees, 2));

        $totalPaidOut = (float) OrganizerPayoutRequest::query()
            ->where('organizer_id', $organizerId)
            ->where('status', OrganizerPayoutRequest::STATUS_PAID)
            ->sum('amount');

        $pendingPayouts = (float) OrganizerPayoutRequest::query()
            ->where('organizer_id', $organizerId)
            ->where('status', OrganizerPayoutRequest::STATUS_PENDING)
            ->sum('amount');

        $availableBalance = max(0.0, round($netEarnings - $totalPaidOut - $pendingPayouts, 2));

        $bankAccount = OrganizerPayoutAccount::query()->where('organizer_id', $organizerId)->first();
        $payouts = OrganizerPayoutRequest::query()
            ->where('organizer_id', $organizerId)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return $this->jsonResponse([
            'data' => [
                'currency' => $organizer->currency ?? 'NGN',
                'total_sales' => round($totalSales, 2),
                'platform_fee_percent' => $feePercent,
                'platform_fees' => $platformFees,
                'net_earnings' => $netEarnings,
                'total_paid_out' => round($totalPaidOut, 2),
                'pending_payouts' => round($pendingPayouts, 2),
                'available_balance' => $availableBalance,
                'bank_account' => $bankAccount,
                'payouts' => $payouts,
            ],
        ]);
    }
}
