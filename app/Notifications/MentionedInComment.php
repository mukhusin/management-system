<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MentionedInComment extends Notification
{
    use Queueable;

    public function __construct(public Comment $comment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $subject = $this->comment->commentable;

        return [
            'comment_id' => $this->comment->id,
            'by' => $this->comment->user->name,
            'excerpt' => str($this->comment->body)->limit(120)->value(),
            'subject_type' => class_basename($subject),
            'subject_id' => $subject?->getKey(),
            'subject_label' => $subject->title ?? $subject->name ?? ('#'.$subject?->getKey()),
            'url' => method_exists($subject, 'commentUrl') ? $subject->commentUrl() : null,
        ];
    }
}
