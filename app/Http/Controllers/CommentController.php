<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesSubject;
use App\Models\Comment;
use App\Services\MentionParser;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CommentController extends Controller
{
    use ResolvesSubject;

    public function store(Request $request, string $type, int|string $id)
    {
        abort_unless($request->user()->can('tenders.comment'), 403);

        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        $subject = $this->resolveSubject($type, $id);
        $subject->addComment($request->user(), $data['body']);

        return back()->with('status', 'Comment posted.');
    }

    /** Server-rendered markdown preview (same pipeline as a saved comment). */
    public function preview(Request $request)
    {
        $body = (string) $request->input('body', '');

        $html = Str::markdown($body, ['html_input' => 'strip', 'allow_unsafe_links' => false]);

        foreach (app(MentionParser::class)->extract($body) as $user) {
            $handle = Str::slug($user->name, '.');
            $html = str_replace(
                ['@'.$user->email, '@'.$handle],
                '<span class="mention">@'.e($user->name).'</span>',
                $html,
            );
        }

        return response()->json(['html' => $html]);
    }

    public function destroy(Request $request, Comment $comment)
    {
        abort_unless($comment->deletableBy($request->user()), 403);

        $comment->delete();

        return back()->with('status', 'Comment deleted.');
    }
}
