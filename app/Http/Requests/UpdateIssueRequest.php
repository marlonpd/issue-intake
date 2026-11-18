<?php

namespace App\Http\Requests;

use App\Enums\IssueCategory;
use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIssueRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'max:5000'],
            'priority' => ['sometimes', 'required', Rule::enum(IssuePriority::class)],
            'category' => ['sometimes', 'required', Rule::enum(IssueCategory::class)],
            'status' => ['sometimes', 'required', Rule::enum(IssueStatus::class)],
        ];
    }
}
