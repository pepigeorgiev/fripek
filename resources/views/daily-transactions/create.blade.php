@extends('layouts.app')
<style>
.select2-container {
    width: 100% !important;
}

.select2-container .select2-selection--single {
    height: 38px !important;
    padding: 4px !important;
}

.select2-container--default .select2-selection--single {
    border-color: rgb(209, 213, 219) !important;
    border-radius: 0.375rem !important;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 28px !important;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px !important;
}

.select2-dropdown {
    border-color: rgb(209, 213, 219) !important;
    border-radius: 0.375rem !important;
    z-index: 9999;
}

.select2-search__field {
    padding: 8px !important;
    border-radius: 0.375rem !important;
}

.select2-results__option {
    padding: 8px !important;
}

.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: rgb(79, 70, 229) !important;
}

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

@section('content')

<!-- Include Select2 CSS and JS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

<!-- Remove container padding for mobile -->
<div class="mx-auto md:p-0">
    <h1 class="text-xl md:text-2xl font-bold mb-2 md:mb-4 px-2 md:px-0">Дневни Трансакции</h1>
    
    <div class="bg-white p-2 md:p-6 rounded shadow">
        <!-- Mobile-friendly grid -->
        <form id="filterForm" method="GET" action="{{ route('daily-transactions.create') }}">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 md:gap-4 mb-4 md:mb-6">
            <div>
    <label for="company_id" class="block text-sm font-medium text-gray-700">Компанија</label>
    <select id="company_id" name="company_id" class="company-select mt-1 block w-full text-sm md:text-base rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">Изберете компанија</option>
        @foreach($companies as $company)
            <option value="{{ $company->id }}" {{ $selectedCompanyId == $company->id ? 'selected' : '' }}>
                {{ $company->name }}
            </option>
        @endforeach
    </select>
