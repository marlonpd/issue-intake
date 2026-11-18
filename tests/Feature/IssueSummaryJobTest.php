<?php

namespace Tests\Feature;

use App\Jobs\GenerateIssueSummary;
use App\Models\Issue;
use App\Services\Summary\SummaryGenerator;
use App\Services\Summary\SummaryResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class IssueSummaryJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_issue_dispatches_summary_job(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/issues', [
            'title' => 'Cannot log in',
            'description' => 'The login page says my password is wrong.',
            'priority' => 'medium',
            'category' => 'account',
        ])->assertCreated();

        Queue::assertPushed(GenerateIssueSummary::class, function (GenerateIssueSummary $job) use ($response) {
            return $job->issue->id === $response->json('data.id');
        });
    }

    public function test_updating_description_dispatches_summary_job(): void
    {
        $issue = Issue::factory()->create();
        Queue::fake();

        $this->patchJson("/api/issues/{$issue->id}", ['description' => 'New details about the problem.'])
            ->assertOk()
            ->assertJsonPath('data.summary_status', 'pending');

        Queue::assertPushed(GenerateIssueSummary::class, 1);
    }

    public function test_updating_status_only_does_not_dispatch_summary_job(): void
    {
        $issue = Issue::factory()->create();
        Queue::fake();

        $this->patchJson("/api/issues/{$issue->id}", ['status' => 'resolved'])
            ->assertOk()
            ->assertJsonPath('data.summary_status', 'ready');

        Queue::assertNothingPushed();
    }

    public function test_summary_job_populates_summary_fields(): void
    {
        Queue::fake();
        $issue = Issue::factory()->create([
            'title' => 'Charged twice',
            'description' => 'I was charged twice this month. Please send a refund.',
            'priority' => 'high',
            'category' => 'billing',
        ]);

        $this->assertSame('pending', $issue->fresh()->summary_status->value);

        (new GenerateIssueSummary($issue))->handle(app(SummaryGenerator::class));

        $issue->refresh();
        $this->assertSame('ready', $issue->summary_status->value);
        $this->assertSame('High priority billing issue: I was charged twice this month.', $issue->summary);
        $this->assertSame(
            'Check the payment provider for duplicate charges and refund the customer if confirmed.',
            $issue->suggested_next_action,
        );
    }

    public function test_job_uses_the_bound_summary_generator(): void
    {
        $this->app->instance(SummaryGenerator::class, new class implements SummaryGenerator
        {
            public function generate(Issue $issue): SummaryResult
            {
                return new SummaryResult('Fake summary.', 'Fake action.');
            }
        });

        $issue = Issue::factory()->create();

        $this->getJson("/api/issues/{$issue->id}")
            ->assertJsonPath('data.summary', 'Fake summary.')
            ->assertJsonPath('data.suggested_next_action', 'Fake action.')
            ->assertJsonPath('data.summary_status', 'ready');
    }

    public function test_failed_job_marks_summary_as_failed(): void
    {
        Queue::fake();
        $issue = Issue::factory()->create();

        (new GenerateIssueSummary($issue))->failed(new RuntimeException('Generator is down'));

        $this->getJson("/api/issues/{$issue->id}")
            ->assertOk()
            ->assertJsonPath('data.summary_status', 'failed')
            ->assertJsonPath('data.summary', null);
    }
}
