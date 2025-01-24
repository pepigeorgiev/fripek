@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header and Controls Section -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="mb-6 border-b pb-4">
            <h1 class="text-2xl font-bold text-gray-900">Месечен преглед за {{ $company->name }}</h1>
            <p class="text-gray-600 mt-2">
                Период од: {{ $startDate->format('d.m.Y') }} - {{ $endDate->format('d.m.Y') }}
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Month Selection -->
            <form id="filter-form" action="{{ route('monthly-summary.show', ['company' => $company]) }}" method="GET">
                <div class="flex gap-4">
                    <div class="flex-1">
                        <label for="month" class="block text-sm font-medium text-gray-700 mb-1">Избери месец</label>
                        <input type="month" 
                               id="month" 
                               name="month" 
                               value="{{ $month }}" 
                               required 
                               class="w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-white border border-gray-300 rounded-md px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Види Преглед
                        </button>
                    </div>
                </div>
            </form>

            <!-- Export Form -->
            <form id="export-form" action="{{ route('monthly-summary.show', ['company' => $company]) }}" method="GET">
                <input type="hidden" name="export" value="excel">
                <input type="hidden" name="month" value="{{ $month }}">
                
                <div class="flex gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Период за експорт</label>
                        <div class="flex gap-2">
                            <input type="date" 
                                   name="export_start_date" 
                                   value="{{ $startDate->format('Y-m-d') }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm">
                            <input type="date" 
                                   name="export_end_date" 
                                   value="{{ $endDate->format('Y-m-d') }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-white border border-gray-300 rounded-md px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Експорт Excel
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="mt-4 text-right">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                Назад кон почетна
            </a>
        </div>
    </div>

    <!-- Table Section -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="border bg-gray-50 px-4 py-3 text-left text-sm font-semibold text-gray-900">
                            Име на лебот
                        </th>
                        <th class="border bg-gray-50 px-4 py-3 text-center text-sm font-semibold text-gray-900">
                            Цена
                        </th>
                        @for ($day = 1; $day <= $endDate->day; $day++)
                            <th class="border bg-gray-50 px-4 py-3 text-center text-sm font-semibold text-gray-900">
                                {{ $day }}
                            </th>
                        @endfor
                        <th class="border bg-gray-50 px-4 py-3 text-center text-sm font-semibold text-gray-900">
                            Вкупно количина
                        </th>
                        <th class="border bg-gray-50 px-4 py-3 text-center text-sm font-semibold text-gray-900">
                            Вкупно цена
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($monthlyTotals as $breadTypeId => $totals)
                        <tr>
                            <td class="border px-4 py-3 text-sm font-medium">
                                {{ $totals['name'] }}
                            </td>
                            <td class="border px-4 py-3 text-sm text-center font-medium">
                                {{ number_format($totals['company_price']) }}ден.
                            </td>
                            @for ($day = 1; $day <= $endDate->day; $day++)
                                @php
                                    $date = $startDate->copy()->addDays($day - 1)->format('Y-m-d');
                                    $dailyData = $dailySummaries[$date][$breadTypeId] ?? ['delivered' => 0, 'returned' => 0, 'total' => 0, 'total_price' => 0];
                                @endphp
                                <td class="border px-4 py-3 text-sm">
                                    П:{{ $dailyData['delivered'] }}<br>
                                    В:{{ $dailyData['returned'] }}<br>
                                    Г:{{ $dailyData['gratis'] }}<br>
                                    ВК:{{ $dailyData['total'] }}
                                </td>
                            @endfor
                            <td class="border px-4 py-3 text-sm bg-gray-50">
                                П:{{ $totals['delivered'] }}<br>
                                В:{{ $totals['returned'] }}<br>
                                Г:{{ $totals['gratis'] }}<br>
                                ВК:{{ $totals['total'] }}
                            </td>
                            <td class="border px-4 py-3 text-sm text-center bg-gray-50 font-medium">
                                {{ number_format($totals['total_price']) }}ден.
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="{{ $endDate->day + 3 }}" class="border px-4 py-3 text-right font-medium bg-gray-50">
                            Вкупно:
                        </td>
                        <td class="border px-4 py-3 text-center font-medium bg-gray-50">
                            {{ number_format(collect($monthlyTotals)->sum('total_price')) }}ден.
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection