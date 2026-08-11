<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable(['post_id', 'social_account_id', 'settings'])]
class PostSocialAccount extends Pivot
{
    protected function casts(): array
    {
        return [
            'settings' => 'array',
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
