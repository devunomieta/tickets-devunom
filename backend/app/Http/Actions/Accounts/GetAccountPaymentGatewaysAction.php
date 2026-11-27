<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Accounts;

use HiEvents\DomainObjects\AccountDomainObject;
use HiEvents\DomainObjects\Enums\Role;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Models\AccountPaymentGateway;
use Illuminate\Http\JsonResponse;

class GetAccountPaymentGatewaysAction extends BaseAction
{
    public function __invoke(int $accountId): JsonResponse
    {
        $this->isActionAuthorized($accountId, AccountDomainObject::class, Role::ADMIN);

        $existing = AccountPaymentGateway::query()
            ->where('account_id', $accountId)
            ->get()
            ->keyBy('provider');

        $providers = [
            AccountPaymentGateway::PROVIDER_PAYSTACK,
            AccountPaymentGateway::PROVIDER_BACHS,
        ];

        $result = [];
        foreach ($providers as $provider) {
            $model = $existing->get($provider) ?? new AccountPaymentGateway([
                'account_id' => $accountId,
                'provider' => $provider,
                'is_enabled' => false,
                'mode' => AccountPaymentGateway::MODE_TEST,
            ]);
            $result[$provider] = $model->toSafeArray();
        }

        return $this->jsonResponse(['data' => $result]);
    }
}
