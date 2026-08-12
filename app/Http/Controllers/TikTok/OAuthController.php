<?php

namespace App\Http\Controllers\TikTok;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\Workspace;
use App\Services\TikTok\TikTokOAuthClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;

        abort_if(! $workspace, 422, 'No active workspace.');

        $state = Str::random(40);
        $codeVerifier = Str::random(64);
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        $request->session()->put('tiktok_oauth', [
            'state' => $state,
            'code_verifier' => $codeVerifier,
            'workspace_id' => $workspace->id,
        ]);

        return redirect()->away($this->client()->authorizationUrl($state, $codeChallenge));
    }

    public function callback(Request $request): RedirectResponse
    {
        $pending = $request->session()->pull('tiktok_oauth');

        if ($request->query('error') || ! $request->query('code')) {
            return redirect()->route('accounts')->with('error', 'TikTok authorization was cancelled.');
        }

        if (! $pending || ! hash_equals($pending['state'], (string) $request->query('state'))) {
            return redirect()->route('accounts')->with('error', 'TikTok authorization could not be verified. Please try again.');
        }

        $workspace = Workspace::where('id', $pending['workspace_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $client = $this->client();

        $token = $client->exchangeCodeForToken((string) $request->query('code'), $pending['code_verifier']);
        $profile = $client->fetchProfile($token['access_token']);

        $workspace->socialAccounts()->updateOrCreate(
            [
                'provider' => SocialAccount::PROVIDER_TIKTOK,
                'provider_user_id' => $token['open_id'],
            ],
            [
                'username' => $profile['username'] ?? null,
                'name' => $profile['display_name'] ?? null,
                'avatar_url' => $profile['avatar_url'] ?? null,
                'access_token' => $token['access_token'],
                'token_expires_at' => now()->addSeconds($token['expires_in']),
            ],
        );

        return redirect()->route('accounts')->with('status', 'TikTok account connected.');
    }

    private function client(): TikTokOAuthClient
    {
        return new TikTokOAuthClient(
            (string) config('services.tiktok.client_key'),
            (string) config('services.tiktok.client_secret'),
            route('tiktok.callback'),
        );
    }
}
