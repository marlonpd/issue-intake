<?php

namespace Tests\Feature;

use App\Models\Issue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NeedsAttentionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    public function test_high_priority_issue_needs_attention(): void
    {
        $this->assertTrue(Issue::factory()->create(['priority' => 'high'])->needs_attention);
        $this->assertFalse(Issue::factory()->create(['priority' => 'low'])->needs_attention);
    }

    public function test_resolving_an_issue_clears_needs_attention(): void
    {
        $issue = Issue::factory()->create(['priority' => 'high']);

        $issue->update(['status' => 'resolved']);

        $this->assertFalse($issue->fresh()->needs_attention);
    }

    public function test_command_flags_overdue_issues_only(): void
    {
        $overdue = $this->issueCreatedDaysAgo(5, ['priority' => 'low', 'status' => 'open']);
        $recent = $this->issueCreatedDaysAgo(1, ['priority' => 'low', 'status' => 'open']);
        $resolved = $this->issueCreatedDaysAgo(5, ['priority' => 'low', 'status' => 'resolved']);

        $this->artisan('issues:flag-overdue')
            ->expectsOutput('Flagged 1 overdue issue(s).')
            ->assertSuccessful();

        $this->assertTrue($overdue->fresh()->needs_attention);
        $this->assertFalse($recent->fresh()->needs_attention);
        $this->assertFalse($resolved->fresh()->needs_attention);
    }

    public function test_saving_an_overdue_issue_keeps_it_flagged(): void
    {
        $issue = $this->issueCreatedDaysAgo(5, ['priority' => 'low', 'status' => 'open']);

        $issue->update(['status' => 'in_progress']);

        $this->assertTrue($issue->fresh()->needs_attention);
    }

    public function test_overdue_command_is_scheduled(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('issues:flag-overdue')
            ->assertSuccessful();
    }

    private function issueCreatedDaysAgo(int $days, array $attributes): Issue
    {
        $this->travel(-$days)->days();
        $issue = Issue::factory()->create($attributes);
        $this->travelBack();

        return $issue;
    }
}
