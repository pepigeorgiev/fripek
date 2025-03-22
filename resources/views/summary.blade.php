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
            <!-- Removed the "Продаден" and "Разлика" columns -->
            <th class="px-4 py-2 text-lg font-bold text-center-desktop">Цена</th>
            <!-- <th class="px-4 py-2 text-lg font-bold text-center-desktop">Вкупно</th> -->
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
                
                <!-- Hidden input for old_bread_sold that might be needed for the form data -->
                <input type="hidden" 
                       name="old_bread_sold[{{ $breadType }}]" 
                       value="{{ old('old_bread_sold['.$breadType.']', $data['sold'] ?? 0) }}">
                
                <td class="border px-4 py-2 text-lg font-bold text-center-desktop">{{ $counts['price'] }}</td>
                <!-- <td class="border px-4 py-2 text-lg font-bold text-center-desktop">
                    {{ ($breadSale->sold_amount ?? $counts['sold'] ?? 0) * $counts['price'] }}
                </td> -->
            </tr>
        @endforeach
    </tbody>
    <tfoot>
    <tr>
        <td colspan="3" class="border px-4 py-2 font-bold text-right text-lg font-bold ">Вкупно:</td>
        <td class="border px-4 py-2 text-lg font-bold text-center-desktop">
            @php
                $totalDifference = 0;
                foreach($breadCounts as $breadType => $counts) {
                    $breadTypeObj = $breadTypes->firstWhere('name', $breadType);
                    $breadSale = $breadSales->flatten()->where('bread_type_id', $breadTypeObj->id)->first();
                    $returned = $breadSale->returned_amount ?? $counts['returned'] ?? 0;
                    $difference = $counts['sent'] - $returned;
                    $totalDifference += $difference;
                }
            @endphp
            {{ $totalDifference }}
        </td>
        <td class="border px-4 py-2 text-lg font-bold text-center-desktop"></td>
        <!-- <td class="border px-4 py-2 text-lg font-bold text-center-desktop">{{ number_format($totalInPrice, 2) }}</td> -->
    </tr>
</tfoot>
    <!-- <tfoot>
        <tr>
            <td colspan="3" class="border px-4 py-2 font-bold text-right text-lg font-bold ">Вкупно:</td>
            <td class="border px-4 py-2 text-lg font-bold text-center-desktop">{{ $totalSold }}</td>
            <td class="border px-4 py-2 text-lg font-bold text-center-desktop"></td>
            <td class="border px-4 py-2 text-lg font-bold text-center-desktop">{{ number_format($totalInPrice, 2) }}</td>
        </tr>
    </tfoot> -->
