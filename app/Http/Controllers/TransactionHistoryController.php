<?php

namespace App\Http\Controllers;

use App\Models\TransactionHistory;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class TransactionHistoryController extends Controller
{
    public function index(Request $request)
    {
        // Default date range (last 7 days)
        $date_from = Carbon::parse($request->get('date_from', now()->subDays(7)));
        $date_to = Carbon::parse($request->get('date_to', now()));

        $query = TransactionHistory::with(['transaction.company', 'transaction.breadType', 'user'])
            ->orderBy('created_at', 'desc');

        // Filter by date range
        $query->whereBetween('created_at', [
            $date_from->startOfDay(),
            $date_to->endOfDay()
        ]);

        // Filter by company
        if ($request->filled('company_id')) {
            $query->whereHas('transaction', function($q) use ($request) {
                $q->where('company_id', $request->company_id);
            });
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter significant changes (>10 pieces)
        if ($request->filled('significant_only')) {
            $query->where(function($q) {
                $q->whereRaw("ABS(CAST(JSON_EXTRACT(new_values, '$.delivered') AS SIGNED) - 
                             CAST(JSON_EXTRACT(old_values, '$.delivered') AS SIGNED)) > 10")
                  ->orWhereRaw("ABS(CAST(JSON_EXTRACT(new_values, '$.returned') AS SIGNED) - 
                               CAST(JSON_EXTRACT(old_values, '$.returned') AS SIGNED)) > 10");
            });
        }

        // Filter past date changes
        if ($request->filled('past_date_changes')) {
            $query->whereHas('transaction', function($q) {
                $q->whereRaw('transaction_date < DATE(created_at)');
            });
        }

        // Get companies and users for filters
        $companies = Company::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        $history = $query->paginate(20);

        return view('transactions.history', compact(
            'history',
            'companies',
            'users',
            'date_from',
            'date_to'
        ));
    }
} 