<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MentionController extends Controller
{
    /** Typeahead for the @mention autocomplete. */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));

        $users = User::query()
            ->when($q !== '', fn ($query) => $query
                ->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(8)
            ->get(['name', 'email']);

        return $users->map(fn (User $u) => [
            'name' => $u->name,
            'email' => $u->email,
            'handle' => Str::slug($u->name, '.'),
        ]);
    }
}
