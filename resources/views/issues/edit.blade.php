@extends('layouts.app')

@section('title', 'Edit issue')

@section('content')
    <a href="{{ route('issues.show', $issue) }}" class="link">&larr; Back to issue</a>
    <h1>Edit issue</h1>

    <form method="POST" action="{{ route('issues.update', $issue) }}" class="form card">
        @csrf
        @method('PATCH')
        @include('issues._form')
        <button type="submit" class="button">Save changes</button>
    </form>
@endsection
