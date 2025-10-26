<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\JiraController;
use App\Http\Controllers\MonitoredUserController;
use App\Http\Controllers\ManageInstancesController;
use App\Http\Controllers\ManageIssuesController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Guest Jira OAuth routes (for login/registration)
Route::get('/jira/authorize', [JiraController::class, 'authorize'])->name('jira.authorize');
Route::get('/jira/callback', [JiraController::class, 'callback'])->name('jira.callback');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Jira routes (authenticated)
    Route::get('/jira/test-connection', [JiraController::class, 'testConnection'])->name('jira.test');
    Route::get('/jira/projects', [JiraController::class, 'getProjects'])->name('jira.projects');
    Route::get('/jira/project/{projectKey}', [JiraController::class, 'getProject'])->name('jira.project');
    Route::get('/jira/search', [JiraController::class, 'searchIssues'])->name('jira.search');
    Route::get('/jira/board/{boardId}/sprints', [JiraController::class, 'getBoardSprints'])->name('jira.board.sprints');
    Route::get('/jira/sprint/{sprintId}/issues', [JiraController::class, 'getSprintIssues'])->name('jira.sprint.issues');

    // Manage Users routes
    Route::get('/manage/users', [MonitoredUserController::class, 'index'])->name('monitored-users.index');
    Route::post('/manage/users/fetch', [MonitoredUserController::class, 'fetchUsers'])->name('monitored-users.fetch');
    Route::post('/manage/users', [MonitoredUserController::class, 'store'])->name('monitored-users.store');
    Route::delete('/manage/users/{id}', [MonitoredUserController::class, 'destroy'])->name('monitored-users.destroy');
    Route::patch('/manage/users/{id}/toggle', [MonitoredUserController::class, 'toggleStatus'])->name('monitored-users.toggle');

    // DataTable routes
    Route::get('/datatable/available-users.json', [MonitoredUserController::class, 'datatable'])->name('monitored-users.datatable');
    Route::get('/datatable/monitored-users.json', [MonitoredUserController::class, 'monitoredDatatable'])->name('monitored-users.monitored-datatable');

    // Manage Instances routes
    Route::get('/manage/instances', [ManageInstancesController::class, 'index'])->name('manage-instances.index');
    Route::post('/manage/instances', [ManageInstancesController::class, 'store'])->name('manage-instances.store');
    Route::delete('/manage/instances/{id}', [ManageInstancesController::class, 'destroy'])->name('manage-instances.destroy');
    Route::patch('/manage/instances/{id}/toggle', [ManageInstancesController::class, 'toggleStatus'])->name('manage-instances.toggle');

    // DataTable routes for instances
    Route::get('/datatable/available-instances.json', [ManageInstancesController::class, 'availableDatatable'])->name('manage-instances.available-datatable');
    Route::get('/datatable/monitored-instances.json', [ManageInstancesController::class, 'monitoredDatatable'])->name('manage-instances.monitored-datatable');

    // Manage Issues routes
    Route::get('/manage/issues', [ManageIssuesController::class, 'index'])->name('manage-issues.index');
    Route::post('/manage/issues', [ManageIssuesController::class, 'store'])->name('manage-issues.store');
    Route::delete('/manage/issues/{id}', [ManageIssuesController::class, 'destroy'])->name('manage-issues.destroy');
    Route::patch('/manage/issues/{id}/toggle', [ManageIssuesController::class, 'toggleStatus'])->name('manage-issues.toggle');

    // DataTable routes for issues
    Route::get('/datatable/available-issues.json', [ManageIssuesController::class, 'availableDatatable'])->name('manage-issues.available-datatable');
    Route::get('/datatable/monitored-issues.json', [ManageIssuesController::class, 'monitoredDatatable'])->name('manage-issues.monitored-datatable');
});

require __DIR__.'/auth.php';
