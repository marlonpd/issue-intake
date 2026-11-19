@extends('layouts.app')

@section('title', $issue->title)

@if ($issue->summary_status === App\Enums\SummaryStatus::Pending)
    @push('head')
        <meta http-equiv="refresh" content="3">
    @endpush
@endif

@section('content')
    <a href="{{ route('issues.index') }}" class="link">&larr; All issues</a>

    <div class="issue-header">
        <h1>{{ $issue->title }}</h1>
        <a href="{{ route('issues.edit', $issue) }}" class="button button-secondary">Edit</a>
    </div>

    <p class="meta">
        <span class="tag tag-{{ $issue->priority->value }}">{{ Str::headline($issue->priority->value) }}</span>
        @if ($issue->needs_attention)
            <span class="tag tag-attention">Needs attention</span>
        @endif
        {{ Str::headline($issue->category->value) }} · {{ Str::headline($issue->status->value) }}
        · opened {{ $issue->created_at->diffForHumans() }}
    </p>

    <section class="card summary">
        <h2>Summary</h2>
        @switch($issue->summary_status)
            @case(App\Enums\SummaryStatus::Ready)
                <p>{{ $issue->summary }}</p>
                <p><strong>Next action:</strong> {{ $issue->suggested_next_action }}</p>
                @break
            @case(App\Enums\SummaryStatus::Failed)
                <p class="muted">The summary could not be generated.</p>
                @break
            @default
                <p class="muted">Generating summary&hellip; this page refreshes automatically.</p>
        @endswitch
    </section>

    <section class="card">
        <h2>Description</h2>
        <p class="description">{{ $issue->description }}</p>
    </section>

    <section class="card" id="comments">
        <h2>Comments ({{ $issue->comments->count() }})</h2>

        @forelse ($issue->comments as $comment)
            <div class="comment">
                <p class="comment-meta"><strong>{{ $comment->author_name }}</strong> · {{ $comment->created_at->diffForHumans() }}</p>
                <p class="description">{{ $comment->body }}</p>
            </div>
        @empty
            <p class="muted">No comments yet.</p>
        @endforelse

        <form method="POST" action="{{ route('issues.comments.store', $issue) }}" class="form comment-form">
            @csrf
            <label>
                Your name
                <input type="text" name="author_name" value="{{ old('author_name') }}" required>
                @error('author_name') <span class="error">{{ $message }}</span> @enderror
            </label>
            <label>
                Comment
                <textarea name="body" rows="3" required>{{ old('body') }}</textarea>
                @error('body') <span class="error">{{ $message }}</span> @enderror
            </label>
            <button type="submit" class="button">Add comment</button>
        </form>
    </section>
@endsection
