<?php

namespace App\Models;

use App\Enums\IssueCategory;
use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Enums\SummaryStatus;
use App\Jobs\GenerateIssueSummary;
use Database\Factories\IssueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'priority', 'category', 'status'])]
class Issue extends Model
{
    /** @use HasFactory<IssueFactory> */
    use HasFactory;

    // Changing any of these makes the current summary outdated. Status changes do not.
    public const SUMMARY_FIELDS = ['title', 'description', 'priority', 'category'];

    protected $attributes = [
        'status' => 'open',
        'summary_status' => 'pending',
        'needs_attention' => false,
    ];

    protected function casts(): array
    {
        return [
            'priority' => IssuePriority::class,
            'category' => IssueCategory::class,
            'status' => IssueStatus::class,
            'summary_status' => SummaryStatus::class,
            'needs_attention' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Issue $issue) {
            $issue->needs_attention = $issue->priority === IssuePriority::High;
        });

        static::updating(function (Issue $issue) {
            if ($issue->isDirty(self::SUMMARY_FIELDS)) {
                $issue->summary_status = SummaryStatus::Pending;
            }
        });

        static::created(function (Issue $issue) {
            GenerateIssueSummary::dispatch($issue);
        });

        static::updated(function (Issue $issue) {
            if ($issue->wasChanged(self::SUMMARY_FIELDS)) {
                GenerateIssueSummary::dispatch($issue);
            }
        });
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function scopeFilter(Builder $query, array $filters): void
    {
        foreach (['status', 'priority', 'category'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
    }
}
