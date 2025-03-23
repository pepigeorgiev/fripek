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
                                {{ (request('company_id') == $company->id) ? 'selected' : '' }}>
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

            <div class="mt-4 flex flex-wrap items-center gap-4">
               
                </div>
                
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
                @if($history->isEmpty())
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                            Нема пронајдени записи за избраниот период. Обидете се да го проширите временскиот опсег.
                        </td>
                    </tr>
                @else
                    @foreach($history as $record)
                        @php
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
                                        <span class="text-red-500 text-xs block mb-1">Промена надвор од работни часови ({{ $record->created_at->format('H:i') }})</span>
                                    @endif
                                    
                                    @if($isNotCurrentDate)
                                        <span class="text-orange-500 text-xs block mb-1">Промена на минат датум</span>
                                    @endif
                                    
                                    @if(isset($record->old_values['delivered']) && isset($record->new_values['delivered']) && $record->old_values['delivered'] != $record->new_values['delivered'])
                                        <div class="mb-1">
                                            <span class="text-red-500">Испорачано: {{ $record->old_values['delivered'] }}</span>
                                            <span class="text-green-500">→ {{ $record->new_values['delivered'] }}</span>
                                        </div>
                                    @endif
                                    
                                    @if(isset($record->old_values['returned']) && isset($record->new_values['returned']) && $record->old_values['returned'] != $record->new_values['returned'])
                                        <div class="mb-1">
                                            <span class="text-red-500">Вратено: {{ $record->old_values['returned'] }}</span>
                                            <span class="text-green-500">→ {{ $record->new_values['returned'] }}</span>
                                        </div>
                                    @endif
                                    
                                    @if(isset($record->old_values['gratis']) && isset($record->new_values['gratis']) && $record->old_values['gratis'] != $record->new_values['gratis'])
                                        <div class="mb-1">
                                            <span class="text-red-500">Гратис: {{ $record->old_values['gratis'] }}</span>
                                            <span class="text-green-500">→ {{ $record->new_values['gratis'] }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $record->ip_address }}</td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $history->appends(request()->query())->links() }}
    </div>
</div>
@endsection