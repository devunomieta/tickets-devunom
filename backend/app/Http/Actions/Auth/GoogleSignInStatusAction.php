<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Auth;

use HiEvents\Http\Actions\BaseAction;
use Illuminate\Http\JsonResponse;

class GoogleSignInStatusAction extends BaseAction
{
    public function __invoke(): JsonResponse
    {
        $enabled = ! empty(config('services.google.client_id'))
            && ! empty(config('services.google.client_secret'));

        return $this->jsonResponse(['enabled' => $enabled]);
    }
}
