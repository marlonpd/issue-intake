<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Issue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CommentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    public function test_can_add_comment_to_issue(): void
    {
        $issue = Issue::factory()->create();

        $this->postJson("/api/issues/{$issue->id}/comments", [
            'author_name' => ' Anna Reyes ',
            'body' => ' Checked the logs, looks like a timeout. ',
        ])->assertCreated()
            ->assertJsonPath('data.issue_id', $issue->id)
            ->assertJsonPath('data.author_name', 'Anna Reyes')
            ->assertJsonPath('data.body', 'Checked the logs, looks like a timeout.');

        $this->assertCount(1, $issue->comments()->get());

        $this->getJson("/api/issues/{$issue->id}")
            ->assertJsonCount(1, 'data.comments')
            ->assertJsonPath('data.comments.0.author_name', 'Anna Reyes');
    }

    public function test_comment_requires_author_and_body(): void
    {
        $issue = Issue::factory()->create();

        $this->postJson("/api/issues/{$issue->id}/comments", [
            'author_name' => '',
            'body' => '   ',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['author_name', 'body']);

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_comment_on_missing_issue_returns_404(): void
    {
        $this->postJson('/api/issues/999/comments', [
            'author_name' => 'Anna Reyes',
            'body' => 'Hello',
        ])->assertNotFound()
            ->assertExactJson(['message' => 'Issue not found.']);
    }

    public function test_show_issue_loads_comments_without_n_plus_one(): void
    {
        $few = Issue::factory()->has(Comment::factory()->count(1))->create();
        $many = Issue::factory()->has(Comment::factory()->count(10))->create();

        $fewQueries = $this->countQueries(function () use ($few) {
            $this->getJson("/api/issues/{$few->id}")->assertOk();
        });

        $manyQueries = $this->countQueries(function () use ($many) {
            $this->getJson("/api/issues/{$many->id}")->assertOk()->assertJsonCount(10, 'data.comments');
        });

        // One query for the issue and one for all of its comments.
        $this->assertSame(2, $fewQueries);
        $this->assertSame($fewQueries, $manyQueries);
    }

    public function test_issue_list_query_count_does_not_grow_with_issues(): void
    {
        Issue::factory()->count(2)->has(Comment::factory()->count(2))->create();

        $fewQueries = $this->countQueries(function () {
            $this->getJson('/api/issues')->assertOk();
        });

        Issue::factory()->count(10)->has(Comment::factory()->count(3))->create();

        $manyQueries = $this->countQueries(function () {
            $this->getJson('/api/issues')->assertOk()->assertJsonCount(12, 'data');
        });

        $this->assertSame($fewQueries, $manyQueries);
    }

    private function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $callback();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}
