<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class MonthlySummaryExport implements FromCollection, WithHeadings
{
    protected $dailySummaries;
    protected $monthlyTotals;
    protected $startDate;
    protected $endDate;

    public function __construct($dailySummaries, $monthlyTotals, $startDate, $endDate)
    {
        $this->dailySummaries = $dailySummaries;
        $this->monthlyTotals = $monthlyTotals;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function headings(): array
    {
        $headers = ['Bread Type', 'Price'];
        
        // Add dates from start date to end date
        $currentDate = clone $this->startDate;
        while ($currentDate <= $this->endDate) {
            $headers[] = $currentDate->format('d');
            $currentDate->addDay();
        }
        
        $headers[] = 'Total';
        $headers[] = 'Total Price';
        
        return $headers;
    }

    public function collection()
    {
        $rows = new Collection();

        foreach ($this->monthlyTotals as $breadTypeId => $totals) {
            $row = [
                $totals['name'],
                $totals['price']
            ];

            // Add daily totals
            $currentDate = clone $this->startDate;
            while ($currentDate <= $this->endDate) {
                $dateStr = $currentDate->format('Y-m-d');
                $dailyData = $this->dailySummaries[$dateStr][$breadTypeId] ?? ['total' => 0];
                $row[] = $dailyData['total'];
                $currentDate->addDay();
            }

            // Add monthly totals
            $row[] = $totals['total'];
            $row[] = $totals['total_price'];

            $rows->push($row);
        }

        return $rows;
    }
}