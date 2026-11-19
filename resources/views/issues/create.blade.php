@extends('layouts.app')

@section('title', 'New issue')

@section('content')
    <a href="{{ route('issues.index') }}" class="link">&larr; All issues</a>
    <h1>New issue</h1>

    <form method="POST" action="{{ route('issues.store') }}" class="form card">
        @csrf
        @include('issues._form')
        <button type="submit" class="button">Create issue</button>
    </form>
@endsection
