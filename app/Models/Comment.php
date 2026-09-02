<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class Comment extends Model
{
    protected $fillable = ['user_id', 'body'];

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mentions(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'comment_mentions');
    }

    public function editableBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->isAdmin()
            || ($this->user_id === $user->id && $this->created_at->gt(now()->subMinutes(15)));
    }

    public function deletableBy(?User $user): bool
    {
        return $user && ($user->isAdmin() || $this->user_id === $user->id);
    }

    /**
     * Render the markdown body to safe HTML, linking @mentions.
     */
    public function renderedBody(): HtmlString
    {
        $html = Str::markdown($this->body, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        foreach ($this->mentions as $user) {
            $handle = Str::slug($user->name, '.');
            $html = str_replace(
                ['@'.$user->email, '@'.$handle],
                '<span class="mention">@'.e($user->name).'</span>',
                $html,
            );
        }

        return new HtmlString($html);
    }
}
