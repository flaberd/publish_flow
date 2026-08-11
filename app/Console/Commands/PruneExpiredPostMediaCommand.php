<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('posts:prune-media')]
#[Description('Delete the photo/video for posts published more than 2 days ago, keeping the post record')]
class PruneExpiredPostMediaCommand extends Command
{
    const RETENTION_DAYS = 2;

    public function handle(): int
    {
        Post::query()
            ->where('status', Post::STATUS_PUBLISHED)
            ->whereNull('media_deleted_at')
            ->where('published_at', '<=', now()->subDays(self::RETENTION_DAYS))
            ->each(function (Post $post) {
                Storage::disk($post->media_disk)->delete($post->media_path);

                $post->update(['media_deleted_at' => now()]);

                $this->info("Deleted media for post [{$post->id}].");
            });

        return self::SUCCESS;
    }
}
