<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Carbon;

/**
 * World Bank "Procurement Notices" open API.
 * Explorer: https://search.worldbank.org/api/v2/procnotices?format=json
 *
 * The response is `{ "procnotices": [ { ...notice... }, ... ] }` — a flat
 * array, not a nested `procnotice` key. Field names vary by notice_type
 * (Contract Award notices carry far fewer fields than a Request for Bids),
 * so every mapping below is defensive.
 */
class WorldBankTenderService implements TenderSourceInterface
{
    protected Client $client;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client([
            'base_uri' => 'https://search.worldbank.org/',
            'timeout' => config('tenders.http_timeout', 30),
        ]);
    }

    public function sourceKey(): string
    {
        return 'world_bank';
    }

    public function isEnabled(): bool
    {
        return (bool) config('tenders.world_bank.enabled', true);
    }

    public function fetch(): array
    {
        $query = [
            'format' => 'json',
            'rows' => config('tenders.world_bank.rows', 100),
            'os' => 0, // offset — increase to paginate
        ];

        if ($term = trim((string) config('tenders.world_bank.query', ''))) {
            $query['qterm'] = $term;
        }

        $response = $this->client->get('api/v2/procnotices', ['query' => $query]);

        $body = json_decode((string) $response->getBody(), true);
        $notices = $body['procnotices'] ?? [];

        $rows = [];

        foreach ($notices as $notice) {
            if (! is_array($notice)) {
                continue;
            }

            $id = (string) ($notice['id'] ?? $notice['bid_reference_no'] ?? md5(json_encode($notice)));

            $rows[] = [
                'source' => $this->sourceKey(),
                'external_id' => $id,
                'title' => $notice['bid_description']
                    ?? $notice['project_name']
                    ?? 'Untitled World Bank notice',
                'description' => $this->plainText($notice['notice_text'] ?? null),
                'country' => $notice['project_ctry_name'] ?? $notice['contact_ctry_name'] ?? null,
                'sector' => $this->category($notice['procurement_group'] ?? null),
                'buyer' => $notice['contact_organization'] ?? $notice['project_name'] ?? 'World Bank',
                'value' => null,
                'currency' => null,
                'published_date' => $this->toDate($notice['noticedate'] ?? $notice['submission_date'] ?? null),
                'deadline_date' => $this->toDate($notice['submission_deadline_date'] ?? null),
                'url' => "https://projects.worldbank.org/en/projects-operations/procurement-detail/{$id}",
                'raw' => $notice,
            ];
        }

        return $rows;
    }

    /**
     * Map the World Bank procurement_group code to a readable category.
     */
    protected function category(?string $code): ?string
    {
        return match ($code) {
            'GO' => 'Goods',
            'CW' => 'Civil works',
            'CS' => 'Consulting services',
            'NC' => 'Non-consulting services',
            default => $code ?: null,
        };
    }

    protected function plainText(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return null;
        }

        return trim(html_entity_decode(strip_tags($html))) ?: null;
    }

    protected function toDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Exception) {
            return null;
        }
    }
}
