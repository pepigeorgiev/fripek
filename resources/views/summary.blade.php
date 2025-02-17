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
    <form id="oldBreadSalesForm" action="{{ route('old-bread-sales.store') }}" method="POST">
    @csrf
    <input type="hidden" name="transaction_date" value="{{ $date }}">
    <input type="hidden" 
           name="sold[{{ $breadType }}][bread_type_id]" 
           value="{{ $breadTypeObj->id }}">
           
    <input type="number" 
           name="sold[{{ $breadType }}][sold]" 
           value="{{ $soldValue }}" 
           class="w-full px-2 py-1 border rounded text-center-desktop"
           @unless($canEdit) readonly @endunless
           min="0">
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
                                @php
                                    $breadInfo = $payment['breads'][$breadType->name] ?? null;
                                    if ($breadInfo) {
                                        echo $breadInfo;
                                    } else {
                                        $price = $breadType->getPriceForCompany($payment['company_id'], $date)['price'];
                                        echo "0 x {$price} = 0.00";
                                    }
                                @endphp
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
        <h2 class="text-xl font-semibold mb-2 text-xl font-bold">Табела за дневен преглед на компании за плаќање на фактура</h2>
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
                                @php
                                    $breadInfo = $payment['breads'][$breadType->name] ?? null;
                                    if ($breadInfo) {
                                        echo $breadInfo;
                                    } else {
                                        $price = $breadType->getPriceForCompany($payment['company_id'], $date)['price'];
                                        echo "0 x {$price} = 0.00";
                                    }
                                @endphp
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
                    <td colspan="{{ count($breadTypes) + 1 }}" class="border px-4 py-2 font-bold text-right text-lg font-bold">
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
    // Select all number input fields
    const numberInputs = document.querySelectorAll('input[type="number"]');

    // Add mobile-friendly attributes to each input
    numberInputs.forEach(input => {
        input.addEventListener('focus', function() {
            // Ensure the numeric keypad is shown
            this.setAttribute('inputmode', 'numeric');
            this.setAttribute('pattern', '[0-9]*');
        });
    });
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



<!-- <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Select all number input fields
        const numberInputs = document.querySelectorAll('input[type="number"]');

        // Add focus event listener to each input
        numberInputs.forEach(input => {
            input.addEventListener('focus', function() {
                // Ensure the numeric keypad is shown
                this.setAttribute('inputmode', 'numeric');
            });
        });
    });
</script> -->

@endsection