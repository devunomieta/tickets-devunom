<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Admin\Payouts;

use HiEvents\Http\Actions\BaseAction;
use HiEvents\Models\Organizer;
use HiEvents\Models\OrganizerPayoutRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetAdminPayoutsAction extends BaseAction
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->requireSuperAdmin();

        $status = $request->query('status');
        $query = OrganizerPayoutRequest::query()->orderByDesc('created_at');
        if (in_array($status, [
            OrganizerPayoutRequest::STATUS_PENDING,
            OrganizerPayoutRequest::STATUS_PAID,
            OrganizerPayoutRequest::STATUS_REJECTED,
        ], true)) {
            $query->where('status', $status);
        }

        $payouts = $query->limit(100)->get();
        $organizers = Organizer::query()
            ->whereIn('id', $payouts->pluck('organizer_id')->unique())
            ->get(['id', 'name', 'email'])
            ->keyBy('id');

        $data = $payouts->map(function (OrganizerPayoutRequest $p) use ($organizers) {
            $org = $organizers->get($p->organizer_id);
            return [
                'id' => $p->id,
                'organizer_id' => $p->organizer_id,
                'organizer_name' => $org?->name ?? "Organizer #{$p->organizer_id}",
                'organizer_email' => $org?->email ?? '',
                'amount' => $p->amount,
                'currency' => $p->currency,
                'status' => $p->status,
                'bank_name' => $p->bank_name,
                'account_number' => $p->account_number,
                'account_name' => $p->account_name,
                'transfer_reference' => $p->transfer_reference,
                'admin_note' => $p->admin_note,
                'paid_at' => $p->paid_at?->toIso8601String(),
                'created_at' => $p->created_at?->toIso8601String(),
            ];
        });

        return $this->jsonResponse(['data' => $data]);
    }
}