</div>
                <!-- <div>
                    <label for="company_id" class="block text-sm font-medium text-gray-700">Компанија</label>
                    <select id="company_id" name="company_id" class="company-select mt-1 block w-full text-sm md:text-base rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Изберете компанија</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ $selectedCompanyId == $company->id ? 'selected' : '' }}>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                </div> -->
                <div>
                    <label for="transaction_date" class="block text-sm font-medium text-gray-700">Дата</label>
                    <input type="date" id="transaction_date" name="transaction_date" 
                        class="mt-1 block w-full text-sm md:text-base rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        value="{{ $date }}">
                </div>
            </div>
        </form>
    </div>

    <!-- Toggle buttons -->
    <div class="mt-4 px-2 md:px-0 flex space-x-4">
        <button type="button" id="dailyTransactionsButton" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 text-sm md:text-base">
            Дневни Трансакции
        </button>
        <button type="button" id="oldBreadSalesButton" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 text-sm md:text-base">
            Продажба на стар леб
        </button>
        <div class="flex items-center gap-3 p-4 bg-white border border-gray-300 rounded-lg shadow-sm hover:shadow-md transition-shadow">
            <input type="checkbox" id="update_existing_transaction" class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded cursor-pointer">
            <label for="update_existing_transaction" class="text-gray-800 text-base md:text-lg cursor-pointer">
                Допуна/ажурирање на веќе внесена трансакција
            </label>
        </div>
    </div>

    <!-- Daily Transactions Section -->
    <div id="dailyTransactionsSection" class="mt-4 px-2 md:px-0">
        <form id="transactionForm" action="{{ route('daily-transactions.store') }}" method="POST">
            @csrf
            <input type="hidden" name="company_id" id="form_company_id" value="">
            <input type="hidden" name="transaction_date" id="form_transaction_date" value="">

            <div class="overflow-x-auto -mx-2 md:mx-0">
                <table class="w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-2 md:px-6 py-2 md:py-3 bg-gray-50 text-left text-xs md:text-sm font-bold text-gray-700 uppercase">
                                Тип
                            </th>
                            <th class="px-2 md:px-6 py-2 md:py-3 bg-gray-50 text-center text-xs md:text-sm font-bold text-gray-700 uppercase">
                                Про
                            </th>
                            <th class="px-2 md:px-6 py-2 md:py-3 bg-gray-50 text-center text-xs md:text-sm font-bold text-gray-700 uppercase">
                                Пов
                            </th>
                            <th class="px-2 md:px-6 py-2 md:py-3 bg-gray-50 text-center text-xs md:text-sm font-bold text-gray-700 uppercase">
                                Гра
                            </th>
                            <th class="px-2 md:px-6 py-2 md:py-3 bg-gray-50 text-center text-xs md:text-sm font-bold text-gray-700 uppercase">
                                Вк
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($breadTypes as $index => $breadType)
                        <tr>
                            <td class="px-2 md:px-6 py-2 md:py-4 text-sm md:text-lg font-medium text-gray-900">
                                {{ $breadType->name }}
                                <input type="hidden" name="transactions[{{ $index }}][bread_type_id]" value="{{ $breadType->id }}">
                            </td>
                            <td class="px-1 md:px-6 py-2 md:py-4">
                                <input type="number" 
                                    name="transactions[{{ $index }}][delivered]" 
                                    class="delivered-input block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-center text-base md:text-lg" 
                                    data-row="{{ $index }}"
                                    min="0" value="0"
                                    inputmode="numeric"
                                    pattern="[0-9]*">
                            </td>
                            <td class="px-1 md:px-6 py-2 md:py-4">
                                <input type="number" 
                                    name="transactions[{{ $index }}][returned]" 
                                    class="returned-input block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-center text-base md:text-lg" 
                                    data-row="{{ $index }}"
                                    min="0" 
                                    value="0"
                                    inputmode="numeric"
                                    pattern="[0-9]*">
                                    
                            </td>
                            <td class="px-1 md:px-6 py-2 md:py-4">
                                <input type="number" 
                                    name="transactions[{{ $index }}][gratis]" 
                                    class="gratis-input block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-center text-base md:text-lg" 
                                    data-row="{{ $index }}"
                                    min="0" value="0"
                                    inputmode="numeric"
                                    pattern="[0-9]*">
                                    
                            </td>
                            <td class="px-1 md:px-6 py-2 md:py-4 text-center">
                                <span class="total text-base md:text-lg font-bold" id="total-{{ $index }}">0</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex items-center mt-4 mb-4 px-2 md:px-0">
                <input type="checkbox" name="is_paid" id="is_paid" class="mr-2">
                <label for="is_paid" class="text-sm md:text-lg text-gray-700">Не е платена трансакцијата</label>
            </div>

            <div class="mt-4 px-2 md:px-0">
                <button type="submit" class="w-full md:w-auto bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 text-sm md:text-base">
                    Зачувај Трансакции
                </button>
            </div>

            <!-- Transaction Summary Modal -->
            <div id="transactionSummaryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 overflow-y-auto">
                <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
                    <div class="mt-3 text-center">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Преглед на трансакција</h3>
                        <div class="mt-2 px-7 py-3">
                            <div id="summaryContent" class="overflow-y-auto max-h-60 text-left">
                                <!-- Will be populated by JavaScript -->
                            </div>
                            <div class="mt-3 border-t pt-3">
                                <div class="flex justify-between font-bold">
                                    <span>Вкупно количина:</span>
                                    <span id="summaryQuantity">0</span>
                                </div>
                                <div class="flex justify-between font-bold mt-1">
                                    <span>Вкупна цена:</span>
                                    <span id="summaryPrice">0 ден.</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-center gap-4 mt-3">
                            <button id="cancelSummary" class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md">
                                Откажи
                            </button>
                            <button id="confirmSummary" class="px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md">
                                Потврди
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hidden data to store bread type prices and company data -->
            <div id="breadTypesData" style="display: none;" 
                data-bread-types="{{ json_encode($breadTypes->map(function($breadType) {
                    return [
                        'id' => $breadType->id,
                        'name' => $breadType->name,
                        'price' => $breadType->price,
                        'price_group_1' => $breadType->price_group_1,
                        'price_group_2' => $breadType->price_group_2,
                        'price_group_3' => $breadType->price_group_3,
                        'price_group_4' => $breadType->price_group_4,
                        'price_group_5' => $breadType->price_group_5
                    ];
                })) }}"></div>

            <div id="companiesData" style="display: none;" 
                data-companies="{{ json_encode($companies->pluck('price_group', 'id')) }}"></div>
        </form>
    </div>

    <!-- Old Bread Sales Section -->
    <div id="oldBreadSalesSection" class="hidden mt-4 px-2 md:px-0">
        <form id="oldBreadSalesForm" action="{{ route('daily-transactions.store-old-bread') }}" method="POST">
            @csrf
            <input type="hidden" name="transaction_date" value="{{ $date }}">

            <div class="overflow-x-auto -mx-2 md:mx-0">
                <table class="w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-lg font-bold text-center-desktop">Тип на лебот</th>
                            <th class="px-4 py-2 text-lg font-bold text-center-desktop">Продаден</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($breadTypes as $breadType)
                            @if($breadType->available_for_daily)
                                <tr>
                                    <td class="border px-4 py-2 text-lg font-bold text-center-desktop">{{ $breadType->name }}</td>
                                    <td class="border px-4 py-2 text-lg font-bold text-center-desktop">
                                        <input type="number" 
                                            name="old_bread_sold[{{ $breadType->id }}][sold]" 
                                            value="{{ old('old_bread_sold.'.$breadType->id.'.sold', $additionalTableData[$breadType->id]['sold'] ?? 0) }}" 
                                            class="w-full px-2 py-1 border rounded text-center-desktop"
                                            inputmode="numeric"
                                            pattern="[0-9]*">
                                        <input type="hidden" 
                                            name="old_bread_sold[{{ $breadType->id }}][bread_type_id]" 
                                            value="{{ $breadType->id }}">
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Зачувај без да внесуваш компанија
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Main application namespace to avoid global scope pollution
const DailyTransactions = {
    // DOM element cache
    elements: {},
    
    // Data cache
    data: {
        breadTypes: {},
        companies: {}
    },
    
    // State management
    state: {
        isProcessing: false,
        isOnline: true,
        offlineTransactions: []
    },
    
    // Initialize the application
    init: function() {
        this.cacheElements();
        this.loadData();
        this.bindEvents();
        // this.initializeSelect2();
        this.setupKeyboardNavigation();
        this.calculateAllTotals();
        this.syncOfflineTransactions();
        this.restoreFormState();
    },
    
    // Cache DOM elements for better performance
    cacheElements: function() {
        const el = this.elements;
        
        // Forms
        el.filterForm = document.getElementById('filterForm');
        el.transactionForm = document.getElementById('transactionForm');
        el.oldBreadSalesForm = document.getElementById('oldBreadSalesForm');
        
        // Inputs
        el.companySelect = document.getElementById('company_id');
        el.dateInput = document.getElementById('transaction_date');
        el.formCompanyId = document.getElementById('form_company_id');
        el.formTransactionDate = document.getElementById('form_transaction_date');
        el.updateExistingCheckbox = document.getElementById('update_existing_transaction');
        
        // Toggle buttons
        el.dailyTransactionsButton = document.getElementById('dailyTransactionsButton');
        el.oldBreadSalesButton = document.getElementById('oldBreadSalesButton');
        
        // Sections
        el.dailyTransactionsSection = document.getElementById('dailyTransactionsSection');
        el.oldBreadSalesSection = document.getElementById('oldBreadSalesSection');
        
        // Transaction inputs
        el.deliveredInputs = document.querySelectorAll('.delivered-input');
        el.returnedInputs = document.querySelectorAll('.returned-input');
        el.gratisInputs = document.querySelectorAll('.gratis-input');
        el.allInputs = document.querySelectorAll('.delivered-input, .returned-input, .gratis-input');
        el.oldBreadInputs = document.querySelectorAll('input[name^="old_bread_sold"][name$="[sold]"]');
        
        // Modal elements
        el.modal = document.getElementById('transactionSummaryModal');
        el.summaryContent = document.getElementById('summaryContent');
        el.summaryQuantity = document.getElementById('summaryQuantity');
        el.summaryPrice = document.getElementById('summaryPrice');
        el.cancelButton = document.getElementById('cancelSummary');
        el.confirmButton = document.getElementById('confirmSummary');
        el.submitButton = el.transactionForm ? el.transactionForm.querySelector('button[type="submit"]') : null;
    },
    
    // Load data from hidden fields
    loadData: function() {
        try {
            // Load bread types data
            const breadTypesElement = document.getElementById('breadTypesData');
            if (breadTypesElement && breadTypesElement.dataset.breadTypes) {
                const breadTypes = JSON.parse(breadTypesElement.dataset.breadTypes);
                breadTypes.forEach(breadType => {
                    this.data.breadTypes[breadType.id] = breadType;
                });
            }
            
            // Load companies data
            const companiesElement = document.getElementById('companiesData');
            if (companiesElement && companiesElement.dataset.companies) {
                this.data.companies = JSON.parse(companiesElement.dataset.companies);
            }
            
            // Load offline transactions from localStorage
            const storedTransactions = localStorage.getItem('offline_transactions');
            if (storedTransactions) {
                this.state.offlineTransactions = JSON.parse(storedTransactions);
            }
        } catch (e) {
            console.error('Error loading data:', e);
        }
    },
    
    // Bind all event handlers
    bindEvents: function() {
        const self = this;
        const el = this.elements;
        
        // Company select change
        if (el.companySelect) {
            el.companySelect.addEventListener('change', function() {
                self.handleCompanyChange();
            });
        }
        
        // Date input change
        if (el.dateInput) {
            el.dateInput.addEventListener('change', function() {
                self.handleDateChange();
            });
        }
        
        // Form submission
        if (el.transactionForm) {
            el.transactionForm.addEventListener('submit', function(e) {
                e.preventDefault();
                self.handleTransactionFormSubmit();
            });
        }
        
        // Old bread sales form submission
        if (el.oldBreadSalesForm) {
            el.oldBreadSalesForm.addEventListener('submit', function(e) {
                // Standard form submission - no special handling needed
            });
        }
        
        // Toggle buttons
        if (el.dailyTransactionsButton && el.oldBreadSalesButton) {
            el.dailyTransactionsButton.addEventListener('click', function() {
                self.toggleSection('daily');
            });
            
            el.oldBreadSalesButton.addEventListener('click', function() {
                self.toggleSection('oldBread');
            });
        }
        
        // Input handlers for transaction fields
        if (el.allInputs) {
            el.allInputs.forEach(input => {
                const rowIndex = input.getAttribute('data-row');
                
                // Focus handler
                input.addEventListener('focus', function() {
                    if (this.value === '0') {
                        this.value = '';
                    }
                });
                
                // Blur handler
                input.addEventListener('blur', function() {
                    if (this.value === '') {
                        this.value = '0';
                    }
                    self.calculateRowTotal(rowIndex);
                });
                
                // Input handler
                input.addEventListener('input', function() {
                    self.calculateRowTotal(rowIndex);
                });
            });
        }
        
        // Make bread names clickable
        this.setupBreadNameClicks();
        
        // Modal buttons
        if (el.cancelButton) {
            el.cancelButton.addEventListener('click', function() {
                el.modal.classList.add('hidden');
            });
        }
        
        if (el.confirmButton) {
            el.confirmButton.addEventListener('click', function() {
                self.submitTransaction();
            });
        }
        
        // Replace submit button with summary button
        if (el.submitButton) {
            const newButton = document.createElement('button');
            newButton.type = 'button';
            newButton.className = el.submitButton.className;
            newButton.innerHTML = el.submitButton.innerHTML;
            
            newButton.addEventListener('click', function() {
                self.showTransactionSummary();
            });
            
            el.submitButton.parentNode.replaceChild(newButton, el.submitButton);
            el.summaryButton = newButton;
        }
        
        // Online/offline events
        window.addEventListener('online', function() {
            self.state.isOnline = true;
            self.syncOfflineTransactions();
        });
        
        window.addEventListener('offline', function() {
            self.state.isOnline = false;
            alert('Нема интернет конекција. Трансакциите ќе бидат зачувани локално.');
        });
        
        // Old bread inputs
        if (el.oldBreadInputs) {
            el.oldBreadInputs.forEach(input => {
                input.addEventListener('focus', function() {
                    if (this.value === '0') {
                        this.value = '';
                    }
                });
                
                input.addEventListener('blur', function() {
                    if (this.value === '') {
                        this.value = '0';
                    }
                });
            });
        }
    },
    
    // Set up Select2 for company dropdown
    // initializeSelect2: function() {
    //     const self = this;
    //     const el = this.elements;
        
    //     if (!el.companySelect || !window.jQuery) return;
        
    //     try {
    //         // Wait for jQuery to be fully loaded
    //         function waitForJQuery(callback) {
    //             if (window.jQuery && window.jQuery.fn.select2) {
    //                 callback();
    //             } else {
    //                 setTimeout(function() { waitForJQuery(callback); }, 100);
    //             }
    //         }
            
    //         waitForJQuery(function() {
    //             try {
    //                 if ($('#company_id').length) {
    //                     $('#company_id').select2({
    //                         placeholder: 'Изберете компанија',
    //                         allowClear: true,
    //                         width: '100%',
    //                         dropdownParent: $('body')
    //                     }).on('select2:open', function() {
    //                         $('.select2-dropdown').css('z-index', '9999');
    //                     });
                        
    //                     // We don't need the change handler here as we've already set it up in bindEvents
    //                 }
    //             } catch (e) {
    //                 console.error('Error initializing Select2:', e);
    //                 self.fallbackToRegularSelect();
    //             }
                
    //             // Add refresh button
    //             setTimeout(self.addRefreshButton, 300);
    //         });
            
    //         // Timeout fallback
    //         setTimeout(function() {
    //             if (!window.jQuery.fn.select2) {
    //                 console.warn('Select2 initialization timeout');
    //                 self.fallbackToRegularSelect();
    //             }
    //         }, 2000);
    //     } catch (e) {
    //         console.error('Error in Select2 initialization:', e);
    //         self.fallbackToRegularSelect();
    //     }
    // },
    
    // // Fallback to regular select if Select2 fails
    // fallbackToRegularSelect: function() {
    //     const el = this.elements;
    //     if (!el.companySelect) return;
        
    //     el.companySelect.classList.add('form-control');
    //     el.companySelect.removeAttribute('disabled');
    // },
    
    // // Add refresh button to company select
    // addRefreshButton: function() {
    //     const companySelectContainer = document.querySelector('#company_id').parentNode;
    //     if (!companySelectContainer) return;
        
    //     // Check if button already exists to prevent duplicates
    //     if (companySelectContainer.querySelector('button[type="button"]')) return;
        
    //     companySelectContainer.style.position = 'relative';
        
    //     const refreshButton = document.createElement('button');
    //     refreshButton.type = 'button';
    //     refreshButton.className = 'absolute right-0 top-0 mt-7 mr-2 text-blue-500';
    //     refreshButton.innerHTML = '<i class="fas fa-sync-alt"></i>';
    //     refreshButton.onclick = function() {
    //         window.location.reload();
    //     };
        
    //     companySelectContainer.appendChild(refreshButton);
    // },
    
    // Set up clickable bread names
    setupBreadNameClicks: function() {
        document.querySelectorAll('tbody tr').forEach(row => {
            const nameCell = row.querySelector('td:first-child');
            if (!nameCell) return;
            
            // Make the bread name clickable
            nameCell.style.cursor = 'pointer';
            nameCell.classList.add('hover:bg-gray-100');
            
            nameCell.addEventListener('click', function() {
                const deliveredInput = row.querySelector('.delivered-input');
                if (deliveredInput) {
                    deliveredInput.focus();
                    // Clear the value if it's 0
                    if (deliveredInput.value === '0') {
                        deliveredInput.value = '';
                    }
                }
            });
        });
    },
    
    // Set up keyboard navigation between fields
    setupKeyboardNavigation: function() {
        const self = this;
        
        document.querySelectorAll('.delivered-input, .returned-input, .gratis-input').forEach(input => {
            input.addEventListener('keydown', function(e) {
                const rowIndex = parseInt(this.getAttribute('data-row'));
                const isDelivered = this.classList.contains('delivered-input');
                const isReturned = this.classList.contains('returned-input');
                const isGratis = this.classList.contains('gratis-input');
                
                // Function to select and clear zero value
                function selectAndClearIfZero(input) {
                    input.focus();
                    if (input.value === '0') {
                        input.value = '';
                    }
                }
                
                // Handle keyboard navigation
                switch (e.key) {
                    case 'ArrowDown':
                        // Move to same type of input in next row
                        const nextRowInput = document.querySelector(`input[data-row="${rowIndex + 1}"]${isDelivered ? '.delivered-input' : isReturned ? '.returned-input' : '.gratis-input'}`);
                        if (nextRowInput) {
                            e.preventDefault();
                            selectAndClearIfZero(nextRowInput);
                        }
                        break;
                        
                    case 'ArrowUp':
                        // Move to same type of input in previous row
                        const prevRowInput = document.querySelector(`input[data-row="${rowIndex - 1}"]${isDelivered ? '.delivered-input' : isReturned ? '.returned-input' : '.gratis-input'}`);
                        if (prevRowInput) {
                            e.preventDefault();
                            selectAndClearIfZero(prevRowInput);
                        }
                        break;
                        
                    case 'ArrowRight':
                        // Only if at end of input
                        if (this.selectionStart === this.value.length) {
                            e.preventDefault();
                            // Move to next input in same row
                            if (isDelivered) {
                                const returnedInput = document.querySelector(`input[data-row="${rowIndex}"].returned-input`);
                                if (returnedInput) selectAndClearIfZero(returnedInput);
                            } else if (isReturned) {
                                const gratisInput = document.querySelector(`input[data-row="${rowIndex}"].gratis-input`);
                                if (gratisInput) selectAndClearIfZero(gratisInput);
                            }
                        }
                        break;
                        
                    case 'ArrowLeft':
                        // Only if at start of input
                        if (this.selectionStart === 0) {
                            e.preventDefault();
                            // Move to previous input in same row
                            if (isReturned) {
                                const deliveredInput = document.querySelector(`input[data-row="${rowIndex}"].delivered-input`);
                                if (deliveredInput) selectAndClearIfZero(deliveredInput);
                            } else if (isGratis) {
                                const returnedInput = document.querySelector(`input[data-row="${rowIndex}"].returned-input`);
                                if (returnedInput) selectAndClearIfZero(returnedInput);
                            }
                        }
                        break;
                        
                    case 'Enter':
                        e.preventDefault();
                        // Move to next input in row or first input in next row
                        if (isDelivered) {
                            const returnedInput = document.querySelector(`input[data-row="${rowIndex}"].returned-input`);
                            if (returnedInput) selectAndClearIfZero(returnedInput);
                        } else if (isReturned) {
                            const gratisInput = document.querySelector(`input[data-row="${rowIndex}"].gratis-input`);
                            if (gratisInput) selectAndClearIfZero(gratisInput);
                        } else if (isGratis) {
                            // Move to delivered input in next row
                            const nextDeliveredInput = document.querySelector(`input[data-row="${rowIndex + 1}"].delivered-input`);
                            if (nextDeliveredInput) selectAndClearIfZero(nextDeliveredInput);
                        }
                        break;
                }
            });
        });
    },
    
    // Calculate the total for a specific row
    calculateRowTotal: function(index) {
        const delivered = parseInt(document.querySelector(`input[name="transactions[${index}][delivered]"]`).value) || 0;
        const returned = parseInt(document.querySelector(`input[name="transactions[${index}][returned]"]`).value) || 0;
        const gratis = parseInt(document.querySelector(`input[name="transactions[${index}][gratis]"]`).value) || 0;
        
        const total = delivered - returned - gratis;
        const totalElement = document.querySelector(`#total-${index}`);
        if (totalElement) {
            totalElement.textContent = total;
        }
    },
    
    // Calculate all row totals
    calculateAllTotals: function() {
        document.querySelectorAll('.delivered-input').forEach(input => {
            const rowIndex = input.getAttribute('data-row');
            if (rowIndex) {
                this.calculateRowTotal(rowIndex);
            }
        });
    },
    
    // Toggle between daily transactions and old bread sales
    toggleSection: function(section) {
        const el = this.elements;
        
        if (section === 'daily') {
            // Show daily transactions, hide old bread
            el.dailyTransactionsSection.classList.remove('hidden');
            el.oldBreadSalesSection.classList.add('hidden');
            
            // Update button styles
            el.dailyTransactionsButton.classList.remove('bg-gray-500');
            el.dailyTransactionsButton.classList.add('bg-blue-500');
            el.oldBreadSalesButton.classList.remove('bg-blue-500');
            el.oldBreadSalesButton.classList.add('bg-gray-500');
            
            // Restore company selection if available
            const savedCompanyId = localStorage.getItem('saved_company_id');
            if (savedCompanyId && el.companySelect) {
                if (window.jQuery && $('#company_id').select2) {
                    try {
                        $('#company_id').val(savedCompanyId).trigger('change');
                    } catch (e) {
                        el.companySelect.value = savedCompanyId;
                    }
                } else {
                    el.companySelect.value = savedCompanyId;
                }
            }
        } else if (section === 'oldBread') {
            // Show old bread section, hide daily transactions
            el.oldBreadSalesSection.classList.remove('hidden');
            el.dailyTransactionsSection.classList.add('hidden');
            
            // Update button styles
            el.oldBreadSalesButton.classList.remove('bg-gray-500');
            el.oldBreadSalesButton.classList.add('bg-blue-500');
            el.dailyTransactionsButton.classList.remove('bg-blue-500');
            el.dailyTransactionsButton.classList.add('bg-gray-500');
            
            // Save company selection before clearing it
            if (el.companySelect) {
                // Save current value
                localStorage.setItem('saved_company_id', el.companySelect.value);
                
                // Clear the company selection
                if (window.jQuery && $('#company_id').select2) {
                    try {
                        $('#company_id').val('').trigger('change');
                    } catch (e) {
                        el.companySelect.value = '';
                    }
                } else {
                    el.companySelect.value = '';
                }
            }
        }
    },
    
    // Handle company select change
// Handle company select change
handleCompanyChange: function() {
    const el = this.elements;
    const currentDate = el.dateInput.value;
    
    // Save the date in localStorage
    if (currentDate) {
        localStorage.setItem('lastTransactionDate', currentDate);
    }
    
    // Update hidden field
    if (el.formCompanyId) {
        el.formCompanyId.value = el.companySelect.value;
    }
    
    // Navigate to refresh bread types with full URL
    if (el.companySelect.value) {
        // Use a fully qualified URL to avoid any path issues
        const baseUrl = window.location.origin;
        const url = `${baseUrl}/daily-transactions/create?company_id=${el.companySelect.value}&date=${currentDate}`;
        console.log('Redirecting to:', url);
        window.location.href = url;
    }
},
   
    
    // Handle date input change
    handleDateChange: function() {
        const el = this.elements;
        
        // Save to localStorage
        localStorage.setItem('lastTransactionDate', el.dateInput.value);
        
        // Update hidden field
        if (el.formTransactionDate) {
            el.formTransactionDate.value = el.dateInput.value;
        }
        
        // If company is selected, refresh page
        if (el.companySelect.value) {
            window.location.href = `/daily-transactions/create?company_id=${el.companySelect.value}&date=${el.dateInput.value}`;
        }
    },
    
    // Show transaction summary before submit
    showTransactionSummary: function() {
        const el = this.elements;
        
        // Update hidden form fields first
        this.updateHiddenFormFields();
        
        // Check if company is selected
        if (!el.companySelect.value) {
            alert('Ве молиме изберете компанија');
            return;
        }
        
        // Get all transaction rows
        const rows = document.querySelectorAll('tbody tr');
        let totalQuantity = 0;
        let totalPrice = 0;
        let hasData = false;
        
        // Create table for summary
        let tableHtml = `
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr>
                        <th class="text-left py-2">Тип</th>
                        <th class="text-right py-2">Про</th>
                        <th class="text-right py-2">Пов</th>
                        <th class="text-right py-2">Гра</th>
                        <th class="text-right py-2">Вк</th>
                        <th class="text-right py-2">Цена</th>
                        <th class="text-right py-2">Вкупно</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        // Process each row
        rows.forEach(row => {
            try {
                // Get bread type name and ID
                const nameCell = row.querySelector('td:first-child');
                if (!nameCell) return;
                
                const typeName = nameCell.textContent.trim();
                
                // Get bread type ID from hidden input
                const breadTypeIdInput = row.querySelector('input[name$="[bread_type_id]"]');
                if (!breadTypeIdInput) return;
                
                const breadTypeId = breadTypeIdInput.value;
                
                // Get input values
                const deliveredInput = row.querySelector('.delivered-input');
                const returnedInput = row.querySelector('.returned-input');
                const gratisInput = row.querySelector('.gratis-input');
                
                if (!deliveredInput || !returnedInput || !gratisInput) return;
                
                const delivered = parseInt(deliveredInput.value) || 0;
                const returned = parseInt(returnedInput.value) || 0;
                const gratis = parseInt(gratisInput.value) || 0;
                const netQuantity = delivered - returned - gratis;
                
                // Only include rows with data
                if (delivered > 0 || returned > 0 || gratis > 0) {
                    hasData = true;
                    
                    // Get price based on company's price group
                    const price = this.getBreadTypePrice(breadTypeId, el.companySelect.value);
                    const rowTotal = netQuantity * price;
                    
                    totalQuantity += netQuantity;
                    totalPrice += rowTotal;
                    
                    // Add row to table
                    tableHtml += `
                        <tr class="border-t">
                            <td class="py-2">${typeName}</td>
                            <td class="text-right py-2">${delivered}</td>
                            <td class="text-right py-2">${returned}</td>
                            <td class="text-right py-2">${gratis}</td>
                            <td class="text-right py-2 font-bold">${netQuantity}</td>
                            <td class="text-right py-2">${price.toFixed(2)} ден.</td>
                            <td class="text-right py-2 font-bold">${rowTotal.toFixed(2)} ден.</td>
                        </tr>
                    `;
                }
            } catch (e) {
                console.error('Error processing row:', e);
            }
        });
        
        tableHtml += '</tbody></table>';
        
        // Show message if no data
        if (!hasData) {
            tableHtml = '<p class="text-center py-4">Нема внесени податоци</p>';
        }
        
        // Update modal content
        el.summaryContent.innerHTML = tableHtml;
        el.summaryQuantity.textContent = totalQuantity;
        el.summaryPrice.textContent = totalPrice.toFixed(2) + ' ден.';
        
        // Show modal
        el.modal.classList.remove('hidden');
    },
    
    // Get bread type price based on company price group
    getBreadTypePrice: function(breadTypeId, companyId) {
        // Default price
        let price = 0;
        
        try {
            // Try to fetch the price from the server directly
            const date = this.elements.dateInput.value;
            const timestamp = new Date().getTime();

            const xhr = new XMLHttpRequest();
            // Make a synchronous request for simplicity
            xhr.open('GET', `/api/get-bread-price/${breadTypeId}/${companyId}?date=${date}`, false);
            
            try {
                xhr.send();
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    return parseFloat(response.price) || 0;
                }
            } catch (error) {
                console.error('Error fetching price from server:', error);
                // Fall back to client-side calculation
            }
            
            // If server request failed, fall back to client-side calculation
            // Get the bread type data
            const breadType = this.data.breadTypes[breadTypeId];
            if (!breadType) {
                console.error(`Bread type ID ${breadTypeId} not found in data`);
                return price;
            }
            
            // Get company's price group
            const companyPriceGroup = this.data.companies[companyId];
            if (companyPriceGroup === undefined) {
                console.error(`Company ID ${companyId} not found in data`);
                return breadType.price; // Fall back to default price
            }
            
            // Determine price based on company's price group
            if (companyPriceGroup === 0 || companyPriceGroup === null) {
                // Use default price
                price = parseFloat(breadType.price) || 0;
            } else {
                // Use price for specific group if available, otherwise default
                const priceGroupField = `price_group_${companyPriceGroup}`;
                if (breadType[priceGroupField] !== undefined && breadType[priceGroupField] !== null) {
                    price = parseFloat(breadType[priceGroupField]) || 0;
                } else {
                    price = parseFloat(breadType.price) || 0;
                }
            }
            
            return price;
        } catch (e) {
            console.error('Error getting bread type price:', e);
            return 0;
        }
    },
    
    // Update hidden form fields with values from visible fields
    updateHiddenFormFields: function() {
        const el = this.elements;
        
        if (el.companySelect && el.formCompanyId) {
            el.formCompanyId.value = el.companySelect.value;
        }
        
        if (el.dateInput && el.formTransactionDate) {
            el.formTransactionDate.value = el.dateInput.value;
        }
    },
    
    // Handle transaction form submission
    handleTransactionFormSubmit: function() {
        // Update hidden fields
        this.updateHiddenFormFields();
        
        // Check if company is selected
        const el = this.elements;
        if (!el.companySelect.value) {
            alert('Ве молиме изберете компанија');
            return false;
        }
        
        // Show transaction summary
        this.showTransactionSummary();
    },
    
    // Submit the transaction (called from modal)
    submitTransaction: function() {
        const self = this;
        const el = this.elements;
        
        // Hide modal
        el.modal.classList.add('hidden');
        
        // Prevent duplicate submissions
        if (this.state.isProcessing) return;
        this.state.isProcessing = true;
        
        // Show loading indicator
        this.showLoadingIndicator();
        
        // Get form data
        const formData = new FormData(el.transactionForm);
        const companyId = el.companySelect.value;
        const transactionDate = el.dateInput.value;
        
        // Check if this is an update to existing transaction
        const isUpdate = el.updateExistingCheckbox && el.updateExistingCheckbox.checked;
        
        // Check online status
        if (!this.state.isOnline) {
            this.storeOfflineTransaction(formData);
            this.hideLoadingIndicator();
            alert('Нема интернет конекција. Трансакцијата е зачувана локално.');
            this.state.isProcessing = false;
            return;
        }
        
        if (isUpdate) {
            // Process data for update endpoint
            const transactions = [];
            
            document.querySelectorAll('input[name^="transactions"]').forEach((input) => {
                const match = input.name.match(/transactions\[(\d+)\]\[(\w+)\]/);
                if (match) {
                    const rowIndex = match[1];
                    const field = match[2];
                    
                    // Initialize transaction object if not exists
                    if (!transactions[rowIndex]) {
                        transactions[rowIndex] = {
                            bread_type_id: null,
                            delivered: 0,
                            returned: 0,
                            gratis: 0
                        };
                    }
                    
                    // Add field value
                    if (field === 'bread_type_id') {
                        transactions[rowIndex][field] = input.value;
                    } else {
                        transactions[rowIndex][field] = parseInt(input.value) || 0;
                    }
                }
            });
            
            // Filter out transactions with no delivered items
            const filteredTransactions = transactions.filter(t => t && t.delivered > 0);
            
            // Prepare payload
            const payload = {
                company_id: companyId,
                transaction_date: transactionDate,
                transactions: filteredTransactions
            };
            
            // Send to update endpoint
            fetch('/update-daily-transaction', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data => this.handleResponse(data, companyId, transactionDate))
            .catch(error => this.handleError(error));
        } else {
            // Submit regular form
            fetch(el.transactionForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => this.handleResponse(data, companyId, transactionDate))
            .catch(error => this.handleError(error));
        }
    },
    
    // Handle successful response
    handleResponse: function(data, companyId, transactionDate) {
        // Remove loading indicator
        this.hideLoadingIndicator();
        this.state.isProcessing = false;
        
        if (data.success) {
            // Show success message
            const successDiv = document.createElement('div');
            successDiv.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50';
            successDiv.innerHTML = `
                <div class="bg-white p-4 rounded-lg shadow-lg text-center max-w-md">
                    <div class="text-green-500 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Успешно!</h3>
                    <p class="text-gray-600 mb-4">${data.message}</p>
                    <button class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600" onclick="this.parentNode.parentNode.remove(); window.location.reload();">
                        ОК
                    </button>
                </div>
            `;
            document.body.appendChild(successDiv);
            
            // Set a small timeout to allow user to see the message
            setTimeout(() => {
                window.location.href = '/daily-transactions/create?company_id=' + companyId + '&date=' + transactionDate;
            }, 2000);
        } else {
            // Show error message
            alert(data.message || 'Грешка при зачувување.');
        }
    },
    
    // Handle error
    handleError: function(error) {
        // Remove loading indicator
        this.hideLoadingIndicator();
        this.state.isProcessing = false;
        
        console.error('Error submitting form:', error);
        alert('Грешка при комуникација со серверот. Обидете се повторно.');
    },
    
    // Show loading indicator
    showLoadingIndicator: function() {
        const loadingDiv = document.createElement('div');
        loadingDiv.id = 'loadingIndicator';
        loadingDiv.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50';
        loadingDiv.innerHTML = `
            <div class="bg-white p-4 rounded-lg shadow-lg text-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto mb-2"></div>
                <p>Зачувување...</p>
            </div>
        `;
        document.body.appendChild(loadingDiv);
    },
    
    // Hide loading indicator
    hideLoadingIndicator: function() {
        const loadingIndicator = document.getElementById('loadingIndicator');
        if (loadingIndicator) {
            loadingIndicator.remove();
        }
    },
    
    // Store transaction offline
    storeOfflineTransaction: function(formData) {
        const OFFLINE_STORAGE_KEY = 'offline_transactions';
        const transactions = JSON.parse(localStorage.getItem(OFFLINE_STORAGE_KEY) || '[]');
        
        // Convert FormData to object
        const formDataObject = {};
        for (const [key, value] of formData.entries()) {
            formDataObject[key] = value;
        }
        
        transactions.push({
            data: formDataObject,
            timestamp: new Date().getTime()
        });
        
        localStorage.setItem(OFFLINE_STORAGE_KEY, JSON.stringify(transactions));
        this.state.offlineTransactions = transactions;
    },
    
    // Sync offline transactions
    syncOfflineTransactions: function() {
        if (!this.state.isOnline || this.state.offlineTransactions.length === 0) return;
        
        const OFFLINE_STORAGE_KEY = 'offline_transactions';
        const transactions = [...this.state.offlineTransactions];
        
        transactions.forEach((transaction, index) => {
            const formData = new FormData();
            
            // Convert stored object back to FormData
            Object.entries(transaction.data).forEach(([key, value]) => {
                formData.append(key, value);
            });
            
            fetch('/daily-transactions/store', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove this transaction from storage on success
                    this.state.offlineTransactions.splice(index, 1);
                    localStorage.setItem(OFFLINE_STORAGE_KEY, JSON.stringify(this.state.offlineTransactions));
                    alert('Офлајн трансакцијата е успешно синхронизирана.');
                }
            })
            .catch(error => {
                console.error('Failed to sync transaction:', transaction, error);
            });
        });
    },
    
    // Restore form state from localStorage
restoreFormState: function() {
    const el = this.elements;
    
    // Always prioritize today's date when returning to page unless date is in URL
    const urlParams = new URLSearchParams(window.location.search);
    const today = new Date().toISOString().slice(0, 10);
    
    // If no date in URL, use today's date instead of stored date
    if (!urlParams.has('date')) {
        el.dateInput.value = today;
        
        // If company is already selected, refresh with today's date
        if (el.companySelect.value) {
            const url = new URL(el.filterForm.action, window.location.origin);
            url.searchParams.set('company_id', el.companySelect.value);
            url.searchParams.set('date', today);
            
            // Only redirect if URL would change
            if (url.toString() !== window.location.href) {
                window.location.href = url.toString();
            }
        }
    }
    
    // Save current date for potential future use
    localStorage.setItem('lastTransactionDate', el.dateInput.value);
    
    // Set initial form hidden values
    this.updateHiddenFormFields();
}
};


// Execute when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    DailyTransactions.init();
})


// Optimized Select2 Implementation
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize Select2 once
    initializeSelect2WithOptimizations();
});


function initializeSelect2WithOptimizations() {
    // Check if jQuery and Select2 are available
    if (!window.jQuery || !window.jQuery.fn.select2) {
        console.warn('Select2 or jQuery not available, falling back to regular select');
        setupRegularSelect();
        return;
    }
    
    try {
        const companySelect = $('#company_id');
        
        // Only initialize if not already initialized and element exists
        if (companySelect.length && !companySelect.hasClass('select2-initialized')) {
            // Add an initialization flag to prevent multiple initializations
            companySelect.addClass('select2-initialized');
            
            // Cache for previously processed search results to improve performance
            const searchCache = {};
            
            // Initialize Select2 with optimized options
            companySelect.select2({
                placeholder: 'Изберете компанија',
                allowClear: true,
                width: '100%',
                dropdownParent: $('body'),
                // No minimum input length - show all options by default
                delay: 250,             // Add debounce delay (milliseconds)
                // Use a simplified matcher that supports partial words
                matcher: function(params, data) {
                    // If no search term, return all data
                    if (!params.term || $.trim(params.term) === '') {
                        return data;
                    }
                    
                    // If there's no 'text' property, this data object can't be matched
                    if (typeof data.text === 'undefined') {
                        return null;
                    }
                    
                    const searchTerm = params.term.toLowerCase();
                    const originalText = data.text.toLowerCase();
                    
                    // Check for direct match first (most efficient)
                    if (originalText.indexOf(searchTerm) > -1) {
                        return data;
                    }
                    
                    // Simple character-by-character transliteration
                    // This approach works better for partial words
                    
                    // Check if the search term contains Cyrillic characters
                    const macedonianCyrillicChars = 'абвгдѓежзѕијклљмнњопрстќуфхцчџш';
                    const hasCyrillic = searchTerm.split('').some(char => macedonianCyrillicChars.includes(char));
                    
                    if (hasCyrillic) {
                        // Convert Cyrillic search term to Latin to match against potential Latin text
                        let latinSearchTerm = '';
                        for (let i = 0; i < searchTerm.length; i++) {
                            const char = searchTerm[i];
                            latinSearchTerm += {
                                'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'ѓ': 'gj', 'е': 'e',
                                'ж': 'zh', 'з': 'z', 'ѕ': 'dz', 'и': 'i', 'ј': 'j', 'к': 'k', 'л': 'l',
                                'љ': 'lj', 'м': 'm', 'н': 'n', 'њ': 'nj', 'о': 'o', 'п': 'p', 'р': 'r',
                                'с': 's', 'т': 't', 'ќ': 'kj', 'у': 'u', 'ф': 'f', 'х': 'h', 'ц': 'c',
                                'ч': 'ch', 'џ': 'dzh', 'ш': 'sh'
                            }[char] || char;
                        }
                        
                        // Convert the company name to Latin for comparison (if it contains Cyrillic)
                        if (originalText.split('').some(char => macedonianCyrillicChars.includes(char))) {
                            let latinText = '';
                            for (let i = 0; i < originalText.length; i++) {
                                const char = originalText[i];
                                latinText += {
                                    'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'ѓ': 'gj', 'е': 'e',
                                    'ж': 'zh', 'з': 'z', 'ѕ': 'dz', 'и': 'i', 'ј': 'j', 'к': 'k', 'л': 'l',
                                    'љ': 'lj', 'м': 'm', 'н': 'n', 'њ': 'nj', 'о': 'o', 'п': 'p', 'р': 'r',
                                    'с': 's', 'т': 't', 'ќ': 'kj', 'у': 'u', 'ф': 'f', 'х': 'h', 'ц': 'c',
                                    'ч': 'ch', 'џ': 'dzh', 'ш': 'sh'
                                }[char] || char;
                            }
                            
                            if (latinText.indexOf(latinSearchTerm) > -1) {
                                return data;
                            }
                        }
                    } else {
                        // Latin search term - try to match against Cyrillic text
                        // Convert the company name from Cyrillic to Latin for comparison
                        if (originalText.split('').some(char => macedonianCyrillicChars.includes(char))) {
                            let latinText = '';
                            for (let i = 0; i < originalText.length; i++) {
                                const char = originalText[i];
                                latinText += {
                                    'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'ѓ': 'gj', 'е': 'e',
                                    'ж': 'zh', 'з': 'z', 'ѕ': 'dz', 'и': 'i', 'ј': 'j', 'к': 'k', 'л': 'l',
                                    'љ': 'lj', 'м': 'm', 'н': 'n', 'њ': 'nj', 'о': 'o', 'п': 'p', 'р': 'r',
                                    'с': 's', 'т': 't', 'ќ': 'kj', 'у': 'u', 'ф': 'f', 'х': 'h', 'ц': 'c',
                                    'ч': 'ch', 'џ': 'dzh', 'ш': 'sh'
                                }[char] || char;
                            }
                            
                            if (latinText.indexOf(searchTerm) > -1) {
                                return data;
                            }
                        }
                        
                        // Also try to convert the search term to Cyrillic for matching
                        // This is a simple character-by-character conversion for single letters only
                        let cyrillicSearchTerm = '';
                        for (let i = 0; i < searchTerm.length; i++) {
                            const char = searchTerm[i];
                            cyrillicSearchTerm += {
                                'a': 'а', 'b': 'б', 'v': 'в', 'g': 'г', 'd': 'д', 'e': 'е',
                                'z': 'з', 'i': 'и', 'j': 'ј', 'k': 'к', 'l': 'л', 'm': 'м', 
                                'n': 'н', 'o': 'о', 'p': 'п', 'r': 'р', 's': 'с', 't': 'т',
                                'u': 'у', 'f': 'ф', 'h': 'х', 'c': 'ц'
                            }[char] || char;
                        }
                        
                        if (originalText.indexOf(cyrillicSearchTerm) > -1) {
                            return data;
                        }
                    }
                    
                    // No match found
                    return null;
                }
            }).on('select2:select', function() {
                try {
                    // Get selected values
                    const selectedCompanyId = this.value;
                    const currentDate = document.getElementById('transaction_date').value;
                    
                    // Update hidden form field
                    const formCompanyId = document.getElementById('form_company_id');
                    if (formCompanyId) {
                        formCompanyId.value = selectedCompanyId;
                    }
                    
                    // Save date to localStorage for persistence
                    if (currentDate) {
                        localStorage.setItem('lastTransactionDate', currentDate);
                    }
                    
                    // Only navigate if company is selected
                    if (selectedCompanyId) {
                        // Use full URL to avoid path issues
                        const baseUrl = window.location.origin;
                        const url = `${baseUrl}/daily-transactions/create?company_id=${selectedCompanyId}&date=${currentDate}`;
                        
                        // Show loading indicator
                        showLoadingOverlay('');
                        
                        // Navigate with small delay to allow loading indicator to appear
                        setTimeout(() => {
                            window.location.href = url;
                        }, 100);
                    }
                } catch (e) {
                    console.error('Error in Select2 select handler:', e);
                    hideLoadingOverlay();
                }
            }).on('select2:open', function() {
                // Ensure dropdown is above other elements
                $('.select2-dropdown').css('z-index', '9999');
                
                // Focus the search field when opened
                setTimeout(function() {
                    $('.select2-search__field').focus();
                }, 10);
            });
            
            console.log('[PWA] Select2 initialized with improved partial matching');
            
            // Add refresh button with proper positioning
            addRefreshButton();
        }
    } catch (e) {
        console.error('[PWA] Error initializing optimized Select2:', e);
        setupRegularSelect();
    }
}
// function initializeSelect2WithOptimizations() {
//     // Check if jQuery and Select2 are available
//     if (!window.jQuery || !window.jQuery.fn.select2) {
//         console.warn('Select2 or jQuery not available, falling back to regular select');
//         setupRegularSelect();
//         return;
//     }
    
//     try {
//         const companySelect = $('#company_id');
        
//         // Only initialize if not already initialized and element exists
//         if (companySelect.length && !companySelect.hasClass('select2-initialized')) {
//             // Add an initialization flag to prevent multiple initializations
//             companySelect.addClass('select2-initialized');
            
//             // Cache for previously processed search results to improve performance
//             const searchCache = {};
            
//             // Initialize Select2 with optimized options
//             companySelect.select2({
//                 placeholder: 'Изберете компанија',
//                 allowClear: true,
//                 width: '100%',
//                 dropdownParent: $('body'),
//                 minimumInputLength: 1,  // Only trigger search after at least 1 character
//                 delay: 250,             // Add debounce delay (milliseconds)
//                 // Use a more efficient matcher with caching
//                 matcher: function(params, data) {
//                     // If no search term, return all data
//                     if ($.trim(params.term) === '') {
//                         return data;
//                     }
                    
//                     // Create a cache key from the search term and data text
//                     const cacheKey = params.term.toLowerCase() + '_' + data.text.toLowerCase();
                    
//                     // Check if we have a cached result
//                     if (searchCache[cacheKey] !== undefined) {
//                         return searchCache[cacheKey];
//                     }
                    
//                     // If there's no 'text' property, this data object can't be matched
//                     if (typeof data.text === 'undefined') {
//                         searchCache[cacheKey] = null;
//                         return null;
//                     }
                    
//                     const searchTerm = params.term.toLowerCase();
//                     const originalText = data.text.toLowerCase();
                    
//                     // Check for direct match first (most efficient)
//                     if (originalText.indexOf(searchTerm) > -1) {
//                         searchCache[cacheKey] = data;
//                         return data;
//                     }
                    
//                     // Simplified transliteration check - only convert the search term once
//                     const cyrillicChars = 'абвгдеёжзийклмнопрстуфхцчшщъыьэюя';
//                     const isCyrillic = searchTerm.split('').some(char => cyrillicChars.includes(char));
                    
//                     // Only do the heavy transliteration if we need to
//                     if (isCyrillic) {
//                         // Simple character mapping for basic Cyrillic to Latin
//                         // This is much more efficient than the full transliteration
//                         const text = originalText.replace(/[а-я]/g, char => {
//                             return {
//                                 'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e',
//                                 'ж': 'zh', 'з': 'z', 'и': 'i', 'й': 'y', 'к': 'k', 'л': 'l',
//                                 'м': 'm', 'н': 'n', 'о': 'o', 'п': 'p', 'р': 'r', 'с': 's',
//                                 'т': 't', 'у': 'u', 'ф': 'f', 'х': 'h', 'ц': 'ts', 'ч': 'ch',
//                                 'ш': 'sh', 'щ': 'sch', 'ъ': '', 'ы': 'y', 'ь': '', 'э': 'e',
//                                 'ю': 'yu', 'я': 'ya'
//                             }[char] || char;
//                         });
                        
//                         if (text.indexOf(searchTerm) > -1) {
//                             searchCache[cacheKey] = data;
//                             return data;
//                         }
//                     }
                    
//                     // No match found
//                     searchCache[cacheKey] = null;
//                     return null;
//                 }
//             }).on('select2:select', function() {
//                 try {
//                     // Get selected values
//                     const selectedCompanyId = this.value;
//                     const currentDate = document.getElementById('transaction_date').value;
                    
//                     // Update hidden form field
//                     const formCompanyId = document.getElementById('form_company_id');
//                     if (formCompanyId) {
//                         formCompanyId.value = selectedCompanyId;
//                     }
                    
//                     // Save date to localStorage for persistence
//                     if (currentDate) {
//                         localStorage.setItem('lastTransactionDate', currentDate);
//                     }
                    
//                     // Only navigate if company is selected
//                     if (selectedCompanyId) {
//                         // Use full URL to avoid path issues
//                         const baseUrl = window.location.origin;
//                         const url = `${baseUrl}/daily-transactions/create?company_id=${selectedCompanyId}&date=${currentDate}`;
                        
//                         // Show loading indicator
//                         showLoadingOverlay('Учитувам податоци...');
                        
//                         // Navigate with small delay to allow loading indicator to appear
//                         setTimeout(() => {
//                             window.location.href = url;
//                         }, 100);
//                     }
//                 } catch (e) {
//                     console.error('Error in Select2 select handler:', e);
//                     hideLoadingOverlay();
//                 }
//             }).on('select2:open', function() {
//                 // Ensure dropdown is above other elements
//                 $('.select2-dropdown').css('z-index', '9999');
                
//                 // Focus the search field when opened
//                 setTimeout(function() {
//                     $('.select2-search__field').focus();
//                 }, 10);
//             });
            
//             console.log('[PWA] Select2 initialized with optimizations');
            
//             // Add refresh button with proper positioning
//             addRefreshButton();
//         }
//     } catch (e) {
//         console.error('[PWA] Error initializing optimized Select2:', e);
//         setupRegularSelect();
//     }
// }

// Fallback to regular select if Select2 fails
function setupRegularSelect() {
    const companySelect = document.getElementById('company_id');
    if (!companySelect) return;
    
    companySelect.classList.add('form-control');
    companySelect.classList.remove('select2-hidden-accessible');
    
    // Remove any Select2 containers
    const select2Container = companySelect.nextElementSibling;
    if (select2Container && select2Container.classList.contains('select2-container')) {
        select2Container.remove();
    }
    
    // Add direct event handler
    companySelect.addEventListener('change', function() {
        try {
            const selectedCompanyId = this.value;
            const currentDate = document.getElementById('transaction_date').value;
            
            // Update hidden field
            const formCompanyId = document.getElementById('form_company_id');
            if (formCompanyId) {
                formCompanyId.value = selectedCompanyId;
            }
            
            // Navigate to refresh connected bread types
            if (selectedCompanyId) {
                showLoadingOverlay('');
                const baseUrl = window.location.origin;
                setTimeout(() => {
                    window.location.href = `${baseUrl}/daily-transactions/create?company_id=${selectedCompanyId}&date=${currentDate}`;
                }, 100);
            }
        } catch (e) {
            console.error('Error in regular select handler:', e);
            hideLoadingOverlay();
        }
    });
    
    console.log('[PWA] Regular select initialized as fallback');
}

// Add refresh button with proper positioning
function addRefreshButton() {
    const companySelectContainer = document.querySelector('.select2-container').parentNode;
    if (!companySelectContainer) return;
    
    // Check if button already exists to prevent duplicates
    if (companySelectContainer.querySelector('.refresh-button')) return;
    
    companySelectContainer.style.position = 'relative';
    
    const refreshButton = document.createElement('button');
    refreshButton.type = 'button';
    refreshButton.className = 'absolute right-8 top-0 mt-7 mr-2 text-blue-500 refresh-button';
    refreshButton.innerHTML = '<i class="fas fa-sync-alt"></i>';
    refreshButton.onclick = function(e) {
        e.preventDefault();
        e.stopPropagation();
        showLoadingOverlay('Освежувам страна...');
        setTimeout(() => {
            window.location.reload();
        }, 100);
    };
    
    companySelectContainer.appendChild(refreshButton);
}

// Show loading overlay
function showLoadingOverlay(message = 'Учитувам...') {
    // Remove any existing overlay first
    hideLoadingOverlay();
    
    const overlay = document.createElement('div');
    overlay.id = 'loading-overlay';
    overlay.className = 'fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center z-50';
    overlay.innerHTML = `
        <div class="bg-white p-5 rounded-lg shadow-xl flex items-center space-x-4">
            <div class="w-8 h-8 border-t-4 border-b-4 border-blue-500 rounded-full animate-spin"></div>
            <p class="text-lg font-medium text-gray-700">${message}</p>
        </div>
    `;
    
    document.body.appendChild(overlay);
}

// Hide loading overlay
function hideLoadingOverlay() {
    const existingOverlay = document.getElementById('loading-overlay');
    if (existingOverlay) {
        existingOverlay.remove();
    }
}

// document.addEventListener('DOMContentLoaded', function() {
//     // Check if jQuery and Select2 are available
//     function waitForJQuery(callback) {
//         if (window.jQuery && window.jQuery.fn.select2) {
//             callback();
//         } else {
//             setTimeout(function() { waitForJQuery(callback); }, 100);
//         }
//     }
    
//     // Initialize Select2 with proper event handling
//     waitForJQuery(function() {
//         try {
//             if ($('#company_id').length) {
//                 // Initialize Select2
//                 $('#company_id').select2({
//                     placeholder: 'Изберете компанија',
//                     allowClear: true,
//                     width: '100%',
//                     dropdownParent: $('body')
//                 }).on('select2:select', function() {
//                     // Get the selected company ID and current date
//                     const selectedCompanyId = this.value;
//                     const currentDate = document.getElementById('transaction_date').value;
                    
//                     // Update the hidden form field
//                     const formCompanyId = document.getElementById('form_company_id');
//                     if (formCompanyId) {
//                         formCompanyId.value = selectedCompanyId;
//                     }
                    
//                     // Navigate to the URL with parameters
//                     if (selectedCompanyId) {
//                         window.location.href = `/daily-transactions/create?company_id=${selectedCompanyId}&date=${currentDate}`;
//                     }
//                 });
                
//                 console.log('Select2 initialized successfully');
//             }
//         } catch (e) {
//             console.error('Error initializing Select2:', e);
            
//             // Fallback to regular select
//             const companySelect = document.getElementById('company_id');
//             if (companySelect) {
//                 companySelect.addEventListener('change', function() {
//                     const selectedCompanyId = this.value;
//                     const currentDate = document.getElementById('transaction_date').value;
                    
//                     // Update hidden field
//                     const formCompanyId = document.getElementById('form_company_id');
//                     if (formCompanyId) {
//                         formCompanyId.value = selectedCompanyId;
//                     }
                    
//                     // Navigate to refresh connected bread types
//                     if (selectedCompanyId) {
//                         window.location.href = `/daily-transactions/create?company_id=${selectedCompanyId}&date=${currentDate}`;
//                     }
//                 });
//             }
//         }
//     });
// });
// // Transliteration maps
// const latinToCyrillicMap = {
//     'a': 'а', 'b': 'б', 'v': 'в', 'g': 'г', 'd': 'д', 'e': 'е', 'zh': 'ж', 'z': 'з', 
//     'i': 'и', 'j': 'ј', 'k': 'к', 'l': 'л', 'm': 'м', 'n': 'н', 'o': 'о', 'p': 'п', 
//     'r': 'р', 's': 'с', 't': 'т', 'u': 'у', 'f': 'ф', 'h': 'х', 'c': 'ц', 'ch': 'ч', 
//     'sh': 'ш', 'dj': 'џ', 'gj': 'ѓ', 'kj': 'ќ', 'lj': 'љ', 'nj': 'њ'
// };

// // Create reverse map (Cyrillic to Latin)
// const cyrillicToLatinMap = {};
// for (const [latin, cyrillic] of Object.entries(latinToCyrillicMap)) {
//     cyrillicToLatinMap[cyrillic] = latin;
// }

// // Function to transliterate Latin to Cyrillic
// function latinToCyrillic(input) {
//     if (!input) return '';
    
//     let result = input.toLowerCase();
    
//     // First replace two-character combinations
//     result = result.replace(/(ch|sh|zh|dj|gj|kj|lj|nj)/g, function(match) {
//         return latinToCyrillicMap[match] || match;
//     });
    
//     // Then replace single characters
//     result = result.replace(/[a-z]/g, function(match) {
//         return latinToCyrillicMap[match] || match;
//     });
    
//     return result;
// }

// // Function to transliterate Cyrillic to Latin
// function cyrillicToLatin(input) {
//     if (!input) return '';
    
//     let result = input.toLowerCase();
    
//     // Replace Cyrillic characters with Latin equivalents
//     result = result.replace(/[а-џљњѓќ]/g, function(match) {
//         return cyrillicToLatinMap[match] || match;
//     });
    
//     return result;
// }

// // Custom matcher function for Select2
// function customMatcher(params, data) {
//     // If there are no search terms, return all of the data
//     if ($.trim(params.term) === '') {
//         return data;
//     }
    
//     // Do not display the item if there is no 'text' property
//     if (typeof data.text === 'undefined') {
//         return null;
//     }
    
//     const searchTerm = params.term.toLowerCase();
//     const originalText = data.text.toLowerCase();
    
//     // Case 1: Direct match (case insensitive)
//     if (originalText.indexOf(searchTerm) > -1) {
//         return data;
//     }
    
//     // Case 2: If search term is Latin, convert to Cyrillic and search
//     const latinToCyrillicTerm = latinToCyrillic(searchTerm);
//     if (originalText.indexOf(latinToCyrillicTerm) > -1) {
//         return data;
//     }
    
//     // Case 3: If search term is Cyrillic, convert to Latin and search in potentially Latin text
//     const cyrillicToLatinTerm = cyrillicToLatin(searchTerm);
//     if (originalText.indexOf(cyrillicToLatinTerm) > -1) {
//         return data;
//     }
    
//     // Case 4: If company name is Cyrillic, convert to Latin and check if it contains the Latin search term
//     const companyNameInLatin = cyrillicToLatin(originalText);
//     if (companyNameInLatin.indexOf(searchTerm) > -1) {
//         return data;
//     }
    
//     // Case 5: If company name is Latin, convert to Cyrillic and check if it contains the Cyrillic search term
//     const companyNameInCyrillic = latinToCyrillic(originalText);
//     if (companyNameInCyrillic.indexOf(searchTerm) > -1) {
//         return data;
//     }
    
//     // If all checks fail, don't return anything
//     return null;
// }

// // Initialize Select2 when DOM is ready
// document.addEventListener('DOMContentLoaded', function() {
//     // Wait for jQuery and Select2 to be loaded
//     function waitForJQuery(callback) {
//         if (window.jQuery && window.jQuery.fn.select2) {
//             callback();
//         } else {
//             setTimeout(function() { waitForJQuery(callback); }, 100);
//         }
//     }
    
//     waitForJQuery(function() {
//         try {
//             if ($('#company_id').length) {
//                 $('#company_id').select2({
//                     placeholder: 'Изберете компанија',
//                     allowClear: true,
//                     width: '100%',
//                     dropdownParent: $('body'),
//                     matcher: customMatcher // Use our custom matcher
//                 }).on('select2:select', function() {
//                     // Handle company selection
//                     const selectedCompanyId = this.value;
//                     const currentDate = document.getElementById('transaction_date').value;
                    
//                     // Update hidden form field
//                     const formCompanyId = document.getElementById('form_company_id');
//                     if (formCompanyId) {
//                         formCompanyId.value = selectedCompanyId;
//                     }
                    
//                     // Navigate to the URL with parameters
//                     if (selectedCompanyId) {
//                         // Use full URL to avoid any path issues
//                         const baseUrl = window.location.origin;
//                         window.location.href = `${baseUrl}/daily-transactions/create?company_id=${selectedCompanyId}&date=${currentDate}`;
//                     }
//                 });
                
//                 console.log('Select2 initialized with transliteration support');
//             }
//         } catch (e) {
//             console.error('Error initializing Select2 with transliteration:', e);
            
//             // Fallback to regular select
//             const companySelect = document.getElementById('company_id');
//             if (companySelect) {
//                 companySelect.addEventListener('change', function() {
//                     const selectedCompanyId = this.value;
//                     const currentDate = document.getElementById('transaction_date').value;
                    
//                     // Update hidden field
//                     const formCompanyId = document.getElementById('form_company_id');
//                     if (formCompanyId) {
//                         formCompanyId.value = selectedCompanyId;
//                     }
                    
//                     // Navigate to refresh page
//                     if (selectedCompanyId) {
//                         const baseUrl = window.location.origin;
//                         window.location.href = `${baseUrl}/daily-transactions/create?company_id=${selectedCompanyId}&date=${currentDate}`;
//                     }
//                 });
//             }
//         }
//     });
// });
// Global CSRF token error handler
document.addEventListener('DOMContentLoaded', function() {
    // Intercept all fetch requests
    const originalFetch = window.fetch;
    window.fetch = async function(url, options = {}) {
        try {
            const response = await originalFetch(url, options);
            
            // Check for 419 CSRF token errors
            if (response.status === 419) {
                console.log('CSRF token expired. Refreshing token...');
                
                // Get a new CSRF token
                try {
                    const tokenResponse = await originalFetch('/api/refresh-csrf', {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    if (tokenResponse.ok) {
                        const tokenData = await tokenResponse.json();
                        
                        if (tokenData.token) {
                            // Update all CSRF tokens in the DOM
                            document.querySelectorAll('[name="_token"]').forEach(input => {
                                input.value = tokenData.token;
                            });
                            
                            document.querySelectorAll('meta[name="csrf-token"]').forEach(meta => {
                                meta.setAttribute('content', tokenData.token);
                            });
                            
                            console.log('CSRF token refreshed. Retrying request...');
                            
                            // Retry the original request with the new token
                            if (options && options.headers) {
                                options.headers['X-CSRF-TOKEN'] = tokenData.token;
                            }
                            
                            return originalFetch(url, options);
                        }
                    }
                } catch (tokenError) {
                    console.error('Error refreshing token:', tokenError);
                }
                
                // If we can't refresh the token, reload the page
                alert('Вашата сесија е истечена. Страницата ќе се вчита повторно.');
                location.reload();
                return response;
            }
            
            return response;
        } catch (error) {
            console.error('Fetch error:', error);
            throw error;
        }
    };
    
    // Also intercept XMLHttpRequest for older code
    const originalXhrOpen = XMLHttpRequest.prototype.open;
    const originalXhrSend = XMLHttpRequest.prototype.send;
    
    XMLHttpRequest.prototype.open = function() {
        this._url = arguments[1];
        this._method = arguments[0];
        return originalXhrOpen.apply(this, arguments);
    };
    
    XMLHttpRequest.prototype.send = function() {
        const xhr = this;
        const originalOnReadyStateChange = xhr.onreadystatechange;
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 419) {
                    console.log('CSRF token expired in XHR. Refreshing token...');
                    
                    // Create a new XHR to get a fresh token
                    const tokenXhr = new XMLHttpRequest();
                    tokenXhr.open('GET', '/api/refresh-csrf', false); // Synchronous for simplicity
                    tokenXhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    
                    try {
                        tokenXhr.send();
                        
                        if (tokenXhr.status === 200) {
                            const response = JSON.parse(tokenXhr.responseText);
                            
                            if (response.token) {
                                // Update all CSRF tokens in the DOM
                                document.querySelectorAll('[name="_token"]').forEach(input => {
                                    input.value = response.token;
                                });
                                
                                document.querySelectorAll('meta[name="csrf-token"]').forEach(meta => {
                                    meta.setAttribute('content', response.token);
                                });
                                
                                console.log('CSRF token refreshed. Reloading page...');
                                
                                // For XHR, just reload the page to restart the flow
                                alert('Вашата сесија е истечена. Страницата ќе се вчита повторно.');
                                location.reload();
                                return;
                            }
                        }
                    } catch (e) {
                        console.error('Error refreshing CSRF token:', e);
                    }
                    
                    // If we can't refresh the token, reload the page
                    alert('Вашата сесија е истечена. Страницата ќе се вчита повторно.');
                    location.reload();
                    return;
                }
            }
            
            if (originalOnReadyStateChange) {
                originalOnReadyStateChange.apply(xhr, arguments);
            }
        };
        
        return originalXhrSend.apply(xhr, arguments);
    };
    
    // Direct event handler for modal cancel button
    const cancelButton = document.getElementById('cancelSummary');
    const modal = document.getElementById('transactionSummaryModal');
    
    if (cancelButton && modal) {
        cancelButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            modal.classList.add('hidden');
        });
    }
    
    // Refresh CSRF token on page load to ensure it's fresh
    function refreshCSRFTokenOnLoad() {
        try {
            fetch('/api/refresh-csrf', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.token) {
                    // Update all CSRF tokens in the DOM
                    document.querySelectorAll('[name="_token"]').forEach(input => {
                        input.value = data.token;
                    });
                    
                    document.querySelectorAll('meta[name="csrf-token"]').forEach(meta => {
                        meta.setAttribute('content', data.token);
                    });
                    
                    console.log('CSRF token refreshed on page load');
                }
            })
            .catch(error => {
                console.error('Error refreshing CSRF token on load:', error);
            });
        } catch (e) {
            console.error('Error in CSRF token refresh:', e);
        }
    }
    
    // Call the refresh function with a slight delay to ensure the page is fully loaded
    setTimeout(refreshCSRFTokenOnLoad, 500);
});
</script>





@endsection