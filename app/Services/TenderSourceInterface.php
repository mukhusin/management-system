<?php

namespace App\Services;

interface TenderSourceInterface
{
    /**
     * Fetch the latest notices from this source and return them as
     * an array of normalized rows, ready for Tender::updateOrCreate().
     * Each row must contain at least: source, external_id, title.
     */
    public function fetch(): array;

    /**
     * The 'source' value stored on each Tender row, e.g. "world_bank".
     */
    public function sourceKey(): string;

    /**
     * Whether this source is turned on and has everything it needs
     * (credentials, URLs) to run. Disabled sources are skipped by
     * `tenders:fetch` instead of erroring.
     */
    public function isEnabled(): bool;
}
