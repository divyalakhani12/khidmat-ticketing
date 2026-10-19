<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;

Route::get('/', function () {
    return redirect()->route('tickets.create');
});

Route::get('/submit-ticket', [TicketController::class, 'create'])
    ->name('tickets.create');

Route::post('/submit-ticket', [TicketController::class, 'store'])
    ->name('tickets.store');

Route::get('/ticket-success/{ticket:tracking_token}', [TicketController::class, 'success'])
    ->name('tickets.success');

Route::get('/track/{ticket:tracking_token}', [TicketController::class, 'track'])
    ->name('tickets.track');

Route::get('/submitted', [TicketController::class, 'submitted'])
    ->name('tickets.submitted');

Route::get('/new-ticket', [TicketController::class, 'resetForm'])
    ->name('tickets.reset');

Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [AuthController::class, 'login'])
    ->name('login.submit');

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

Route::post('/dashboard/tickets/{ticket}/status', [DashboardController::class, 'updateStatus'])
    ->middleware('auth')
    ->name('dashboard.tickets.status');
Route::get('/dashboard/tickets/{ticket}', [DashboardController::class, 'show'])
    ->middleware('auth')
    ->name('dashboard.tickets.show');
Route::post('/dashboard/tickets/{ticket}/assign', [DashboardController::class, 'assign'])
    ->middleware('auth')
    ->name('dashboard.tickets.assign');
Route::post('/dashboard/tickets/{ticket}/comments', [DashboardController::class, 'addComment'])
    ->middleware('auth')
    ->name('dashboard.tickets.comments');
Route::get('/dashboard/tickets/{ticket}/attachments/{attachment}', [DashboardController::class, 'downloadAttachment'])
    ->middleware('auth')
    ->name('dashboard.tickets.attachments.download');

Route::get('/track/{ticket:tracking_token}/attachments/{attachment}', [TicketController::class, 'downloadAttachment'])
    ->name('tickets.track.attachments.download');