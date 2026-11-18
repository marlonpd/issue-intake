<?php

namespace Tests\Unit;

use App\Models\Issue;
use App\Services\Summary\RuleBasedSummaryGenerator;
use PHPUnit\Framework\TestCase;

class RuleBasedSummaryGeneratorTest extends TestCase
{
    private RuleBasedSummaryGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new RuleBasedSummaryGenerator;
    }

    public function test_summary_uses_priority_category_and_first_sentence(): void
    {
        $result = $this->generator->generate($this->issue(
            description: 'The dashboard takes 20 seconds to load. It started yesterday.',
            priority: 'high',
            category: 'technical',
        ));

        $this->assertSame('High priority technical issue: The dashboard takes 20 seconds to load.', $result->summary);
    }

    public function test_long_description_is_shortened(): void
    {
        $result = $this->generator->generate($this->issue(description: str_repeat('word ', 100)));

        $this->assertLessThan(200, strlen($result->summary));
        $this->assertStringEndsWith('...', $result->summary);
    }

    public function test_keyword_rule_picks_next_action(): void
    {
        $result = $this->generator->generate($this->issue(
            title: 'Page loads slowly',
            description: 'Customers are complaining.',
            category: 'other',
        ));

        $this->assertSame('Check the slow query log and response times for the affected page.', $result->nextAction);
    }

    public function test_falls_back_to_category_action_when_no_keyword_matches(): void
    {
        $result = $this->generator->generate($this->issue(
            title: 'Change company name',
            description: 'We would like our company name updated.',
            category: 'account',
        ));

        $this->assertSame('Verify the user\'s identity and check their account status in the admin panel.', $result->nextAction);
    }

    public function test_feature_requests_ignore_keyword_rules(): void
    {
        $result = $this->generator->generate($this->issue(
            title: 'Export invoices to CSV',
            description: 'Please let us download all invoices.',
            category: 'feature_request',
        ));

        $this->assertSame('Add the request to the product backlog for the next planning session.', $result->nextAction);
    }

    private function issue(
        string $title = 'Something happened',
        string $description = 'Some details.',
        string $priority = 'low',
        string $category = 'other',
    ): Issue {
        return new Issue(compact('title', 'description', 'priority', 'category'));
    }
}
