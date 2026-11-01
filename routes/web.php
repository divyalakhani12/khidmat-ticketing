<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\SlaSettingController;
use App\Http\Controllers\AdminController;

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

Route::get(
    '/track/{ticket:tracking_token}/messages',
    [TicketController::class, 'getMessages']
)->name('tickets.track.messages.get');

Route::post('/login', [AuthController::class, 'login'])
    ->name('login.submit');

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');

Route::post(
    '/dashboard/tickets/{ticket}/merge',
    [DashboardController::class, 'merge']
)->middleware('auth')->name('dashboard.tickets.merge');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

Route::get('/dashboard/reports', [DashboardController::class, 'report'])
    ->middleware('auth')
    ->name('dashboard.reports');

Route::get('/dashboard/tickets-export', [DashboardController::class, 'export'])
    ->middleware('auth')
    ->name('dashboard.export');

Route::prefix('dashboard/admin')->middleware('auth')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.index');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::post('/branches', [AdminController::class, 'storeBranch'])->name('admin.branches.store');
    Route::put('/branches/{branch}', [AdminController::class, 'updateBranch'])->name('admin.branches.update');
    Route::post('/departments', [AdminController::class, 'storeDepartment'])->name('admin.departments.store');
    Route::put('/departments/{department}', [AdminController::class, 'updateDepartment'])->name('admin.departments.update');
    Route::post('/categories', [AdminController::class, 'storeCategory'])->name('admin.categories.store');
    Route::put('/categories/{category}', [AdminController::class, 'updateCategory'])->name('admin.categories.update');
});

Route::post('/dashboard/tickets/{ticket}/priority', [DashboardController::class, 'updatePriority'])
    ->middleware('auth')
    ->name('dashboard.tickets.priority');

Route::post('/dashboard/tickets/{ticket}/transfer', [DashboardController::class, 'transferBranch'])
    ->middleware('auth')
    ->name('dashboard.tickets.transfer');

Route::post('/dashboard/tickets/{ticket}/status', [DashboardController::class, 'updateStatus'])
    ->middleware('auth')
    ->name('dashboard.tickets.status');

Route::get('/dashboard/tickets/{ticket}', [DashboardController::class, 'show'])
    ->middleware('auth')
    ->name('dashboard.tickets.show');

Route::get('/dashboard/tickets/{ticket}/messages', [DashboardController::class, 'getMessages'])
    ->middleware('auth')
    ->name('dashboard.tickets.messages.get');

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

Route::get('/dashboard/settings/sla', [SlaSettingController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard.sla');

Route::post('/dashboard/settings/sla', [SlaSettingController::class, 'update'])
    ->middleware('auth')
    ->name('dashboard.sla.update');

Route::post('/track/{ticket:tracking_token}/cancel', [TicketController::class, 'cancel'])
    ->name('tickets.track.cancel');

Route::post('/track/{ticket:tracking_token}/approve', [TicketController::class, 'approveResolution'])
    ->name('tickets.track.approve');

Route::post('/track/{ticket:tracking_token}/reject', [TicketController::class, 'rejectResolution'])
    ->name('tickets.track.reject');

Route::post('/dashboard/tickets/{ticket}/cancel', [DashboardController::class, 'cancel'])
    ->middleware('auth')
    ->name('dashboard.tickets.cancel');

Route::post(
    '/track/{ticket:tracking_token}/messages',
    [TicketController::class, 'sendMessage']
)->name('tickets.track.messages');
