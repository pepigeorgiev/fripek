@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold mb-4">Историја на промени</h1>
        
        <!-- Filter Form -->
        <form action="{{ route('transaction.history') }}" method="GET" class="bg-white p-4 rounded-lg shadow-sm mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Од датум</label>
                    <input type="date" name="date_from" 
                           value="{{ request('date_from', $date_from->format('Y-m-d')) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">До датум</label>
                    <input type="date" name="date_to" 
                           value="{{ request('date_to', $date_to->format('Y-m-d')) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Компанија</label>
                    <select name="company_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">Сите компании</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" 
                                {{ (request('company_id') == $company->id || (isset($selectedCompany) && $selectedCompany->id == $company->id)) ? 'selected' : '' }}>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Корисник</label>
                    <select name="user_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">Сите корисници</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4 flex items-center">
                <!-- Checkbox removed since we're always filtering zero changes -->
                <button type="submit" class="ml-auto bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                    Филтрирај
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Време</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Корисник</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Компанија</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Производ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Промени</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Адреса</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($history as $record)
                @php
                    $hasSignificantChange = false;
                    
                    // Check if there are significant changes in delivered
                    if (isset($record->old_values['delivered']) && isset($record->new_values['delivered'])) {
                        $oldDelivered = (int)$record->old_values['delivered'];
                        $newDelivered = (int)$record->new_values['delivered'];
                        $deliveredDiff = abs($newDelivered - $oldDelivered);
                        if ($deliveredDiff > 0) {
                            $hasSignificantChange = true;
                        }
                    }
                    
                    // Check if there are significant changes in returned
                    if (isset($record->old_values['returned']) && isset($record->new_values['returned'])) {
                        $oldReturned = (int)$record->old_values['returned'];
                        $newReturned = (int)$record->new_values['returned'];
                        $returnedDiff = abs($newReturned - $oldReturned);
                        if ($returnedDiff > 0) {
                            $hasSignificantChange = true;
                        }
                    }
                    
                    // Check if there are significant changes in gratis
                    if (isset($record->old_values['gratis']) && isset($record->new_values['gratis'])) {
                        $oldGratis = (int)$record->old_values['gratis'];
                        $newGratis = (int)$record->new_values['gratis'];
                        $gratisDiff = abs($newGratis - $oldGratis);
                        if ($gratisDiff > 0) {
                            $hasSignificantChange = true;
                        }
                    }
                    
                    // Skip records without significant changes - always filter out zero changes regardless of checkbox
                    if (!$hasSignificantChange) {
                        continue;
                    }
                    
                    $createdAt = \Carbon\Carbon::parse($record->created_at);
                    // Flag changes made outside of working hours (5 AM to 11 AM)
                    $isOutsideWorkingHours = !($createdAt->hour >= 5 && $createdAt->hour < 11);
                    $isNotCurrentDate = $record->transaction && !$record->transaction->transaction_date->isToday();
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">{{ $record->created_at->format('d.m.Y H:i:s') }}</td>
                    <td class="px-6 py-4">{{ $record->user->name ?? 'N/A' }}</td>
                    <td class="px-6 py-4">
                        {{ optional($record->transaction->company)->name ?? 'Избришана компанија' }}
                    </td>
                    <td class="px-6 py-4">
                        {{ optional($record->transaction->breadType)->name ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm">
                            @if($isOutsideWorkingHours)
                                <span class="text-red-500 text-xs">Промена надвор од работни часови ({{ $record->created_at->format('H:i') }})</span>
                            @endif
                            
                            @if($isNotCurrentDate)
                                <span class="text-orange-500 text-xs">Промена на минат датум</span>
                            @endif
                            
                            @if(isset($record->old_values['delivered']) || isset($record->new_values['delivered']))
                                <div>
                                    <span class="text-red-500">Старо испорачано: {{ $record->old_values['delivered'] ?? 'N/A' }}</span>
                                    <span class="text-green-500">→ Ново: {{ $record->new_values['delivered'] ?? 'N/A' }}</span>
                                </div>
                            @endif
                            
                            @if(isset($record->old_values['returned']) || isset($record->new_values['returned']))
                                <div class="text-sm">
                                    <span class="text-red-500">Старо вратено: {{ $record->old_values['returned'] ?? 'N/A' }}</span>
                                    <span class="text-green-500">→ Ново: {{ $record->new_values['returned'] ?? 'N/A' }}</span>
                                </div>
                            @endif
                            
                            @if(isset($record->old_values['gratis']) || isset($record->new_values['gratis']))
                                <div class="text-sm">
                                    <span class="text-red-500">Старо гратис: {{ $record->old_values['gratis'] ?? 'N/A' }}</span>
                                    <span class="text-green-500">→ Ново: {{ $record->new_values['gratis'] ?? 'N/A' }}</span>
                                </div>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm">{{ $record->ip_address }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $history->appends(request()->query())->links() }}
    </div>
</div>
@endsection