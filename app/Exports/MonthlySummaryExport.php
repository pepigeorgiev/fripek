<?php





namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MonthlySummaryExport implements FromCollection, WithHeadings, WithStyles
{
    protected $dailySummaries;
    protected $monthlyTotals;
    protected $startDate;
    protected $endDate;
    protected $companyName;

    public function __construct($dailySummaries, $monthlyTotals, $startDate, $endDate, $companyName)
    {
        $this->dailySummaries = $dailySummaries;
        $this->monthlyTotals = $monthlyTotals;
        $this->startDate = Carbon::parse($startDate);
        $this->endDate = Carbon::parse($endDate);
        $this->companyName = $companyName;
    }

    public function headings(): array
    {
        // Create title rows for company and date range
        $dateRange = $this->startDate->format('d.m.Y') . ' - ' . $this->endDate->format('d.m.Y');
        
        $headers = [
            // First row - Company name with prefix
            ['Месечен преглед за ' . $this->companyName],
            // Second row - Date range
            ['Преглед за период: ' . $dateRange],
            // Third row - Empty row for spacing
            [''],
            // Fourth row - Actual table headers
            ['Име на лебот', 'Цена']
        ];

        // Add dates within the selected range
        $currentDate = $this->startDate->copy();
        while ($currentDate <= $this->endDate) {
            $headers[3][] = $currentDate->format('d');
            $currentDate->addDay();
        }

        $headers[3][] = 'Kол';
        $headers[3][] = 'Вк.цена';

        return $headers;
    }

    public function collection()
{
    $rows = new Collection();
    $grandTotalPrice = 0;

    foreach ($this->monthlyTotals as $breadTypeId => $totals) {
        // Check if this bread type has any non-zero quantities in any day
        $hasSummary = false;
        $totalQty = 0;
        
        // First check if this bread type has any non-zero quantities
        $currentDate = $this->startDate->copy();
        while ($currentDate <= $this->endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $dailyData = $this->dailySummaries[$dateStr][$breadTypeId] ?? null;
            
            if ($dailyData && $dailyData['total'] != 0) {  // Changed from > 0 to != 0
                $hasSummary = true;
                $totalQty += $dailyData['total'];
            }
            $currentDate->addDay();
        }

        // Only process this bread type if it has any non-zero summaries
        if ($hasSummary) {
            $rowData = [
                $totals['name'],
                number_format($totals['price'], 2),
            ];

            $totalPrice = 0;

            $currentDate = $this->startDate->copy();
            while ($currentDate <= $this->endDate) {
                $dateStr = $currentDate->format('Y-m-d');
                $dailyData = $this->dailySummaries[$dateStr][$breadTypeId] ?? null;

                if ($dailyData) {
                    $dailyTotal = $dailyData['total'];
                    $totalPrice += $dailyTotal * $totals['price'];
                    // Show negative numbers as is
                    $rowData[] = $dailyTotal ?: '0';
                } else {
                    $rowData[] = '0';
                }

                $currentDate->addDay();
            }

            // Show total quantity (can be negative)
            $rowData[] = $totalQty ?: '0';
            $rowData[] = number_format($totalPrice, 2);

            $rows->push($rowData);
            $grandTotalPrice += $totalPrice;
        }
    }

    // Only add the empty and total rows if we have any data
    if ($rows->isNotEmpty()) {
        $columnsCount = count($rows->first()) - 1;

        // Add empty row before total
        $emptyRow = array_fill(0, $columnsCount + 1, '');
        $rows->push($emptyRow);

        // Add total row
        $totalRow = array_fill(0, $columnsCount, '');
        $totalRow[0] = 'Вкупно';
        $totalRow[] = number_format($grandTotalPrice, 2);
        $rows->push($totalRow);
    }

    return $rows;
}




    public function styles(Worksheet $sheet)
    {
        $lastColumn = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();
    
        // Set column widths even narrower
        foreach (range('A', $lastColumn) as $col) {
            if ($col === 'A') {
                // Name column
                $sheet->getColumnDimension($col)->setWidth(18);
            } elseif ($col === 'B' || $col === $lastColumn) {
                // Price columns
                $sheet->getColumnDimension($col)->setWidth(8);
            } else {
                // Date columns
                $sheet->getColumnDimension($col)->setWidth(4);
            }
        }
    
        // Style the company name
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT
            ]
        ]);
        $sheet->mergeCells("A1:{$lastColumn}1");
    
        // Style the date range
        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT
            ]
        ]);
        $sheet->mergeCells("A2:{$lastColumn}2");
    
        // Define border style
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
    
        // Header styles (4th row)
        $sheet->getStyle("A4:{$lastColumn}4")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 10
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'E0E0E0',
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    
        // Content styles
        $contentRange = "A5:{$lastColumn}{$lastRow}";
        $sheet->getStyle($contentRange)->applyFromArray([
            'font' => [
                'size' => 10
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    
        // First column left alignment
        $sheet->getStyle("A4:A{$lastRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);
    
        // Price columns right alignment
        $priceColumns = ['B', $lastColumn];
        foreach ($priceColumns as $col) {
            $sheet->getStyle("{$col}4:{$col}{$lastRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            
            $sheet->getStyle("{$col}5:{$col}{$lastRow}")
                ->getNumberFormat()
                ->setFormatCode('#,##0.00');
        }
    
        // Total row styles
        $sheet->getStyle("A{$lastRow}:{$lastColumn}{$lastRow}")->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'F0F0F0',
                ],
            ],
        ]);
    
        // Apply borders to the data table only (excluding company name and date range)
        $sheet->getStyle("A4:{$lastColumn}{$lastRow}")->applyFromArray($borderStyle);
    
        // Set print layout
        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
    
        // Set print margins
        $sheet->getPageMargins()
            ->setTop(0.5)
            ->setRight(0.5)
            ->setLeft(0.5)
            ->setBottom(0.5);
    
        return [
            4 => ['font' => ['bold' => true]],
        ];
    }
}

