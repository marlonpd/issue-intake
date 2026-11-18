<?php

namespace App\Http\Requests;

use App\Enums\IssueCategory;
use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListIssuesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(IssueStatus::class)],
            'priority' => ['nullable', Rule::enum(IssuePriority::class)],
            'category' => ['nullable', Rule::enum(IssueCategory::class)],
        ];
    }
}
