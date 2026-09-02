<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Carbon;

/**
 * ReliefWeb API v2 (run by UN OCHA) — humanitarian / development-aid
 * reports; agencies cross-post tenders and RFPs there.
 * Docs: https://apidoc.reliefweb.int/
 *
 * The v1 API was decommissioned (410 Gone). v2 lives at
 * https://api.reliefweb.int/v2/reports and, since 2025-11-01, rejects
 * any `appname` that hasn't been pre-approved with a 403. Request one at
 * https://apidoc.reliefweb.int/parameters#appname and put it in
 * RELIEFWEB_APPNAME; without it this source is skipped.
 */
class ReliefWebTenderService implements TenderSourceInterface
{
    protected Client $client;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client([
            'base_uri' => 'https://api.reliefweb.int/',
            'timeout' => config('tenders.http_timeout', 30),
        ]);
    }

    public function sourceKey(): string
    {
        return 'reliefweb';
    }

    public function isEnabled(): bool
    {
        return (bool) config('tenders.reliefweb.enabled', true)
            && filled(config('tenders.reliefweb.appname'));
    }

    public function fetch(): array
    {
        $appname = (string) config('tenders.reliefweb.appname');

        $payload = [
            'query' => [
                'value' => config('tenders.reliefweb.query', 'tender OR procurement'),
            ],
            'fields' => [
                'include' => [
                    'title', 'url', 'url_alias', 'date', 'body', 'body-html',
                    'country', 'theme', 'source',
                ],
            ],
            'limit' => (int) config('tenders.reliefweb.limit', 50),
            'sort' => ['date:desc'],
        ];

        if ($country = config('tenders.reliefweb.country')) {
            $payload['filter'] = ['field' => 'country', 'value' => $country];
        }

        $response = $this->client->post('v2/reports?appname='.rawurlencode($appname), [
            'json' => $payload,
        ]);

        $body = json_decode((string) $response->getBody(), true);
        $items = $body['data'] ?? [];

        $rows = [];

        foreach ($items as $item) {
            $fields = $item['fields'] ?? [];

            $rows[] = [
                'source' => $this->sourceKey(),
                'external_id' => (string) ($item['id'] ?? md5(json_encode($item))),
                'title' => $fields['title'] ?? 'Untitled ReliefWeb notice',
                'description' => trim(strip_tags($fields['body-html'] ?? $fields['body'] ?? '')) ?: null,
                'country' => collect($fields['country'] ?? [])->pluck('name')->join(', ') ?: null,
                'sector' => collect($fields['theme'] ?? [])->pluck('name')->join(', ') ?: null,
                'buyer' => collect($fields['source'] ?? [])->pluck('name')->join(', ') ?: null,
                'value' => null,
                'currency' => null,
                'published_date' => $this->toDate($fields['date']['created'] ?? null),
                'deadline_date' => null,
                'url' => $fields['url'] ?? $fields['url_alias'] ?? null,
                'raw' => $item,
            ];
        }

        return $rows;
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
