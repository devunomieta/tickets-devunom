<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Admin;

use HiEvents\Http\Actions\BaseAction;
use HiEvents\Models\PlatformBranding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdatePlatformBrandingAction extends BaseAction
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->requireSuperAdmin();

        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:120'],
            'primary_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_mode' => ['required', 'in:dark,light'],
            'accent_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo_url' => ['nullable', 'url', 'max:500'],
            'dark_logo_url' => ['nullable', 'url', 'max:500'],
            'favicon_url' => ['nullable', 'url', 'max:500'],
        ]);

        $branding = PlatformBranding::query()->first() ?? new PlatformBranding();
        $branding->fill([
            'app_name' => trim($validated['app_name']),
            'primary_color' => $validated['primary_color'],
            'theme_mode' => $validated['theme_mode'],
            'accent_color' => $validated['accent_color'],
            'logo_url' => $validated['logo_url'] ?: null,
            'dark_logo_url' => $validated['dark_logo_url'] ?: null,
            'favicon_url' => $validated['favicon_url'] ?: null,
        ]);
        $branding->save();

        return $this->jsonResponse(['data' => $branding->toPublicArray()]);
    }
}
