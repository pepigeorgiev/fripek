@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold mb-6">Цени по компании за {{ $breadType->name }}</h2>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-4 flex items-center space-x-4">
            <div class="flex-1">
                <label for="search" class="block text-gray-700 font-bold mb-2">Пребарај компании</label>
                <div class="flex">
                    <input type="text" 
                           id="search" 
                           placeholder="Внеси име на компанија"
                           class="w-full px-3 py-2 border rounded-lg">
                    <button type="button" 
                            class="ml-2 bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                        Пребарај
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6" id="company-list">
            @foreach($companies as $company)
            <form action="{{ route('bread-types.updateCompanyPrices', ['breadType' => $breadType->id, 'company' => $company->id]) }}" 
                  method="POST" 
                  class="border p-4 rounded-lg company-item">
                @csrf
                <input type="hidden" name="companies[{{ $company->id }}][company_id]" value="{{ $company->id }}">
                <h3 class="font-bold mb-4">{{ $company->name }}</h3>
                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Избери ценовна група</label>
                    <select name="companies[{{ $company->id }}][price_group]" 
                            class="w-full px-3 py-2 border rounded-lg price-group-select"
                            data-company-id="{{ $company->id }}"
                            data-base-price="{{ $breadType->price }}">
                        <option value="0">Основна цена</option>
                        @for ($i = 1; $i <= 5; $i++)
                            @php
                                $groupPrice = $breadType->{'price_group_' . $i} ?? null;
                            @endphp
                            @if($groupPrice)
                                <option value="{{ $i }}" 
                                        {{ (isset($company->pivot->price_group) && $company->pivot->price_group == $i) ? 'selected' : '' }}
                                        data-price="{{ $groupPrice }}">
                                    Цена група {{ $i }} ({{ number_format($groupPrice, 2) }} ден.)
                                </option>
                            @endif
                        @endfor
                    </select>
                </div>
                
                @php
                    $pivot = $company->pivot ?? null;
                    $priceGroup = $pivot ? ($pivot->price_group ?? 0) : 0;
                    $price = old('companies.' . $company->id . '.price');
                    
                    if (!$price) {
                        if ($pivot && $pivot->price) {
                            $price = $pivot->price;
                        } else {
                            $price = $priceGroup > 0 ? 
                                     ($breadType->{'price_group_' . $priceGroup} ?? $breadType->price) : 
                                     $breadType->price;
                        }
                    }
                @endphp

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 mb-2">Цена</label>
                        <input type="number" 
                               name="companies[{{ $company->id }}][price]" 
                               value="{{ $price }}"
                               step="0.01"
                               min="0"
                               required
                               class="w-full px-3 py-2 border rounded-lg company-price">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2">Стара цена</label>
                        <input type="number" 
                               name="companies[{{ $company->id }}][old_price]" 
                               value="{{ old('companies.' . $company->id . '.old_price', 
                                        $pivot->old_price ?? $breadType->old_price) }}"
                               step="0.01"
                               min="0"
                               required
                               class="w-full px-3 py-2 border rounded-lg">
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-gray-700 mb-2">Важи од датум</label>
                    <input type="date" 
                           name="companies[{{ $company->id }}][valid_from]" 
                           value="{{ old('valid_from', date('Y-m-d')) }}"
                           required
                           min="{{ date('Y-m-d') }}"
                           class="w-full px-3 py-2 border rounded-lg">
                </div>

                <div class="flex justify-end mt-4">
                    <button type="submit" 
                            class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                        Зачувај за {{ $company->name }}
                    </button>
                </div>
            </form>
            @endforeach
        </div>

        <div class="flex justify-end mt-6">
            <a href="{{ route('bread-types.edit', $breadType) }}" 
               class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 mr-2">
                Назад
            </a>
        </div>

        @if($breadType->companies->isNotEmpty())
        <div class="mt-8">
            <h3 class="font-bold mb-4">Историја на цени по компании</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="px-4 py-2 text-left">Компанија</th>
                            <th class="px-4 py-2 text-right">Цена</th>
                            <th class="px-4 py-2 text-right">Стара цена</th>
                            <th class="px-4 py-2 text-left">Важи од</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($breadType->companies->unique('id') as $company)
                            @php
                            $priceHistory = DB::table('bread_type_company')
                                ->where('bread_type_id', $breadType->id)
                                ->where('company_id', $company->id)
                                ->orderBy('valid_from', 'desc')
                                ->orderBy('created_at', 'desc')
                                ->take(3)
                                ->get();
                            @endphp
                            
                            @foreach($priceHistory as $history)
                            <tr class="border-t hover:bg-gray-50">
                                @if($loop->first)
                                    <td class="px-4 py-2 align-top" rowspan="3">
                                        <span class="font-medium">{{ $company->name }}</span>
                                    </td>
                                @endif
                                <td class="px-4 py-2 text-right">{{ number_format($history->price, 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($history->old_price, 2) }}</td>
                                <td class="px-4 py-2">{{ date('d.m.Y', strtotime($history->valid_from)) }}</td>
                            </tr>
                            @endforeach
                            
                            <tr class="h-2 bg-gray-50">
                                <td colspan="4"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Search functionality
        const translitMap = {
            'a': 'а', 'b': 'б', 'v': 'в', 'g': 'г', 'd': 'д', 'e': 'е', 'zh': 'ж', 'z': 'з', 
            'i': 'и', 'j': 'ј', 'k': 'к', 'l': 'л', 'm': 'м', 'n': 'н', 'o': 'о', 'p': 'п', 
            'r': 'р', 's': 'с', 't': 'т', 'u': 'у', 'f': 'ф', 'h': 'х', 'c': 'ц', 'ch': 'ч', 
            'sh': 'ш', 'dj': 'џ', 'gj': 'ѓ', 'kj': 'ќ', 'z': 'ж', 'c': 'ч', 's':'ш' 
        };

        function transliterate(input) {
            return input.toLowerCase().replace(/ch|sh|dj|gj|kj|zh|[a-z]/g, function(match) {
                return translitMap[match] || match;
            });
        }

        function filterCompanies() {
            const searchValue = document.getElementById('search').value.toLowerCase();
            const transliteratedSearch = transliterate(searchValue);
            const companyItems = document.querySelectorAll('.company-item');

            companyItems.forEach(item => {
                const companyName = item.querySelector('h3').textContent.toLowerCase();
                const transliteratedName = transliterate(companyName);

                if (transliteratedName.includes(transliteratedSearch) || companyName.includes(transliteratedSearch)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        document.getElementById('search').addEventListener('input', filterCompanies);
        document.querySelector('button[type="button"]').addEventListener('click', filterCompanies);

        // Price group handling
        const priceGroupSelects = document.querySelectorAll('.price-group-select');
        
        priceGroupSelects.forEach(select => {
            select.addEventListener('change', function() {
                const form = this.closest('form');
                const priceInput = form.querySelector('.company-price');
                const selectedOption = this.options[this.selectedIndex];
                
                let newPrice = this.value === '0' ? 
                              this.dataset.basePrice : 
                              selectedOption.dataset.price;
                
                priceInput.value = parseFloat(newPrice).toFixed(2);
            });
        });
    });
</script>
@endsection