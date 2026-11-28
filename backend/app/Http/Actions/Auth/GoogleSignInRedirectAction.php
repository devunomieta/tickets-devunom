<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Auth;

use HiEvents\Http\Actions\BaseAction;
use Illuminate\Http\RedirectResponse;

class GoogleSignInRedirectAction extends BaseAction
{
    public function __invoke(): RedirectResponse
    {
        $clientId = (string) config('services.google.client_id', '');
        $clientSecret = (string) config('services.google.client_secret', '');
        $frontendUrl = rtrim((string) config('app.frontend_url', env('APP_FRONTEND_URL', '')), '/');

        if ($clientId === '' || $clientSecret === '') {
            return redirect()->away("{$frontendUrl}/auth/login?google_error=not_configured");
        }

        $backendUrl = rtrim((string) config('app.url', env('APP_URL', '')), '/');
        $redirectUri = "{$backendUrl}/public/auth/google/callback";

        // Stateless signed state token (timestamp + HMAC)
        $ts = (string) time();
        $sig = hash_hmac('sha256', $ts, (string) config('app.key'));
        $state = base64_encode("{$ts}.{$sig}");

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]);

        return redirect()->away("https://accounts.google.com/o/oauth2/v2/auth?{$query}");
    }
}
