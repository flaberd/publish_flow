<?php

namespace Tests\Feature;

use App\Jobs\PublishPost;
use App\Models\Post;
use App\Models\PostSocialAccount;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Instagram\InstagramPublishClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PublishPostJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_publishes_to_instagram_and_marks_everything_published(): void
    {
        Http::fake(function ($request) {
            if ($request->method() === 'GET') {
                return Http::response(['status_code' => 'FINISHED']);
            }

            if (str_contains($request->url(), 'media_publish')) {
                return Http::response(['id' => 'remote-1']);
            }

            return Http::response(['id' => 'container-1']);
        });

        $post = $this->pendingPost();

        (new PublishPost($post))->handle(app(InstagramPublishClient::class));

        $this->assertSame(Post::STATUS_PUBLISHED, $post->fresh()->status);

        $pivot = $post->socialAccounts()->first()->pivot;
        $this->assertSame(PostSocialAccount::STATUS_PUBLISHED, $pivot->status);
        $this->assertSame('remote-1', $pivot->remote_id);
        $this->assertNotNull($pivot->published_at);
    }

    public function test_it_marks_the_post_failed_when_instagram_rejects_it(): void
    {
        Http::fake([
            'graph.instagram.com/*' => Http::response(['error' => ['message' => 'Invalid token']], 400),
        ]);

        $post = $this->pendingPost();

        (new PublishPost($post))->handle(app(InstagramPublishClient::class));

        $this->assertSame(Post::STATUS_FAILED, $post->fresh()->status);

        $pivot = $post->socialAccounts()->first()->pivot;
        $this->assertSame(PostSocialAccount::STATUS_FAILED, $pivot->status);
        $this->assertNotNull($pivot->error);
    }

    public function test_it_surfaces_instagrams_detailed_error_when_processing_fails(): void
    {
        Http::fake(function ($request) {
            if ($request->method() === 'GET') {
                return Http::response([
                    'status_code' => 'ERROR',
                    'status' => 'Video could not be downloaded from the provided URL.',
                ]);
            }

            return Http::response(['id' => 'container-1']);
        });

        $post = $this->pendingPost();

        (new PublishPost($post))->handle(app(InstagramPublishClient::class));

        $pivot = $post->socialAccounts()->first()->pivot;
        $this->assertSame(PostSocialAccount::STATUS_FAILED, $pivot->status);
        $this->assertSame(
            'Instagram failed to process the uploaded media: Video could not be downloaded from the provided URL.',
            $pivot->error,
        );
    }

    private function pendingPost(): Post
    {
        $user = User::factory()->create();
        $workspace = $user->workspaces()->create(['name' => 'W1']);

        $account = $workspace->socialAccounts()->create([
            'provider' => SocialAccount::PROVIDER_INSTAGRAM,
            'provider_user_id' => '123',
            'username' => 'testig',
            'access_token' => 'token',
        ]);

        $post = $workspace->posts()->create([
            'caption' => 'Hi',
            'media_disk' => 'public',
            'media_path' => 'posts/photo.jpg',
            'media_type' => Post::MEDIA_TYPE_IMAGE,
            'scheduled_at' => now(),
            'status' => Post::STATUS_SCHEDULED,
        ]);

        $post->socialAccounts()->attach($account->id, [
            'settings' => [],
            'status' => PostSocialAccount::STATUS_PENDING,
        ]);

        return $post;
    }
}
