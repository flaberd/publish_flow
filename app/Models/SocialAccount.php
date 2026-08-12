<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['provider', 'provider_user_id', 'username', 'name', 'avatar_url', 'access_token', 'token_expires_at'])]
#[Hidden(['access_token'])]
class SocialAccount extends Model
{
    const PROVIDER_INSTAGRAM = 'instagram';

    const PROVIDER_TIKTOK = 'tiktok';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'token_expires_at' => 'datetime',
        ];
    }

    public function providerLabel(): string
    {
        return match ($this->provider) {
            self::PROVIDER_TIKTOK => 'TikTok',
            default => ucfirst($this->provider),
        };
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_social_accounts')
            ->using(PostSocialAccount::class)
            ->withPivot(['settings', 'status', 'remote_id', 'published_at', 'error'])
            ->withTimestamps();
    }
}
