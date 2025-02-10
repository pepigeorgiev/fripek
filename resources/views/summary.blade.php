@extends('layouts.app')


<style>
    /* Default styles for desktop */
    .text-center-desktop {
        text-align: center;
    }

    /* Media query for mobile devices */
    @media (max-width: 768px) {
        .text-center-desktop {
            text-align: left;
        }
    }
</style>
@section('content')
<div class="container mx-auto">
    <h1 class="text-2xl font-bold mb-4">Денешен преглед - Сите компании</h1>
    <div class="container mx-auto px-0 py-6">
        <div class="mb-6">
            <div class="flex items-center gap-4">
                @if($currentUser->isAdmin() || $currentUser->role === 'super_admin')
                    <form method="GET" action="{{ route('summary.index') }}" class="flex items-center">
                        @if(request()->has('date'))
                            <input type="hidden" name="date" value="{{ $date }}">
                        @endif
                        
                        <select 
                            name="user_id" 
                            onchange="this.form.submit()"
                            class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                        >
                            <option value="">Сите корисници</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ $selectedUserId == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif

                @include('components.date-selector', ['availableDates' => $availableDates])
            </div>
        </div>


        

    
    {{-- First Table: Today's Bread --}}
    <div class="mb-8">
        <h2 class="text-xl font-semibold mb-2">Денешен леб</h2>
        <form method="POST" action="{{ route('summary.update') }}">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">
            @if($currentUser->isAdmin() || $currentUser->role === 'super_admin')
                <input type="hidden" name="selected_user_id" value="{{ $selectedUserId }}">
            @endif
            <table class="w-full bg-white shadow-md rounded ">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-lg font-bold ">Име на лебот</th>
                        <th class="px-4 py-2 text-lg font-bold ">Продаден</th>
                        <th class="px-4 py-2 text-lg font-bold ">Задолжен</th>
                        <th class="px-4 py-2 text-lg font-bold ">Разлика</th>
                        <th class="px-4 py-2 text-lg font-bold ">Продаден</th>
                        <th class="px-4 py-2 text-lg font-bold ">Разлика</th>
                        <th class="px-4 py-2 text-lg font-bold ">Цена</th>
                        <th class="px-4 py-2 text-lg font-bold ">Вкупно</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($breadCounts as $breadType => $counts)
                        @php
                            $breadTypeObj = $breadTypes->firstWhere('name', $breadType);
                            $breadSale = $breadSales->flatten()->where('bread_type_id', $breadTypeObj->id)->first();
                        @endphp
                        <tr>
                            <td class="border px-4 py-2 text-lg font-bold text-center">{{ $breadType }}</td>
                            <td class="border px-4 py-2 text-lg font-bold text-center">{{ $counts['sent'] }}</td>
                            <td class="border px-4 py-2 text-lg font-bold text-center">
                                @if(auth()->user()->role === 'user')
                                    <div class="text-lg font-bold">
                                        {{ $breadSale->returned_amount ?? $counts['returned'] ?? 0 }}
                                        <input type="hidden" 
                                               name="returned[{{ $breadType }}]" 
                                               value="{{ $breadSale->returned_amount ?? $counts['returned'] ?? 0 }}">
                                    </div>
                                @else
                                    <input type="number" 
                                           name="returned[{{ $breadType }}]" 
                                           value="{{ $breadSale->returned_amount ?? $counts['returned'] ?? 0 }}" 
                                           class="w-full px-2 py-1 border rounded text-center">
                                @endif
                            </td>
                            <td class="border px-4 py-2 text-lg font-bold text-center">
                                @php
                                    $firstDifference = $counts['sent'] - ($breadSale->returned_amount ?? $counts['returned'] ?? 0);
                                @endphp
                                {{ $firstDifference }}
                            </td>
                            <td class="border px-4 py-2 text-lg font-bold text-center">
                                <input type="number" 
                                       name="sold[{{ $breadType }}]" 
                                       value="{{ $breadSale->sold_amount ?? $counts['sold'] ?? 0 }}" 
                                       class="w-full px-2 py-1 border rounded text-center">
                            </td>
                            <td class="border px-4 py-2 text-lg font-bold text-center">
                                @php
                                    $soldAmount = $breadSale->sold_amount ?? $counts['sold'] ?? 0;
                                    
                                    if ($firstDifference < 0) {
                                        // If first difference is negative, add the sold amount
                                        $finalDifference = $firstDifference + $soldAmount;
                                    } else {
                                        // If first difference is positive or zero, subtract the sold amount
                                        $finalDifference = $firstDifference - $soldAmount;
                                    }
                                @endphp
                                {{ $finalDifference }}
                            </td>
                            <td class="border px-4 py-2 text-lg font-bold text-center">{{ $counts['price'] }}</td>
                            <td class="border px-4 py-2 text-lg font-bold text-center">
                                {{ ($breadSale->sold_amount ?? $counts['sold'] ?? 0) * $counts['price'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="border px-4 py-2 font-bold text-right text-lg font-bold ">Вкупно:</td>
                        <td class="border px-4 py-2 text-lg font-bold text-center">{{ $totalSold }}</td>
                        <td class="border px-4 py-2 text-lg font-bold text-center"></td>
                        <td class="border px-4 py-2 text-lg font-bold text-center"></td>
                        <td class="border px-4 py-2 text-lg font-bold text-center">{{ number_format($totalInPrice, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
            <div class="mt-4">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Ажурирај ја табелата
                </button>
            </div>
        </form>
    </div>

    {{-- Second Table: Yesterday's Returned Bread --}}
<div class="mb-8">
    <h2 class="text-xl font-semibold mb-2">Вчерашен леб вратен</h2>
    <form method="POST" action="{{ route('summary.updateAdditional') }}">
        @csrf
        <input type="hidden" name="date" value="{{ $date }}">
        @if($currentUser->isAdmin() || $currentUser->role === 'super_admin')
    <input type="hidden" name="selected_user_id" value="{{ $selectedUserId }}">
@endif
        <table class="w-full bg-white shadow-md rounded">
            <thead>
                <tr>
                    <th class="px-4 py-2 text-lg font-bold ">Тип на лебот</th>
                    <th class="px-4 py-2 text-lg font-bold ">Евидентиран</th>
                    <th class="px-4 py-2 text-lg font-bold ">Продаден</th>
                    <th class="px-4 py-2 text-lg font-bold ">Разлика</th>
                    <th class="px-4 py-2 text-lg font-bold ">Вратен</th>
                    <th class="px-4 py-2 text-lg font-bold ">Разлика повторно</th>
                    <th class="px-4 py-2 text-lg font-bold ">Цена</th>
                    <th class="px-4 py-2 text-lg font-bold ">Вкупно</th>
                </tr>
            </thead>
            <tbody>
                @foreach($additionalTableData['data'] as $breadType => $data)
                    <tr>
                        <td class="border px-4 py-2 text-lg font-bold text-center">{{ $breadType }}</td>
                        <td class="border px-4 py-2 text-lg font-bold text-center">{{ $data['returned'] ?? 0 }}</td>
                        <td class="border px-4 py-2 text-lg font-bold text-center">
                            <input type="number" 
                                   name="sold[{{ $breadType }}]" 
                                   value="{{ old('sold['.$breadType.']', $data['sold'] ?? 0) }}" 
                                   class="w-full px-2 py-1 border rounded text-center">
                        </td>
                        <td class="border px-4 py-2 text-lg font-bold text-center">{{ $data['difference'] ?? 0 }}</td>
                        <td class="border px-4 py-2 text-lg font-bold text-center">
                            <input type="number" 
                                   name="returned1[{{ $breadType }}]" 
                                   value="{{ old('returned1['.$breadType.']', $data['returned1'] ?? 0) }}" 
                                   class="w-full px-2 py-1 border rounded text-center">
                        </td>
                        <td class="border px-4 py-2 text-lg font-bold text-center">{{ $data['difference1'] ?? 0 }}</td>
                        <td class="border px-4 py-2 text-lg font-bold text-center">{{ $data['price'] ?? 0 }}</td>
                        <td class="border px-4 py-2 text-lg font-bold text-center">{{ number_format($data['total'] ?? 0, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7" class="border px-4 py-2 font-bold text-right text-lg font-bold ">Вкупно:</td>
                    <td class="border px-4 py-2 font-bold text-lg text-center">{{ number_format($additionalTableData['totalPrice'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
        <div class="mt-4">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Ажурирај ја табелата за продажба на вчерашен леб
            </button>
        </div>
    </form>
</div>

{{-- Third Table: Cash Payments --}}
@if(!empty($cashPayments))
    <div class="mb-8">
        <h2 class="text-xl font-semibold mb-2 text-xl font-bold  ">Табела за дневен преглед на компании за плаќање во ќеш</h2>
        <table class="w-full bg-white shadow-md rounded text-lg font-bold ">
            <thead>
                <tr>
                    <th class="px-4 py-2 text-lg font-bold text-center">Име на компанија</th>
                    @foreach($breadTypes as $breadType)
                        <th class="px-4 py-2 text-lg font-bold text-center">{{ $breadType->name }}</th>
                    @endforeach
                    <th class="px-4 py-2 text-lg font-bold text-center">Вкупно</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cashPayments as $payment)
                    <tr>
                        <td class="border px-4 py-2 text-lg font-bold text-center">{{ $payment['company'] }}</td>
                        @foreach($breadTypes as $breadType)
                        
                            <td class="border px-4 py-2 text-center">
                                {{ $payment['breads'][$breadType->name] ?? '0 x ' . $breadType->price . ' = 0' }}
                            </td>
                        @endforeach
                        <td class="border px-4 py-2 text-center">
                            {{ number_format($payment['total'], 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="{{ count($breadTypes) + 1 }}" class="border px-4 py-2 font-bold text-right">
                        Вкупно во кеш:
                    </td>
                    <td class="border px-4 py-2 font-bold text-center">
                        {{ number_format($overallTotal, 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
@endif

    {{-- Fourth Table: Invoice Payments --}}
    @if(!empty($invoicePayments))
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-2 text-xl font-bold ">Табела за дневен преглед на компании за плаќање на фактура</h2>
            <table class="w-full bg-white shadow-md rounded">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-lg font-bold text-center">Име на компанија</th>
                        @foreach($breadTypes as $breadType)
                            <th class="px-4 py-2 text-lg font-bold text-center">{{ $breadType->name }}</th>
                        @endforeach
                        <th class="px-4 py-2 text-lg font-bold text-center">Вкупно</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoicePayments as $payment)
                        <tr>
                            <td class="border px-4 py-2 text-lg font-bold text-center">{{ $payment['company'] }}</td>
                            @foreach($breadTypes as $breadType)
                                <td class="border px-4 py-2 text-lg font-bold text-center">
                                   {{ $payment['breads'][$breadType->name] ?? '0 x ' . $breadType->price . ' = 0' }}
                                   
                                </td>
                            @endforeach
                            <td class="border px-4 py-2 text-lg font-bold text-center">
                                {{ number_format($payment['total'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="{{ count($breadTypes) + 1 }}" class="border px-4 py-2 font-bold text-right text-lg font-bold ">
                            Вкупно на фактура:
                        </td>
                        <td class="border px-4 py-2 font-bold text-lg text-center">
                            {{ number_format($overallInvoiceTotal, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif


    @if(!empty($unpaidTransactions))
    <div class="mb-8">
        <h2 class="text-xl font-semibold mb-2 text-xl font-bold">Неплатени трансакции за следење</h2>
        <div class="bg-yellow-50 p-4 mb-4 border-l-4 border-yellow-400">
            <p class="text-blue-700 text-xl font-bold">
                Овие трансакции се означени како неплатени и не се вклучени во вкупната сума на кеш плаќања.
            </p>
        </div>
        <table class="w-full bg-white shadow-md rounded">
            <thead>
                <tr>
                    <th class="px-4 py-2 text-xl font-bold text-lg font-bold text-center">Име на компанија</th>
                    @foreach($breadTypes as $breadType)
                        <th class="px-4 py-2 text-xl font-bold text-lg font-bold text-center">{{ $breadType->name }}</th>
                    @endforeach
                    <th class="px-4 py-2 text-xl font-bold text-lg font-bold text-center">Вкупно</th>
                    <th class="px-4 py-2 text-xl font-bold text-lg font-bold text-center">Акции</th>
                </tr>
            </thead>
            <tbody>
                @foreach($unpaidTransactions as $payment)
                    <tr>
                        <td class="border px-4 py-2 text-xl font-bold text-center">
                            {{ $payment['company'] }}
                            <div class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($payment['transaction_date'])->format('d.m.Y') }}</div>
                        </td>
                        @foreach($breadTypes as $breadType)
                            <td class="border px-4 py-2 text-xl font-bold text-center">
                                @if(isset($payment['breads'][$breadType->name]))
                                    @php
                                        $bread = $payment['breads'][$breadType->name];
                                        $totalBreads = $bread['delivered'] - $bread['returned'] - $bread['gratis'];
                                        $companyPrice = $breadType->getPriceForCompany($payment['company_id'], $payment['transaction_date'])['price'];
                                        $total = $bread['potential_total'];
                                    @endphp
                                    {{ "{$totalBreads} x {$companyPrice} = " . number_format($total, 2) }}
                                @else
                                    @php
                                        $companyPrice = $breadType->getPriceForCompany($payment['company_id'], $payment['transaction_date'])['price'];
                                    @endphp
                                    0 x {{ $companyPrice }} = 0
                                @endif
                            </td>
                        @endforeach
                        <td class="border px-4 py-2 text-xl font-bold text-center">
                            {{ number_format($payment['total_amount'], 2) }}
                        </td>
                        <td class="border px-4 py-2 text-center">
                            <form action="{{ route('daily-transactions.markAsPaid') }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="company_id" value="{{ $payment['company_id'] }}">
                                <input type="hidden" name="date" value="{{ $payment['transaction_date'] }}">
                                <button type="submit" 
                                        class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded-md text-sm font-medium transition-colors">
                                    Означи како платено
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="{{ count($breadTypes) + 1 }}" class="border px-4 py-2 font-bold text-right text-lg">
                        Вкупно неплатено:
                    </td>
                    <td class="border px-4 py-2 font-bold text-xl text-center">
                        {{ number_format(collect($unpaidTransactions)->sum('total_amount'), 2) }}
                    </td>
                    <td class="border px-4 py-2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
@endif

   

{{-- Final Summary Section --}}
<div class="mb-8">
    <h2 class="text-xl font-semibold mb-2 text-xl font-bold">Краен преглед на плаќања</h2>
    <div class="bg-white shadow-md rounded p-4">
        <div class="space-y-2">
            <p class="font-bold text-lg">
                <span class="font-bold text-xl">Денешен леб:</span> 
                {{ number_format($todayBreadTotal, 2) }}
            </p>
            <p class="font-bold text-lg">
                <span class="font-bold text-xl">Вчерашен леб:</span> 
                {{ number_format($yesterdayBreadTotal, 2) }}
            </p>
            <p class="font-bold text-lg">
                <span class="font-bold text-xl">Вкупно од продажба на леб:</span> 
                {{ number_format($breadSalesTotal, 2) }}
            </p>
            
            @if(!empty($cashPayments))
                <p class="font-bold text-lg">
                    <span class="font-bold text-xl">Вкупно во кеш од компании:</span> 
                    {{ number_format($overallTotal, 2) }}
                </p>
            @endif
            
            <p class="font-bold text-lg border-t pt-2 mt-2">
                <span class="font-bold text-xl">Вкупно во кеш:</span> 
                {{ number_format($totalCashRevenue, 2) }}
            </p>

            @if(!empty($invoicePayments))
                <p class="text-xl font-bold">
                    <span class="font-bold text-xl">Вкупно на фактура:</span> 
                    {{ number_format($overallInvoiceTotal, 2) }}
                </p>
            @endif
        </div>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to clear input on focus if the value is zero
        function clearInputOnFocus(event) {
            if (event.target.value === '0') {
                event.target.value = '';
            }
        }

        // Select all relevant input fields in both tables
        const inputsToClear = document.querySelectorAll('input[type="number"]');

        // Attach the focus event listener to each input
        inputsToClear.forEach(input => {
            input.addEventListener('focus', clearInputOnFocus);
        });
    });
</script>

@endsection