<?php

namespace App\Http\Controllers;

use App\Exports\InvoiceExport;
use App\Jobs\ProcessInvoiceExport;
use App\Models\ExportJob;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoiceCompanies = Company::where('type', 'invoice')->get();
        $exportJobs = ExportJob::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('invoice-companies.index', compact('invoiceCompanies', 'exportJobs'));
    }

    public function export(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        try {
            $startDate = $request->start_date;
            $endDate = $request->end_date;

            \Log::info('Export requested', [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);

            // Debug query to check prices
            $priceCheck = \DB::select("
                SELECT btc.*, bt.name as bread_name, c.name as company_name
                FROM bread_type_company btc
                JOIN bread_types bt ON bt.id = btc.bread_type_id
                JOIN companies c ON c.id = btc.company_id
                WHERE btc.valid_from <= ?
                ORDER BY btc.valid_from DESC", 
                [$endDate]
            );

            \Log::info('Available prices:', [
                'prices' => $priceCheck
            ]);

            // Check if there's data to export
            $hasData = Company::where('type', 'invoice')
                ->whereHas('dailyTransactions', function($query) use ($startDate, $endDate) {
                    $query->whereBetween('transaction_date', [$startDate, $endDate])
                        ->whereRaw('(delivered - returned) > 0');
                })
                ->exists();

            if (!$hasData) {
                return back()->with('error', 'Нема податоци за експорт во избраниот период.');
            }

            // Create export job record
            $exportJob = ExportJob::create([
                'user_id' => auth()->id(),
                'type' => 'invoice',
                'status' => 'pending',
                'params' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]
            ]);

            // Dispatch the job
            ProcessInvoiceExport::dispatch(
                $startDate,
                $endDate,
                $exportJob->id
            );

            return back()->with('success', 'Експортот е започнат. Ќе биде достапен за преземање наскоро.');

        } catch (\Exception $e) {
            \Log::error('Export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Се појави грешка при експортирање. Ве молиме обидете се повторно.');
        }
    }

    public function download(ExportJob $exportJob)
    {
        if ($exportJob->user_id !== auth()->id() || $exportJob->status !== 'completed') {
            abort(403);
        }

        if (!Storage::exists($exportJob->file_path)) {
            return back()->with('error', 'Фајлот не е пронајден.');
        }

        return Storage::download($exportJob->file_path);
    }
}