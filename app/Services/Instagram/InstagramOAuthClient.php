<?php

namespace App\Services\Instagram;

use Illuminate\Support\Facades\Http;

class InstagramOAuthClient
{
    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $redirectUri,
        private readonly string $graphVersion,
    ) {}

    public function authorizationUrl(string $state): string
    {
        return 'https://www.instagram.com/oauth/authorize?'.http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'instagram_business_basic,instagram_business_content_publish',
            'state' => $state,
        ]);
    }

    /**
     * @return array{access_token: string, user_id: string}
     */
    public function exchangeCodeForShortLivedToken(string $code): array
    {
        return Http::asForm()->post('https://api.instagram.com/oauth/access_token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
        ])->throw()->json();
    }

    /**
     * @return array{access_token: string, token_type: string, expires_in: int}
     */
    public function exchangeForLongLivedToken(string $shortLivedToken): array
    {
        return Http::get('https://graph.instagram.com/access_token', [
            'grant_type' => 'ig_exchange_token',
            'client_secret' => $this->clientSecret,
            'access_token' => $shortLivedToken,
        ])->throw()->json();
    }

    /**
     * @return array{user_id: string, username?: string, name?: string, account_type?: string, profile_picture_url?: string}
     */
    public function fetchProfile(string $accessToken): array
    {
        return Http::get("https://graph.instagram.com/{$this->graphVersion}/me", [
            'fields' => 'user_id,username,name,account_type,profile_picture_url',
            'access_token' => $accessToken,
        ])->throw()->json();
    }
}
