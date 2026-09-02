<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeatureSetController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\MentionController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\PhaseController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ScopeItemController;
use App\Http\Controllers\ServiceLineController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\SubtaskController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TenderController;
use App\Http\Controllers\TrackerItemController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// --- Guest-only routes (no public registration) ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// --- Authenticated app ---
Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    // Tenders
    Route::get('tenders', [TenderController::class, 'index'])->name('tenders.index');
    Route::get('tenders/create', [TenderController::class, 'create'])->name('tenders.create')->middleware('can:tenders.create');
    Route::post('tenders', [TenderController::class, 'store'])->name('tenders.store')->middleware('can:tenders.create');
    Route::get('tenders/{tender}', [TenderController::class, 'show'])->name('tenders.show');
    Route::get('tenders/{tender}/edit', [TenderController::class, 'edit'])->name('tenders.edit')->middleware('can:tenders.edit');
    Route::put('tenders/{tender}', [TenderController::class, 'update'])->name('tenders.update')->middleware('can:tenders.edit');
    Route::patch('tenders/{tender}/transition', [TenderController::class, 'transition'])->name('tenders.transition')->middleware('can:tenders.transition');
    Route::patch('tenders/{tender}/promote', [TenderController::class, 'promote'])->name('tenders.promote')->middleware('can:projects.initiate');

    // Service requests
    Route::resource('service-requests', ServiceRequestController::class);
    Route::patch('service-requests/{service_request}/transition', [ServiceRequestController::class, 'transition'])->name('service-requests.transition')->middleware('can:service_requests.transition');
    Route::patch('service-requests/{service_request}/promote', [ServiceRequestController::class, 'promote'])->name('service-requests.promote')->middleware('can:projects.initiate');

    // Projects + SDLC hierarchy
    Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::middleware('can:projects.initiate')->group(function () {
        Route::get('projects/create', [ProjectController::class, 'create'])->name('projects.create');
        Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
    });
    Route::middleware('can:projects.edit')->group(function () {
        Route::get('projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
        Route::put('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::patch('phases/{phase}/sign-off', [PhaseController::class, 'signOff'])->name('phases.sign-off');
    });
    Route::get('projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::resource('projects.phases', PhaseController::class)->shallow()->only(['store', 'update', 'destroy'])->middleware('can:projects.manage_work');
    Route::resource('phases.milestones', MilestoneController::class)->shallow()->only(['store', 'update', 'destroy'])->middleware('can:projects.manage_work');
    Route::resource('milestones.feature-sets', FeatureSetController::class)->shallow()->only(['store', 'update', 'destroy'])->middleware('can:projects.manage_work');
    Route::resource('feature-sets.tasks', TaskController::class)->shallow()->only(['store', 'update', 'destroy'])->middleware('can:projects.manage_work');
    Route::resource('tasks.subtasks', SubtaskController::class)->shallow()->only(['store', 'update', 'destroy']);
    Route::patch('tasks/{task}/toggle', [TaskController::class, 'toggle'])->name('tasks.toggle');
    Route::patch('subtasks/{subtask}/toggle', [SubtaskController::class, 'toggle'])->name('subtasks.toggle');
    Route::get('my-work', [TaskController::class, 'mine'])->name('tasks.mine');

    // Requirements traceability
    Route::middleware('can:projects.manage_work')->group(function () {
        Route::post('projects/{project}/scope-items', [ScopeItemController::class, 'store'])->name('scope-items.store');
        Route::put('scope-items/{scopeItem}', [ScopeItemController::class, 'update'])->name('scope-items.update');
        Route::delete('scope-items/{scopeItem}', [ScopeItemController::class, 'destroy'])->name('scope-items.destroy');
    });

    // Generic tracker
    Route::resource('tracker', TrackerItemController::class)->parameters(['tracker' => 'trackerItem']);

    // Comments + attachments (polymorphic)
    Route::get('mentions', [MentionController::class, 'index'])->name('mentions.index');
    Route::post('comments/preview', [CommentController::class, 'preview'])->name('comments.preview');
    Route::post('{type}/{id}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    Route::post('{type}/{id}/attachments', [AttachmentController::class, 'store'])->name('attachments.store');
    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::patch('notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');

    // Reports + audit
    Route::get('reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::get('audit', [AuditController::class, 'index'])->name('audit.index')->middleware('can:audit.view');

    // Admin
    Route::middleware('can:users.manage')->group(function () {
        Route::resource('users', UserController::class)->except('show');
        Route::get('import', [ImportController::class, 'create'])->name('import.create');
        Route::post('import', [ImportController::class, 'store'])->name('import.store');
    });
    Route::resource('service-lines', ServiceLineController::class)->except('show')->middleware('can:services.manage');
});
