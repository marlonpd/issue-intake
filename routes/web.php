<?php

use App\Http\Controllers\Web\CommentController;
use App\Http\Controllers\Web\IssueController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/issues');

Route::resource('issues', IssueController::class)->except('destroy');
Route::post('issues/{issue}/comments', [CommentController::class, 'store'])->name('issues.comments.store');
