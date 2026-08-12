<?php

namespace App\Services\Instagram;

use App\Models\Post;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class InstagramPublishClient
{
    public function __construct(
        private readonly string $graphVersion,
    ) {}

    public function publish(SocialAccount $account, Post $post, array $settings): string
    {
        $containerId = $this->createContainer($account, $post, $settings);

        $this->waitUntilReady($account, $containerId);

        return $this->publishContainer($account, $containerId);
    }

    private function createContainer(SocialAccount $account, Post $post, array $settings): string
    {
        $mediaType = $this->resolveMediaType($post, $settings);

        $payload = array_filter([
            'caption' => $post->caption,
            'media_type' => $mediaType,
            'access_token' => $account->access_token,
        ], fn ($value) => $value !== null && $value !== '');

        $payload[$mediaType === 'IMAGE' ? 'image_url' : 'video_url'] = $this->mediaUrl($post);

        $response = Http::asForm()
            ->post("https://graph.instagram.com/{$this->graphVersion}/{$account->provider_user_id}/media", $payload)
            ->throw()
            ->json();

        return $response['id'];
    }

    /**
     * Instagram processes uploaded media asynchronously before it can be published.
     * Poll a bounded number of times rather than indefinitely, so a stuck container
     * fails the job instead of blocking a queue worker forever.
     */
    private function waitUntilReady(SocialAccount $account, string $containerId): void
    {
        for ($attempt = 0; $attempt < 15; $attempt++) {
            $response = Http::get("https://graph.instagram.com/{$this->graphVersion}/{$containerId}", [
                'fields' => 'status_code,status',
                'access_token' => $account->access_token,
            ])->throw()->json();

            $status = $response['status_code'] ?? null;

            if ($status === 'FINISHED') {
                return;
            }

            if ($status === 'ERROR') {
                $detail = $response['status'] ?? 'Instagram did not provide further detail.';

                throw new RuntimeException("Instagram failed to process the uploaded media: {$detail}");
            }

            sleep(2);
        }

        throw new RuntimeException('Timed out waiting for Instagram to finish processing the media.');
    }

    private function publishContainer(SocialAccount $account, string $containerId): string
    {
        $response = Http::asForm()
            ->post("https://graph.instagram.com/{$this->graphVersion}/{$account->provider_user_id}/media_publish", [
                'creation_id' => $containerId,
                'access_token' => $account->access_token,
            ])
            ->throw()
            ->json();

        return $response['id'];
    }

    private function resolveMediaType(Post $post, array $settings): string
    {
        if ($post->media_type === Post::MEDIA_TYPE_IMAGE) {
            return 'IMAGE';
        }

        return match ($settings['post_type'] ?? 'feed') {
            'story' => 'STORIES',
            default => 'REELS',
        };
    }

    private function mediaUrl(Post $post): string
    {
        if ($post->media_disk === 'videos') {
            // Storage::disk(...)->temporaryUrl() (not a manual URL::temporarySignedRoute
            // call) is required here: the "serve" route this disk is exposed through
            // validates against a *relative* signature, and only temporaryUrl() builds
            // one — a directly-generated signed route defaults to signing the absolute
            // URL, which that relative check can never match, so every request 404s.
            return Storage::disk('videos')->temporaryUrl($post->media_path, now()->addHours(2));
        }

        return Storage::disk($post->media_disk)->url($post->media_path);
    }
}
