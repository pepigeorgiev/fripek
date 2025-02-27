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
use App\Http\Controllers\LogController;
use App\Http\Controllers\DailyTransactionsController;
use App\Http\Controllers\OldBreadSalesController;
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
        Route::get('/daily-transactions/create', [DailyTransactionsController::class, 'create'])
            ->name('daily-transactions.create');
        Route::post('/daily-transactions', [DailyTransactionController::class, 'store'])
            ->name('daily-transactions.store');
            Route::post('bread-types/{breadType}/company-prices/{company}', [BreadTypeController::class, 'updateCompanyPrices'])
            ;
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
    Route::post('companies/bulk-assign-user', [CompanyController::class, 'bulkAssignUser'])->name('companies.bulk-assign-user');    
    
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
    Route::post('/daily-transactions/store-old-bread', [DailyTransactionController::class, 'storeOldBreadSales'])->name('daily-transactions.store-old-bread');
    // In your routes file
// Route::post('/update-daily-transaction', [DailyTransactionController::class, 'updateDailyTransaction'])
// ->name('update-daily-transaction');
Route::post('/daily-transactions/old-bread', [DailyTransactionController::class, 'storeOldBreadSales'])
    ->name('daily-transactions.store-old-bread');
    Route::post('/store-old-bread', [DailyTransactionController::class, 'storeOldBreadSales'])
    ->name('daily-transactions.store-old-bread');
// Route::post('/update', [DailyTransactionController::class, 'updateDailyTransaction'])
//     ->name('update-daily-transaction');
    Route::post('/store-old-bread', [DailyTransactionController::class, 'storeOldBreadSales'])
    ->name('daily-transactions.store-old-bread');
    Route::post('/daily-transactions/update', [DailyTransactionController::class, 'updateDailyTransaction'])
    ->name('update-daily-transaction');
    Route::post('/update-daily-transaction', [\App\Http\Controllers\DailyTransactionController::class, 'updateDailyTransaction'])->name('update-daily-transaction');
    Route::middleware(['auth'])->group(function () {
        // Daily Transactions routes
        Route::prefix('daily-transactions')->group(function () {
            Route::get('/', [DailyTransactionController::class, 'index'])
                ->name('daily-transactions.index');
            Route::get('/create', [DailyTransactionController::class, 'create'])
                ->name('daily-transactions.create');
            Route::post('/', [DailyTransactionController::class, 'store'])
                ->name('daily-transactions.store');
            Route::post('/store-old-bread', [DailyTransactionController::class, 'storeOldBreadSales'])
                ->name('daily-transactions.store-old-bread');
            // Route::post('/update', [DailyTransactionController::class, 'updateDailyTransaction'])
            //     ->name('update-daily-transaction');
                Route::post('/daily-transactions/mark-multiple-as-paid', [SummaryController::class, 'markMultipleAsPaid'])
    ->name('daily-transactions.markMultipleAsPaid');
                

        });
    });


    
    // Summary routes
    Route::get('/summary', [SummaryController::class, 'index'])->name('summary.index');
    Route::post('/summary/update', [SummaryController::class, 'update'])->name('summary.update');
    Route::post('/summary/update-additional', [SummaryController::class, 'updateAdditional'])
        ->name('summary.updateAdditional');
    
    // Bread Types routes
    Route::resource('bread-types', BreadTypeController::class);
    Route::get('bread-types/{breadType}/company-prices', [BreadTypeController::class, 'showCompanyPrices'])
        ->name('bread-types.companyPrices');
    Route::post('bread-types/{breadType}/company-prices/{company}', [BreadTypeController::class, 'updateCompanyPrices'])->name('bread-types.updateCompanyPrices');
    
        
        
        
    
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