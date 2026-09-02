<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_mention_typeahead_matches_by_name_or_email(): void
    {
        User::factory()->create(['name' => 'Jane Rivers', 'email' => 'jane@emrec.co.tz']);
        User::factory()->create(['name' => 'Bob Stone', 'email' => 'bob@emrec.co.tz']);

        $this->actingAs(User::factory()->create())
            ->getJson('/mentions?q=riv')
            ->assertOk()
            ->assertJsonFragment(['handle' => 'jane.rivers'])
            ->assertJsonMissing(['name' => 'Bob Stone']);
    }

    public function test_preview_renders_markdown_links_mentions_and_strips_scripts(): void
    {
        $u = User::factory()->create(['name' => 'Jane Rivers', 'email' => 'jane@emrec.co.tz']);

        $this->actingAs(User::factory()->create())
            ->postJson('/comments/preview', ['body' => "**hi** @jane.rivers <script>x</script>"])
            ->assertOk()
            ->assertJson(fn ($json) => $json
                ->where('html', fn ($html) => str_contains($html, '<strong>hi</strong>')
                    && str_contains($html, 'class="mention">@Jane Rivers')
                    && ! str_contains($html, '<script>')));
    }
}
