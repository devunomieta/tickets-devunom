<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Admin;

use HiEvents\Http\Actions\BaseAction;
use HiEvents\Models\PlatformBranding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class UploadPlatformBrandingAssetAction extends BaseAction
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->requireSuperAdmin();

        $validated = $request->validate([
            'type' => ['required', 'in:logo,dark_logo,favicon'],
            'file' => ['required', 'file', 'max:2048', 'mimes:png,jpg,jpeg,svg,webp,ico'],
        ]);

        $type = $validated['type'];
        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        $filename = "branding/{$type}-" . time() . ".{$ext}";

        $diskName = (string) config('filesystems.public_disk', env('FILESYSTEM_PUBLIC_DISK', 's3-public'));
        $stored = Storage::disk($diskName)->put($filename, file_get_contents($file->getRealPath()), 'public');

        if (! $stored) {
            return $this->errorResponse('Failed to upload branding asset to storage.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $cdnUrl = rtrim((string) config('app.cdn_url', env('APP_CDN_URL', '')), '/');
        $publicUrl = $cdnUrl !== ''
            ? "{$cdnUrl}/{$filename}"
            : Storage::disk($diskName)->url($filename);

        $columnMap = [
            'logo' => 'logo_url',
            'dark_logo' => 'dark_logo_url',
            'favicon' => 'favicon_url',
        ];

        $branding = PlatformBranding::query()->first() ?? new PlatformBranding();
        $branding->{$columnMap[$type]} = $publicUrl;
        $branding->save();

        return $this->jsonResponse([
            'data' => [
                'type' => $type,
                'url' => $publicUrl,
                'disk' => $diskName,
                'branding' => $branding->toPublicArray(),
            ],
        ]);
    }
}
