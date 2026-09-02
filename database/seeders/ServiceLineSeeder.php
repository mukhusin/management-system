<?php

namespace Database\Seeders;

use App\Models\ServiceLine;
use Illuminate\Database\Seeder;

class ServiceLineSeeder extends Seeder
{
    /**
     * EMREC's service lines (emrec.co.tz). Matched by slug so re-running is safe.
     */
    public function run(): void
    {
        $lines = [
            ['Research, Consulting & Training', 'Customised research, consulting and training across public health, environment, climate change, poverty alleviation and market research.'],
            ['Monitoring & Evaluation', 'Structured M&E for government, NGOs, donors and companies, from baseline through impact assessment.'],
            ['Health Sector Solutions', 'Public health research, hospitals, medical equipment, digital health, health insurance and health technology.'],
            ['Sourcing, Supply, Import & Export', 'Products sourced through supplier partnerships in Shenzhen, China and regional networks.'],
            ['Legal Services', 'Contract support, partnerships, tenders, PPP transactions, corporate matters and IP protection.'],
            ['Engineering & Construction', 'Buildings, roads, hospitals and infrastructure project coordination.'],
            ['AI, Technology & Innovation', 'AI systems, digital platforms and proprietary EMREC technology products.'],
            ['Finance & Investment Advisory', 'Investment-readiness support and financing connections with investors and DFIs.'],
            ['International Partnerships', 'Cross-border sourcing and market-entry facilitation.'],
            ['Business Opportunity Development', 'Proactive opportunity identification beyond advertised tenders.'],
        ];

        foreach ($lines as $i => [$name, $description]) {
            ServiceLine::updateOrCreate(
                ['slug' => str($name)->slug()->value()],
                ['name' => $name, 'description' => $description, 'position' => $i, 'active' => true],
            );
        }
    }
}
