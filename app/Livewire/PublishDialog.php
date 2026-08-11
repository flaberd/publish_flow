<?php

namespace App\Livewire;

use App\Models\Post;
use App\Models\SocialAccount;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class PublishDialog extends Component
{
    use WithFileUploads;

    public bool $showModal = false;

    /** @var int[] */
    public array $enabledAccountIds = [];

    public $media = null;

    public string $caption = '';

    public string $scheduledDate = '';

    public string $scheduledTime = '';

    /** @var array<int, array<string, mixed>> */
    public array $settings = [];

    public function with(): array
    {
        $accounts = $this->user()->currentWorkspace?->socialAccounts()->orderBy('provider')->get() ?? collect();

        return [
            'accounts' => $accounts,
            'publishLabel' => $this->scheduledAt()->greaterThan(now()) ? 'Schedule' : 'Publish',
        ];
    }

    public function openModal(): void
    {
        $accounts = $this->user()->currentWorkspace?->socialAccounts ?? collect();

        $this->reset(['media', 'caption', 'settings']);
        $this->enabledAccountIds = $accounts->pluck('id')->all();
        $this->scheduledDate = now()->format('Y-m-d');
        $this->scheduledTime = now()->format('H:i');
        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function removeMedia(): void
    {
        $this->reset('media');
    }

    public function toggleAccount(int $accountId): void
    {
        if (in_array($accountId, $this->enabledAccountIds, true)) {
            $this->enabledAccountIds = array_values(array_diff($this->enabledAccountIds, [$accountId]));
        } else {
            $this->enabledAccountIds[] = $accountId;
        }
    }

    public function submit(): void
    {
        $workspace = $this->user()->currentWorkspace;

        abort_if(! $workspace, 422, 'No active workspace.');

        $this->validate([
            'media' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,mp4,mov,webm', 'max:102400'],
            'caption' => ['nullable', 'string', 'max:2200'],
            'scheduledDate' => ['required', 'date'],
            'scheduledTime' => ['required', 'date_format:H:i'],
            'enabledAccountIds' => ['required', 'array', 'min:1'],
        ], attributes: [
            'media' => 'content',
            'enabledAccountIds' => 'social accounts',
        ]);

        $accounts = $workspace->socialAccounts()->whereIn('id', $this->enabledAccountIds)->get();

        $isVideo = str_starts_with((string) $this->media->getMimeType(), 'video/');
        $disk = $isVideo ? 'videos' : 'public';
        $path = $this->media->store('posts', $disk);

        $scheduledAt = $this->scheduledAt();

        $post = $workspace->posts()->create([
            'caption' => $this->caption,
            'media_disk' => $disk,
            'media_path' => $path,
            'media_type' => $isVideo ? Post::MEDIA_TYPE_VIDEO : Post::MEDIA_TYPE_IMAGE,
            'scheduled_at' => $scheduledAt,
            'status' => $scheduledAt->greaterThan(now()) ? Post::STATUS_SCHEDULED : Post::STATUS_PUBLISHED,
        ]);

        foreach ($accounts as $account) {
            $post->socialAccounts()->attach($account->id, [
                'settings' => $this->settings[$account->id] ?? [],
            ]);
        }

        $this->showModal = false;

        $this->dispatch('post-published');

        session()->flash('status', $scheduledAt->greaterThan(now()) ? 'Post scheduled.' : 'Post published.');
    }

    public function isInstagram(SocialAccount $account): bool
    {
        return $account->provider === SocialAccount::PROVIDER_INSTAGRAM;
    }

    private function scheduledAt(): Carbon
    {
        $date = $this->scheduledDate ?: now()->format('Y-m-d');
        $time = $this->scheduledTime ?: now()->format('H:i');

        try {
            return Carbon::createFromFormat('Y-m-d H:i', "{$date} {$time}");
        } catch (\Exception) {
            return now();
        }
    }

    private function user(): User
    {
        return Auth::user();
    }
}
