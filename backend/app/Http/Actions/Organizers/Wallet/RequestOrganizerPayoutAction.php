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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RequestOrganizerPayoutAction extends BaseAction
{
    public function __invoke(Request $request, int $organizerId): JsonResponse
    {
        $this->isActionAuthorized($organizerId, OrganizerDomainObject::class, Role::ORGANIZER);

        $organizer = Organizer::query()->findOrFail($organizerId);
        $bankAccount = OrganizerPayoutAccount::query()->where('organizer_id', $organizerId)->first();

        if (! $bankAccount) {
            return $this->errorResponse('Please save your bank payout details before requesting a payout.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $feePercent = (float) config('app.saas_stripe_application_fee_percent', env('APP_SAAS_STRIPE_APPLICATION_FEE_PERCENT', 1.5));
        $totalSales = (float) DB::table('orders')
            ->join('events', 'events.id', '=', 'orders.event_id')
            ->where('events.organizer_id', $organizerId)
            ->where('orders.status', 'COMPLETED')
            ->where('orders.payment_status', 'PAYMENT_RECEIVED')
            ->whereNull('orders.deleted_at')
            ->sum('orders.total_gross');

        $netEarnings = max(0.0, round($totalSales - ($totalSales * ($feePercent / 100)), 2));
        $claimed = (float) OrganizerPayoutRequest::query()
            ->where('organizer_id', $organizerId)
            ->whereIn('status', [OrganizerPayoutRequest::STATUS_PAID, OrganizerPayoutRequest::STATUS_PENDING])
            ->sum('amount');

        $available = max(0.0, round($netEarnings - $claimed, 2));
        $amount = round((float) $validated['amount'], 2);

        if ($amount > $available) {
            return $this->errorResponse("Requested amount exceeds available balance ({$available}).", Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $payout = OrganizerPayoutRequest::create([
            'organizer_id' => $organizerId,
            'account_id' => $organizer->account_id,
            'amount' => $amount,
            'currency' => $bankAccount->currency,
            'status' => OrganizerPayoutRequest::STATUS_PENDING,
            'bank_name' => $bankAccount->bank_name,
            'account_number' => $bankAccount->account_number,
            'account_name' => $bankAccount->account_name,
        ]);

        return $this->jsonResponse(['data' => $payout], Response::HTTP_CREATED);
    }
}
