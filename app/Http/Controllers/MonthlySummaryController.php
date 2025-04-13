<?php

namespace App\Http\Controllers;

use App\Exports\InvoiceExport;
use App\Exports\MonthlySummaryExport;
use App\Models\Company;
use App\Models\DailyTransaction;
use App\Models\BreadType;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class MonthlySummaryController extends Controller
{

    public function show(Company $company)
{
    $user = Auth::user();
    
    if (!$user->isAdmin() && !$user->companies->contains($company->id)) {
        abort(403, 'Unauthorized access to company data.');
    }

    $month = request('month', now()->format('Y-m'));
    $dateRange = request('date_range', 'full');
    
    try {
        $monthDate = Carbon::createFromFormat('Y-m', $month);
        
        // Set date range based on selection
        if ($dateRange === 'first_half') {
            $startDate = $monthDate->copy()->startOfMonth();
            $endDate = $monthDate->copy()->startOfMonth()->addDays(14);
        } elseif ($dateRange === 'second_half') {
            $startDate = $monthDate->copy()->startOfMonth()->addDays(15);
            $endDate = $monthDate->copy()->endOfMonth();
        } else {
            // Default to full month
            $startDate = $monthDate->copy()->startOfMonth();
            $endDate = $monthDate->copy()->endOfMonth();
        }
    } catch (\Carbon\Exceptions\InvalidFormatException $e) {
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();
        $month = now()->format('Y-m');
        $dateRange = 'full';
    }

    // Get all data first
    $dailySummaries = $this->generateDailySummaries($company, $startDate, $endDate);
    $monthlyTotals = $this->calculateMonthlyTotals($dailySummaries);

    // Now filter to only include bread types that have any activity
    $filteredMonthlyTotals = [];
    foreach ($monthlyTotals as $breadTypeId => $totals) {
        // Only include bread types with any activity (delivered > 0 OR returned > 0 OR gratis > 0)
        if ($totals['delivered'] > 0 || $totals['returned'] > 0 || $totals['gratis'] > 0) {
            $filteredMonthlyTotals[$breadTypeId] = $totals;
        }
    }

    // Use the filtered totals instead of the full list
    $monthlyTotals = $filteredMonthlyTotals;

    if (request()->has('export')) {
        $exportStartDate = request('export_start_date') ? Carbon::parse(request('export_start_date')) : $startDate;
        $exportEndDate = request('export_end_date') ? Carbon::parse(request('export_end_date')) : $endDate;
        return $this->export(request('export'), $company, $exportStartDate, $exportEndDate);
    }

    return view('monthly-summaries.show', compact(
        'company',
        'dailySummaries',
        'monthlyTotals',
        'month',
        'startDate',
        'endDate',
        'dateRange'
    ));
}

    // public function show(Company $company)
    // {
    //     $user = Auth::user();
        
    //     if (!$user->isAdmin() && !$user->companies->contains($company->id)) {
    //         abort(403, 'Unauthorized access to company data.');
    //     }
    
    //     $month = request('month', now()->format('Y-m'));
    //     $dateRange = request('date_range', 'full');
        
    //     try {
    //         $monthDate = Carbon::createFromFormat('Y-m', $month);
            
    //         // Set date range based on selection
    //         if ($dateRange === 'first_half') {
    //             $startDate = $monthDate->copy()->startOfMonth();
    //             $endDate = $monthDate->copy()->startOfMonth()->addDays(14);
    //         } elseif ($dateRange === 'second_half') {
    //             $startDate = $monthDate->copy()->startOfMonth()->addDays(15);
    //             $endDate = $monthDate->copy()->endOfMonth();
    //         } else {
    //             // Default to full month
    //             $startDate = $monthDate->copy()->startOfMonth();
    //             $endDate = $monthDate->copy()->endOfMonth();
    //         }
    //     } catch (\Carbon\Exceptions\InvalidFormatException $e) {
    //         $startDate = now()->startOfMonth();
    //         $endDate = now()->endOfMonth();
    //         $month = now()->format('Y-m');
    //         $dateRange = 'full';
    //     }
    
    //     $dailySummaries = $this->generateDailySummaries($company, $startDate, $endDate);
    //     $monthlyTotals = $this->calculateMonthlyTotals($dailySummaries);
    
    //     // Fetch all bread types and their company-specific prices
    //     $breadTypes = BreadType::all()->mapWithKeys(function ($breadType) use ($company, $startDate) {
    //         $prices = $breadType->getPriceForCompany($company->id, $startDate->format('Y-m-d'));
    //         return [$breadType->id => [
    //             'name' => $breadType->name,
    //             'company_price' => $prices['price']
    //         ]];
    //     });
    
    //     // Add the company-specific prices to monthly totals
    //     foreach ($monthlyTotals as $breadTypeId => &$totals) {
    //         if (isset($breadTypes[$breadTypeId])) {
    //             $totals['company_price'] = $breadTypes[$breadTypeId]['company_price'];
    //         }
    //     }
    
    //     if (request()->has('export')) {
    //         $exportStartDate = request('export_start_date') ? Carbon::parse(request('export_start_date')) : $startDate;
    //         $exportEndDate = request('export_end_date') ? Carbon::parse(request('export_end_date')) : $endDate;
    //         return $this->export(request('export'), $company, $exportStartDate, $exportEndDate);
    //     }
    
    //     return view('monthly-summaries.show', compact(
    //         'company',
    //         'dailySummaries',
    //         'monthlyTotals',
    //         'month',
    //         'startDate',
    //         'endDate',
    //         'dateRange'
    //     ));
    // }
    
    // public function show(Company $company)
    // {
    //     $user = Auth::user();
        
    //     if (!$user->isAdmin() && !$user->companies->contains($company->id)) {
    //         abort(403, 'Unauthorized access to company data.');
    //     }
    
    //     $month = request('month', now()->format('Y-m'));
        
    //     try {
    //         $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
    //         $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
    //     } catch (\Carbon\Exceptions\InvalidFormatException $e) {
    //         $startDate = now()->startOfMonth();
    //         $endDate = now()->endOfMonth();
    //         $month = now()->format('Y-m');
    //     }
    
    //     $dailySummaries = $this->generateDailySummaries($company, $startDate, $endDate);
    //     $monthlyTotals = $this->calculateMonthlyTotals($dailySummaries);

    //     // Fetch all bread types and their company-specific prices
    //     $breadTypes = BreadType::all()->mapWithKeys(function ($breadType) use ($company, $startDate) {
    //         $prices = $breadType->getPriceForCompany($company->id, $startDate->format('Y-m-d'));
    //         return [$breadType->id => [
    //             'name' => $breadType->name,
    //             'company_price' => $prices['price']
    //         ]];
    //     });

    //     // Add the company-specific prices to monthly totals
    //     foreach ($monthlyTotals as $breadTypeId => &$totals) {
    //         if (isset($breadTypes[$breadTypeId])) {
    //             $totals['company_price'] = $breadTypes[$breadTypeId]['company_price'];
    //         }
    //     }

    //     if (request()->has('export')) {
    //         $exportStartDate = request('export_start_date') ? Carbon::parse(request('export_start_date')) : $startDate;
    //         $exportEndDate = request('export_end_date') ? Carbon::parse(request('export_end_date')) : $endDate;
    //         return $this->export(request('export'), $company, $exportStartDate, $exportEndDate);
    //     }
    
    //     return view('monthly-summaries.show', compact(
    //         'company',
    //         'dailySummaries',
    //         'monthlyTotals',
    //         'month',
    //         'startDate',
    //         'endDate'
    //     ));
    // }

    
    

    public function dailyTransaction(Request $request, Company $company)
    {
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        $breadTypes = BreadType::all();
        $transactions = DailyTransaction::where('company_id', $company->id)
            ->whereDate('transaction_date', $date)
            ->get()
            ->keyBy('bread_type_id');

        return view('daily-transactions.edit', [
            'company' => $company,
            'date' => $date,
            'breadTypes' => $breadTypes,
            'transactions' => $transactions,
        ]);
    }

    public function updateDailyTransaction(Request $request, Company $company)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'transactions' => 'required|array',
            'transactions.*.bread_type_id' => 'required|exists:bread_types,id',
            'transactions.*.delivered' => 'required|integer|min:0',
            'transactions.*.returned' => 'required|integer|min:0',
        ]);

        foreach ($data['transactions'] as $transaction) {
            DailyTransaction::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'bread_type_id' => $transaction['bread_type_id'],
                    'transaction_date' => $data['date'],
                ],
                [
                    'delivered' => $transaction['delivered'],
                    'returned' => $transaction['returned'],
                ]
            );
        }

        return redirect()->route('daily-transactions.edit', ['company' => $company, 'date' => $data['date']])
            ->with('success', 'Daily transactions updated successfully.');
    }
    

     private function getCompanyBreadPrice($company, $breadType, $date)
    {
        $prices = $breadType->getPriceForCompany($company->id, $date);
        
     
        
        return $prices['price'];
    }

    private function generateDailySummaries(Company $company, $startDate, $endDate)
    {
        $breadTypes = BreadType::all();
        
        // Debug: Log all company-specific prices at start
        foreach ($breadTypes as $breadType) {
            $companySpecificPrice = $breadType->companies()
                ->where('company_id', $company->id)
                ->where('valid_from', '<=', $startDate)
                ->orderBy('valid_from', 'desc')
                ->first();
                
        
        }
    
        $transactions = DailyTransaction::where('company_id', $company->id)
            ->whereBetween('transaction_date', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->get()
            ->groupBy(function ($transaction) {
                return $transaction->transaction_date->format('Y-m-d');
            });
    
        $dailySummaries = [];
        $currentDate = clone $startDate;
    
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $dailySummaries[$dateStr] = [];
    
            foreach ($breadTypes as $breadType) {
                $transaction = $transactions->get($dateStr, collect())->firstWhere('bread_type_id', $breadType->id);
                $total = $transaction ? ($transaction->delivered - $transaction->returned - ($transaction->gratis ?? 0)) : 0;
                
                // Get company-specific price for this date
                $price = $this->getCompanyBreadPrice($company, $breadType, $dateStr);
                
             
    
                $dailySummaries[$dateStr][$breadType->id] = [
                    'name' => $breadType->name,
                    'price' => $price,
                    'delivered' => $transaction ? $transaction->delivered : 0,
                    'returned' => $transaction ? $transaction->returned : 0,
                    'gratis' => $transaction ? ($transaction->gratis ?? 0) : 0,
                    'total' => $total,
                    'total_price' => $total * $price
                ];
            }
    
            $currentDate->addDay();
        }
    
        return $dailySummaries;
    }

    


    private function calculateMonthlyTotals($dailySummaries)
    {
        $monthlyTotals = [];
        
        // Get the first date from dailySummaries to get initial prices
        $firstDate = array_key_first($dailySummaries);
        
        foreach ($dailySummaries as $dayData) {
            foreach ($dayData as $breadTypeId => $data) {
                if (!isset($monthlyTotals[$breadTypeId])) {
                    // Use the price from the first day's data
                    $monthlyTotals[$breadTypeId] = [
                        'name' => $data['name'],
                        'price' => $data['price'],
                        'company_price' => $dailySummaries[$firstDate][$breadTypeId]['price'], // Add this line
                        'delivered' => 0,
                        'returned' => 0,
                        'gratis' => 0,
                        'total' => 0,
                        'total_price' => 0
                    ];
                }
    
                $monthlyTotals[$breadTypeId]['delivered'] += $data['delivered'];
                $monthlyTotals[$breadTypeId]['returned'] += $data['returned'];
                $monthlyTotals[$breadTypeId]['gratis'] += $data['gratis'];
                $monthlyTotals[$breadTypeId]['total'] += $data['total'];
                $monthlyTotals[$breadTypeId]['total_price'] += $data['total_price'];
            }
        }
    
        return $monthlyTotals;
    }


   


    private function export($type, $company, $startDate, $endDate)
    {
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);
        $filename = "monthly_summary_{$company->id}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";
    
        $dailySummaries = $this->generateDailySummaries($company, $startDate, $endDate);
        $monthlyTotals = $this->calculateMonthlyTotals($dailySummaries);

        switch ($type) {
            case 'pdf':
                $pdf = PDF::loadView('exports.monthly-summary-pdf', [
                    'company' => $company,
                    'dailySummaries' => $dailySummaries,
                    'monthlyTotals' => $monthlyTotals,
                    'startDate' => $startDate,
                    'endDate' => $endDate
                ]);
                return $pdf->download($filename . '.pdf');

            case 'excel':
                return Excel::download(
                    new MonthlySummaryExport(
                        $dailySummaries,
                        $monthlyTotals,
                        $startDate,
                        $endDate,
                        $company->name
                    ),
                    $filename . '.xlsx'
                );

            case 'invoice':
                $companyDetails = [
                    'address' => $company->address ?? 'Default Address',
                    'city' => $company->city ?? 'Default City',
                    'phone' => $company->phone ?? 'Default Phone',
                    'email' => $company->email ?? 'Default Email',
                    'tax_number' => $company->tax_number ?? 'Default Tax Number',
                    'bank_account' => $company->bank_account ?? 'Default Bank Account'
                ];

                return Excel::download(
                    new InvoiceExport(
                        $dailySummaries,
                        $monthlyTotals,
                        $startDate,
                        $endDate,
                        $company->name,
                        $companyDetails
                    ),
                    'invoice_' . $company->id . '_' . now()->format('YmdHis') . '.xlsx'
                );

            default:
                abort(400, 'Invalid export type');
        }
    }
}

    