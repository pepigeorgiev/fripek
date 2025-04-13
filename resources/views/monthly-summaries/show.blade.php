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
            <!-- Month Selection with Date Range -->
            <form id="filter-form" action="{{ route('monthly-summary.show', ['company' => $company]) }}" method="GET">
                <div class="mb-4">
                    <label for="month" class="block text-sm font-medium text-gray-700 mb-1">Избери месец</label>
                    <input type="month" 
                           id="month" 
                           name="month" 
                           value="{{ $month ?? now()->format('Y-m') }}" 
                           required 
                           class="w-full border-gray-300 rounded-md shadow-sm">
                </div>
                
                <div class="mb-4">
                    <label for="date_range" class="block text-sm font-medium text-gray-700 mb-1">Избери период</label>
                    <select id="date_range" name="date_range" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="full" {{ ($dateRange ?? 'full') == 'full' ? 'selected' : '' }}>Цел месец</option>
                        <option value="first_half" {{ ($dateRange ?? '') == 'first_half' ? 'selected' : '' }}>1-15 ден</option>
                        <option value="second_half" {{ ($dateRange ?? '') == 'second_half' ? 'selected' : '' }}>16-крај</option>
                    </select>
                </div>
                
                <div class="flex">
                    <button type="submit" class="bg-blue-600 text-white rounded-md px-4 py-2 text-sm font-medium hover:bg-blue-700">
                        Види Преглед
                    </button>
                </div>
            </form>

            <!-- Export Form -->
            <form id="export-form" action="{{ route('monthly-summary.show', ['company' => $company]) }}" method="GET">
                <input type="hidden" name="export" value="excel">
                <input type="hidden" name="month" value="{{ $month ?? now()->format('Y-m') }}">
                <input type="hidden" name="date_range" value="{{ $dateRange ?? 'full' }}">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Период за експорт</label>
                    <div class="flex gap-2 mb-2">
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
                
                <div class="flex gap-2">
                    <button type="submit" class="bg-white border border-gray-300 rounded-md px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Експорт Excel
                    </button>
                    
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Назад кон почетна
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table Section (Responsive) -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <!-- Swipe indication for mobile -->
        <div class="md:hidden text-sm text-center text-gray-500 py-2 border-b">
            ← Swipe left/right to see more →
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="border bg-gray-50 px-4 py-3 text-left text-sm font-semibold text-gray-900 sticky left-0 z-10 whitespace-nowrap">
                            Име на лебот
                        </th>
                        <th class="border bg-gray-50 px-4 py-3 text-center text-sm font-semibold text-gray-900 whitespace-nowrap">
                            Цена
                        </th>
                        @php
                            $days = [];
                            $currentDate = clone $startDate;
                            while ($currentDate <= $endDate) {
                                $days[] = clone $currentDate;
                                $currentDate->addDay();
                            }
                            $dayCount = count($days);
                        @endphp
                        @foreach ($days as $day)
                            <th class="border bg-gray-50 px-2 py-2 text-center text-sm font-semibold text-gray-900 whitespace-nowrap">
                                {{ $day->format('d') }}
                            </th>
                        @endforeach
                        <th class="border bg-gray-50 px-4 py-3 text-center text-sm font-semibold text-gray-900 whitespace-nowrap">
                            Вкупно <br>количина
                        </th>
                        <th class="border bg-gray-50 px-4 py-3 text-center text-sm font-semibold text-gray-900 whitespace-nowrap">
                            Вкупно <br>цена
                        </th>
                    </tr>
                </thead>
                <tbody>
    @foreach ($monthlyTotals as $breadTypeId => $totals)
        <tr class="hover:bg-gray-50">
            <td class="border px-4 py-3 text-sm font-medium sticky left-0 z-10 bg-white whitespace-nowrap">
                {{ $totals['name'] }}
            </td>
            <td class="border px-4 py-3 text-sm text-center font-medium whitespace-nowrap">
                {{ number_format($totals['company_price']) }}ден.
            </td>
            @foreach ($days as $day)
                @php
                    $dateStr = $day->format('Y-m-d');
                    $dailyData = $dailySummaries[$dateStr][$breadTypeId] ?? ['delivered' => 0, 'returned' => 0, 'gratis' => 0, 'total' => 0, 'total_price' => 0];
                @endphp
                <td class="border px-2 py-1 text-xs whitespace-nowrap">
                    <div class="flex flex-col items-start">
                        <div class="text-green-600">П:{{ $dailyData['delivered'] }}</div>
                        <div class="text-red-600">В:{{ $dailyData['returned'] }}</div>
                        <div class="text-blue-600">Г:{{ $dailyData['gratis'] }}</div>
                        <div class="font-bold">ВК:{{ $dailyData['total'] }}</div>
                    </div>
                </td>
            @endforeach
            <td class="border px-3 py-2 text-sm bg-gray-50 whitespace-nowrap">
                <div class="flex flex-col items-center">
                    <div>П:<strong>{{ $totals['delivered'] }}</strong></div>
                    <div>В:<strong>{{ $totals['returned'] }}</strong></div>
                    <div>Г:<strong>{{ $totals['gratis'] }}</strong></div>
                    <div class="font-bold">ВК:{{ $totals['total'] }}</div>
                </div>
            </td>
            <td class="border px-3 py-2 text-sm text-center bg-gray-50 font-bold whitespace-nowrap">
                {{ number_format($totals['total_price']) }}ден.
            </td>
        </tr>
    @endforeach
