<?php

namespace App\Services\Summary;

use App\Models\Issue;

interface SummaryGenerator
{
    public function generate(Issue $issue): SummaryResult;
}
