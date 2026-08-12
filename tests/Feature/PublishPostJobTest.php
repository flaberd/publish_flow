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
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function test_the_video_url_sent_to_instagram_is_actually_fetchable(): void
    {
        // Deliberately not Storage::fake('videos') here: the fake disk stubs
        // temporaryUrl() with a plain, unsigned URL, which would make this
        // test pass regardless of whether the real signed route actually
        // works. Writing to the real local disk and hitting the real route
        // is the whole point of this test.
        Storage::disk('videos')->put('posts/test.mp4', 'fake-video-bytes');

        $capturedVideoUrl = null;

        Http::fake(function ($request) use (&$capturedVideoUrl) {
            if ($request->method() === 'GET') {
                return Http::response(['status_code' => 'FINISHED']);
            }

            if (str_contains($request->url(), 'media_publish')) {
                return Http::response(['id' => 'remote-1']);
            }

            $capturedVideoUrl = $request['video_url'];

            return Http::response(['id' => 'container-1']);
        });

        $post = $this->pendingPost(
            mediaDisk: 'videos',
            mediaPath: 'posts/test.mp4',
            mediaType: Post::MEDIA_TYPE_VIDEO,
        );

        try {
            (new PublishPost($post))->handle(app(InstagramPublishClient::class));

            $this->assertNotNull($capturedVideoUrl, 'video_url was never sent to Instagram.');

            $this->get($capturedVideoUrl)->assertOk();
        } finally {
            Storage::disk('videos')->delete('posts/test.mp4');
        }
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function postTypeProvider(): array
    {
        return [
            'feed defaults to a plain video post' => ['feed', 'VIDEO'],
            'no setting at all also defaults to a plain video post' => ['', 'VIDEO'],
            'reel maps to REELS' => ['reel', 'REELS'],
            'story maps to STORIES' => ['story', 'STORIES'],
        ];
    }

    #[DataProvider('postTypeProvider')]
    public function test_it_sends_the_media_type_matching_the_chosen_post_type(string $postType, string $expectedMediaType): void
    {
        $capturedMediaType = null;

        Http::fake(function ($request) use (&$capturedMediaType) {
            if ($request->method() === 'GET') {
                return Http::response(['status_code' => 'FINISHED']);
            }

            if (str_contains($request->url(), 'media_publish')) {
                return Http::response(['id' => 'remote-1']);
            }

            $capturedMediaType = $request['media_type'];

            return Http::response(['id' => 'container-1']);
        });

        $post = $this->pendingPost(
            mediaType: Post::MEDIA_TYPE_VIDEO,
            settings: $postType === '' ? [] : ['post_type' => $postType],
        );

        (new PublishPost($post))->handle(app(InstagramPublishClient::class));

        $this->assertSame($expectedMediaType, $capturedMediaType);
    }

    private function pendingPost(
        string $mediaDisk = 'public',
        string $mediaPath = 'posts/photo.jpg',
        string $mediaType = Post::MEDIA_TYPE_IMAGE,
        array $settings = [],
    ): Post {
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
