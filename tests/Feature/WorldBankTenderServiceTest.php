<?php

namespace Tests\Feature;

use App\Services\WorldBankTenderService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class WorldBankTenderServiceTest extends TestCase
{
    private function service(array $procnotices): WorldBankTenderService
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['procnotices' => $procnotices])),
        ]);

        $client = new Client(['handler' => HandlerStack::create($mock)]);

        return new WorldBankTenderService($client);
    }

    public function test_it_reads_the_flat_procnotices_array(): void
    {
        $rows = $this->service([
            [
                'id' => 'OP00465428',
                'bid_description' => 'Consultancy for something',
                'project_name' => 'Some Project',
                'project_ctry_name' => 'Tanzania',
                'procurement_group' => 'CS',
                'contact_organization' => 'Ministry of X',
                'noticedate' => '28-Aug-2026',
                'submission_deadline_date' => '2026-09-16T00:00:00Z',
                'notice_text' => '<p>Hello &amp; welcome</p>',
            ],
        ])->fetch();

        $this->assertCount(1, $rows);

        $row = $rows[0];
        $this->assertSame('world_bank', $row['source']);
        $this->assertSame('OP00465428', $row['external_id']);
        $this->assertSame('Consultancy for something', $row['title']);
        $this->assertSame('Tanzania', $row['country']);
        $this->assertSame('Consulting services', $row['sector']);
        $this->assertSame('Ministry of X', $row['buyer']);
        $this->assertSame('2026-08-28', $row['published_date']);
        $this->assertSame('2026-09-16', $row['deadline_date']);
        $this->assertSame('Hello & welcome', $row['description']);
        $this->assertSame(
            'https://projects.worldbank.org/en/projects-operations/procurement-detail/OP00465428',
            $row['url'],
        );
    }

    public function test_it_falls_back_when_optional_fields_are_missing(): void
    {
        $rows = $this->service([
            ['id' => 'OP1', 'project_name' => 'Bare Award'],
        ])->fetch();

        $this->assertSame('Bare Award', $rows[0]['title']);
        $this->assertSame('Bare Award', $rows[0]['buyer']);
        $this->assertNull($rows[0]['deadline_date']);
        $this->assertNull($rows[0]['description']);
    }

    public function test_empty_response_yields_no_rows(): void
    {
        $this->assertSame([], $this->service([])->fetch());
    }
}
