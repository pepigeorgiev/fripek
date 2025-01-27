<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;

class InvoiceExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithColumnFormatting, WithCustomValueBinder
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function bindValue(Cell $cell, $value)
    {
        if ($cell->getColumn() === 'G' && is_numeric($value)) {
            $cell->setValueExplicit(
                number_format((float)$value, 2, '.', ''),
                DataType::TYPE_NUMERIC
            );
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function collection()
    {
        $result = new Collection();

        $query = "
            WITH latest_prices AS (
                SELECT 
                    bread_type_id,
                    company_id,
                    price,
                    valid_from,
                    ROW_NUMBER() OVER (PARTITION BY bread_type_id, company_id ORDER BY valid_from DESC) as rn
                FROM bread_type_company
                WHERE valid_from <= ?
            )
            SELECT 
                c.code as company_code,
                c.name as company_name,
                bt.code as bread_code,
                bt.name as bread_name,
                SUM(dt.delivered - dt.returned) as quantity,
                COALESCE(lp.price, bt.price) as price
            FROM daily_transactions dt
            JOIN companies c ON dt.company_id = c.id
            JOIN bread_types bt ON dt.bread_type_id = bt.id
            LEFT JOIN latest_prices lp ON lp.bread_type_id = dt.bread_type_id 
                AND lp.company_id = dt.company_id
                AND lp.rn = 1
            WHERE c.type = 'invoice'
            AND dt.transaction_date BETWEEN ? AND ?
            GROUP BY 
                c.code,
                c.name,
                bt.code,
                bt.name,
                COALESCE(lp.price, bt.price)
            HAVING SUM(dt.delivered - dt.returned) > 0
            ORDER BY 
                c.code,
                bt.code
        ";

        // Log the query parameters
        \Log::info('Executing export query with params:', [
            'end_date' => $this->endDate,
            'start_date' => $this->startDate
        ]);

        $transactions = \DB::select($query, [$this->endDate, $this->startDate, $this->endDate]);

        \Log::info('Query results:', [
            'count' => count($transactions),
            'data' => $transactions
        ]);

        foreach ($transactions as $transaction) {
            $result->push([
                Date::dateTimeToExcel(Carbon::parse($this->endDate)),
                $transaction->company_code,
                $transaction->company_name,
                $transaction->bread_code,
                $transaction->bread_name,
                $transaction->quantity,
                (float)$transaction->price
            ]);
        }

        return $result;
    }

    public function headings(): array
    {
        return [
            'Датум',
            'Код на компанија',
            'Име на компанија',
            'Код на леб',
            'Име на леб',
            'Количина',
            'Цена'
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_DATE_DMYSLASH,
            'G' => '#,##0.00'
        ];
    }
}