@extends('layouts.app')

@section('content')
    <h1>Issues</h1>

    <form method="GET" action="{{ route('issues.index') }}" class="filters">
        <select name="status" aria-label="Status">
            <option value="">All statuses</option>
            @foreach (App\Enums\IssueStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ Str::headline($status->value) }}</option>
            @endforeach
        </select>

        <select name="priority" aria-label="Priority">
            <option value="">All priorities</option>
            @foreach (App\Enums\IssuePriority::cases() as $priority)
                <option value="{{ $priority->value }}" @selected(request('priority') === $priority->value)>{{ Str::headline($priority->value) }}</option>
            @endforeach
        </select>

        <select name="category" aria-label="Category">
            <option value="">All categories</option>
            @foreach (App\Enums\IssueCategory::cases() as $category)
                <option value="{{ $category->value }}" @selected(request('category') === $category->value)>{{ Str::headline($category->value) }}</option>
            @endforeach
        </select>

        <select name="sort" aria-label="Sort by">
            <option value="created_at" @selected(request('sort') === 'created_at')>Newest</option>
            <option value="updated_at" @selected(request('sort') === 'updated_at')>Recently updated</option>
            <option value="priority" @selected(request('sort') === 'priority')>Priority</option>
        </select>

        <button type="submit" class="button">Filter</button>
        <a href="{{ route('issues.index') }}" class="link">Reset</a>
    </form>

    @if ($errors->any())
        <div class="error-box">{{ $errors->first() }}</div>
    @endif

    @if ($issues->isEmpty())
        <p class="empty">No issues match these filters.</p>
    @else
        <div class="table-wrap">
            <table class="issues">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Priority</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Comments</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($issues as $issue)
                        <tr @class(['attention' => $issue->needs_attention])>
                            <td>
                                <a href="{{ route('issues.show', $issue) }}">{{ $issue->title }}</a>
                                @if ($issue->needs_attention)
                                    <span class="tag tag-attention">Needs attention</span>
                                @endif
                            </td>
                            <td><span class="tag tag-{{ $issue->priority->value }}">{{ Str::headline($issue->priority->value) }}</span></td>
                            <td>{{ Str::headline($issue->category->value) }}</td>
                            <td>{{ Str::headline($issue->status->value) }}</td>
                            <td>{{ $issue->comments_count }}</td>
                            <td title="{{ $issue->created_at }}">{{ $issue->created_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $issues->links('pagination::simple-bootstrap-5') }}
    @endif
@endsection
