<?php

namespace App\Services\TikTok;

use App\Models\Post;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TikTokPublishClient
{
    public function publish(SocialAccount $account, Post $post, array $settings): string
    {
        $publishId = $this->initPublish($account, $post, $settings);

        return $this->waitUntilPublished($account, $publishId);
    }

    private function initPublish(SocialAccount $account, Post $post, array $settings): string
    {
        $privacyLevel = $settings['privacy_level'] ?? 'SELF_ONLY';

        $payload = [
            'post_info' => [
                'title' => (string) $post->caption,
                'privacy_level' => $privacyLevel,
                'disable_duet' => false,
                'disable_comment' => false,
                'disable_stitch' => false,
            ],
            'source_info' => [
                'source' => 'PULL_FROM_URL',
            ],
        ];

        if ($post->media_type === Post::MEDIA_TYPE_IMAGE) {
            $endpoint = 'https://open.tiktokapis.com/v2/post/publish/content/init/';

            $payload['post_mode'] = 'DIRECT_POST';
            $payload['media_type'] = 'PHOTO';
            $payload['post_info']['description'] = (string) $post->caption;
            $payload['source_info']['photo_images'] = [$this->mediaUrl($post)];
            $payload['source_info']['photo_cover_index'] = 0;
        } else {
            $endpoint = 'https://open.tiktokapis.com/v2/post/publish/video/init/';

            $payload['source_info']['video_url'] = $this->mediaUrl($post);
        }

        $response = Http::withToken($account->access_token)
            ->post($endpoint, $payload)
            ->throw()
            ->json();

        $errorCode = $response['error']['code'] ?? 'ok';

        if ($errorCode !== 'ok') {
            $message = $response['error']['message'] ?? 'TikTok did not provide further detail.';

            throw new RuntimeException("TikTok rejected the post: {$message}");
        }

        return $response['data']['publish_id'];
    }

    /**
     * TikTok processes pulled media asynchronously before it publishes. Poll a
     * bounded number of times rather than indefinitely, so a stuck publish
     * fails the job instead of blocking a queue worker forever.
     */
    private function waitUntilPublished(SocialAccount $account, string $publishId): string
    {
        for ($attempt = 0; $attempt < 15; $attempt++) {
            $response = Http::withToken($account->access_token)
                ->post('https://open.tiktokapis.com/v2/post/publish/status/fetch/', [
                    'publish_id' => $publishId,
                ])
                ->throw()
                ->json();

            $status = $response['data']['status'] ?? null;

            if ($status === 'PUBLISH_COMPLETE') {
                return $publishId;
            }

            if ($status === 'FAILED') {
                $reason = $response['data']['fail_reason'] ?? 'TikTok did not provide further detail.';

                throw new RuntimeException("TikTok failed to process the uploaded media: {$reason}");
            }

            sleep(2);
        }

        throw new RuntimeException('Timed out waiting for TikTok to finish processing the media.');
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
