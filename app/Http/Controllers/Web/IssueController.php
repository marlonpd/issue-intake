<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListIssuesRequest;
use App\Http\Requests\StoreIssueRequest;
use App\Http\Requests\UpdateIssueRequest;
use App\Models\Issue;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class IssueController extends Controller
{
    public function index(ListIssuesRequest $request): View
    {
        $issues = Issue::query()
            ->filter($request->validated())
            ->sorted($request->input('sort', 'created_at'), $request->input('direction', 'desc'))
            ->withCount('comments')
            ->paginate(15)
            ->withQueryString();

        return view('issues.index', ['issues' => $issues]);
    }

    public function create(): View
    {
        return view('issues.create', ['issue' => new Issue]);
    }

    public function store(StoreIssueRequest $request): RedirectResponse
    {
        $issue = Issue::create($request->validated());

        return redirect()->route('issues.show', $issue)
            ->with('status', 'Issue created. The summary is being generated.');
    }

    public function show(Issue $issue): View
    {
        $issue->load('comments');

        return view('issues.show', ['issue' => $issue]);
    }

    public function edit(Issue $issue): View
    {
        return view('issues.edit', ['issue' => $issue]);
    }

    public function update(UpdateIssueRequest $request, Issue $issue): RedirectResponse
    {
        $issue->update($request->validated());

        return redirect()->route('issues.show', $issue)->with('status', 'Issue updated.');
    }
}
