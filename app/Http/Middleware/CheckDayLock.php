<?php

namespace App\Http\Middleware;

use App\Models\LockedDay;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckDayLock
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        // Admins and super admins bypass this check
        if ($user && ($user->isAdmin() || $user->role === 'super_admin')) {
            return $next($request);
        }

        // Get date from various possible request parameters
        $date = $request->input('date') ?? 
                $request->input('transaction_date') ?? 
                $request->route('date') ??
                now()->toDateString();
        
        // For company_id_date format transactions
        $selectedTransactions = $request->input('selected_transactions', []);
        foreach ($selectedTransactions as $transaction) {
            if (strpos($transaction, '_') !== false) {
                list(, $transactionDate) = explode('_', $transaction);
                if (LockedDay::isLocked($transactionDate, $user->id)) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Еден или повеќе избрани денови се заклучени.'
                        ], 403);
                    }
                    return redirect()->back()->with('error', 'Еден или повеќе избрани денови се заклучени.');
                }
            }
        }

        // For company_id and date parameters
        if ($request->has('company_id') && $request->has('date')) {
            $date = $request->input('date');
        }
        
        // Check if the date is locked for this user
        if ($user && LockedDay::isLocked($date, $user->id)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Овој ден е заклучен и не може да се модифицира.'
                ], 403);
            }
            
            return redirect()->back()->with('error', 'Овој ден е заклучен и не може да се модифицира.');
        }

        return $next($request);
    }
}