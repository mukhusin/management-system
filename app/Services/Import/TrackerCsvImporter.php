<?php

namespace App\Services\Import;

use App\Enums\Priority;
use App\Enums\TrackerCategory;
use App\Enums\TrackerStatus;
use App\Models\TrackerItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Imports the EMREC Master Business Tracker spreadsheet into tracker_items.
 * Idempotent: rows are matched by their reference (EMREC-001, ...).
 */
class TrackerCsvImporter
{
    /** @var array<int, string> */
    public array $unmatchedPeople = [];

    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public function import(string $path): self
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \RuntimeException("Cannot open {$path}");
        }

        $header = null;

        while (($row = fgetcsv($handle)) !== false) {
            if ($header === null) {
                $header = array_map(fn ($h) => Str::of($h)->lower()->squish()->value(), $row);

                continue;
            }

            $data = $this->associate($header, $row);

            if (blank($data['title'] ?? null)) {
                $this->skipped++;

                continue;
            }

            $this->upsert($data);
        }

        fclose($handle);

        return $this;
    }

    private function associate(array $header, array $row): array
    {
        $assoc = [];
        foreach ($header as $i => $key) {
            $assoc[$key] = trim((string) ($row[$i] ?? ''));
        }

        return [
            'reference' => $assoc['id'] ?? null,
            'entry_date' => $this->date($assoc['date'] ?? null),
            'category' => $this->category($assoc['category'] ?? null),
            'title' => $assoc['title'] ?? null,
            'description' => $assoc['description'] ?: null,
            'owner_id' => $this->person($assoc['responsible person'] ?? null),
            'priority' => $this->priority($assoc['priority'] ?? null),
            'status' => $this->status($assoc['status'] ?? null),
            'progress' => $this->percent($assoc['progress %'] ?? null),
            'next_action' => $assoc['next action'] ?: null,
            'due_date' => $this->date($assoc['due date'] ?? null),
            'remarks' => ($assoc['remarks / outcome'] ?? '') ?: null,
        ];
    }

    private function upsert(array $data): void
    {
        $reference = $data['reference'] ?: TrackerItem::nextReference();
        $existing = TrackerItem::where('reference', $reference)->first();

        $attributes = array_merge($data, ['reference' => $reference]);

        if ($existing) {
            $existing->fill($attributes)->save();
            $this->updated++;
        } else {
            TrackerItem::create($attributes);
            $this->created++;
        }
    }

    private function category(?string $value): string
    {
        $map = config('tracker.import.categories', []);
        $key = Str::lower(trim((string) $value));

        return $map[$key]
            ?? TrackerCategory::tryFromLabel((string) $value)?->value
            ?? TrackerCategory::Other->value;
    }

    private function status(?string $value): string
    {
        return match (Str::lower(trim((string) $value))) {
            'ongoing', 'in progress' => TrackerStatus::Ongoing->value,
            'blocked' => TrackerStatus::Blocked->value,
            'done', 'complete', 'completed' => TrackerStatus::Done->value,
            'dropped', 'cancelled' => TrackerStatus::Dropped->value,
            default => TrackerStatus::NotStarted->value,
        };
    }

    private function priority(?string $value): string
    {
        return match (Str::lower(trim((string) $value))) {
            'high' => Priority::High->value,
            'low' => Priority::Low->value,
            default => Priority::Medium->value,
        };
    }

    private function percent(?string $value): ?int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return (int) round((float) rtrim($value, "% \t"));
    }

    private function date(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || strcasecmp($value, 'Unspecified') === 0) {
            return null;
        }

        foreach (['d.m.Y', 'd/m/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->toDateString();
            } catch (\Throwable) {
                // try next format
            }
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function person(?string $value): ?int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        // The spreadsheet sometimes lists several people; take the first.
        $name = trim(Str::before($value, ','));
        $aliases = config('tracker.import.people', []);

        $email = $aliases[$name] ?? $aliases[$value] ?? null;
        $user = $email
            ? User::where('email', $email)->first()
            : User::where('name', 'like', $name)->first();

        if (! $user) {
            $this->unmatchedPeople[] = $value;

            return null;
        }

        return $user->id;
    }

    /** @return array{created:int,updated:int,skipped:int,unmatched_people:array<int,string>} */
    public function summary(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'unmatched_people' => array_values(array_unique($this->unmatchedPeople)),
        ];
    }
}
