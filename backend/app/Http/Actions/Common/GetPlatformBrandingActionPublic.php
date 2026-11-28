<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Common;

use HiEvents\Http\Actions\BaseAction;
use HiEvents\Models\PlatformBranding;
use Illuminate\Http\JsonResponse;
use Throwable;

class GetPlatformBrandingActionPublic extends BaseAction
{
    public function __invoke(): JsonResponse
    {
        try {
            $branding = PlatformBranding::current()->toPublicArray();
        } catch (Throwable) {
            $branding = [
                'app_name' => config('app.name', 'CortDevs Tickets'),
                'primary_color' => '#cd58dd',
                'logo_url' => null,
                'dark_logo_url' => null,
                'favicon_url' => null,
            ];
        }

        return $this->jsonResponse(['data' => $branding]);
    }
}
