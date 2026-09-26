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

Route::get('/ticket-success/{ticket}', [TicketController::class, 'success'])
    ->name('tickets.success');

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