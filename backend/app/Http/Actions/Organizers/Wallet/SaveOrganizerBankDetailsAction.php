<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Organizers\Wallet;

use HiEvents\DomainObjects\Enums\Role;
use HiEvents\DomainObjects\OrganizerDomainObject;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Models\Organizer;
use HiEvents\Models\OrganizerPayoutAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaveOrganizerBankDetailsAction extends BaseAction
{
    public function __invoke(Request $request, int $organizerId): JsonResponse
    {
        $this->isActionAuthorized($organizerId, OrganizerDomainObject::class, Role::ORGANIZER);

        $organizer = Organizer::query()->findOrFail($organizerId);
        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:150'],
            'account_number' => ['required', 'string', 'max:64'],
            'account_name' => ['required', 'string', 'max:150'],
            'currency' => ['nullable', 'string', 'max:10'],
        ]);

        $bankAccount = OrganizerPayoutAccount::updateOrCreate(
            ['organizer_id' => $organizerId],
            [
                'account_id' => $organizer->account_id,
                'bank_name' => trim($validated['bank_name']),
                'account_number' => trim($validated['account_number']),
                'account_name' => trim($validated['account_name']),
                'currency' => strtoupper($validated['currency'] ?? $organizer->currency ?? 'NGN'),
            ]
        );

        return $this->jsonResponse(['data' => $bankAccount]);
    }
}
