<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Issue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebIssueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    public function test_home_redirects_to_issue_list(): void
    {
        $this->get('/')->assertRedirect('/issues');
    }

    public function test_issue_list_shows_filtered_issues(): void
    {
        Issue::factory()->create(['title' => 'Billing problem', 'category' => 'billing']);
        Issue::factory()->create(['title' => 'Login problem', 'category' => 'account']);

        $this->get('/issues?category=billing')
            ->assertOk()
            ->assertSee('Billing problem')
            ->assertDontSee('Login problem');
    }

    public function test_can_create_issue_from_form(): void
    {
        $this->post('/issues', [
            'title' => 'Checkout times out',
            'description' => 'Customers see a timeout when paying.',
            'priority' => 'high',
            'category' => 'technical',
            'status' => 'open',
        ])->assertRedirect('/issues/1');

        $this->get('/issues/1')
            ->assertSee('Checkout times out')
            ->assertSee('Generating summary');
    }

    public function test_form_shows_validation_errors(): void
    {
        $this->from('/issues/create')
            ->post('/issues', ['title' => '', 'priority' => 'urgent'])
            ->assertRedirect('/issues/create')
            ->assertSessionHasErrors(['title', 'description', 'priority', 'category']);
    }

    public function test_issue_page_shows_summary_and_comments(): void
    {
        $issue = Issue::factory()->has(Comment::factory()->state(['body' => 'Looking into it.']))->create();
        $issue->summary = 'Short summary.';
        $issue->suggested_next_action = 'Call the customer.';
        $issue->summary_status = 'ready';
        $issue->save();

        $this->get("/issues/{$issue->id}")
            ->assertOk()
            ->assertSee('Short summary.')
            ->assertSee('Call the customer.')
            ->assertSee('Looking into it.');
    }

    public function test_can_add_comment_from_form(): void
    {
        $issue = Issue::factory()->create();

        $this->post("/issues/{$issue->id}/comments", [
            'author_name' => 'Joy Cruz',
            'body' => 'Customer confirmed it works now.',
        ])->assertRedirect("/issues/{$issue->id}#comments");

        $this->assertDatabaseHas('comments', [
            'issue_id' => $issue->id,
            'author_name' => 'Joy Cruz',
        ]);
    }
}
