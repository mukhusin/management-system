<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->paginate(30),
        ]);
    }

    public function read(Request $request, string $id)
    {
        $request->user()->notifications()->where('id', $id)->update(['read_at' => now()]);

        return back();
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'All notifications marked read.');
    }
}
