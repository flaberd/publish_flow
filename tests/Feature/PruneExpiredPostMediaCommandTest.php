<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PruneExpiredPostMediaCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_media_for_posts_published_more_than_two_days_ago(): void
    {
        Storage::fake('public');

        $post = $this->publishedPost(publishedDaysAgo: 3);

        $this->artisan('posts:prune-media')->assertSuccessful();

        Storage::disk('public')->assertMissing($post->media_path);
        $this->assertNotNull($post->fresh()->media_deleted_at);
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_it_leaves_recently_published_media_alone(): void
    {
        Storage::fake('public');

        $post = $this->publishedPost(publishedDaysAgo: 1);

        $this->artisan('posts:prune-media')->assertSuccessful();

        Storage::disk('public')->assertExists($post->media_path);
        $this->assertNull($post->fresh()->media_deleted_at);
    }

    public function test_it_leaves_unpublished_media_alone(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $workspace = $user->workspaces()->create(['name' => 'W1']);

        $file = UploadedFile::fake()->image('photo.jpg');
        $path = $file->store('posts', 'public');

        $post = $workspace->posts()->create([
            'caption' => 'Still scheduled',
            'media_disk' => 'public',
            'media_path' => $path,
            'media_type' => Post::MEDIA_TYPE_IMAGE,
            'scheduled_at' => now()->addDay(),
            'status' => Post::STATUS_SCHEDULED,
        ]);

        $this->artisan('posts:prune-media')->assertSuccessful();

        Storage::disk('public')->assertExists($path);
        $this->assertNull($post->fresh()->media_deleted_at);
    }

    private function publishedPost(int $publishedDaysAgo): Post
    {
        $user = User::factory()->create();
        $workspace = $user->workspaces()->create(['name' => 'W1']);

        $file = UploadedFile::fake()->image('photo.jpg');
        $path = $file->store('posts', 'public');

        return $workspace->posts()->create([
            'caption' => 'Published',
            'media_disk' => 'public',
            'media_path' => $path,
            'media_type' => Post::MEDIA_TYPE_IMAGE,
            'scheduled_at' => now()->subDays($publishedDaysAgo),
            'published_at' => now()->subDays($publishedDaysAgo),
            'status' => Post::STATUS_PUBLISHED,
        ]);
    }
}
