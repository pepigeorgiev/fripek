@extends('layouts.app')

@section('content')
<style>
        /* Hide the spinners in number inputs */
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { 
            -webkit-appearance: none; 
            margin: 0; 
        }

        input[type=number] {
            -moz-appearance: textfield; /* Firefox */
        }
    </style>
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
            <table class="w-full bg-white shadow-md rounded">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-lg font-bold text-center-desktop">Име на лебот</th>
                        <th class="px-4 py-2 text-lg font-bold text-center-desktop">Продаден</th>
                        <th class="px-4 py-2 text-lg font-bold text-center-desktop">Задолжен</th>
                        <th class="px-4 py-2 text-lg font-bold text-center-desktop">Разлика</th>
                        <th class="px-4 py-2 text-lg font-bold text-center-desktop">Продаден</th>
                        <th class="px-4 py-2 text-lg font-bold text-center-desktop">Разлика</th>
                        <th class="px-4 py-2 text-lg font-bold text-center-desktop">Цена</th>
                        <th class="px-4 py-2 text-lg font-bold text-center-desktop">Вкупно</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($breadCounts as $breadType => $counts)
                        @php
                            $breadTypeObj = $breadTypes->firstWhere('name', $breadType);
                            $breadSale = $breadSales->flatten()->where('bread_type_id', $breadTypeObj->id)->first();
                        @endphp
                        <tr>
                            <td class="border px-4 py-2 text-lg font-bold text-center-desktop">{{ $breadType }}</td>
                            <td class="border px-4 py-2 text-lg font-bold text-center-desktop">{{ $counts['sent'] }}</td>
                            
                            <td class="border px-4 py-2 text-lg font-bold text-center-desktop">
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
               class="w-full px-2 py-1 border rounded text-center-desktop accumulating-input"
               data-original-value="{{ $breadSale->returned_amount ?? $counts['returned'] ?? 0 }}"
               min="0">
    @endif
