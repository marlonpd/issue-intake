<?php

namespace App\Console\Commands;

use App\Models\Issue;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('issues:flag-overdue')]
#[Description('Set needs_attention on open issues that are past the overdue limit')]
class FlagOverdueIssues extends Command
{
    public function handle(): int
    {
        $count = Issue::overdue()
            ->where('needs_attention', false)
            ->update(['needs_attention' => true]);

        $this->info("Flagged {$count} overdue issue(s).");

        return self::SUCCESS;
    }
}
