<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Models\Issue;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, Issue $issue): RedirectResponse
    {
        $issue->comments()->create($request->validated());

        return redirect()->to(route('issues.show', $issue).'#comments')->with('status', 'Comment added.');
    }
}
