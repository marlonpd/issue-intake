<?php

namespace App\Jobs;

use App\Enums\SummaryStatus;
use App\Models\Issue;
use App\Services\Summary\SummaryGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateIssueSummary implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30];

    public bool $deleteWhenMissingModels = true;

    public function __construct(public Issue $issue) {}

    public function handle(SummaryGenerator $generator): void
    {
        $result = $generator->generate($this->issue);

        $this->issue->summary = $result->summary;
        $this->issue->suggested_next_action = $result->nextAction;
        $this->issue->summary_status = SummaryStatus::Ready;
        $this->issue->save();
    }

    public function failed(?Throwable $exception): void
    {
        $this->issue->summary_status = SummaryStatus::Failed;
        $this->issue->save();
    }
}
