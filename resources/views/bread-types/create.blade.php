@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-lg mx-auto bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold mb-6">Додади нов леб</h2>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('bread-types.store') }}" class="space-y-4">
            @csrf

            <div class="mb-4">
    <label for="code" class="block text-gray-700 font-bold mb-2">Шифра на производ</label>
    <input type="text" 
           name="code" 
           id="code" 
           value="{{ old('code') }}"
           required
           class="w-full px-3 py-2 border rounded-lg @error('code') border-red-500 @enderror">
    <p class="text-sm text-gray-500 mt-1">
        Внеси шифра на производ од MyGPM
    </p>
</div>
            <div class="mb-4">
                <label for="name" class="block text-gray-700 font-bold mb-2">Име</label>
                <input type="text" 
                       name="name" 
                       id="name" 
                       value="{{ old('name') }}"
                       required
                       class="w-full px-3 py-2 border rounded-lg @error('name') border-red-500 @enderror">
            </div>

            <div class="mb-4">
                <label for="price" class="block text-gray-700 font-bold mb-2">Цена</label>
                <input type="number" 
                       name="price" 
                       id="price" 
                       value="{{ old('price', 0) }}"
                       step="0.00001"
                       min="0"
                       required
                       class="w-full px-3 py-2 border rounded-lg @error('price') border-red-500 @enderror">
            </div>

            <div class="mb-4">
                <label for="old_price" class="block text-gray-700 font-bold mb-2">Цена за продажба на вчерашен леб</label>
                <input type="number" 
                       name="old_price" 
                       id="old_price" 
                       value="{{ old('old_price', 0) }}"
                       step="0.00001"
                       min="0"
                       required
                       class="w-full px-3 py-2 border rounded-lg @error('old_price') border-red-500 @enderror">
            </div>

            <div class="mb-4">
                <label for="valid_from" class="block text-gray-700 font-bold mb-2">Важи од датум</label>
                <input type="date" 
                       name="valid_from" 
                       id="valid_from" 
                       value="{{ old('valid_from', date('Y-m-d')) }}"
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
                           {{ old('is_active', true) ? 'checked' : '' }}
                           class="form-checkbox h-5 w-5 text-blue-600">
                    <span class="ml-2 text-gray-700">Дали лебот е активен</span>
                </label>
            </div>

            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" 
                           name="available_for_daily" 
                           value="1"
                           {{ old('available_for_daily', false) ? 'checked' : '' }}
                           class="form-checkbox h-5 w-5 text-blue-600">
                    <span class="ml-2 text-gray-700">Вклучи го лебот за продажба на стар леб</span>
                </label>
                <p class="text-sm text-gray-500 mt-1">
                    Означи го ова доколку лебот ќе се продава како вчерашен леб
                </p>
            </div>

            <div class="flex items-center justify-end">
                <a href="{{ route('bread-types.index') }}" 
                   class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 mr-2">
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                    Додади леб
                </button>
            </div>
        </form>
    </div>
</div>
@endsection