</table>
            <!-- <table class="w-full bg-white shadow-md rounded">
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
            </table> -->
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
    <div class="mb-8" id="unpaidTransactionsSection">
        <h2 class="text-xl font-semibold mb-2 text-xl font-bold">Неплатени трансакции за следење</h2>
        <div class="bg-yellow-50 p-4 mb-4 border-l-4 border-yellow-400">
            <p class="text-blue-700 text-xl font-bold">
                Овие трансакции се означени како неплатени и не се вклучени во вкупната сума на кеш плаќања.
            </p>
        </div>

        <form id="bulkPaymentForm" action="{{ route('daily-transactions.markMultipleAsPaid') }}" method="POST">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">
            
            {{-- Pagination selector and bulk payment button --}}
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center">
                    <span class="mr-2">Прикажи:</span>
                    <select id="unpaidPerPage" class="bg-white border border-gray-300 rounded px-2 py-1 text-sm" onchange="changeUnpaidPerPage(this.value)">
                        <option value="10" {{ $unpaidTransactionsPagination['perPage'] == 10 ? 'selected' : '' }}>10</option>
                        <option value="25" {{ $unpaidTransactionsPagination['perPage'] == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ $unpaidTransactionsPagination['perPage'] == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ $unpaidTransactionsPagination['perPage'] == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>
                
                <button type="submit" 
                        id="bulkPaymentButton"
                        disabled
                        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed">
                    Означи ги селектираните како платени
                </button>
            </div>

            {{-- Main table - this is the same as your original --}}
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
                            {{ number_format($unpaidTransactionsTotal, 2) }}
                        </td>
                        <td class="border px-4 py-2 border-t-2 border-gray-400"></td>
                    </tr>
                </tfoot>
            </table>
        </form>
        
        {{-- Pagination controls - new section --}}
        @if($unpaidTransactionsPagination['total'] > 0)
            <div class="mt-4 flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Прикажани <span class="font-medium">{{ count($unpaidTransactions) }}</span> од вкупно <span class="font-medium">{{ $unpaidTransactionsPagination['total'] }}</span> неплатени трансакции
                </div>
                
                <div class="flex items-center space-x-2">
                    {{-- Previous Page Button --}}
                    @if($unpaidTransactionsPagination['currentPage'] > 1)
                        <a href="{{ request()->fullUrlWithQuery(['unpaid_page' => $unpaidTransactionsPagination['currentPage'] - 1, 'unpaid_per_page' => $unpaidTransactionsPagination['perPage']]) }}" 
                        class="px-3 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                            &laquo; Претходна
                        </a>
                    @else
                        <span class="px-3 py-1 bg-gray-100 text-gray-400 rounded-md cursor-not-allowed">
                            &laquo; Претходна
                        </span>
                    @endif
                    
                    {{-- Page Numbers --}}
                    <div class="flex space-x-1">
                        @for($i = 1; $i <= $unpaidTransactionsPagination['lastPage']; $i++)
                            <a href="{{ request()->fullUrlWithQuery(['unpaid_page' => $i, 'unpaid_per_page' => $unpaidTransactionsPagination['perPage']]) }}" 
                            class="px-3 py-1 {{ $i == $unpaidTransactionsPagination['currentPage'] ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }} rounded-md">
                                {{ $i }}
                            </a>
                        @endfor
                    </div>
                    
                    {{-- Next Page Button --}}
                    @if($unpaidTransactionsPagination['currentPage'] < $unpaidTransactionsPagination['lastPage'])
                        <a href="{{ request()->fullUrlWithQuery(['unpaid_page' => $unpaidTransactionsPagination['currentPage'] + 1, 'unpaid_per_page' => $unpaidTransactionsPagination['perPage']]) }}" 
                        class="px-3 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                            Следна &raquo;
                        </a>
                    @else
                        <span class="px-3 py-1 bg-gray-100 text-gray-400 rounded-md cursor-not-allowed">
                            Следна &raquo;
                        </span>
                    @endif
                </div>
            </div>
        @endif
    </div>
    
    {{-- Add JavaScript to handle pagination controls --}}
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to change items per page
        window.changeUnpaidPerPage = function(perPage) {
            const url = new URL(window.location.href);
            url.searchParams.set('unpaid_per_page', perPage);
            url.searchParams.set('unpaid_page', 1); // Reset to first page when changing items per page
            window.location.href = url.toString();
        };
        
        // The existing checkbox functionality
        const selectAllCheckbox = document.getElementById('selectAll');
        const transactionCheckboxes = document.querySelectorAll('.transaction-checkbox');
        const bulkPaymentButton = document.getElementById('bulkPaymentButton');
        
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

        // Store scroll position when navigating pagination
        const paginationLinks = document.querySelectorAll('.mt-4 a');
        paginationLinks.forEach(link => {
            link.addEventListener('click', function() {
                sessionStorage.setItem('unpaidScrollPosition', window.scrollY);
            });
        });
        
        // Restore scroll position
        const savedScrollPosition = sessionStorage.getItem('unpaidScrollPosition');
        if (savedScrollPosition) {
            window.scrollTo(0, savedScrollPosition);
            sessionStorage.removeItem('unpaidScrollPosition');
        }
    });
    </script>
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
   // Add this script to your page
   document.addEventListener('DOMContentLoaded', function() {
    // Create a simple search box and button
    const searchContainer = document.createElement('div');
    searchContainer.className = 'flex items-center mb-4 mt-4';
    
    const searchLabel = document.createElement('span');
    searchLabel.textContent = 'Пребарувај : ';
    searchLabel.className = 'mr-2 font-bold';
    
    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.id = 'simpleSearchInput';
    searchInput.placeholder = 'Внесете текст за пребарување...';
    searchInput.className = 'bg-white border border-gray-300 rounded px-3 py-2 text-sm w-64 mr-2';
    
    const searchButton = document.createElement('button');
    searchButton.type = 'button';
    searchButton.id = 'simpleSearchButton';
    searchButton.className = 'bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition duration-150 ease-in-out';
    searchButton.textContent = 'Пребарај';
    
    const clearButton = document.createElement('button');
    clearButton.type = 'button';
    clearButton.id = 'simpleClearButton';
    clearButton.className = 'bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg ml-2 transition duration-150 ease-in-out';
    clearButton.textContent = 'Исчисти';
    
    searchContainer.appendChild(searchLabel);
    searchContainer.appendChild(searchInput);
    searchContainer.appendChild(searchButton);
    searchContainer.appendChild(clearButton);
    
    // Find the unpaid transactions section and add the search box
    const unpaidSection = document.getElementById('unpaidTransactionsSection');
    if (unpaidSection) {
        const firstElement = unpaidSection.querySelector('.bg-yellow-50') || unpaidSection.firstElementChild;
        unpaidSection.insertBefore(searchContainer, firstElement.nextSibling);
        
        // Find all pagination links
        const pagination = document.querySelector('.flex.items-center.space-x-2');
        const allPaginationLinks = pagination ? pagination.querySelectorAll('a') : [];
        
        // Get the current page and total pages
        const currentUrl = new URL(window.location.href);
        const currentPage = parseInt(currentUrl.searchParams.get('unpaid_page') || '1');
        const lastPage = allPaginationLinks.length > 0 ? 
            parseInt(allPaginationLinks[allPaginationLinks.length - 1].textContent.trim()) : 1;
        
        // Function to load all pages and search them
        async function loadAllPagesAndSearch(searchText) {
            // Show loading indicator
            const loadingIndicator = document.createElement('div');
            loadingIndicator.id = 'searchLoadingIndicator';
            loadingIndicator.className = 'fixed top-0 left-0 w-full h-full bg-black bg-opacity-50 flex items-center justify-center z-50';
            loadingIndicator.innerHTML = `
                <div class="bg-white p-5 rounded-lg shadow-lg text-center">
                    <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-500 mx-auto mb-2"></div>
                    <p class="text-lg font-medium">Пребарување на сите страници...</p>
                </div>
            `;
            document.body.appendChild(loadingIndicator);
            
            try {
                // Convert to Latin if Cyrillic
                const latinSearchText = convertCyrillicToLatin(searchText);
                
                // Store all found companies
                const allMatchingCompanies = new Set();
                
                // Fetch all pages to find matching companies
                for (let page = 1; page <= lastPage; page++) {
                    if (page !== currentPage) {
                        // Create URL for fetching the page content
                        const pageUrl = new URL(window.location.href);
                        pageUrl.searchParams.set('unpaid_page', page.toString());
                        
                        // Fetch the page content
                        const response = await fetch(pageUrl.toString());
                        const html = await response.text();
                        
                        // Create a temporary element to parse the HTML
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = html;
                        
                        // Find all company cells in the page
                        const companyCells = tempDiv.querySelectorAll('#unpaidTransactionsSection tbody > tr > td:nth-child(2)');
                        
                        // Check each company for matches
                        companyCells.forEach(cell => {
                            const companyText = cell.textContent.toLowerCase();
                            const latinCompanyText = convertCyrillicToLatin(companyText);
                            
                            if (companyText.includes(searchText.toLowerCase()) || 
                                latinCompanyText.includes(latinSearchText)) {
                                // Extract company name (first line of text)
                                const companyName = cell.childNodes[0].textContent.trim();
                                allMatchingCompanies.add(companyName);
                            }
                        });
                    }
                }
                
                // Add matches from current page
                const currentPageCompanyCells = unpaidSection.querySelectorAll('tbody > tr > td:nth-child(2)');
                currentPageCompanyCells.forEach(cell => {
                    const companyText = cell.textContent.toLowerCase();
                    const latinCompanyText = convertCyrillicToLatin(companyText);
                    
                    if (companyText.includes(searchText.toLowerCase()) || 
                        latinCompanyText.includes(latinSearchText)) {
                        // Extract company name (first line of text)
                        const companyName = cell.childNodes[0].textContent.trim();
                        allMatchingCompanies.add(companyName);
                    }
                });
                
                // If we found matches on any page, filter the current page
                if (allMatchingCompanies.size > 0) {
                    filterCurrentPage(allMatchingCompanies);
                } else {
                    // No matches found
                    alert('Нема пронајдени резултати на ниту една страница.');
                }
            } catch (error) {
                console.error('Error searching across pages:', error);
                alert('Се појави грешка при пребарувањето низ страниците.');
            } finally {
                // Remove loading indicator
                document.getElementById('searchLoadingIndicator').remove();
            }
        }
        
        // Function to filter the current page based on matching companies
        function filterCurrentPage(matchingCompanies) {
            const tbody = unpaidSection.querySelector('tbody');
            if (!tbody) return;
            
            // Process all rows in the current page
            const rows = Array.from(tbody.children);
            
            rows.forEach(row => {
                const companyCell = row.querySelector('td:nth-child(2)');
                if (!companyCell) return;
                
                const companyName = companyCell.childNodes[0].textContent.trim();
                
                if (matchingCompanies.has(companyName)) {
                    // Show this row
                    row.style.display = '';
                    
                    // Ensure all nested tables and content are visible
                    ensureNestedContentVisible(row);
                } else {
                    // Hide this row
                    row.style.display = 'none';
                }
            });
            
            // Update the results counter
            updateResultCounter();
        }
        
        // Function to ensure all nested content is visible
        function ensureNestedContentVisible(element) {
            // Make the element itself visible
            element.style.display = '';
            
            // Process all direct child elements
            Array.from(element.children).forEach(child => {
                // Make each child visible
                child.style.display = '';
                
                // If this is a table cell with a nested table
                if (child.tagName === 'TD') {
                    // Find any nested tables
                    const nestedTables = child.querySelectorAll('table');
                    nestedTables.forEach(table => {
                        // Make the table visible
                        table.style.display = '';
                        
                        // Make all table elements visible
                        const tableElements = table.querySelectorAll('*');
                        tableElements.forEach(el => {
                            el.style.display = '';
                        });
                    });
                }
                
                // Recursively process any other nested elements
                ensureNestedContentVisible(child);
            });
        }
        
        // Improved search function
        function runSearch() {
            const searchText = searchInput.value.toLowerCase().trim();
            
            // If search is empty, show everything
            if (!searchText) {
                // Show all rows
                const rows = unpaidSection.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    row.style.display = '';
                    ensureNestedContentVisible(row);
                });
                
                // Remove any previous result messages
                const prevResults = document.getElementById('searchResultMessage');
                if (prevResults) prevResults.remove();
                
                updateResultCounter();
                return;
            }
            
            // Check if we should search across all pages
            // For simplicity, always search across pages if more than one page exists
            if (lastPage > 1) {
                loadAllPagesAndSearch(searchText);
                return;
            }
            
            // Simple search for single page
            const latinSearchText = convertCyrillicToLatin(searchText);
            
            // Track companies that match
            const matchingCompanies = new Set();
            
            // Find all matching companies on this page
            const companyCells = unpaidSection.querySelectorAll('tbody > tr > td:nth-child(2)');
            companyCells.forEach(cell => {
                const companyText = cell.textContent.toLowerCase();
                const latinCompanyText = convertCyrillicToLatin(companyText);
                
                if (companyText.includes(searchText) || latinCompanyText.includes(latinSearchText)) {
                    const companyName = cell.childNodes[0].textContent.trim();
                    matchingCompanies.add(companyName);
                }
            });
            
            filterCurrentPage(matchingCompanies);
        }
        
        // Function to convert Cyrillic to Latin
        function convertCyrillicToLatin(text) {
            const cyrillicMap = {
                'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'ѓ': 'gj', 'е': 'e',
                'ж': 'zh', 'з': 'z', 'ѕ': 'dz', 'и': 'i', 'ј': 'j', 'к': 'k', 'л': 'l',
                'љ': 'lj', 'м': 'm', 'н': 'n', 'њ': 'nj', 'о': 'o', 'п': 'p', 'р': 'r',
                'с': 's', 'т': 't', 'ќ': 'kj', 'у': 'u', 'ф': 'f', 'х': 'h', 'ц': 'c',
                'ч': 'ch', 'џ': 'dzh', 'ш': 'sh', 'А': 'A', 'Б': 'B', 'В': 'V', 'Г': 'G',
                'Д': 'D', 'Ѓ': 'GJ', 'Е': 'E', 'Ж': 'ZH', 'З': 'Z', 'Ѕ': 'DZ', 'И': 'I',
                'Ј': 'J', 'К': 'K', 'Л': 'L', 'Љ': 'LJ', 'М': 'M', 'Н': 'N', 'Њ': 'NJ',
                'О': 'O', 'П': 'P', 'Р': 'R', 'С': 'S', 'Т': 'T', 'Ќ': 'KJ', 'У': 'U',
                'Ф': 'F', 'Х': 'H', 'Ц': 'C', 'Ч': 'CH', 'Џ': 'DZH', 'Ш': 'SH'
            };
            
            return text.split('').map(char => cyrillicMap[char] || char).join('');
        }
        
        // Update the "showing X of Y results" counter
        function updateResultCounter() {
            const resultsText = unpaidSection.querySelector('.text-sm.text-gray-700');
            if (!resultsText) return;
            
            const allRows = unpaidSection.querySelectorAll('tbody > tr');
            const visibleRows = Array.from(allRows).filter(row => row.style.display !== 'none').length;
            
            const firstSpan = resultsText.querySelector('span:first-of-type');
            if (firstSpan) {
                firstSpan.textContent = visibleRows;
            }
        }
        
        // Add event listeners
        searchButton.addEventListener('click', runSearch);
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                runSearch();
                e.preventDefault();
            }
        });
        
        clearButton.addEventListener('click', function() {
            searchInput.value = '';
            // Remove any previous result messages
            const prevResults = document.getElementById('searchResultMessage');
            if (prevResults) prevResults.remove();
            runSearch();
        });
        
        // Add code to preserve scroll position when changing items per page
        const perPageSelect = document.getElementById('unpaidPerPage');
        if (perPageSelect) {
            // Save current implementation
            const originalChangePerPage = window.changeUnpaidPerPage;
            
            // Override with our improved version
            window.changeUnpaidPerPage = function(perPage) {
                // Save scroll position to session storage
                sessionStorage.setItem('unpaidScrollPosition', window.scrollY);
                
                // Call the original function
                if (typeof originalChangePerPage === 'function') {
                    originalChangePerPage(perPage);
                } else {
                    // Fallback implementation
                    const url = new URL(window.location.href);
                    url.searchParams.set('unpaid_per_page', perPage);
                    url.searchParams.set('unpaid_page', 1);
                    window.location.href = url.toString();
                }
            };
        }
        
        // Restore scroll position on page load
        const savedScrollPosition = sessionStorage.getItem('unpaidScrollPosition');
        if (savedScrollPosition) {
            // Add a small delay to ensure the page is fully loaded
            setTimeout(function() {
                window.scrollTo(0, savedScrollPosition);
                sessionStorage.removeItem('unpaidScrollPosition');
            }, 100);
        }
    }
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