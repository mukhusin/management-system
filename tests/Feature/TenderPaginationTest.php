<?php

namespace Tests\Feature;

use App\Models\Tender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenderPaginationTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenders(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            Tender::create([
                'source' => 'world_bank',
                'external_id' => "wb-{$i}",
                'title' => "Notice number {$i}",
                'deadline_date' => null, // exercise the "nulls last" ordering path
            ]);
        }
    }

    public function test_index_paginates_at_20_per_page(): void
    {
        $this->makeTenders(45);

        $this->actingAs(User::factory()->create())
            ->get('/?open_only=0')
            ->assertOk()
            ->assertSee('Showing 1&ndash;20', false)
            ->assertSee('of 45', false)
            ->assertSee('pager-link'); // our custom view, not pagination::tailwind
    }

    public function test_pages_do_not_overlap_or_skip_rows(): void
    {
        $this->makeTenders(45);
        $user = User::factory()->create();

        $idsOnPage = function (int $page) use ($user) {
            $html = $this->actingAs($user)->get("/?open_only=0&page={$page}")->getContent();
            preg_match_all('#/tenders/(\d+)"#', $html, $m);

            return array_values(array_unique($m[1]));
        };

        $p1 = $idsOnPage(1);
        $p2 = $idsOnPage(2);
        $p3 = $idsOnPage(3);

        $this->assertCount(20, $p1);
        $this->assertCount(20, $p2);
        $this->assertCount(5, $p3);

        // No id appears on more than one page, and all 45 are covered.
        $all = array_merge($p1, $p2, $p3);
        $this->assertCount(45, array_unique($all));
    }

    public function test_filters_are_preserved_across_pages(): void
    {
        $this->makeTenders(30);

        $html = $this->actingAs(User::factory()->create())
            ->get('/?open_only=0&q=Notice&page=2')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('q=Notice', $html);
        $this->assertStringContainsString('open_only=0', $html);
    }
}