</tbody>
                <!-- <tbody>
                    @foreach ($monthlyTotals as $breadTypeId => $totals)
                        <tr class="hover:bg-gray-50">
                            <td class="border px-4 py-3 text-sm font-medium sticky left-0 z-10 bg-white whitespace-nowrap">
                                {{ $totals['name'] }}
                            </td>
                            <td class="border px-4 py-3 text-sm text-center font-medium whitespace-nowrap">
                                {{ number_format($totals['company_price']) }}ден.
                            </td>
                            @foreach ($days as $day)
                                @php
                                    $dateStr = $day->format('Y-m-d');
                                    $dailyData = $dailySummaries[$dateStr][$breadTypeId] ?? ['delivered' => 0, 'returned' => 0, 'gratis' => 0, 'total' => 0, 'total_price' => 0];
                                @endphp
                                <td class="border px-1 py-1 text-xs whitespace-nowrap">
                                    <div class="flex flex-col items-center">
                                        <div class="flex justify-between w-full mb-0.5">
                                            <span class="text-green-600">П:{{ $dailyData['delivered'] }}</span>
                                            <span class="text-red-600">В:{{ $dailyData['returned'] }}</span>
                                        </div>
                                        <div class="flex justify-between w-full">
                                            <span class="text-blue-600">Г:{{ $dailyData['gratis'] }}</span>
                                            <span class="font-bold">{{ $dailyData['total'] }}</span>
                                        </div>
                                    </div>
                                </td>
                            @endforeach
                            <td class="border px-3 py-2 text-sm bg-gray-50 whitespace-nowrap">
                                <div class="flex flex-col items-center">
                                    <div>П:<strong>{{ $totals['delivered'] }}</strong></div>
                                    <div>В:<strong>{{ $totals['returned'] }}</strong></div>
                                    <div>Г:<strong>{{ $totals['gratis'] }}</strong></div>
                                    <div class="font-bold">ВК:{{ $totals['total'] }}</div>
                                </div>
                            </td>
                            <td class="border px-3 py-2 text-sm text-center bg-gray-50 font-bold whitespace-nowrap">
                                {{ number_format($totals['total_price']) }}ден.
                            </td>
                        </tr>
                    @endforeach
                </tbody> -->
                <tfoot>
                    <tr>
                        <td colspan="{{ $dayCount + 2 }}" class="border px-4 py-3 text-right font-medium bg-gray-50">
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-submit form when date range changes
        document.getElementById('date_range').addEventListener('change', function() {
            document.getElementById('filter-form').submit();
        });
    });
</script>
@endpush
@endsection