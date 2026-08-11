<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['caption', 'media_disk', 'media_path', 'media_type', 'scheduled_at', 'status'])]
class Post extends Model
{
    const STATUS_SCHEDULED = 'scheduled';

    const STATUS_PUBLISHED = 'published';

    const STATUS_FAILED = 'failed';

    const MEDIA_TYPE_IMAGE = 'image';

    const MEDIA_TYPE_VIDEO = 'video';

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function socialAccounts(): BelongsToMany
    {
        return $this->belongsToMany(SocialAccount::class, 'post_social_accounts')
            ->using(PostSocialAccount::class)
            ->withPivot(['settings', 'status', 'remote_id', 'published_at', 'error'])
            ->withTimestamps();
    }
}
