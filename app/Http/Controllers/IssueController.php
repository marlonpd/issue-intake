<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListIssuesRequest;
use App\Http\Requests\StoreIssueRequest;
use App\Http\Requests\UpdateIssueRequest;
use App\Http\Resources\IssueResource;
use App\Models\Issue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IssueController extends Controller
{
    public function index(ListIssuesRequest $request): AnonymousResourceCollection
    {
        $issues = Issue::query()
            ->filter($request->validated())
            ->sorted($request->input('sort', 'created_at'), $request->input('direction', 'desc'))
            ->withCount('comments')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return IssueResource::collection($issues);
    }

    public function store(StoreIssueRequest $request): JsonResponse
    {
        $issue = Issue::create($request->validated());

        return (new IssueResource($issue))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Issue $issue): IssueResource
    {
        $issue->load('comments');

        return new IssueResource($issue);
    }

    public function update(UpdateIssueRequest $request, Issue $issue): IssueResource
    {
        $issue->update($request->validated());

        return new IssueResource($issue);
    }
}
