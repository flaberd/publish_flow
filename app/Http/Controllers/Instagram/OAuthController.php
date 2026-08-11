<?php

namespace App\Http\Controllers\Instagram;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\Workspace;
use App\Services\Instagram\InstagramOAuthClient;
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

        $request->session()->put('instagram_oauth', [
            'state' => $state,
            'workspace_id' => $workspace->id,
        ]);

        return redirect()->away($this->client()->authorizationUrl($state));
    }

    public function callback(Request $request): RedirectResponse
    {
        $pending = $request->session()->pull('instagram_oauth');

        if ($request->query('error') || ! $request->query('code')) {
            return redirect()->route('accounts')->with('error', 'Instagram authorization was cancelled.');
        }

        if (! $pending || ! hash_equals($pending['state'], (string) $request->query('state'))) {
            return redirect()->route('accounts')->with('error', 'Instagram authorization could not be verified. Please try again.');
        }

        $workspace = Workspace::where('id', $pending['workspace_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $client = $this->client();

        $shortLived = $client->exchangeCodeForShortLivedToken((string) $request->query('code'));
        $longLived = $client->exchangeForLongLivedToken($shortLived['access_token']);
        $profile = $client->fetchProfile($longLived['access_token']);

        $workspace->socialAccounts()->updateOrCreate(
            [
                'provider' => SocialAccount::PROVIDER_INSTAGRAM,
                'provider_user_id' => $profile['user_id'],
            ],
            [
                'username' => $profile['username'] ?? null,
                'name' => $profile['name'] ?? null,
                'avatar_url' => $profile['profile_picture_url'] ?? null,
                'access_token' => $longLived['access_token'],
                'token_expires_at' => now()->addSeconds($longLived['expires_in']),
            ],
        );

        return redirect()->route('accounts')->with('status', 'Instagram account connected.');
    }

    private function client(): InstagramOAuthClient
    {
        return new InstagramOAuthClient(
            (string) config('services.instagram.client_id'),
            (string) config('services.instagram.client_secret'),
            route('instagram.callback'),
            (string) config('services.instagram.graph_version'),
        );
    }
}
