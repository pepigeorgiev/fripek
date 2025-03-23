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

        // Always filter for changes in delivered, returned, or gratis
        $query->where(function($q) {
            $q->whereRaw("JSON_EXTRACT(new_values, '$.delivered') != JSON_EXTRACT(old_values, '$.delivered')")
              ->orWhereRaw("JSON_EXTRACT(new_values, '$.returned') != JSON_EXTRACT(old_values, '$.returned')")
              ->orWhereRaw("JSON_EXTRACT(new_values, '$.gratis') != JSON_EXTRACT(old_values, '$.gratis')");
        });

        // Filter for outside working hours if requested
        if ($request->filled('outside_hours_only')) {
            $query->where(function($q) {
                // Hours outside 5:00-11:00 (so 11:00-23:59 and 00:00-04:59)
                $q->whereRaw('HOUR(created_at) >= 11')
                  ->orWhereRaw('HOUR(created_at) < 5');
            });
        }

        // Filter past date changes
        if ($request->filled('past_date_changes')) {
            $query->where('date_change_type', 'past');
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