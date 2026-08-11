<?php

namespace App\Livewire;

use App\Jobs\PublishPost;
use App\Models\Post;
use App\Models\PostSocialAccount;
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

    public string $scheduledHour = '';

    public string $scheduledMinute = '';

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
        $this->scheduledHour = (string) now()->hour;
        $this->scheduledMinute = (string) now()->minute;
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
            'scheduledHour' => ['required', 'integer', 'between:0,23'],
            'scheduledMinute' => ['required', 'integer', 'between:0,59'],
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
        $isFuture = $scheduledAt->greaterThan(now());

        $post = $workspace->posts()->create([
            'caption' => $this->caption,
            'media_disk' => $disk,
            'media_path' => $path,
            'media_type' => $isVideo ? Post::MEDIA_TYPE_VIDEO : Post::MEDIA_TYPE_IMAGE,
            'scheduled_at' => $scheduledAt,
            'status' => Post::STATUS_SCHEDULED,
        ]);

        foreach ($accounts as $account) {
            $post->socialAccounts()->attach($account->id, [
                'settings' => $this->settings[$account->id] ?? [],
                'status' => PostSocialAccount::STATUS_PENDING,
            ]);
        }

        PublishPost::dispatch($post)->delay($scheduledAt);

        $this->showModal = false;

        $this->dispatch('post-published');

        session()->flash('status', $isFuture ? 'Post scheduled.' : 'Post is publishing now.');
    }

    public function isInstagram(SocialAccount $account): bool
    {
        return $account->provider === SocialAccount::PROVIDER_INSTAGRAM;
    }

    private function scheduledAt(): Carbon
    {
        $date = $this->scheduledDate ?: now()->format('Y-m-d');
        $hour = str_pad($this->scheduledHour !== '' ? $this->scheduledHour : now()->format('H'), 2, '0', STR_PAD_LEFT);
        $minute = str_pad($this->scheduledMinute !== '' ? $this->scheduledMinute : now()->format('i'), 2, '0', STR_PAD_LEFT);

        try {
            return Carbon::createFromFormat('Y-m-d H:i', "{$date} {$hour}:{$minute}");
        } catch (\Exception) {
            return now();
        }
    }

    private function user(): User
    {
        return Auth::user();
    }
}
