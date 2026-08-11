<?php

namespace Tests\Feature;

use App\Livewire\PublishDialog;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

        $account = $workspace->socialAccounts()->create([
            'provider' => SocialAccount::PROVIDER_INSTAGRAM,
            'provider_user_id' => '123',
            'username' => 'testig',
            'access_token' => 'x',
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee('Publish');
    }

    public function test_publish_dialog_creates_immediate_post(): void
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

        Livewire::actingAs($user)
            ->test(PublishDialog::class)
            ->call('openModal')
            ->assertSet('enabledAccountIds', [$account->id])
            ->set('media', UploadedFile::fake()->image('photo.jpg'))
            ->set('caption', 'Hello world')
            ->set('scheduledDate', now()->format('Y-m-d'))
            ->set('scheduledTime', now()->format('H:i'))
            ->call('submit')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('posts', [
            'workspace_id' => $workspace->id,
            'caption' => 'Hello world',
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('post_social_accounts', [
            'social_account_id' => $account->id,
        ]);
    }

    public function test_publish_dialog_schedules_future_post(): void
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

        $future = now()->addDay();

        Livewire::actingAs($user)
            ->test(PublishDialog::class)
            ->call('openModal')
            ->set('media', UploadedFile::fake()->create('video.mp4', 500, 'video/mp4'))
            ->set('scheduledDate', $future->format('Y-m-d'))
            ->set('scheduledTime', $future->format('H:i'))
            ->call('submit');

        $this->assertDatabaseHas('posts', [
            'workspace_id' => $workspace->id,
            'status' => 'scheduled',
            'media_type' => 'video',
        ]);
    }
}
