<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Auth;

use HiEvents\Models\AccountUser;
use HiEvents\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GoogleSignInCallbackAction extends BaseAuthAction
{
    public function __invoke(Request $request): RedirectResponse
    {
        $frontendUrl = rtrim((string) config('app.frontend_url', env('APP_FRONTEND_URL', '')), '/');
        $backendUrl = rtrim((string) config('app.url', env('APP_URL', '')), '/');
        $redirectUri = "{$backendUrl}/public/auth/google/callback";

        if ($request->filled('error')) {
            return redirect()->away("{$frontendUrl}/auth/login?google_error=cancelled");
        }

        $code = (string) $request->query('code', '');
        $state = (string) $request->query('state', '');

        if ($code === '' || ! $this->isValidState($state)) {
            return redirect()->away("{$frontendUrl}/auth/login?google_error=invalid_state");
        }

        $tokenResponse = Http::asForm()
            ->timeout(15)
            ->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => (string) config('services.google.client_id'),
                'client_secret' => (string) config('services.google.client_secret'),
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]);

        if (! $tokenResponse->successful() || empty($tokenResponse->json('access_token'))) {
            return redirect()->away("{$frontendUrl}/auth/login?google_error=token_failed");
        }

        $userInfoResponse = Http::withToken((string) $tokenResponse->json('access_token'))
            ->timeout(15)
            ->get('https://openidconnect.googleapis.com/v1/userinfo');

        if (! $userInfoResponse->successful()) {
            return redirect()->away("{$frontendUrl}/auth/login?google_error=userinfo_failed");
        }

        $email = strtolower(trim((string) $userInfoResponse->json('email', '')));
        $emailVerified = (bool) $userInfoResponse->json('email_verified', false);

        if ($email === '' || ! $emailVerified) {
            return redirect()->away("{$frontendUrl}/auth/login?google_error=unverified_email");
        }

        // STRICT SIGN-IN ONLY: Never auto-create an account
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if (! $user) {
            return redirect()->away("{$frontendUrl}/auth/login?google_error=no_account");
        }

        $accountUser = AccountUser::query()
            ->where('user_id', $user->id)
            ->where('status', 'ACTIVE')
            ->first()
            ?? AccountUser::query()->where('user_id', $user->id)->first();

        if (! $accountUser) {
            return redirect()->away("{$frontendUrl}/auth/login?google_error=no_account");
        }

        /** @var string $jwt */
        $jwt = auth()->claims(['account_id' => $accountUser->account_id])->login($user);

        return redirect()
            ->away("{$frontendUrl}/manage/events")
            ->withCookie($this->getAuthCookie($jwt));
    }

    private function isValidState(string $state): bool
    {
        $decoded = base64_decode($state, true);
        if (! $decoded || ! str_contains($decoded, '.')) {
            return false;
        }

        [$ts, $sig] = explode('.', $decoded, 2);
        if (! ctype_digit($ts) || abs(time() - (int) $ts) > 600) {
            return false;
        }

        $expected = hash_hmac('sha256', $ts, (string) config('app.key'));
        return hash_equals($expected, $sig);
    }
}
