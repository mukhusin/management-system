<?php

namespace App\Console\Commands;

use App\Services\Import\TrackerCsvImporter;
use Illuminate\Console\Command;

class ImportTracker extends Command
{
    protected $signature = 'tracker:import {path : Path to the tracker CSV}';

    protected $description = 'Import the EMREC Master Business Tracker spreadsheet into tracker items.';

    public function handle(TrackerCsvImporter $importer): int
    {
        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $summary = $importer->import($path)->summary();

        $this->info("Created {$summary['created']}, updated {$summary['updated']}, skipped {$summary['skipped']}.");

        if ($summary['unmatched_people'] !== []) {
            $this->warn('Unmatched people (left unassigned): '.implode('; ', $summary['unmatched_people']));
        }

        return self::SUCCESS;
    }
}
