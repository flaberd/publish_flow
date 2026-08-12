<?php

namespace Tests\Feature;

use App\Jobs\PublishPost;
use App\Models\Post;
use App\Models\PostSocialAccount;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Instagram\InstagramPublishClient;
use App\Services\TikTok\TikTokPublishClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TikTokPublishPostJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_publishes_to_tiktok_and_marks_everything_published(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), 'status/fetch')) {
                return Http::response(['data' => ['status' => 'PUBLISH_COMPLETE']]);
            }

            return Http::response(['data' => ['publish_id' => 'publish-1']]);
        });

        $post = $this->pendingPost();

        (new PublishPost($post))->handle(app(InstagramPublishClient::class), app(TikTokPublishClient::class));

        $this->assertSame(Post::STATUS_PUBLISHED, $post->fresh()->status);

        $pivot = $post->socialAccounts()->first()->pivot;
        $this->assertSame(PostSocialAccount::STATUS_PUBLISHED, $pivot->status);
        $this->assertSame('publish-1', $pivot->remote_id);
        $this->assertNotNull($pivot->published_at);
    }

    public function test_it_marks_the_post_failed_when_tiktok_rejects_it(): void
    {
        Http::fake([
            'open.tiktokapis.com/*' => Http::response(['error' => ['code' => 'invalid_param', 'message' => 'Invalid token']], 400),
        ]);

        $post = $this->pendingPost();

        (new PublishPost($post))->handle(app(InstagramPublishClient::class), app(TikTokPublishClient::class));

        $this->assertSame(Post::STATUS_FAILED, $post->fresh()->status);

        $pivot = $post->socialAccounts()->first()->pivot;
        $this->assertSame(PostSocialAccount::STATUS_FAILED, $pivot->status);
        $this->assertNotNull($pivot->error);
    }

    public function test_it_surfaces_tiktoks_detailed_error_when_processing_fails(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), 'status/fetch')) {
                return Http::response([
                    'data' => [
                        'status' => 'FAILED',
                        'fail_reason' => 'Video could not be downloaded from the provided URL.',
                    ],
                ]);
            }

            return Http::response(['data' => ['publish_id' => 'publish-1']]);
        });

        $post = $this->pendingPost();

        (new PublishPost($post))->handle(app(InstagramPublishClient::class), app(TikTokPublishClient::class));

        $pivot = $post->socialAccounts()->first()->pivot;
        $this->assertSame(PostSocialAccount::STATUS_FAILED, $pivot->status);
        $this->assertSame(
            'TikTok failed to process the uploaded media: Video could not be downloaded from the provided URL.',
            $pivot->error,
        );
    }

    public function test_it_sends_a_photo_publish_request_for_image_posts(): void
    {
        $capturedUrl = null;

        Http::fake(function ($request) use (&$capturedUrl) {
            if (str_contains($request->url(), 'status/fetch')) {
                return Http::response(['data' => ['status' => 'PUBLISH_COMPLETE']]);
            }

            $capturedUrl = $request->url();

            return Http::response(['data' => ['publish_id' => 'publish-1']]);
        });

        $post = $this->pendingPost(mediaType: Post::MEDIA_TYPE_IMAGE);

        (new PublishPost($post))->handle(app(InstagramPublishClient::class), app(TikTokPublishClient::class));

        $this->assertStringContainsString('/post/publish/content/init/', $capturedUrl);
    }

    private function pendingPost(
        string $mediaDisk = 'public',
        string $mediaPath = 'posts/video.mp4',
        string $mediaType = Post::MEDIA_TYPE_VIDEO,
        array $settings = [],
    ): Post {
        $user = User::factory()->create();
        $workspace = $user->workspaces()->create(['name' => 'W1']);

        $account = $workspace->socialAccounts()->create([
            'provider' => SocialAccount::PROVIDER_TIKTOK,
            'provider_user_id' => 'open-id-123',
            'username' => 'testtok',
            'access_token' => 'token',
        ]);

        $post = $workspace->posts()->create([
            'caption' => 'Hi',
            'media_disk' => $mediaDisk,
            'media_path' => $mediaPath,
            'media_type' => $mediaType,
            'scheduled_at' => now(),
            'status' => Post::STATUS_SCHEDULED,
        ]);

        $post->socialAccounts()->attach($account->id, [
            'settings' => $settings,
            'status' => PostSocialAccount::STATUS_PENDING,
        ]);

        return $post;
    }
}
