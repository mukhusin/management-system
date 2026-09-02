<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesSubject;
use App\Models\Attachment;
use Illuminate\Http\Request;

class AttachmentController extends Controller
{
    use ResolvesSubject;

    public function store(Request $request, string $type, int|string $id)
    {
        $request->validate([
            'file' => [
                'required', 'file',
                'max:'.config('attachments.max_kb'),
                'mimes:'.implode(',', config('attachments.extensions')),
            ],
        ]);

        $subject = $this->resolveSubject($type, $id);
        $subject->attach($request->file('file'), $request->user());

        return back()->with('status', 'File attached.');
    }

    public function download(Attachment $attachment)
    {
        return $attachment->download();
    }

    public function destroy(Request $request, Attachment $attachment)
    {
        abort_unless($attachment->deletableBy($request->user()), 403);

        $attachment->delete();

        return back()->with('status', 'Attachment removed.');
    }
}
