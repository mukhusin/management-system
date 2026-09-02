<?php

namespace Database\Seeders;

use App\Services\Import\TrackerCsvImporter;
use Illuminate\Database\Seeder;

class TrackerSeeder extends Seeder
{
    /**
     * Load the shipped EMREC Master Business Tracker snapshot. Skipped in
     * production (real data should be imported deliberately via
     * `php artisan tracker:import`).
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $path = database_path('seeders/data/emrec-tracker.csv');

        if (is_file($path)) {
            app(TrackerCsvImporter::class)->import($path);
        }
    }
}
