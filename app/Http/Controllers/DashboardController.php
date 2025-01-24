<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\DailyTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller 
{
    public function index(Request $request)
    {
        $currentUser = Auth::user();
        
        // Get all users for the dropdown (excluding super admin)
        $users = User::where('role', '!=', 'super_admin')
                    ->orderBy('name')
                    ->get();
        
        // Get selected user ID from request or null for "all users"
        $selectedUserId = $request->get('user_id');
        
        // Get companies based on user role and selection
        if ($currentUser->isAdmin() || $currentUser->role === 'super_admin') {
            if ($selectedUserId) {
                $companies = Company::whereHas('users', function($query) use ($selectedUserId) {
                    $query->where('users.id', $selectedUserId);
                })->get();
            } else {
                $companies = Company::all();
            }
        } else {
            $companies = $currentUser->companies;
        }
        
        // Get selected date from request or default to today
        $selectedDate = $request->get('date', now()->toDateString());
        $selectedDateTime = now()->create($selectedDate);
        
        $month = $selectedDateTime->format('m');
        $year = $selectedDateTime->format('Y');
        
        // Get transactions for selected date with created_at time
        $todaysTransactions = DailyTransaction::with(['company', 'breadType'])
            ->whereIn('company_id', $companies->pluck('id'))
            ->whereDate('transaction_date', $selectedDate)
            ->get()
            ->groupBy('company_id');
        
        // Get monthly summary for the month of the selected date
        $monthlyTransactions = DailyTransaction::with(['company', 'breadType'])
            ->whereIn('company_id', $companies->pluck('id'))
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->get()
            ->map(function($transaction) {
                $transaction->transaction_date = Carbon::parse($transaction->transaction_date);
                return $transaction;
            })
            ->groupBy(function($transaction) {
                return $transaction->transaction_date->format('Y-m-d');
            });
        
        return view('dashboard', compact(
            'companies',
            'todaysTransactions',
            'monthlyTransactions',
            'selectedDate',
            'users',
            'selectedUserId',
            'currentUser'
        ));
    }
}