</td>
                           
                            <td class="border px-4 py-2 text-lg font-bold text-center-desktop">
                                @php
                                    $firstDifference = $counts['sent'] - ($breadSale->returned_amount ?? $counts['returned'] ?? 0);
                                @endphp
                                {{ $firstDifference }}
                            </td>
                            <td class="border px-4 py-2 text-lg font-bold text-center-desktop">
                                <input type="number" 
                                       name="old_bread_sold[{{ $breadType }}]" 
                                       value="{{ old('old_bread_sold['.$breadType.']', $data['sold'] ?? 0) }}" 
                                       class="w-full px-2 py-1 border rounded text-center-desktop">
                            </td>
                            <td class="border px-4 py-2 text-lg font-bold text-center-desktop">
                                @php
                                    $soldAmount = $breadSale->sold_amount ?? $counts['sold'] ?? 0;
                                    
                                    if ($firstDifference < 0) {
                                        $finalDifference = $firstDifference + $soldAmount;
                                    } else {
                                        $finalDifference = $firstDifference - $soldAmount;
                                    }
                                @endphp
                                {{ $finalDifference }}
                            </td>
                            <td class="border px-4 py-2 text-lg font-bold text-center-desktop">{{ $counts['price'] }}</td>
                            <td class="border px-4 py-2 text-lg font-bold text-center-desktop">
                                {{ ($breadSale->sold_amount ?? $counts['sold'] ?? 0) * $counts['price'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="border px-4 py-2 font-bold text-right text-lg font-bold ">Вкупно:</td>
                        <td class="border px-4 py-2 text-lg font-bold text-center-desktop">{{ $totalSold }}</td>
                        <td class="border px-4 py-2 text-lg font-bold text-center-desktop"></td>
                        <td class="border px-4 py-2 text-lg font-bold text-center-desktop"></td>
                        <td class="border px-4 py-2 text-lg font-bold text-center-desktop">{{ number_format($totalInPrice, 2) }}</td>
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

    {{-- summary.blade.php --}}
<div class="mb-8">
    <h2 class="text-xl font-semibold mb-2">Вчерашен леб вратен</h2>
    <form method="POST" action="{{ route('summary.updateAdditional') }}">
        @csrf
        <input type="hidden" name="date" value="{{ $date }}">
        @if($currentUser->isAdmin() || $currentUser->role === 'super_admin')
            <input type="hidden" name="selected_user_id" value="{{ $selectedUserId }}">
        @endif
        <div class="responsive-table">
            <table class="w-full bg-white shadow-md rounded">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-lg font-bold text-center-desktop">Тип на лебот</th>
                        <th class="px-4 py-2 text-lg font-bold text-center-desktop">Евидентиран</th>
                        <th class="px-4 py-2 text-lg font-bold text-center-desktop">Продаден</th>
                        <th class="px-4 py-2 text-lg font-bold text-center-desktop">Разлика</th>
                        <th class="px-4 py-2 text-lg font-bold text-center-desktop">Вратен</th>
                        <th class="px-4 py-2 text-lg font-bold text-center-desktop">Разлика повторно</th>
                        <th class="px-4 py-2 text-lg font-bold text-center-desktop">Цена</th>
                        <th class="px-4 py-2 text-lg font-bold text-center-desktop">Вкупно</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($additionalTableData['data'] as $breadType => $data)
                        <tr>
                            <td class="border px-4 py-2 text-lg font-bold text-center-desktop">{{ $breadType }}</td>
                            <td class="border px-4 py-2 text-lg font-bold text-center-desktop">{{ $data['returned'] ?? 0 }}</td>
                        <td class="border px-4 py-2 text-lg font-bold text-center-desktop">
    @php
        $breadTypeObj = $breadTypes->firstWhere('name', $breadType);
        $soldValue = isset($data['sold']) ? $data['sold'] : 0;
        $canEdit = ($currentUser->role === 'user' && $data['user_id'] === $currentUser->id) || 
                   (($currentUser->isAdmin() || $currentUser->role === 'super_admin') && !$selectedUserId);
    @endphp
    <form id="oldBreadSalesForm" action="{{ route('daily-transactions.store-old-bread') }}" method="POST">
    @csrf
    <input type="hidden" name="transaction_date" value="{{ $date }}">
    <input type="hidden" 
           name="old_bread_sold[{{ $breadTypeObj->id }}][bread_type_id]" 
           value="{{ $breadTypeObj->id }}">
    <input type="number" 
           name="old_bread_sold[{{ $breadTypeObj->id }}][sold]" 
           value="{{ $soldValue }}"
           class="w-full px-2 py-1 border rounded text-center-desktop"
           @unless($canEdit) readonly @endunless>
</form>
   
</td>
                            </td>
                            <td class="border px-4 py-2 text-lg font-bold text-center-desktop">{{ $data['difference'] ?? 0 }}</td>
                            <td class="border px-4 py-2 text-lg font-bold text-center-desktop">
                                <input type="number" 
                                       name="returned1[{{ $breadType }}]" 
                                       value="{{ old('returned1.'.$breadType, $data['returned1'] ?? 0) }}" 
                                       class="w-full px-2 py-1 border rounded text-center-desktop">
                            </td>
                            <td class="border px-4 py-2 text-lg font-bold text-center-desktop">{{ $data['difference1'] ?? 0 }}</td>
                            <td class="border px-4 py-2 text-lg font-bold text-center-desktop">{{ $data['price'] ?? 0 }}</td>
                            <td class="border px-4 py-2 text-lg font-bold text-center-desktop">{{ number_format($data['total'] ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="7" class="border px-4 py-2 font-bold text-right text-lg">Вкупно:</td>
                        <td class="border px-4 py-2 font-bold text-lg text-center-desktop">{{ number_format($additionalTableData['totalPrice'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
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
        <h2 class="text-xl font-semibold mb-2 text-xl font-bold">Табела за дневен преглед на компании за плаќање во ќеш</h2>
        <table class="w-full bg-white shadow-md rounded text-lg font-bold">
            <thead>
                <tr>
                    <th class="px-4 py-2 text-lg font-bold text-center border-b-2 border-gray-400 w-1/4">Име на компанија</th>
                    <th class="px-4 py-2 text-lg font-bold text-center border-b-2 border-gray-400 w-2/4">Видови на леб</th>
                    <th class="px-4 py-2 text-lg font-bold text-center border-b-2 border-gray-400 w-1/4">Вкупно</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cashPayments as $payment)
                <tr class="border-t-2 border-gray-400">
                <td class="border text-lg font-bold px-4 py-2  text-center align-center">{{ $payment['company'] }}</td>
                        <td class="border px-4 py-2">
                            <table class="w-full">
                                <thead>
                                    <tr>
                                        <th class="w-1/2 text-left pb-2 border-b border-gray-400">Вид на леб</th>
                                        <th class="w-1/2 text-right pb-2 border-b border-gray-400">Количина × Цена</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payment['breads'] as $breadName => $breadInfo)
                                        <tr>
                                            <td class="py-1 text-lg font-bold border-b border-gray-300">{{ $breadName }}:</td>
                                            <td class="py-1 text-lg font-bold text-right border-b border-gray-300">{{ $breadInfo }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                        <td class="border text-lg font-bold px-4 py-2  text-center align-center">
                            {{ number_format($payment['total'], 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="border px-4 py-2 font-bold text-right border-t-2 border-gray-400">
                        Вкупно во кеш:
                    </td>
                    <td class="border px-4 py-2 font-bold text-center border-t-2 border-gray-400">
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
        <h2 class="text-xl font-semibold mb-2 text-xl font-bold">Табела за дневен преглед на компании за плаќање на фактура</h2>
        <table class="w-full bg-white shadow-md rounded">
            <thead>
                <tr>
                    <th class="px-4 py-2 text-lg font-bold text-center border-b-2 border-gray-400 w-1/4">Име на компанија</th>
                    <th class="px-4 py-2 text-lg font-bold text-center border-b-2 border-gray-400 w-2/4">Видови на леб</th>
                    <th class="px-4 py-2 text-lg font-bold text-center border-b-2 border-gray-400 w-1/4">Вкупно</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoicePayments as $payment)
                <tr class="border-t-2 border-gray-400">
                <td class="border px-4 py-2 text-lg font-bold text-center align-center">{{ $payment['company'] }}</td>
                        <td class="border px-4 py-2">
                            <table class="w-full">
                                <thead>
                                    <tr>
                                        <th class="w-1/2 text-left pb-2 border-b border-gray-400">Вид на леб</th>
                                        <th class="w-1/2 text-right pb-2 border-b border-gray-400">Количина × Цена</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payment['breads'] as $breadName => $breadInfo)
                                        <tr>
                                            <td class="py-1 text-lg font-bold border-b border-gray-300">{{ $breadName }}:</td>
                                            <td class="py-1 text-lg font-bold text-right border-b border-gray-300">{{ $breadInfo }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                        <td class="border text-lg font-bold px-4 py-2  text-center align-center">
                            {{ number_format($payment['total'], 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="border px-4 py-2 font-bold text-right border-t-2 border-gray-400">
                        Вкупно на фактура:
                    </td>
                    <td class="border text-lg px-4 py-2 font-bold text-center border-t-2 border-gray-400">
                        {{ number_format($overallInvoiceTotal, 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
@endif
    

   
{{-- Unpaid Transactions Table --}}
@if(!empty($unpaidTransactions))
    <div class="mb-8">
        <h2 class="text-xl font-semibold mb-2 text-xl font-bold">Неплатени трансакции за следење</h2>
        <div class="bg-yellow-50 p-4 mb-4 border-l-4 border-yellow-400">
            <p class="text-blue-700 text-xl font-bold">
                Овие трансакции се означени како неплатени и не се вклучени во вкупната сума на кеш плаќања.
            </p>
        </div>

        <form id="bulkPaymentForm" action="{{ route('daily-transactions.markMultipleAsPaid') }}" method="POST">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">
            
            <div class="flex justify-end mb-4">
                <button type="submit" 
                        id="bulkPaymentButton"
                        disabled
                        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed">
                    Означи ги селектираните како платени
                </button>
            </div>

            <table class="w-full bg-white shadow-md rounded">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-lg font-bold text-center border-b-2 border-gray-400">
                            <input type="checkbox" 
                                   id="selectAll" 
                                   class="form-checkbox h-5 w-5 text-blue-600">
                        </th>
                        <th class="px-4 py-2 text-lg font-bold text-center border-b-2 border-gray-400 w-1/4">Име на компанија</th>
                        <th class="px-4 py-2 text-lg font-bold text-center border-b-2 border-gray-400 w-2/4">Видови на леб</th>
                        <th class="px-4 py-2 text-lg font-bold text-center border-b-2 border-gray-400 w-1/5">Вкупно</th>
                        <th class="px-4 py-2 text-lg font-bold text-center border-b-2 border-gray-400 w-1/5">Индивидуални акции</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($unpaidTransactions as $payment)
                        <tr class="border-t-2 border-gray-400">
                            <td class="border px-4 py-2 text-center">
                                <input type="checkbox" 
                                       name="selected_transactions[]" 
                                       value="{{ $payment['company_id'] }}_{{ $payment['transaction_date'] }}"
                                       class="transaction-checkbox form-checkbox h-5 w-5 text-blue-600">
                            </td>
                            <td class="border px-4 py-2 text-lg font-bold text-center align-center">
                                {{ $payment['company'] }}
                                <div class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($payment['transaction_date'])->format('d.m.Y') }}</div>
                            </td>
                            <td class="border px-4 py-2">
                                <table class="w-full">
                                    <thead>
                                        <tr>
                                            <th class="w-1/2 text-left pb-2 border-b border-gray-400">Вид на леб</th>
                                            <th class="w-1/2 text-right pb-2 border-b border-gray-400">Количина × Цена</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($payment['breads'] as $breadName => $bread)
                                            @if($bread['total'] != 0)
                                                <tr>
                                                    <td class="py-1 text-lg font-bold border-b border-gray-300">{{ $breadName }}:</td>
                                                    <td class="py-1 text-lg font-bold text-right border-b border-gray-300">
                                                        {{ $bread['total'] }} x {{ $bread['price'] }} = {{ number_format($bread['potential_total'], 2) }}
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                            <td class="border px-4 py-2 text-center align-center">
                                {{ number_format($payment['total_amount'], 2) }}
                            </td>
                            <td class="border px-4 py-2 text-center align-center">
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
                        <td colspan="3" class="border px-4 py-2 font-bold text-right border-t-2 border-gray-400">
                            Вкупно неплатено:
                        </td>
                        <td class="border px-4 py-2 font-bold text-center border-t-2 border-gray-400">
                            {{ number_format(collect($unpaidTransactions)->sum('total_amount'), 2) }}
                        </td>
                        <td class="border px-4 py-2 border-t-2 border-gray-400"></td>
                    </tr>
                </tfoot>
            </table>
        </form>
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

        /* Make the table scrollable on smaller screens */
        .responsive-table {
            overflow-x: auto;
            display: block;
            width: 100%;
            -webkit-overflow-scrolling: touch; /* Smooth scrolling for iOS */
        }
    }
</style>

<script>
    
// Keep track of running totals for each input
const runningTotals = {};

function handleAccumulatingInput(input) {
    // Get input identifier (using the input's name)
    const inputId = input.name;
    
    // Get the new entered value
    let newValue = parseInt(input.value) || 0;
    
    // If this is the first time seeing this input, initialize its total
    if (!runningTotals[inputId]) {
        runningTotals[inputId] = parseInt(input.getAttribute('data-original-value')) || 0;
    }
    
    if (newValue === 0) {
        // Reset if zero entered
        runningTotals[inputId] = 0;
        input.value = 0;
    } else {
        // Add new value to running total
        runningTotals[inputId] += newValue;
        input.value = runningTotals[inputId];
    }
    
    // Store current total
    input.setAttribute('data-original-value', runningTotals[inputId]);
}

document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.accumulating-input');
    
    inputs.forEach(input => {
        // Initialize running total
        const inputId = input.name;
        runningTotals[inputId] = parseInt(input.value) || 0;
        
        // Store initial value
        input.setAttribute('data-original-value', input.value);
        
        // On focus, clear for new input
        input.addEventListener('focus', function() {
            const currentTotal = runningTotals[inputId];
            input.setAttribute('data-original-value', currentTotal);
            input.value = '';
        });
        
        // On blur, handle accumulation
        input.addEventListener('blur', function() {
            if (input.value === '') {
                // If no new value entered, restore the current total
                input.value = input.getAttribute('data-original-value');
            } else {
                handleAccumulatingInput(this);
            }
        });
    });
});
</script>

<script>
    

    document.addEventListener('DOMContentLoaded', function() {
    // Handle sold inputs in the second table
    const soldInputs = document.querySelectorAll('input[name^="sold["]');
    
    soldInputs.forEach(function(input) {
        // Clear value on focus if it's zero
        input.addEventListener('focus', function() {
            if (this.value === '0') {
                this.value = '';
            }
        });

        // Handle input validation and formatting
        input.addEventListener('input', function() {
            // Remove any non-numeric characters
            this.value = this.value.replace(/[^\d]/g, '');
            
            // Ensure the value is not negative
            let value = parseInt(this.value) || 0;
            if (value < 0) {
                this.value = 0;
            }
        });

        // Reset empty values to zero on blur
        input.addEventListener('blur', function() {
            if (this.value === '' || isNaN(this.value)) {
                this.value = '0';
            }
        });
    });
});

    // Handle form submission for the second table
const oldBreadForm = document.querySelector('form[action*="updateAdditional"]');
if (oldBreadForm) {
    oldBreadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);

        $.ajax({
            url: oldBreadForm.action,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert('Успешно ажурирање на табелата.');
                    // Redirect to daily transactions create page
                    window.location.href = '/daily-transactions/create';
                } else {
                    alert('Грешка при зачувување. Обидете се повторно.');
                }
            },
            error: function(xhr) {
                console.error('Error response:', xhr);
                alert('Грешка при зачувување. Обидете се повторно.');
            }
        });
    });
}



    
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Find all number input fields
    const numberInputs = document.querySelectorAll('input[type="number"]');
    
    numberInputs.forEach(input => {
        // Prevent default spinner behavior
        input.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
                e.preventDefault();
                
                // Find the current table row
                const currentRow = this.closest('tr');
                
                // Find all input fields in the table
                const allRows = Array.from(currentRow.closest('tbody').querySelectorAll('tr'));
                const currentRowIndex = allRows.indexOf(currentRow);
                
                // Determine target row based on arrow key
                let targetRow;
                if (e.key === 'ArrowUp' && currentRowIndex > 0) {
                    targetRow = allRows[currentRowIndex - 1];
                } else if (e.key === 'ArrowDown' && currentRowIndex < allRows.length - 1) {
                    targetRow = allRows[currentRowIndex + 1];
                }
                
                if (targetRow) {
                    // Find the input in the same column of the target row
                    const inputs = Array.from(currentRow.querySelectorAll('input[type="number"]'));
                    const currentInputIndex = inputs.indexOf(this);
                    const targetInput = targetRow.querySelectorAll('input[type="number"]')[currentInputIndex];
                    
                    if (targetInput) {
                        targetInput.focus();
                        // Optional: Select the content of the target input
                        targetInput.select();
                    }
                }
            }
        });

        // Clear value on focus if it's zero
        input.addEventListener('focus', function() {
            if (this.value === '0') {
                this.value = '';
            }
            // Select all content when focused
            this.select();
        });

        // Handle input validation and formatting
        input.addEventListener('input', function() {
            // Remove any non-numeric characters
            this.value = this.value.replace(/[^\d]/g, '');
            
            // Ensure the value is not negative
            let value = parseInt(this.value) || 0;
            if (value < 0) {
                this.value = 0;
            }
        });

        // Reset empty values to zero on blur
        input.addEventListener('blur', function() {
            if (this.value === '' || isNaN(this.value)) {
                this.value = '0';
            }
        });
    });
});
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle bulk payment functionality
        const selectAllCheckbox = document.getElementById('selectAll');
        const transactionCheckboxes = document.querySelectorAll('.transaction-checkbox');
        const bulkPaymentButton = document.getElementById('bulkPaymentButton');
        const bulkPaymentForm = document.getElementById('bulkPaymentForm');

        // Function to update the bulk payment button state
        function updateBulkPaymentButton() {
            const checkedBoxes = document.querySelectorAll('.transaction-checkbox:checked');
            bulkPaymentButton.disabled = checkedBoxes.length === 0;
        }

        // Handle "Select All" checkbox
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                transactionCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateBulkPaymentButton();
            });
        }

        // Handle individual checkboxes
        transactionCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allChecked = Array.from(transactionCheckboxes).every(cb => cb.checked);
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = allChecked;
                }
                updateBulkPaymentButton();
            });
        });

        // Store scroll position before form submission
        if (bulkPaymentForm) {
            bulkPaymentForm.addEventListener('submit', function() {
                localStorage.setItem('scrollPosition', window.scrollY);
            });
        }

        // Restore scroll position if exists
        if (localStorage.getItem('scrollPosition')) {
            window.scrollTo(0, localStorage.getItem('scrollPosition'));
            localStorage.removeItem('scrollPosition');
        }
    });
    </script>

<style>
/* Hide spinner buttons for number inputs */
input[type=number]::-webkit-inner-spin-button, 
input[type=number]::-webkit-outer-spin-button { 
    -webkit-appearance: none; 
    margin: 0; 
}

input[type=number] {
    -moz-appearance: textfield; /* Firefox */
}
</style>



@endsection