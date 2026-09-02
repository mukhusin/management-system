<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesSubject;
use App\Models\Comment;
use Illuminate\Http\Request;

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

    public function destroy(Request $request, Comment $comment)
    {
        abort_unless($comment->deletableBy($request->user()), 403);

        $comment->delete();

        return back()->with('status', 'Comment deleted.');
    }
}
