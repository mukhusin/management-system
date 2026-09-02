<?php

namespace App\Models\Concerns;

use App\Models\Comment;
use App\Models\User;
use App\Notifications\MentionedInComment;
use App\Services\MentionParser;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

trait HasComments
{
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')->latest('id');
    }

    /**
     * Post a markdown comment, resolve @mentions, notify the mentioned users,
     * and (if the model is auditable) record a comment_added audit entry.
     */
    public function addComment(User $author, string $body): Comment
    {
        return DB::transaction(function () use ($author, $body) {
            /** @var Comment $comment */
            $comment = $this->comments()->create([
                'user_id' => $author->id,
                'body' => $body,
            ]);

            $mentioned = app(MentionParser::class)->extract($body)
                ->reject(fn (User $u) => $u->id === $author->id);

            if ($mentioned->isNotEmpty()) {
                $comment->mentions()->sync($mentioned->pluck('id'));
                foreach ($mentioned as $user) {
                    $user->notify(new MentionedInComment($comment));
                }
            }

            if (method_exists($this, 'audit')) {
                $this->audit('comment_added', null, ['comment_id' => $comment->id]);
            }

            return $comment;
        });
    }
}
