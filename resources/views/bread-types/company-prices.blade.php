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
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
            </svg>
        </div>
        <div class="ml-3">
            <p class="text-sm text-blue-700">
                Секоја компанија има стандардна ценовна група ({{ $company->price_group == 0 ? 'Основна цена' : 'Ценовна група ' . $company->price_group }}). 
                Овде може да изберете друга ценовна група само за овој производ.
            </p>
        </div>
    </div>
</div>
        <div class="mb-4">
    <label class="block text-gray-700 mb-2">Избери ценовна група</label>
    <select name="companies[{{ $company->id }}][price_group]" 
            class="w-full px-3 py-2 border rounded-lg price-group-select"
            data-company-id="{{ $company->id }}"
            data-base-price="{{ $breadType->price }}">
        <option value="0" 
                {{ $company->price_group === 0 ? 'selected' : '' }}
                data-price="{{ $breadType->price }}">
            Основна цена ({{ number_format($breadType->price, 2) }} ден.)
        </option>
        @for ($i = 1; $i <= 5; $i++)
            @php
                $groupPrice = $breadType->{'price_group_' . $i} ?? null;
            @endphp
            @if($groupPrice)
                <option value="{{ $i }}" 
                        {{ $company->price_group === $i ? 'selected' : '' }}
                        data-price="{{ $groupPrice }}">
                    Ценовна група {{ $i }} ({{ number_format($groupPrice, 2) }} ден.)
                </option>
            @endif
        @endfor
    </select>
    <p class="text-xs text-gray-500 mt-1">
        Тековна ценовна група за компанијата: 
        <strong>
            @if($company->price_group == 0)
                Основна цена
            @else
                Ценовна група {{ $company->price_group }}
            @endif
        </strong>
    </p>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-gray-700 mb-2">Цена</label>
        @php
            // First try to get company-specific price from pivot
            $specificPrice = DB::table('bread_type_company')
                ->where('bread_type_id', $breadType->id)
                ->where('company_id', $company->id)
                ->orderBy('valid_from', 'desc')
                ->first();
            
            // If company-specific price exists, use it
            if ($specificPrice) {
                $currentPrice = $specificPrice->price;
            } else {
                // Otherwise, use price based on company's price group
                $priceGroup = $company->price_group;
                
                if ($priceGroup > 0) {
                    $priceGroupField = "price_group_" . $priceGroup;
                    $currentPrice = $breadType->$priceGroupField ?? $breadType->price;
                } else {
                    $currentPrice = $breadType->price;
                }
            }
        @endphp
        <input type="number" 
               name="companies[{{ $company->id }}][price]" 
               value="{{ $currentPrice }}"
               step="0.01"
               min="0"
               required
               class="w-full px-3 py-2 border rounded-lg company-price">
    </div>
    
    <div>
        <label class="block text-gray-700 mb-2">Стара цена</label>
        @php
            // Get old price from pivot or use default
            $currentOldPrice = $specificPrice->old_price ?? $breadType->old_price;
        @endphp
        <input type="number" 
               name="companies[{{ $company->id }}][old_price]" 
               value="{{ $currentOldPrice }}"
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

    // Set up search functionality
    const searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.addEventListener('input', filterCompanies);
        const searchButton = document.querySelector('button[type="button"]');
        if (searchButton) {
            searchButton.addEventListener('click', filterCompanies);
        }
    }
    
    // Price group handling
    const priceGroupSelects = document.querySelectorAll('.price-group-select');
    
    priceGroupSelects.forEach(select => {
        // Initial price setup based on selected group
        const setInitialPrice = () => {
            const form = select.closest('form');
            const priceInput = form.querySelector('.company-price');
            const selectedOption = select.options[select.selectedIndex];
            
            if (selectedOption) {
                // Get price from the selected option's data-price attribute
                let price = selectedOption.dataset.price;
                
                // Only set if there's no existing value
                if (!priceInput.value || priceInput.value === '0.00') {
                    priceInput.value = parseFloat(price).toFixed(2);
                }
            }
        };

        // Run initial price setup
        setInitialPrice();

        // Handle price group changes
        select.addEventListener('change', function() {
            const form = this.closest('form');
            const priceInput = form.querySelector('.company-price');
            const selectedOption = this.options[this.selectedIndex];
            
            if (selectedOption) {
                // Get price from data-price attribute
                let newPrice = selectedOption.dataset.price;
                
                // Make sure we have a valid price
                if (newPrice) {
                    console.log('Updating price to:', newPrice);
                    priceInput.value = parseFloat(newPrice).toFixed(2);
                } else {
                    console.warn('No data-price found for selected option');
                }
            }
        });
    });

    // Handle form submissions for scroll position memory
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            // Store scroll position before submit
            localStorage.setItem('scrollPosition', window.scrollY);
        });
    });

    // Restore scroll position if exists
    if (localStorage.getItem('scrollPosition')) {
        window.scrollTo(0, localStorage.getItem('scrollPosition'));
        localStorage.removeItem('scrollPosition');
    }
});

// Replace the existing price group change handlers with this improved version
document.addEventListener('DOMContentLoaded', function() {
    // Get all price group select inputs
    const priceGroupSelects = document.querySelectorAll('.price-group-select');

    // For each price group select
    priceGroupSelects.forEach(select => {
        // Add change event listener
        select.addEventListener('change', function() {
            const form = this.closest('form');
            const priceInput = form.querySelector('.company-price');
            const selectedOption = this.options[this.selectedIndex];
            
            // Check if we have a selected option with data-price
            if (selectedOption && selectedOption.hasAttribute('data-price')) {
                // Get price from data-price attribute
                const newPrice = selectedOption.getAttribute('data-price');
                console.log('Changing price to:', newPrice);
                
                // Update the price input value
                if (newPrice && !isNaN(parseFloat(newPrice))) {
                    priceInput.value = parseFloat(newPrice).toFixed(2);
                    
                    // Add hidden input to mark this as a price group change, not a manual override
                    let hiddenInput = form.querySelector('input[name="price_group_change"]');
                    if (!hiddenInput) {
                        hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'price_group_change';
                        hiddenInput.value = '1';
                        form.appendChild(hiddenInput);
                    }
                }
            }
        });
        
        // Add event listener to price input to detect manual changes
        const form = select.closest('form');
        const priceInput = form.querySelector('.company-price');
        
        priceInput.addEventListener('change', function() {
            // Add hidden input to mark this as a manual override
            let hiddenInput = form.querySelector('input[name="manual_price_override"]');
            if (!hiddenInput) {
                hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'manual_price_override';
                hiddenInput.value = '1';
                form.appendChild(hiddenInput);
            }
        });
    });
});



</script>
@endsection