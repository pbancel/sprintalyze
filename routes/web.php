<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\JiraController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Jira routes
    Route::get('/jira-connect', [JiraController::class, 'connect'])->name('jira.connect');
    Route::get('/jira/callback', [JiraController::class, 'callback'])->name('jira.callback');
    Route::get('/jira/test-connection', [JiraController::class, 'testConnection'])->name('jira.test');
    Route::get('/jira/projects', [JiraController::class, 'getProjects'])->name('jira.projects');
    Route::get('/jira/project/{projectKey}', [JiraController::class, 'getProject'])->name('jira.project');
    Route::get('/jira/search', [JiraController::class, 'searchIssues'])->name('jira.search');
    Route::get('/jira/board/{boardId}/sprints', [JiraController::class, 'getBoardSprints'])->name('jira.board.sprints');
    Route::get('/jira/sprint/{sprintId}/issues', [JiraController::class, 'getSprintIssues'])->name('jira.sprint.issues');
});

require __DIR__.'/auth.php';
