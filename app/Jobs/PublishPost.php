<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\PostSocialAccount;
use App\Models\SocialAccount;
use App\Services\Instagram\InstagramPublishClient;
use App\Services\TikTok\TikTokPublishClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class PublishPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly Post $post,
    ) {}

    public function handle(InstagramPublishClient $instagram, TikTokPublishClient $tiktok): void
    {
        $pendingAccounts = $this->post->socialAccounts()
            ->wherePivot('status', PostSocialAccount::STATUS_PENDING)
            ->get();

        foreach ($pendingAccounts as $account) {
            try {
                $remoteId = match ($account->provider) {
                    SocialAccount::PROVIDER_INSTAGRAM => $instagram->publish($account, $this->post, $account->pivot->settings ?? []),
                    SocialAccount::PROVIDER_TIKTOK => $tiktok->publish($account, $this->post, $account->pivot->settings ?? []),
                    default => throw new \RuntimeException("Publishing to [{$account->provider}] is not supported."),
                };

                $account->posts()->updateExistingPivot($this->post->id, [
                    'status' => PostSocialAccount::STATUS_PUBLISHED,
                    'remote_id' => $remoteId,
                    'published_at' => now(),
                    'error' => null,
                ]);
            } catch (Throwable $exception) {
                $account->posts()->updateExistingPivot($this->post->id, [
                    'status' => PostSocialAccount::STATUS_FAILED,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $statuses = $this->post->socialAccounts()->pluck('post_social_accounts.status');
        $published = $statuses->contains(PostSocialAccount::STATUS_PUBLISHED);

        $this->post->update([
            'status' => $published ? Post::STATUS_PUBLISHED : Post::STATUS_FAILED,
            'published_at' => $published ? now() : null,
        ]);
    }
}
