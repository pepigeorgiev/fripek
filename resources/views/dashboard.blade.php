@extends('layouts.app')

@section('content')
<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Дневен преглед</h1>
        
        <div class="flex items-center space-x-4">
            <!-- User Filter Dropdown - Only visible to admin and super admin -->
            @if($currentUser->isAdmin() || $currentUser->role === 'super_admin')
                <form method="GET" action="{{ route('dashboard') }}" class="flex items-center space-x-2">
                    <!-- Preserve date if it was selected -->
                    @if(request()->has('date'))
                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                    @endif
                    
                    <select 
                        name="user_id" 
                        onchange="this.form.submit()"
                        class="border rounded px-3 py-1 bg-white"
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

            <!-- Date Filter -->
            <form method="GET" action="{{ route('dashboard') }}" class="flex items-center space-x-2">
                <!-- Preserve user_id if it was selected -->
                @if(request()->has('user_id'))
                    <input type="hidden" name="user_id" value="{{ $selectedUserId }}">
                @endif
                
                <input 
                    type="date" 
                    name="date" 
                    value="{{ $selectedDate }}"
                    class="border rounded px-3 py-1"
                    onchange="this.form.submit()"
                >
            </form>
        </div>
    </div>
    
    <!-- Companies Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
    @forelse($companies as $company)
        <div class="bg-white p-4 rounded shadow border-l-4 {{ $company->type === 'cash' ? 'border-green-500' : 'border-blue-500' }}">
            <div class="flex justify-between items-center mb-2">
                <h2 class="text-lg font-semibold">{{ $company->name }}</h2>
                <span class="px-2 py-1 rounded-full text-sm {{ $company->type === 'cash' 
                    ? 'bg-green-100 text-green-800' 
                    : 'bg-blue-100 text-blue-800' }}">
                    {{ $company->type === 'cash' ? 'Кеш' : 'Фактура' }}
                </span>
            </div>
            
            @if(isset($todaysTransactions[$company->id]) && $todaysTransactions[$company->id]->isNotEmpty())
                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="text-left">Име на лебот</th>
                            <th class="text-right">Пратен</th>
                            <th class="text-right">Вратен</th>
                            <th class="text-right">Гратис</th>

                        </tr>
                    </thead>
                    <tbody>
                        @foreach($todaysTransactions[$company->id] as $transaction)
                            @if($transaction->breadType && ($transaction->delivered > 0 || $transaction->returned > 0))
                                <tr>
                                    <td>{{ $transaction->breadType->name }}</td>
                                    <td class="text-center">{{ $transaction->delivered }}</td>
                                    <td class="text-center font-semibold {{ $transaction->returned > 0 ? 'text-red-600' : '' }}">
                                        {{ $transaction->returned }}
                                    </td>
                                    <td class="text-center font-semibold {{ $transaction->gratis > 0 ? 'text-blue-600' : '' }}">
                                        {{ $transaction->gratis }}
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
                
                <div class="text-sm text-gray-500 mt-2">
                    Време на трансакција: {{ $todaysTransactions[$company->id]->first()->created_at->format('H:i') }}
                </div>
            @else
                <p class="text-gray-500">Денес нема трансакции</p>
            @endif

            <a href="{{ route('monthly-summary.show', $company) }}" 
               class="text-blue-500 hover:underline mt-4 block">
                Месечен преглед
            </a>
        </div>
    @empty
        <div class="col-span-full">
            <p class="text-gray-500">Нема пронајдено компании</p>
        </div>
    @endforelse
</div>
    
@endsection