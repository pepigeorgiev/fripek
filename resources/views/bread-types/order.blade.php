@extends('layouts.app')

@section('content')
<div class="container bg-white rounded-lg shadow-lg p-6 mx-auto max-w-5xl">
    <div class="flex items-center mb-6">
        <i class="fas fa-sort-amount-down text-blue-600 text-2xl mr-3"></i>
        <h1 class="text-2xl font-bold text-gray-800">Редослед на типови леб</h1>
    </div>
    
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-500 mt-1"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700">
                    Промени го редниот број на типот леб што сакаш да го преместиш. Останатите броеви автоматски ќе се прилагодат. Новите типови на леб ќе бидат додадени на крајот од листата.
                </p>
            </div>
        </div>
    </div>
    
    <form method="POST" action="{{ route('bread-types.order.update') }}" id="orderForm">
        @csrf
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">#</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Код</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Име</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Редослед</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($breadTypes as $index => $breadType)
                            <tr class="row-item" data-id="{{ $breadType->id }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">{{ $index + 1 }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $breadType->code }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $breadType->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="number" name="order[{{ $breadType->id }}]" 
                                           value="{{ $breadType->display_order }}" 
                                           class="order-input mt-0 block w-full px-0.5 border-0 border-b-2 border-gray-300 focus:ring-0 focus:border-blue-600 text-center"
                                           min="1" max="{{ count($breadTypes) }}">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="bg-gray-50 px-6 py-4">
                <button type="submit" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 text-white font-medium rounded-lg px-5 py-2.5 text-center transition duration-200 flex items-center justify-center">
                    <i class="fas fa-save mr-2"></i> Зачувај редослед
                </button>
            </div>
        </div>
    </form>
</div>

<style>
    input[type=number]::-webkit-inner-spin-button, 
    input[type=number]::-webkit-outer-spin-button { 
        -webkit-appearance: none; 
        margin: 0; 
    }
    
    input[type=number] {
        -moz-appearance: textfield;
    }
    
    .highlight {
        background-color: rgba(219, 234, 254, 0.5);
    }
</style>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Store original values for highlighting changes
    const originalValues = {};
    const inputs = document.querySelectorAll('.order-input');
    
    inputs.forEach(input => {
        const id = input.name.match(/\d+/)[0];
        originalValues[id] = parseInt(input.value);
        
        // Add change event listener to highlight changed rows
        input.addEventListener('change', function() {
            const id = this.name.match(/\d+/)[0];
            const newValue = parseInt(this.value);
            const row = this.closest('tr');
            
            // Highlight row if value changed
            if (originalValues[id] !== newValue) {
                row.classList.add('highlight');
            } else {
                row.classList.remove('highlight');
            }
        });
    });
});
</script>
@endpush