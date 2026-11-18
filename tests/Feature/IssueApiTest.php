<?php

namespace Tests\Feature;

use App\Models\Issue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class IssueApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    public function test_can_create_issue(): void
    {
        $response = $this->postJson('/api/issues', [
            'title' => '  Customer charged twice  ',
            'description' => 'The customer sees two charges for the same order.',
            'priority' => 'high',
            'category' => 'billing',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Customer charged twice')
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.summary_status', 'pending')
            ->assertJsonPath('data.summary', null)
            ->assertJsonPath('data.needs_attention', true);

        $this->assertDatabaseHas('issues', [
            'title' => 'Customer charged twice',
            'status' => 'open',
        ]);
    }

    public function test_create_fails_when_required_fields_are_missing(): void
    {
        $this->postJson('/api/issues', [])
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonValidationErrors(['title', 'description', 'priority', 'category']);

        $this->assertDatabaseCount('issues', 0);
    }

    public function test_create_fails_with_invalid_priority_and_status(): void
    {
        $this->postJson('/api/issues', [
            'title' => 'Something broke',
            'description' => 'Details here.',
            'priority' => 'urgent',
            'category' => 'technical',
            'status' => 'closed',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['priority', 'status']);
    }

    public function test_create_rejects_whitespace_only_strings(): void
    {
        $this->postJson('/api/issues', [
            'title' => '    ',
            'description' => '   ',
            'priority' => 'low',
            'category' => 'other',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'description']);
    }

    public function test_can_filter_issues_by_status_and_priority(): void
    {
        $match = Issue::factory()->create(['status' => 'open', 'priority' => 'high']);
        Issue::factory()->create(['status' => 'open', 'priority' => 'low']);
        Issue::factory()->create(['status' => 'resolved', 'priority' => 'high']);

        $this->getJson('/api/issues?status=open&priority=high')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $match->id);
    }

    public function test_can_filter_issues_by_three_filters(): void
    {
        $match = Issue::factory()->create(['status' => 'in_progress', 'priority' => 'medium', 'category' => 'account']);
        Issue::factory()->create(['status' => 'in_progress', 'priority' => 'medium', 'category' => 'billing']);

        $this->getJson('/api/issues?status=in_progress&priority=medium&category=account')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $match->id);
    }

    public function test_list_rejects_invalid_filter_value(): void
    {
        $this->getJson('/api/issues?priority=urgent')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['priority']);
    }

    public function test_can_sort_issues_by_priority(): void
    {
        $low = Issue::factory()->create(['priority' => 'low']);
        $high = Issue::factory()->create(['priority' => 'high']);
        $medium = Issue::factory()->create(['priority' => 'medium']);

        $this->getJson('/api/issues?sort=priority&direction=desc')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$high->id, $medium->id, $low->id]);

        $this->getJson('/api/issues?sort=priority&direction=asc')
            ->assertJsonPath('data.*.id', [$low->id, $medium->id, $high->id]);
    }

    public function test_list_is_paginated(): void
    {
        Issue::factory()->count(5)->create();

        $this->getJson('/api/issues?per_page=2&page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 5);
    }

    public function test_list_rejects_invalid_sort_options(): void
    {
        $this->getJson('/api/issues?sort=title&direction=up&per_page=500')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sort', 'direction', 'per_page']);
    }

    public function test_can_update_issue(): void
    {
        $issue = Issue::factory()->create(['priority' => 'high']);

        $this->patchJson("/api/issues/{$issue->id}", ['priority' => 'low', 'status' => 'resolved'])
            ->assertOk()
            ->assertJsonPath('data.priority', 'low')
            ->assertJsonPath('data.status', 'resolved')
            ->assertJsonPath('data.needs_attention', false);
    }

    public function test_update_rejects_empty_title(): void
    {
        $issue = Issue::factory()->create();

        $this->patchJson("/api/issues/{$issue->id}", ['title' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_missing_issue_returns_404_json(): void
    {
        $this->getJson('/api/issues/999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Issue not found.']);
    }

    public function test_malformed_json_returns_400(): void
    {
        $this->call('POST', '/api/issues', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], '{"title": "x",')
            ->assertStatus(400)
            ->assertExactJson(['message' => 'The request body is not valid JSON.']);
    }
}
