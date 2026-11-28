<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Admin\Payouts;

use HiEvents\Http\Actions\BaseAction;
use HiEvents\Models\OrganizerPayoutRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateAdminPayoutAction extends BaseAction
{
    public function __invoke(Request $request, int $payoutId): JsonResponse
    {
        $this->requireSuperAdmin();

        $payout = OrganizerPayoutRequest::query()->find($payoutId);
        if (! $payout) {
            return $this->errorResponse('Payout request not found.', Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:PAID,REJECTED'],
            'transfer_reference' => ['nullable', 'string', 'max:150'],
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $payout->status = $validated['status'];
        $payout->transfer_reference = $validated['transfer_reference'] ?? $payout->transfer_reference;
        $payout->admin_note = $validated['admin_note'] ?? $payout->admin_note;
        if ($validated['status'] === OrganizerPayoutRequest::STATUS_PAID && ! $payout->paid_at) {
            $payout->paid_at = now();
        }
        $payout->save();

        return $this->jsonResponse(['data' => $payout]);
    }
}
