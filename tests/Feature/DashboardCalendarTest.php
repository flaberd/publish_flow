<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\Post;
use App\Models\PostSocialAccount;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_shows_a_provider_icon_with_a_count_for_multiple_posts_same_day(): void
    {
        $user = User::factory()->create();
        $workspace = $user->workspaces()->create(['name' => 'W1']);
        $user->update(['current_workspace_id' => $workspace->id]);

        $account = $workspace->socialAccounts()->create([
            'provider' => SocialAccount::PROVIDER_INSTAGRAM,
            'provider_user_id' => '123',
            'username' => 'testig',
            'access_token' => 'x',
        ]);

        $today = now();

        foreach (range(1, 2) as $i) {
            $post = $workspace->posts()->create([
                'caption' => "Post {$i}",
                'media_disk' => 'public',
                'media_path' => "posts/{$i}.jpg",
                'media_type' => Post::MEDIA_TYPE_IMAGE,
                'scheduled_at' => $today,
                'status' => Post::STATUS_SCHEDULED,
            ]);

            $post->socialAccounts()->attach($account->id, [
                'settings' => [],
                'status' => PostSocialAccount::STATUS_PENDING,
            ]);
        }

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee('2');
    }
}
