<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Resolves @mentions in a comment body. Users can be referenced by email
 * (@name@example.com) or by a dotted handle (@jane.doe for "Jane Doe").
 */
class MentionParser
{
    /**
     * @return Collection<int, User> the distinct users mentioned in $body
     */
    public function extract(string $body): Collection
    {
        preg_match_all('/(?<![\w.])@([\w.\-]+@[\w.\-]+\.\w+|[\w.\-]{2,})/', $body, $matches);

        $tokens = collect($matches[1])->map(fn ($t) => rtrim($t, '.'))->unique();

        if ($tokens->isEmpty()) {
            return collect();
        }

        $emails = $tokens->filter(fn ($t) => str_contains($t, '@'));
        $handles = $tokens->reject(fn ($t) => str_contains($t, '@'));

        return User::query()
            ->when($emails->isNotEmpty(), fn ($q) => $q->orWhereIn('email', $emails->all()))
            ->get()
            ->merge(
                $handles->isEmpty()
                    ? collect()
                    : User::all()->filter(fn (User $u) => $handles->contains(Str::slug($u->name, '.')))
            )
            ->unique('id')
            ->values();
    }
}
