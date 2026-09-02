<?php

namespace App\Console\Commands;

use App\Models\Tender;
use App\Services\ReliefWebTenderService;
use App\Services\TanepsTenderService;
use App\Services\TenderSourceInterface;
use App\Services\WorldBankTenderService;
use Illuminate\Console\Command;

class FetchTenders extends Command
{
    protected $signature = 'tenders:fetch {--source= : Only fetch a single source, e.g. world_bank}';

    protected $description = 'Fetch tenders/opportunities from all configured sources and upsert them into the database.';

    public function handle(): int
    {
        // Ingested notices are a bulk system import, not a user action —
        // don't flood the audit trail with a "created" event per row.
        return Tender::withoutCreationAudit(fn () => $this->fetchAll());
    }

    private function fetchAll(): int
    {
        $only = $this->option('source');

        /** @var TenderSourceInterface[] $sources */
        $sources = [
            new WorldBankTenderService(),
            new ReliefWebTenderService(),
            new TanepsTenderService(),
            // Add more here as you build them, e.g.:
            // new TedTenderService(),
            // new PpraTenderService(),
        ];

        $totalUpserted = 0;
        $failures = 0;

        foreach ($sources as $source) {
            $key = $source->sourceKey();

            if ($only && $key !== $only) {
                continue;
            }

            if (! $source->isEnabled()) {
                $this->warn("Skipping {$key} — disabled or not configured (see config/tenders.php).");

                continue;
            }

            $this->info("Fetching from {$key}...");

            try {
                $rows = $source->fetch();
            } catch (\Throwable $e) {
                $failures++;
                $this->error("  failed: {$e->getMessage()}");

                continue;
            }

            $count = 0;
            foreach ($rows as $row) {
                Tender::updateOrCreate(
                    ['source' => $row['source'], 'external_id' => $row['external_id']],
                    $row
                );
                $count++;
            }

            $totalUpserted += $count;
            $this->info("  upserted {$count} notices from {$key}");
        }

        $this->newLine();
        $this->info("Done — {$totalUpserted} notices upserted".($failures ? ", {$failures} source(s) failed." : '.'));

        return $failures > 0 ? self::FAILURE : self::SUCCESS;
    }
}
