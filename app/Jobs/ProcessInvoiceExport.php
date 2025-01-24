<?php

namespace App\Jobs;

use App\Exports\InvoiceExport;
use App\Models\ExportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class ProcessInvoiceExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $startDate;
    protected $endDate;
    protected $exportJobId;

    public function __construct($startDate, $endDate, $exportJobId)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->exportJobId = $exportJobId;
    }

    public function handle()
    {
        try {
            $exportJob = ExportJob::findOrFail($this->exportJobId);
            $exportJob->update(['status' => 'processing']);

            // Debug log before export
            \Log::info('Starting invoice export', [
                'start_date' => $this->startDate,
                'end_date' => $this->endDate
            ]);

            $filename = sprintf(
                'fakturi%s%s.xlsx',
                Carbon::parse($this->startDate)->format('d_m_Y'),
                Carbon::parse($this->endDate)->format('d_m_Y')
            );

            $filePath = 'exports/' . $filename;

            // Use your working InvoiceExport class
            Excel::store(
                new InvoiceExport($this->startDate, $this->endDate),
                $filePath
            );

            // Debug log after export
            \Log::info('Export completed', [
                'file_path' => $filePath
            ]);

            $exportJob->update([
                'status' => 'completed',
                'file_path' => $filePath
            ]);

        } catch (\Exception $e) {
            \Log::error('Export job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if (isset($exportJob)) {
                $exportJob->update([
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ]);
            }

            throw $e;
        }
    }
}