<?php

namespace App\Services\TikTok;

use Illuminate\Support\Facades\Http;

class TikTokOAuthClient
{
    public function __construct(
        private readonly string $clientKey,
        private readonly string $clientSecret,
        private readonly string $redirectUri,
    ) {}

    public function authorizationUrl(string $state, string $codeChallenge): string
    {
        return 'https://www.tiktok.com/v2/auth/authorize/?'.http_build_query([
            'client_key' => $this->clientKey,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'user.info.basic,video.publish,video.upload',
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);
    }

    /**
     * @return array{access_token: string, expires_in: int, open_id: string, refresh_token: string, refresh_expires_in: int, scope: string, token_type: string}
     */
    public function exchangeCodeForToken(string $code, string $codeVerifier): array
    {
        return Http::asForm()->post('https://open.tiktokapis.com/v2/oauth/token/', [
            'client_key' => $this->clientKey,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
            'code_verifier' => $codeVerifier,
        ])->throw()->json();
    }

    /**
     * @return array{open_id?: string, username?: string, display_name?: string, avatar_url?: string}
     */
    public function fetchProfile(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get('https://open.tiktokapis.com/v2/user/info/', [
                'fields' => 'open_id,username,display_name,avatar_url',
            ])
            ->throw()
            ->json();

        return $response['data']['user'] ?? [];
    }
}
