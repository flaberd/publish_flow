<?php

namespace Tests\Feature;

use App\Jobs\PublishPost;
use App\Livewire\PublishDialog;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class PublishDialogTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_publish_dialog(): void
    {
        $user = User::factory()->create();
        $workspace = $user->workspaces()->create(['name' => 'W1']);
        $user->update(['current_workspace_id' => $workspace->id]);

        $workspace->socialAccounts()->create([
            'provider' => SocialAccount::PROVIDER_INSTAGRAM,
            'provider_user_id' => '123',
            'username' => 'testig',
            'access_token' => 'x',
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee('Publish');
    }

    public function test_publish_dialog_publishes_immediately(): void
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

        $user = User::factory()->create();
        $workspace = $user->workspaces()->create(['name' => 'W1']);
        $user->update(['current_workspace_id' => $workspace->id]);

        $account = $workspace->socialAccounts()->create([
            'provider' => SocialAccount::PROVIDER_INSTAGRAM,
            'provider_user_id' => '123',
            'username' => 'testig',
            'access_token' => 'x',
        ]);

        Livewire::actingAs($user)
            ->test(PublishDialog::class)
            ->call('openModal')
            ->assertSet('enabledAccountIds', [$account->id])
            ->set('media', UploadedFile::fake()->image('photo.jpg'))
            ->set('caption', 'Hello world')
            ->set('scheduledDate', now()->format('Y-m-d'))
            ->set('scheduledHour', (string) now()->hour)
            ->set('scheduledMinute', (string) now()->minute)
            ->call('submit')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('posts', [
            'workspace_id' => $workspace->id,
            'caption' => 'Hello world',
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('post_social_accounts', [
            'social_account_id' => $account->id,
            'status' => 'published',
            'remote_id' => 'remote-1',
        ]);
    }

    public function test_it_accepts_videos_larger_than_livewires_12mb_default(): void
    {
        $user = User::factory()->create();
        $workspace = $user->workspaces()->create(['name' => 'W1']);
        $user->update(['current_workspace_id' => $workspace->id]);

        $workspace->socialAccounts()->create([
            'provider' => SocialAccount::PROVIDER_INSTAGRAM,
            'provider_user_id' => '123',
            'username' => 'testig',
            'access_token' => 'x',
        ]);

        Livewire::actingAs($user)
            ->test(PublishDialog::class)
            ->call('openModal')
            ->set('media', UploadedFile::fake()->create('video.mp4', 20_000, 'video/mp4'))
            ->assertHasNoErrors('media');
    }

    public function test_publish_dialog_queues_a_delayed_job_for_a_future_post(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $workspace = $user->workspaces()->create(['name' => 'W1']);
        $user->update(['current_workspace_id' => $workspace->id]);

        $workspace->socialAccounts()->create([
            'provider' => SocialAccount::PROVIDER_INSTAGRAM,
            'provider_user_id' => '123',
            'username' => 'testig',
            'access_token' => 'x',
        ]);

        $future = now()->addDay();

        Livewire::actingAs($user)
            ->test(PublishDialog::class)
            ->call('openModal')
            ->set('media', UploadedFile::fake()->create('video.mp4', 500, 'video/mp4'))
            ->set('scheduledDate', $future->format('Y-m-d'))
            ->set('scheduledHour', (string) $future->hour)
            ->set('scheduledMinute', (string) $future->minute)
            ->call('submit');

        $this->assertDatabaseHas('posts', [
            'workspace_id' => $workspace->id,
            'status' => 'scheduled',
            'media_type' => 'video',
        ]);

        Queue::assertPushed(PublishPost::class);
    }
}
