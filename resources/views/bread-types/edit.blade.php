@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
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
    <div class="max-w-lg mx-auto bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold mb-6">Промени на леб</h2>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('bread-types.update', $breadType) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="mb-4">
    <label for="code" class="block text-gray-700 font-bold mb-2">Код на артикл</label>
    <input type="text" 
           name="code" 
           id="code" 
           value="{{ old('code', $breadType->code) }}"
           required
           class="w-full px-3 py-2 border rounded-lg @error('code') border-red-500 @enderror">
    <p class="text-sm text-gray-500 mt-1">
        Внеси уникатен код за лебот (пример: BT0001)
    </p>
</div>

            <div class="mb-4">
                <label for="name" class="block text-gray-700 font-bold mb-2">Име</label>
                <input type="text" 
                       name="name" 
                       id="name" 
                       value="{{ old('name', $breadType->name) }}"
                       required
                       class="w-full px-3 py-2 border rounded-lg @error('name') border-red-500 @enderror">
            </div>

            <div class="mb-4">
                <label for="price" class="block text-gray-700 font-bold mb-2">Цена</label>
                <input type="number" 
                       name="price" 
                       id="price" 
                       value="{{ old('price', $breadType->price) }}"
                       step="0.01"
                       min="0"
                       required
                       class="w-full px-3 py-2 border rounded-lg @error('price') border-red-500 @enderror">
            </div>

            <div class="mb-4">
                <label for="old_price" class="block text-gray-700 font-bold mb-2">Стара цена за продажба</label>
                <input type="number" 
                       name="old_price" 
                       id="old_price" 
                       value="{{ old('old_price', $breadType->old_price) }}"
                       step="0.01"
                       min="0"
                       required
                       class="w-full px-3 py-2 border rounded-lg @error('old_price') border-red-500 @enderror">
            </div>

            <div class="mb-4">
                <label for="valid_from" class="block text-gray-700 font-bold mb-2">Промената важи од датум</label>
                <input type="date" 
                       name="valid_from" 
                       id="valid_from" 
                       value="{{ old('valid_from', date('Y-m-d')) }}"
                       step="0.01"

                       required
                       min="{{ date('Y-m-d') }}"
                       class="w-full px-3 py-2 border rounded-lg @error('valid_from') border-red-500 @enderror">
                <p class="text-sm text-gray-500 mt-1">
                    Датумот од кој ќе важат новите цени
                </p>
            </div>

            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" 
                           name="is_active" 
                           value="1"
                           {{ old('is_active', $breadType->is_active) ? 'checked' : '' }}
                           class="form-checkbox h-5 w-5 text-blue-600">
                    <span class="ml-2 text-gray-700">Дали е активен лебот</span>
                </label>
            </div>

            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" 
                           name="available_for_daily" 
                           value="1"
                           {{ old('available_for_daily', $breadType->available_for_daily) ? 'checked' : '' }}
                           class="form-checkbox h-5 w-5 text-blue-600">
                    <span class="ml-2 text-gray-700">Дали ќе се продава као стар леб</span>
                </label>
            </div>
            <div class="mt-4">
    <a href="{{ route('bread-types.companyPrices', $breadType) }}" 
       class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">
        Управувај со цени по компании
    </a>
</div>

            @if($priceHistory && $priceHistory->count() > 0)
            <div class="mb-6">
                <h3 class="font-bold mb-2">Историја на цени</h3>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <table class="min-w-full">
                        <thead>
                            <tr>
                                <th class="text-left">Важи од</th>
                                <th class="text-right">Цена</th>
                                <th class="text-right">Стара цена</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($priceHistory as $history)
                            <tr class="border-t">
                                <td class="py-2">{{ $history->valid_from->format('d.m.Y') }}</td>
                                <td class="py-2 text-right">{{ number_format($history->price, 2) }}</td>
                                <td class="py-2 text-right">{{ number_format($history->old_price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <div class="flex items-center justify-end">
                <a href="{{ route('bread-types.index') }}" 
                   class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 mr-2">
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                    Потврди измени
                </button>
            </div>
        </form>
    </div>
</div>
@endsection