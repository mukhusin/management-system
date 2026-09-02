<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Carbon;
use Symfony\Component\DomCrawler\Crawler;

/**
 * TANePS (Tanzania National e-Procurement System) has no public API, and
 * the current dashboard at https://www.taneps.go.tz/#/website/tender-notice
 * is a JavaScript SPA — the `#/...` route never reaches the server, so
 * Guzzle only ever gets the empty app shell. That's why the old version
 * timed out / returned nothing.
 *
 * The classic PPS platform underneath it does still render plain HTML
 * listings server-side (URLs under `/epps/...`, e.g.
 * `viewOpenedTenders.do`). Set TANEPS_LISTING_URL to that page once
 * you've confirmed it in a browser, and adjust the selectors below to
 * match its table markup. Until then this source is skipped.
 *
 * If the listing turns out to be loaded by an XHR/JSON call (check the
 * browser Network tab), call that endpoint directly instead of scraping
 * HTML — it will be far more stable.
 */
class TanepsTenderService implements TenderSourceInterface
{
    protected Client $client;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client([
            'timeout' => config('tenders.http_timeout', 30),
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; TenderAggregator/1.0)',
            ],
        ]);
    }

    public function sourceKey(): string
    {
        return 'taneps';
    }

    public function isEnabled(): bool
    {
        return (bool) config('tenders.taneps.enabled', true)
            && filled($this->listingUrl());
    }

    protected function listingUrl(): ?string
    {
        return config('tenders.taneps.listing_url');
    }

    public function fetch(): array
    {
        $response = $this->client->get($this->listingUrl());
        $crawler = new Crawler((string) $response->getBody());

        $rows = [];

        // Adjust this selector to the real one for the configured page.
        // `table tbody tr` is a sane default for the classic PPS listings.
        $crawler->filter('table tbody tr')->each(function (Crawler $node) use (&$rows) {
            $cells = $node->filter('td');

            if ($cells->count() < 2) {
                return;
            }

            $title = trim($cells->eq(0)->text(''));

            if ($title === '') {
                return;
            }

            $link = $node->filter('a')->count() ? $node->filter('a')->attr('href') : null;

            if ($link && ! str_starts_with($link, 'http')) {
                $link = rtrim('https://www.taneps.go.tz', '/').'/'.ltrim($link, '/');
            }

            $reference = $cells->count() > 1 ? trim($cells->eq(1)->text('')) : $title;
            $closing = $cells->count() > 2 ? trim($cells->last()->text('')) : null;

            $rows[] = [
                'source' => $this->sourceKey(),
                'external_id' => $reference !== '' ? $reference : $title,
                'title' => $title,
                'description' => null,
                'country' => 'Tanzania',
                'sector' => null,
                'buyer' => null,
                'value' => null,
                'currency' => 'TZS',
                'published_date' => null,
                'deadline_date' => $this->toDate($closing),
                'url' => $link,
                'raw' => ['closing_text' => $closing, 'row_text' => trim($node->text(''))],
            ];
        });

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
