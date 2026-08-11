<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable(['post_id', 'social_account_id', 'settings', 'status', 'remote_id', 'published_at', 'error'])]
class PostSocialAccount extends Pivot
{
    const STATUS_PENDING = 'pending';

    const STATUS_PUBLISHED = 'published';

    const STATUS_FAILED = 'failed';

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }
}
