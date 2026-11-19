<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\IssueController;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function () {
    Route::apiResource('issues', IssueController::class)->except('destroy');
    Route::post('issues/{issue}/comments', [CommentController::class, 'store'])->name('issues.comments.store');
});
