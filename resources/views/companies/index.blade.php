@extends('layouts.app')

@section('content')
<div class="container mx-auto p-0">
    <!-- Header Section -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Преглед на Компании</h1>
        <p class="text-gray-600 mt-2">Управувајте со вашите компании и нивните поставки</p>
    </div>

    <div class="mb-6">
    <form action="{{ route('companies.index') }}" method="GET" class="flex gap-4 items-end">
        <div class="w-64">
            <label class="block text-sm font-medium text-gray-700 mb-2">Филтрирај по корисник</label>
            <select name="user_id" 
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                    onchange="this.form.submit()">
                <option value="all">Сите компании</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="w-64">
            <label class="block text-sm font-medium text-gray-700 mb-2">Пребарај компанија</label>
            <div class="flex">
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}"
                       placeholder="Име или шифра на компанија" 
                       class="w-full rounded-l-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 rounded-r-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </div>
    </form>
</div>

    <!-- Add New Company Card -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Додади нова компанија</h2>
    <form action="{{ route('companies.store') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Име на компанијата</label>
                <input type="text" 
                       name="name" 
                       placeholder="Внесете име на компанијата" 
                       required 
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Шифра на компанијата</label>
                <input type="text" 
                       name="code" 
                       placeholder="Внесете шифра" 
                       required 
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Тип на плаќање</label>
                <select name="type" 
                        required 
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">Изберете тип</option>
                    <option value="invoice">Фактура</option>
                    <option value="cash">Кеш</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Поврзани корисници</label>
                <select name="user_ids" 
                        required 
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">Изберете корисник</option>
                    @foreach($users->sortBy('name') as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-150 ease-in-out flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Додади Компанија
                </button>
            </div>
        </div>
    </form>
</div>



    <!-- Companies Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($companies as $company)
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 bg-blue-500">
                <h3 class="text-lg font-semibold text-gray-800">{{ $company->name }}</h3>
            </div>

            <form action="{{ route('companies.update', $company) }}" method="POST" class="p-4">
    @csrf
    @method('PUT')
    
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Име на компанијата
            </label>
            <input type="text" 
                   name="name" 
                   value="{{ $company->name }}" 
                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Шифра на компанијата
            </label>
            <input type="text" 
                   name="code" 
                   value="{{ $company->code }}" 
                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
        </div>

            
            <!-- <form action="{{ route('companies.update', $company) }}" method="POST" class="p-4">
                @csrf
                @method('PUT')
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Име на компанијата
                        </label>
                        <input type="text" 
                               name="name" 
                               value="{{ $company->name }}" 
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div> -->

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Тип на плаќање
                        </label>
                        <select name="type" 
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            <option value="invoice" {{ $company->type == 'invoice' ? 'selected' : '' }}>Фактура</option>
                            <option value="cash" {{ $company->type == 'cash' ? 'selected' : '' }}>Кеш</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Поврзани корисници
                        </label>
                        <select name="user_ids" 
                                required 
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            @php
                                $assignedUser = $company->users->first();
                                $otherUsers = $users->where('id', '!=', $assignedUser?->id)->sortBy('name');
                            @endphp
                            
                            @if($assignedUser)
                                <option value="{{ $assignedUser->id }}" selected>
                                    {{ $assignedUser->name }}
                                </option>
                            @endif
                            
                            @foreach($otherUsers as $user)
                                <option value="{{ $user->id }}">
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex space-x-3 mt-6">
                    <button type="submit" 
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-150 ease-in-out">
                        Ажурирај
                    </button>

                    <a href="{{ route('companies.confirm-delete', $company) }}" 
                       class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-150 ease-in-out text-center">
                        Избриши
                    </a>
                </div>
            </form>
        </div>
        @endforeach
    </div>
</div>
@endsection