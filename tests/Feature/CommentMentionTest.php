<?php

namespace Tests\Feature;

use App\Models\Tender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentMentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_mention_creates_a_link_row_and_a_notification(): void
    {
        $author = User::factory()->admin()->create();
        $mentioned = User::factory()->create(['email' => 'jane@emrec.co.tz', 'name' => 'Jane Doe']);
        $tender = Tender::factory()->create();

        $comment = $tender->addComment($author, 'Please review @jane@emrec.co.tz');

        $this->assertTrue($comment->mentions->contains($mentioned));
        $this->assertDatabaseHas('comment_mentions', ['comment_id' => $comment->id, 'user_id' => $mentioned->id]);
        $this->assertSame(1, $mentioned->notifications()->count());
    }

    public function test_author_is_not_notified_for_self_mention(): void
    {
        $author = User::factory()->admin()->create(['email' => 'me@emrec.co.tz']);
        $tender = Tender::factory()->create();

        $tender->addComment($author, 'Note to self @me@emrec.co.tz');

        $this->assertSame(0, $author->notifications()->count());
    }

    public function test_markdown_renders_and_scripts_are_stripped(): void
    {
        $author = User::factory()->admin()->create();
        $tender = Tender::factory()->create();

        $comment = $tender->addComment($author, "**bold** <script>alert('x')</script>");
        $html = (string) $comment->renderedBody();

        $this->assertStringContainsString('<strong>bold</strong>', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_comment_endpoint_requires_the_comment_permission(): void
    {
        $tender = Tender::factory()->create();
        $user = User::factory()->create();
        $user->overrides()->create(['permission' => 'tenders.comment', 'granted' => false]);

        $this->actingAs($user)
            ->post("/tenders/{$tender->id}/comments", ['body' => 'hi'])
            ->assertForbidden();
    }
}
