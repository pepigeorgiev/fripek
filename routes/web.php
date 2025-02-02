<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BreadTypeController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DailyTransactionController;
use App\Http\Controllers\DashboardController;
// use App\Http\Controllers\InvoiceCompaniesController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MonthlySummaryController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TransactionHistoryController;
use App\Http\Controllers\InstallController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\DailyTransaction;

// Add this at the top of your routes file
Route::get('/debug-routes', function () {
    $routes = Route::getRoutes();
    foreach ($routes as $route) {
        \Log::info('Route:', [
            'uri' => $route->uri(),
            'methods' => $route->methods(),
            'name' => $route->getName()
        ]);
    }
});

// Remove any previous root route and add this at the top
Route::middleware(['web'])->group(function () {
    // Public routes
    Route::get('/', function () {
        return redirect('/login');
    });

    Route::get('/login', [LoginController::class, 'showLoginForm'])
        ->name('login')
        ->middleware('guest');
        
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Protected routes
    Route::middleware(['auth'])->group(function () {
        Route::get('/daily-transactions/create', [DailyTransactionController::class, 'create'])
            ->name('daily-transactions.create');
        Route::post('/daily-transactions', [DailyTransactionController::class, 'store'])
            ->name('daily-transactions.store');
    });
});

// Authenticated routes
Route::middleware(['auth'])->group(function () {
    // Dashboard routes
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/export', [DashboardController::class, 'export'])->name('dashboard.export');
    
    // Company routes
    Route::resource('companies', CompanyController::class);
    Route::get('/companies/{company}/confirm-delete', [CompanyController::class, 'confirmDelete'])
        ->name('companies.confirm-delete');
    
    // Monthly Summary routes
    Route::get('/monthly-summaries', [MonthlySummaryController::class, 'index'])
        ->name('monthly-summaries.index');
    Route::get('/monthly-summary/{company}', [MonthlySummaryController::class, 'show'])
        ->name('monthly-summary.show');
    Route::get('/monthly-summary/{company}/export', [MonthlySummaryController::class, 'export'])
        ->name('monthly-summary.export');
    
    // Daily Transaction routes
    Route::resource('daily-transactions', DailyTransactionController::class);
    Route::post('/daily-transactions/mark-as-paid', [DailyTransactionController::class, 'markAsPaid'])
        ->name('daily-transactions.markAsPaid');
    Route::get('/daily-transactions/unpaid', [DailyTransactionController::class, 'getUnpaidTransactions'])
        ->name('daily-transactions.unpaid');
    
    // Summary routes
    Route::get('/summary', [SummaryController::class, 'index'])->name('summary.index');
    Route::post('/summary/update', [SummaryController::class, 'update'])->name('summary.update');
    Route::post('/summary/update-additional', [SummaryController::class, 'updateAdditional'])
        ->name('summary.updateAdditional');
    
    // Bread Types routes
    Route::resource('bread-types', BreadTypeController::class);
    Route::get('bread-types/{breadType}/company-prices', [BreadTypeController::class, 'showCompanyPrices'])
        ->name('bread-types.companyPrices');
    Route::post('bread-types/{breadType}/company-prices', [BreadTypeController::class, 'updateCompanyPrices'])
        ->name('bread-types.updateCompanyPrices');
    
    // Invoice routes
    Route::get('/invoice-companies', [InvoiceController::class, 'index'])->name('invoice-companies.index');
    Route::post('/invoice-companies/export', [InvoiceController::class, 'export'])
        ->name('invoice-companies.export');
    Route::get('/invoice-companies/download/{exportJob}', [InvoiceController::class, 'download'])
        ->name('invoice-companies.download');

    // Transaction history
    Route::get('/transactions/history', [TransactionHistoryController::class, 'index'])
        ->name('transaction.history')
        ->middleware('auth');

    // User Management (Super Admin only)
    Route::get('/users/manage', [UsersController::class, 'manage'])->name('users.manage');
    Route::post('/users', [UsersController::class, 'store'])->name('users.store');
    Route::delete('/users/{user}', [UsersController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/update-password', [UsersController::class, 'updatePassword'])
        ->name('users.update-password');
    Route::post('/users/{user}/reset-password', [UsersController::class, 'resetPassword'])
        ->name('users.reset-password');
});

Route::get('/install-app', [App\Http\Controllers\InstallController::class, 'show'])
    ->name('install.show')
    ->middleware('auth');

Route::get('/check-schema', function() {
    $table = DB::select('DESCRIBE bread_types');
    dd($table);
});

Route::get('/test-history', function() {
    Log::info('Testing history tracking');
    
    $transaction = DailyTransaction::first();
    
    if (!$transaction) {
        return "No transactions found";
    }
    
    // Force an update to test history tracking
    $transaction->delivered = $transaction->delivered + 1;
    $transaction->save();
    
    return "Test completed - check logs";
});

// Add this temporary test route
Route::get('/test-tracking', function() {
    Log::info('=== TESTING HISTORY TRACKING ===');
    
    // Get a transaction
    $transaction = \App\Models\DailyTransaction::first();
    
    if (!$transaction) {
        return "No transaction found";
    }
    
    Log::info('Found transaction', [
        'id' => $transaction->id,
        'delivered' => $transaction->delivered
    ]);
    
    // Make a change
    $oldDelivered = $transaction->delivered;
    $transaction->delivered = $oldDelivered + 1;
    
    Log::info('Making change', [
        'old_delivered' => $oldDelivered,
        'new_delivered' => $transaction->delivered
    ]);
    
    // Try to save
    $transaction->save();
    
    // Check history
    $history = \App\Models\TransactionHistory::where('transaction_id', $transaction->id)
        ->latest()
        ->first();
    
    Log::info('History check', [
        'was_recorded' => (bool)$history,
        'history' => $history ? $history->toArray() : null
    ]);
    
    return [
        'transaction_id' => $transaction->id,
        'change_made' => true,
        'history_recorded' => (bool)$history
    ];
});

Route::get('/debug-history', function() {
    \Illuminate\Support\Facades\Log::info('=== START HISTORY DEBUG ===');
    
    try {
        // 1. Get a transaction
        $transaction = \App\Models\DailyTransaction::first();
        
        if (!$transaction) {
            \Illuminate\Support\Facades\Log::error('No transaction found');
            return "No transaction found";
        }
        
        \Illuminate\Support\Facades\Log::info('Transaction found', [
            'id' => $transaction->id,
            'delivered' => $transaction->delivered,
            'date' => $transaction->transaction_date
        ]);
        
        // 2. Create history record directly
        $history = new \App\Models\TransactionHistory();
        $history->transaction_id = $transaction->id;
        $history->user_id = auth()->id();
        $history->action = 'test';
        $history->old_values = ['delivered' => $transaction->delivered];
        $history->new_values = ['delivered' => $transaction->delivered + 1];
        $history->ip_address = request()->ip();
        
        \Illuminate\Support\Facades\Log::info('Attempting to save history', [
            'history_data' => $history->toArray()
        ]);
        
        $history->save();
        
        \Illuminate\Support\Facades\Log::info('History saved successfully', [
            'history_id' => $history->id
        ]);
        
        return [
            'success' => true,
            'history_id' => $history->id
        ];
        
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Error in debug route', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
});

// Add this route for CSRF token refresh
Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
})->middleware('web');