<?php



use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BreadTypeController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DailyTransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MonthlySummaryController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\TransactionHistoryController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\BreadTypeOrderController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Debug route - keep this for troubleshooting
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

// Public routes
Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])
    ->name('login')
    ->middleware('guest');
    
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Protected routes - all routes that require authentication
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/export', [DashboardController::class, 'export'])->name('dashboard.export');
    
    // Companies
    Route::resource('companies', CompanyController::class);
    Route::get('/companies/{company}/confirm-delete', [CompanyController::class, 'confirmDelete'])
        ->name('companies.confirm-delete');
    Route::post('companies/bulk-assign-user', [CompanyController::class, 'bulkAssignUser'])
        ->name('companies.bulk-assign-user');   
        // Manage bread types for companies
Route::get('/companies/{company}/manage-bread-types', [CompanyController::class, 'manageBreadTypes'])
->name('companies.manage-bread-types');
Route::put('/companies/{company}/update-bread-types', [CompanyController::class, 'updateBreadTypes'])
->name('companies.update-bread-types'); 
    
    // Monthly Summaries
    Route::get('/monthly-summaries', [MonthlySummaryController::class, 'index'])
        ->name('monthly-summaries.index');
    Route::get('/monthly-summary/{company}', [MonthlySummaryController::class, 'show'])
        ->name('monthly-summary.show');
    Route::get('/monthly-summary/{company}/export', [MonthlySummaryController::class, 'export'])
        ->name('monthly-summary.export');
    
    // Daily Transactions
    Route::resource('daily-transactions', DailyTransactionController::class);
    Route::post('/daily-transactions/mark-as-paid', [DailyTransactionController::class, 'markAsPaid'])
        ->name('daily-transactions.markAsPaid');
    Route::post('/daily-transactions/mark-multiple-as-paid', [SummaryController::class, 'markMultipleAsPaid'])
        ->name('daily-transactions.markMultipleAsPaid');
    Route::get('/daily-transactions/unpaid', [DailyTransactionController::class, 'getUnpaidTransactions'])
        ->name('daily-transactions.unpaid');
    Route::post('/daily-transactions/store-old-bread', [DailyTransactionController::class, 'storeOldBreadSales'])
        ->name('daily-transactions.store-old-bread');
    Route::post('/update-daily-transaction', [DailyTransactionController::class, 'updateDailyTransaction'])
        ->name('update-daily-transaction');
    
    // Summary
    Route::get('/summary', [SummaryController::class, 'index'])->name('summary.index');
    Route::post('/summary/update', [SummaryController::class, 'update'])->name('summary.update');
    Route::post('/summary/update-additional', [SummaryController::class, 'updateAdditional'])
        ->name('summary.updateAdditional');
    
    // Bread Types
    // Add admin middleware to bread-type-order routes
// Change this in your routes/web.php file
Route::middleware(['auth'])->group(function () {
    Route::get('/bread-types/order', [BreadTypeOrderController::class, 'index'])->name('bread-types.order.index');
    Route::post('/bread-types/order', [BreadTypeOrderController::class, 'update'])->name('bread-types.order.update');
});
    Route::resource('bread-types', BreadTypeController::class);
    Route::get('bread-types/{breadType}/company-prices', [BreadTypeController::class, 'showCompanyPrices'])
        ->name('bread-types.companyPrices');
    Route::post('bread-types/{breadType}/company-prices/{company}', [BreadTypeController::class, 'updateCompanyPrices'])
        ->name('bread-types.updateCompanyPrices');
    
    // Invoice
    Route::get('/invoice-companies', [InvoiceController::class, 'index'])->name('invoice-companies.index');
    Route::post('/invoice-companies/export', [InvoiceController::class, 'export'])
        ->name('invoice-companies.export');
    Route::get('/invoice-companies/download/{exportJob}', [InvoiceController::class, 'download'])
        ->name('invoice-companies.download');

    // Transaction history
    Route::get('/transactions/history', [TransactionHistoryController::class, 'index'])
        ->name('transaction.history');

    // User Management (Super Admin only)
    Route::get('/users/manage', [UsersController::class, 'manage'])->name('users.manage');
    Route::post('/users', [UsersController::class, 'store'])->name('users.store');
    Route::delete('/users/{user}', [UsersController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/update-password', [UsersController::class, 'updatePassword'])
        ->name('users.update-password');
    Route::post('/users/{user}/reset-password', [UsersController::class, 'resetPassword'])
        ->name('users.reset-password');
    
    // Install
    Route::get('/install-app', [InstallController::class, 'show'])
        ->name('install.show');
});

Route::get('/api/bread-type-price/{breadTypeId}/{companyId}', 'ApiController@getBreadTypePrice');

Route::get('/api/get-bread-price/{breadTypeId}/{companyId}', [App\Http\Controllers\DailyTransactionController::class, 'getBreadTypePrice']);


Route::get('/api/refresh-csrf', function () {
    return response()->json([
        'token' => csrf_token(),
    ]);
});


Route::get('/api/refresh-csrf', function () {
    return response()->json([
        'token' => csrf_token(),
    ]);
});

Route::get('/api/check-session', function () {
    return response()->json([
        'status' => 'active',
        'timestamp' => now()->timestamp
    ]);
});






Route::post('/summary/update-yesterday', [SummaryController::class, 'updateYesterday'])->name('summary.updateYesterday');

// Schema check for debugging
Route::get('/check-schema', function() {
    $table = DB::select('DESCRIBE bread_types');
    dd($table);
});

