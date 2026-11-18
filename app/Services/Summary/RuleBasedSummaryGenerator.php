<?php

namespace App\Services\Summary;

use App\Enums\IssueCategory;
use App\Models\Issue;
use Illuminate\Support\Str;

class RuleBasedSummaryGenerator implements SummaryGenerator
{
    // Checked in order, the first match wins. Keywords match the start of a word, so "slow" also matches "slowly".
    private const KEYWORD_ACTIONS = [
        'refund|charged twice|double charge|duplicate charge' => 'Check the payment provider for duplicate charges and refund the customer if confirmed.',
        'invoice|receipt' => 'Regenerate the latest invoice and confirm the details with the customer.',
        'password|log in|login|sign in|locked out|2fa' => 'Ask the user to reset their password and confirm whether they can sign in.',
        'slow|timeout|timed out|performance' => 'Check the slow query log and response times for the affected page.',
        'email|notification' => 'Check the mail queue and the mail provider logs for failed deliveries.',
        'error|crash|bug|broken|not working' => 'Reproduce the problem in staging and attach the error logs to this issue.',
    ];

    public function generate(Issue $issue): SummaryResult
    {
        return new SummaryResult(
            summary: $this->summary($issue),
            nextAction: $this->nextAction($issue),
        );
    }

    private function summary(Issue $issue): string
    {
        $priority = ucfirst($issue->priority->value);
        $category = str_replace('_', ' ', $issue->category->value);

        return "{$priority} priority {$category} issue: {$this->firstSentence($issue->description)}";
    }

    private function nextAction(Issue $issue): string
    {
        // Feature requests are not problems to fix, so keywords like "invoice" should not apply.
        if ($issue->category !== IssueCategory::FeatureRequest) {
            $text = $issue->title.' '.$issue->description;

            foreach (self::KEYWORD_ACTIONS as $keywords => $action) {
                if (preg_match("/\b({$keywords})/i", $text)) {
                    return $action;
                }
            }
        }

        return $this->categoryAction($issue->category);
    }

    private function categoryAction(IssueCategory $category): string
    {
        return match ($category) {
            IssueCategory::Billing => 'Review the customer\'s billing history and confirm the account balance.',
            IssueCategory::Technical => 'Reproduce the problem and attach logs or screenshots to this issue.',
            IssueCategory::Account => 'Verify the user\'s identity and check their account status in the admin panel.',
            IssueCategory::FeatureRequest => 'Add the request to the product backlog for the next planning session.',
            IssueCategory::Other => 'Reply to the customer and ask for any missing details.',
        };
    }

    private function firstSentence(string $text): string
    {
        $sentence = preg_split('/(?<=[.!?])\s+/', trim($text))[0];
        $sentence = Str::limit($sentence, 140);

        if (! Str::endsWith($sentence, ['.', '!', '?'])) {
            $sentence .= '.';
        }

        return $sentence;
    }
}
