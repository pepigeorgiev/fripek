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
</style>

@section('content')

<!-- Include Select2 CSS and JS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

<script>

    // Wait for jQuery and DOM to be fully loaded
// Wait for jQuery and DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Check if jQuery is available
    function waitForJQuery(callback) {
        if (window.jQuery) {
            callback();
        } else {
            console.log('Waiting for jQuery...');
            setTimeout(function() { waitForJQuery(callback); }, 100);
        }
    }
    
    // Initialize Select2 after jQuery is available
    waitForJQuery(function() {
        try {
            // Make sure the company_id element exists before trying to initialize Select2
            if ($('#company_id').length) {
                console.log('Initializing Select2...');
                $('#company_id').select2({
                    placeholder: 'Изберете компанија',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('body')
                }).on('select2:open', function() {
                    // Ensure dropdown is above other elements
                    $('.select2-dropdown').css('z-index', '9999');
                }).on('change', function() {
                    // Update hidden field immediately when selection changes
                    const formCompanyId = document.getElementById('form_company_id');
                    if (formCompanyId) {
                        formCompanyId.value = this.value;
                        console.log('Updated hidden company_id to:', this.value);
                    }
                    
                    // Get the selected company ID and current date
                    const selectedCompanyId = this.value;
                    const currentDate = document.getElementById('transaction_date').value;
                    
                    // Submit the form to refresh connected bread types
                    if (selectedCompanyId) {
                        window.location.href = `/daily-transactions/create?company_id=${selectedCompanyId}&date=${currentDate}`;
                    }
                });
                console.log('Select2 initialized successfully');
            } else {
                console.error('company_id element not found');
            }
        } catch (e) {
            console.error('Error initializing Select2:', e);
            fallbackToRegularSelect();
        }
    });
    
    function fallbackToRegularSelect() {
        const companySelect = document.getElementById('company_id');
        if (companySelect) {
            companySelect.classList.add('form-control');
            companySelect.removeAttribute('disabled');
            
            // Add direct event handler if Select2 fails
            companySelect.addEventListener('change', function() {
                const selectedCompanyId = this.value;
                const currentDate = document.getElementById('transaction_date').value;
                
                // Update hidden field
                const formCompanyId = document.getElementById('form_company_id');
                if (formCompanyId) {
                    formCompanyId.value = selectedCompanyId;
                }
                
                // Navigate to refresh connected bread types
                if (selectedCompanyId) {
                    window.location.href = `/daily-transactions/create?company_id=${selectedCompanyId}&date=${currentDate}`;
                }
            });
        }
    }
    
    // Add refresh button with proper timing
    const addRefreshButton = function() {
        const companySelectContainer = document.querySelector('#company_id').parentNode;
        if (!companySelectContainer) return;
        
        // Check if button already exists to prevent duplicates
        if (companySelectContainer.querySelector('button[type="button"]')) return;
        
        companySelectContainer.style.position = 'relative';
        
        const refreshButton = document.createElement('button');
        refreshButton.type = 'button';
        refreshButton.className = 'absolute right-0 top-0 mt-7 mr-2 text-blue-500';
        refreshButton.innerHTML = '<i class="fas fa-sync-alt"></i>';
        refreshButton.onclick = function() {
            window.location.reload();
        };
        
        companySelectContainer.appendChild(refreshButton);
    };
    
    // Add refresh button after a delay to ensure Select2 is initialized
    setTimeout(addRefreshButton, 300);
    
    // Global form field update function
    function updateHiddenFormFields() {
        const companySelect = document.getElementById('company_id');
        const dateInput = document.getElementById('transaction_date');
        const formCompanyId = document.getElementById('form_company_id');
        const formTransactionDate = document.getElementById('form_transaction_date');
        
        if (companySelect && formCompanyId) {
            formCompanyId.value = companySelect.value;
        }
        
        if (dateInput && formTransactionDate) {
            formTransactionDate.value = dateInput.value;
        }
    }
    
    // Run this once on page load
    updateHiddenFormFields();
    
    // Also update when date changes (company is handled by Select2 change event)
    const dateInput = document.getElementById('transaction_date');
    if (dateInput) {
        dateInput.addEventListener('change', function() {
            updateHiddenFormFields();
            
            // Also refresh page with new date
            const companyId = document.getElementById('company_id').value;
            if (companyId) {
                window.location.href = `/daily-transactions/create?company_id=${companyId}&date=${this.value}`;
            }
        });
    }
    
    // Fix the filterForm behavior
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        // Remove the onchange attribute from company select
        const companySelect = document.getElementById('company_id');
        if (companySelect) {
            companySelect.removeAttribute('onchange');
        }
        
        // Handle form submission manually
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const companyId = document.getElementById('company_id').value;
            const date = document.getElementById('transaction_date').value;
            
            window.location.href = `/daily-transactions/create?company_id=${companyId}&date=${date}`;
        });
    }
});
// document.addEventListener('DOMContentLoaded', function() {
//     // Initialize Select2 with a timeout to ensure it loads properly
//     setTimeout(function() {
//         try {
//             $('#company_id').select2({
//                 placeholder: 'Изберете компанија',
//                 allowClear: true,
//                 width: '100%',
//                 dropdownParent: $('body') // Ensures dropdown shows above other elements
//             });
//             console.log('Select2 initialized successfully');
//         } catch (e) {
//             console.error('Error initializing Select2:', e);
            
//             // Fallback to regular select if Select2 fails
//             const companySelect = document.getElementById('company_id');
//             if (companySelect) {
//                 companySelect.classList.add('form-control');
//                 companySelect.removeAttribute('disabled');
//             }
//         }
//     }, 100);
    
//     // Add refresh button next to company dropdown
//     const companySelectContainer = document.querySelector('#company_id').parentNode;
//     const refreshButton = document.createElement('button');
//     refreshButton.type = 'button';
//     refreshButton.className = 'absolute right-0 top-0 mt-7 mr-2 text-blue-500';
//     refreshButton.innerHTML = '<i class="fas fa-sync-alt"></i>';
//     refreshButton.onclick = function() {
//         window.location.reload();
//     };
//     companySelectContainer.style.position = 'relative';
//     companySelectContainer.appendChild(refreshButton);
// });
</script>

<!-- Remove container padding for mobile -->
<div class="mx-auto md:p-0">
    <h1 class="text-xl md:text-2xl font-bold mb-2 md:mb-4 px-2 md:px-0">Дневни Трансакции</h1>
    
    <div class="bg-white p-2 md:p-6 rounded shadow">
        <!-- Mobile-friendly grid -->

        <!-- Replace your company selection section with this form-based approach -->
        <!-- <form id="filterForm" method="GET" action="{{ route('daily-transactions.create') }}">
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
        <div>
            <label for="transaction_date" class="block text-sm font-medium text-gray-700">Дата</label>
            <input type="date" id="transaction_date" name="transaction_date" 
                class="mt-1 block w-full text-sm md:text-base rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                value="{{ $date }}">
        </div>
    </div>
</form> -->
        <form id="filterForm" method="GET" action="{{ route('daily-transactions.create') }}">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 md:gap-4 mb-4 md:mb-6">
        <div>
            <label for="company_id" class="block text-sm font-medium text-gray-700">Компанија</label>
            <select id="company_id" name="company_id" class="company-select mt-1 block w-full text-sm md:text-base rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" onchange="this.form.submit()">
                <option value="">Изберете компанија</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" {{ $selectedCompanyId == $company->id ? 'selected' : '' }}>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
                <label for="transaction_date" class="block text-sm font-medium text-gray-700">Дата</label>
                <input type="date" id="transaction_date" name="transaction_date" 
                    class="mt-1 block w-full text-sm md:text-base rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    value="{{ $date }}">
            </div>
    </div>
</form>
        

<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Make bread names clickable to focus on input
    function setupBreadNameClicks() {
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
    }
    
    // Setup input field event handlers
    function setupInputHandlers() {
        document.querySelectorAll('.delivered-input, .returned-input, .gratis-input').forEach(input => {
            const rowIndex = input.getAttribute('data-row');
            
            // Clear value on focus if it's 0
            input.addEventListener('focus', function() {
                if (this.value === '0') {
                    this.value = '';
                }
            });
            
            // Reset to 0 if left empty on blur
            input.addEventListener('blur', function() {
                if (this.value === '') {
                    this.value = '0';
                }
                calculateRowTotal(rowIndex);
            });
            
            // Calculate total on input change
            input.addEventListener('input', function() {
                calculateRowTotal(rowIndex);
            });
        });
    }
    
    // Calculate the total for a specific row
    function calculateRowTotal(index) {
        const delivered = parseInt(document.querySelector(`input[name="transactions[${index}][delivered]"]`).value) || 0;
        const returned = parseInt(document.querySelector(`input[name="transactions[${index}][returned]"]`).value) || 0;
        const gratis = parseInt(document.querySelector(`input[name="transactions[${index}][gratis]"]`).value) || 0;
        
        const total = delivered - returned - gratis;
        const totalElement = document.querySelector(`#total-${index}`);
        if (totalElement) {
            totalElement.textContent = total;
        }
    }
    
    // Helper function to select and clear if zero
    function selectAndClearIfZero(input) {
        input.select();
        if (input.value === '0') {
            input.value = '';
        }
    }
    
    // Add keyboard navigation between fields
    function setupKeyboardNavigation() {
        document.querySelectorAll('.delivered-input, .returned-input, .gratis-input').forEach(input => {
            input.addEventListener('keydown', function(e) {
                const rowIndex = parseInt(this.getAttribute('data-row'));
                const isDelivered = this.classList.contains('delivered-input');
                const isReturned = this.classList.contains('returned-input');
                const isGratis = this.classList.contains('gratis-input');
                
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
    }
    
    // Initialize all functionality
    function initialize() {
        setupBreadNameClicks();
        setupInputHandlers();
        setupKeyboardNavigation();
        
        // Calculate all row totals on page load
        document.querySelectorAll('.delivered-input').forEach(input => {
            const rowIndex = input.getAttribute('data-row');
            if (rowIndex) {
                calculateRowTotal(rowIndex);
            }
        });
    }
    
    // Run initialization
    initialize();
});
document.addEventListener('DOMContentLoaded', function() {
    // Update hidden form fields with values from URL
    const urlParams = new URLSearchParams(window.location.search);
    const companyId = urlParams.get('company_id');
    const transactionDate = urlParams.get('date');
    
    console.log('URL parameters:', { companyId, transactionDate });
    
    // Set values in the hidden fields
    const formCompanyId = document.getElementById('form_company_id');
    const formTransactionDate = document.getElementById('form_transaction_date');
    
    if (formCompanyId && companyId) {
        formCompanyId.value = companyId;
        console.log('Set form_company_id to:', companyId);
    }
    
    if (formTransactionDate && transactionDate) {
        formTransactionDate.value = transactionDate;
        console.log('Set form_transaction_date to:', transactionDate);
    }
    
    // If using summary button flow, ensure form values are set before summary
    const summaryButton = document.querySelector('button[type="button"]');
    if (summaryButton) {
        summaryButton.addEventListener('click', function() {
            // Update hidden form fields again before showing summary
            if (formCompanyId) formCompanyId.value = companyId || '';
            if (formTransactionDate) formTransactionDate.value = transactionDate || '';
            
            console.log('Updated before summary:', {
                company_id: formCompanyId ? formCompanyId.value : 'field not found',
                transaction_date: formTransactionDate ? formTransactionDate.value : 'field not found'
            });
        });
    }
    
    // Debug form before submit
    const transactionForm = document.getElementById('transactionForm');
    if (transactionForm) {
        transactionForm.addEventListener('submit', function(e) {
            // For debugging only - log form values
            const formData = new FormData(this);
            console.log('Submitting form with company_id:', formData.get('company_id'));
            console.log('Submitting form with transaction_date:', formData.get('transaction_date'));
            
            // Check if company_id is missing
            if (!formData.get('company_id')) {
                e.preventDefault();
                alert('Потребно е да изберете компанија пред да зачувате трансакција.');
                return false;
            }
        });
    }
});
</script>



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
                <!-- Add this modal right before the closing </form> tag -->

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

<script>
// Wait for the page to fully load
window.addEventListener('load', function() {
    console.log('Transaction summary page loaded');
    
    // Find the form and submit button
    const form = document.getElementById('transactionForm');
    if (!form) {
        console.error('Transaction form not found');
        return;
    }
    
    const originalSubmitButton = form.querySelector('button[type="submit"]');
    if (!originalSubmitButton) {
        console.error('Submit button not found');
        return;
    }
    
    // Find modal elements
    const modal = document.getElementById('transactionSummaryModal');
    const summaryContent = document.getElementById('summaryContent');
    const summaryQuantity = document.getElementById('summaryQuantity');
    const summaryPrice = document.getElementById('summaryPrice');
    const cancelButton = document.getElementById('cancelSummary');
    const confirmButton = document.getElementById('confirmSummary');
    
    if (!modal || !summaryContent || !summaryQuantity || !summaryPrice || !cancelButton || !confirmButton) {
        console.error('Modal elements not found');
        return;
    }
    
    // Load bread types data
    let breadTypesData = {};
    try {
        const breadTypesElement = document.getElementById('breadTypesData');
        if (breadTypesElement && breadTypesElement.dataset.breadTypes) {
            const breadTypes = JSON.parse(breadTypesElement.dataset.breadTypes);
            breadTypes.forEach(breadType => {
                breadTypesData[breadType.id] = breadType;
            });
            console.log('Loaded bread types data:', breadTypesData);
        }
    } catch (e) {
        console.error('Error loading bread types data:', e);
    }
    
    // Load companies data
    let companiesData = {};
    try {
        const companiesElement = document.getElementById('companiesData');
        if (companiesElement && companiesElement.dataset.companies) {
            companiesData = JSON.parse(companiesElement.dataset.companies);
            console.log('Loaded companies data:', companiesData);
        }
    } catch (e) {
        console.error('Error loading companies data:', e);
    }
    
    // Create a new button that looks the same but is type="button"
    const newButton = document.createElement('button');
    newButton.type = 'button';
    newButton.className = originalSubmitButton.className;
    newButton.innerHTML = originalSubmitButton.innerHTML;
    
    // Replace the original button
    originalSubmitButton.parentNode.replaceChild(newButton, originalSubmitButton);
    console.log('Submit button replaced');
    
    // Add click handler for the new button
    newButton.addEventListener('click', function() {
        console.log('Summary button clicked');
        
        // First update the hidden form fields
        const companySelect = document.getElementById('company_id');
        const dateInput = document.getElementById('transaction_date');
        const formCompanyId = document.getElementById('form_company_id');
        const formTransactionDate = document.getElementById('form_transaction_date');
        
        if (companySelect && formCompanyId) {
            formCompanyId.value = companySelect.value;
            console.log('Updated company_id:', companySelect.value);
        }
        
        if (dateInput && formTransactionDate) {
            formTransactionDate.value = dateInput.value;
            console.log('Updated transaction_date:', dateInput.value);
        }
        
        // Check if company is selected
        if (companySelect && !companySelect.value) {
            alert('Ве молиме изберете компанија');
            return;
        }
        
        // Show the summary
        showTransactionSummary(companySelect.value);
    });
    
    // Cancel button closes the modal
    cancelButton.addEventListener('click', function() {
        modal.classList.add('hidden');
    });

    <!-- Update the confirm button's click handler in your script -->

    // Replace the confirm button click handler with this updated version
confirmButton.addEventListener('click', function() {
    modal.classList.add('hidden');
    
    // Check if this is an update to existing transaction
    const updateExistingCheckbox = document.getElementById('update_existing_transaction');
    const isUpdate = updateExistingCheckbox && updateExistingCheckbox.checked;
    
    // Get form data
    const formData = new FormData(form);
    const companyId = document.getElementById('company_id').value;
    const transactionDate = document.getElementById('transaction_date').value;
    
    // Show a loading indicator
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
    
    if (isUpdate) {
        console.log('Submitting as update to existing transaction');
        
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
        
        console.log('Update payload:', payload);
        
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
        .then(data => handleResponse(data, companyId, transactionDate))
        .catch(handleError);
    } else {
        console.log('Submitting as new transaction');
        
        // Submit regular form
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => handleResponse(data, companyId, transactionDate))
        .catch(handleError);
    }
});

// Helper function to handle successful response
function handleResponse(data, companyId, transactionDate) {
    // Remove loading indicator
    if (document.getElementById('loadingIndicator')) {
        document.getElementById('loadingIndicator').remove();
    }
    
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
}

// Helper function to handle errors
function handleError(error) {
    // Remove loading indicator
    if (document.getElementById('loadingIndicator')) {
        document.getElementById('loadingIndicator').remove();
    }
    
    console.error('Error submitting form:', error);
    alert('Грешка при комуникација со серверот. Обидете се повторно.');
}

function getBreadTypePrice(breadTypeId, companyId) {
    // Default price
    let price = 0;
    
    try {
        // Try to fetch the price from the server directly
        const date = document.getElementById('transaction_date').value;
        const xhr = new XMLHttpRequest();
        // Make a synchronous request for simplicity
        xhr.open('GET', `/api/get-bread-price/${breadTypeId}/${companyId}?date=${date}`, false);
        
        try {
            xhr.send();
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                console.log(`Got price from server for bread type ${breadTypeId}: ${response.price}`);
                return parseFloat(response.price) || 0;
            }
        } catch (error) {
            console.error('Error fetching price from server:', error);
            // Fall back to client-side calculation
        }
        
        // If server request failed, fall back to client-side calculation
        // Get the bread type data
        const breadType = breadTypesData[breadTypeId];
        if (!breadType) {
            console.error(`Bread type ID ${breadTypeId} not found in data`);
            return price;
        }
        
        // Get company's price group
        const companyPriceGroup = companiesData[companyId];
        if (companyPriceGroup === undefined) {
            console.error(`Company ID ${companyId} not found in data`);
            return breadType.price; // Fall back to default price
        }
        
        console.log(`Calculating price for bread type ${breadTypeId}, company ${companyId} with price group ${companyPriceGroup}`);
        
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
        
        console.log(`Calculated client-side price: ${price} for bread type ${breadType.name}`);
        return price;
    } catch (e) {
        console.error('Error getting bread type price:', e);
        return 0;
    }
}

// Replace this part in your existing script:
// confirmButton.addEventListener('click', function() {
//     modal.classList.add('hidden');
    
//     // Submit form using fetch to handle the response properly
//     const formData = new FormData(form);
    
//     // Show a loading indicator
//     const loadingDiv = document.createElement('div');
//     loadingDiv.id = 'loadingIndicator';
//     loadingDiv.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50';
//     loadingDiv.innerHTML = `
//         <div class="bg-white p-4 rounded-lg shadow-lg text-center">
//             <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto mb-2"></div>
//             <p>Зачувување...</p>
//         </div>
//     `;
//     document.body.appendChild(loadingDiv);
    
//     // Submit the form using fetch to handle the JSON response
//     fetch(form.action, {
//         method: 'POST',
//         body: formData,
//         headers: {
//             'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
//             'X-Requested-With': 'XMLHttpRequest'
//         }
//     })
//     .then(response => response.json())
//     .then(data => {
//         // Remove loading indicator
//         document.getElementById('loadingIndicator').remove();
        
//         if (data.success) {
//             // Show success message
//             const successDiv = document.createElement('div');
//             successDiv.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50';
//             successDiv.innerHTML = `
//                 <div class="bg-white p-4 rounded-lg shadow-lg text-center max-w-md">
//                     <div class="text-green-500 mb-2">
//                         <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
//                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
//                         </svg>
//                     </div>
//                     <h3 class="text-lg font-medium text-gray-900 mb-2">Успешно!</h3>
//                     <p class="text-gray-600 mb-4">${data.message}</p>
//                     <button class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600" onclick="this.parentNode.parentNode.remove(); window.location.reload();">
//                         ОК
//                     </button>
//                 </div>
//             `;
//             document.body.appendChild(successDiv);
            
//             // Store current parameters for reload
//             const companyId = document.getElementById('company_id').value;
//             const date = document.getElementById('transaction_date').value;
            
//             // Set a small timeout to allow user to see the message
//             setTimeout(() => {
//                 window.location.href = '/daily-transactions/create?company_id=' + companyId + '&date=' + date;
//             }, 2000);
//         } else {
//             // Show error message
//             alert(data.message || 'Грешка при зачувување.');
//         }
//     })
//     .catch(error => {
//         // Remove loading indicator
//         if (document.getElementById('loadingIndicator')) {
//             document.getElementById('loadingIndicator').remove();
//         }
        
//         console.error('Error submitting form:', error);
//         alert('Грешка при комуникација со серверот. Обидете се повторно.');
//     });
// });
    
    // // Confirm button submits the form
    // confirmButton.addEventListener('click', function() {
    //     modal.classList.add('hidden');
        
    //     // Just submit the form
    //     form.submit();
    // });
    
    // Function to get the price for a bread type based on company's price group
    // function getBreadTypePrice(breadTypeId, companyId) {
    //     // Default price
    //     let price = 0;
        
    //     try {
    //         // Get the bread type data
    //         const breadType = breadTypesData[breadTypeId];
    //         if (!breadType) {
    //             console.error(`Bread type ID ${breadTypeId} not found in data`);
    //             return price;
    //         }
            
    //         // Get company's price group
    //         const companyPriceGroup = companiesData[companyId];
    //         if (companyPriceGroup === undefined) {
    //             console.error(`Company ID ${companyId} not found in data`);
    //             return breadType.price; // Fall back to default price
    //         }
            
    //         console.log(`Getting price for bread type ${breadTypeId}, company ${companyId} with price group ${companyPriceGroup}`);
            
    //         // Determine price based on company's price group
    //         if (companyPriceGroup === 0 || companyPriceGroup === null) {
    //             // Use default price
    //             price = parseFloat(breadType.price) || 0;
    //         } else {
    //             // Use price for specific group if available, otherwise default
    //             const priceGroupField = `price_group_${companyPriceGroup}`;
    //             if (breadType[priceGroupField] !== undefined && breadType[priceGroupField] !== null) {
    //                 price = parseFloat(breadType[priceGroupField]) || 0;
    //             } else {
    //                 price = parseFloat(breadType.price) || 0;
    //             }
    //         }
            
    //         console.log(`Selected price: ${price} for bread type ${breadType.name}`);
    //         return price;
    //     } catch (e) {
    //         console.error('Error getting bread type price:', e);
    //         return 0;
    //     }
    // }
    
    // Function to show transaction summary
    function showTransactionSummary(companyId) {
        console.log('Showing transaction summary for company ID:', companyId);
        
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
        rows.forEach(function(row) {
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
                    const price = getBreadTypePrice(breadTypeId, companyId);
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
        summaryContent.innerHTML = tableHtml;
        summaryQuantity.textContent = totalQuantity;
        summaryPrice.textContent = totalPrice.toFixed(2) + ' ден.';
        
        // Show modal
        modal.classList.remove('hidden');
        console.log('Modal shown');
    }
});
</script>
            </form>
        </div>

    

        <!-- Old Bread Sales Section -->
        <div id="oldBreadSalesSection" class="hidden mt-4 px-2 md:px-0">
        <!-- <form id="oldBreadSalesForm" action="{{ route('daily-transactions.store-old-bread') }}" method="POST"> -->
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
                                                   class="w-full px-2 py-1 border rounded text-center-desktop">
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterForm');
    const companySelect = document.getElementById('company_id');
    const dateInput = document.getElementById('transaction_date');
    
    if (filterForm && companySelect && dateInput) {
        // Store initial values in localStorage
        if (dateInput.value) {
            localStorage.setItem('lastTransactionDate', dateInput.value);
        }
        if (companySelect.value) {
            localStorage.setItem('lastCompanyId', companySelect.value);
        }
        
        // Remove the inline onchange handler
        companySelect.removeAttribute('onchange');
        
        // Add change handler for company select
        companySelect.addEventListener('change', function() {
            // Store the selected company
            localStorage.setItem('lastCompanyId', this.value);
            
            // Ensure date is preserved
            const currentDate = dateInput.value || localStorage.getItem('lastTransactionDate');
            if (currentDate) {
                dateInput.value = currentDate;
            }
            
            // Submit the form
            filterForm.submit();
        });
        
        // Add change handler for date input
        dateInput.addEventListener('change', function() {
            // Store the selected date
            localStorage.setItem('lastTransactionDate', this.value);
            
            // Ensure company is preserved
            const currentCompany = companySelect.value || localStorage.getItem('lastCompanyId');
            if (currentCompany) {
                companySelect.value = currentCompany;
            }
            
            // Submit the form
            filterForm.submit();
        });
        
        // Restore values from localStorage if needed
        const storedDate = localStorage.getItem('lastTransactionDate');
        const storedCompany = localStorage.getItem('lastCompanyId');
        
        let needsSubmit = false;
        
        if (storedDate && storedDate !== dateInput.value) {
            dateInput.value = storedDate;
            needsSubmit = true;
        }
        
        if (storedCompany && storedCompany !== companySelect.value) {
            companySelect.value = storedCompany;
            needsSubmit = true;
        }
        
        // If values were restored and a company is selected, submit the form
        if (needsSubmit && companySelect.value) {
            setTimeout(function() {
                filterForm.submit();
            }, 200);
        }
    }
});
</script>


<script>
// CSRF token refresh mechanism
function refreshCsrfToken() {
    return $.get('/csrf-token').then(function(data) {
        $('meta[name="csrf-token"]').attr('content', data.token);
        $('[name="_token"]').val(data.token);
        return data.token;
    });
}

// Setup Ajax to handle 419 globally
$.ajaxSetup({
    error: function(xhr, status, error) {
        if (xhr.status === 419) {
            // Silently refresh CSRF token and retry the request
            refreshCsrfToken().then(() => {
                // Retry the original request with new token
                const originalRequest = this;
                originalRequest.headers = {
                    ...originalRequest.headers,
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                };
                $.ajax(originalRequest);
            });
            return false; // Prevent error from showing
        }
    }
});



document.addEventListener('DOMContentLoaded', function() {
    const companySelect = document.getElementById('company_id');
    const dateInput = document.getElementById('transaction_date');
    const filterForm = document.getElementById('filterForm');
    
    // Remove the inline onchange attribute
    if (companySelect) {
        companySelect.removeAttribute('onchange');
        
        // Add our custom event listener for company changes
        companySelect.addEventListener('change', function() {
            const currentDate = dateInput.value;
            
            // Save the date in localStorage
            if (currentDate) {
                localStorage.setItem('lastTransactionDate', currentDate);
            }
            
            // Always include the current date parameter with company changes
            const url = new URL(filterForm.action, window.location.origin);
            url.searchParams.set('company_id', this.value);
            url.searchParams.set('date', currentDate);
            
            // Navigate to the URL
            window.location.href = url.toString();
        });
    }
    
    // Add event listener for date changes
    if (dateInput) {
        dateInput.addEventListener('change', function() {
            // Save to localStorage
            localStorage.setItem('lastTransactionDate', this.value);
            
            // If company is selected, include both parameters
            const companyId = companySelect.value;
            const url = new URL(filterForm.action, window.location.origin);
            
            if (companyId) {
                url.searchParams.set('company_id', companyId);
            }
            url.searchParams.set('date', this.value);
            
            // Navigate to the URL
            window.location.href = url.toString();
        });
    }
    
    // Check if we need to restore date from localStorage
    const storedDate = localStorage.getItem('lastTransactionDate');
    if (storedDate && dateInput && (!dateInput.value || dateInput.value !== storedDate)) {
        console.log('Restoring date from localStorage:', storedDate);
        dateInput.value = storedDate;
    }
});
// document.addEventListener('DOMContentLoaded', function() {
//     // Get the company select element
//     const companySelect = document.getElementById('company_id');
    
//     if (companySelect) {
//         companySelect.addEventListener('change', function() {
//             // Get the selected company ID
//             const selectedCompanyId = this.value;
//             const currentDate = document.getElementById('transaction_date').value;
            
//             // Redirect to the same page with the company parameter
//             window.location.href = `{{ route('daily-transactions.create') }}?company_id=${selectedCompanyId}&date=${currentDate}`;
//         });
//     }
// });

// Replace all existing form handlers with this consolidated version
document.addEventListener('DOMContentLoaded', function() {
    const transactionForm = document.getElementById('transactionForm');
    const updateExistingCheckbox = document.getElementById('update_existing_transaction');
    const companySelect = document.getElementById('company_id');
    const dateInput = document.getElementById('transaction_date');
    const formCompanyId = document.getElementById('form_company_id');
    const formTransactionDate = document.getElementById('form_transaction_date');
    
    // Set initial values
    formCompanyId.value = companySelect.value;
    formTransactionDate.value = dateInput.value;
    
    // Update hidden inputs when selections change
    companySelect.addEventListener('change', function() {
        formCompanyId.value = this.value;
    });
    
    dateInput.addEventListener('change', function() {
        formTransactionDate.value = this.value;
    });
    
    // Single unified form submission handler
    transactionForm.addEventListener('submit', function(e) {
        // Always prevent default initially
        e.preventDefault();
        
        const selectedCompanyId = companySelect.value;
        const selectedDate = dateInput.value;
        
        // Validation
        if (!selectedCompanyId) {
            alert('Ве молиме изберете компанија');
            return false;
        }
        
        // Set form hidden inputs
        formCompanyId.value = selectedCompanyId;
        formTransactionDate.value = selectedDate;
        
        // Disable submit button to prevent double submission
        const submitButton = transactionForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        
        // Check for online status
        if (!navigator.onLine) {
            const formData = new FormData(transactionForm);
            storeOfflineTransaction(formData);
            alert('Нема интернет конекција. Трансакцијата е зачувана локално.');
            submitButton.disabled = false;
            return false;
        }
        
        // Handle differently based on checkbox
        if (updateExistingCheckbox.checked) {
            // Prepare data for update endpoint
            const transactions = [];
            document.querySelectorAll('input[name^="transactions"]').forEach((input) => {
                const match = input.name.match(/transactions\[(\d+)\]\[(\w+)\]/);
                if (match) {
                    const rowIndex = match[1];
                    const field = match[2];
                    
                    // Initialize transaction object if not exists
                    transactions[rowIndex] = transactions[rowIndex] || {
                        bread_type_id: document.querySelector(`input[name="transactions[${rowIndex}][bread_type_id]"]`).value
                    };
                    
                    // Add field value
                    transactions[rowIndex][field] = parseInt(input.value) || 0;
                }
            });
            
            // Prepare payload - only include transactions with delivered > 0
            const payload = {
                company_id: selectedCompanyId,
                transaction_date: selectedDate,
                transactions: transactions.filter(t => t && t.delivered > 0)
            };
            
            // Send AJAX for update
            $.ajax({
                url: '/update-daily-transaction',
                method: 'POST',
                data: JSON.stringify(payload),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                success: function(response) {
                    if (response.success) {
                        alert('Трансакциите се успешно ажурирани.');
                        // Redirect with params
                        window.location.href = '/daily-transactions/create?' + 
                            'company_id=' + selectedCompanyId + 
                            '&date=' + selectedDate;
                    } else {
                        alert('Грешка при ажурирање.');
                        submitButton.disabled = false;
                    }
                },
                error: function(xhr) {
                    console.error('Error updating transactions:', xhr);
                    alert('Грешка при комуникација со серверот.');
                    submitButton.disabled = false;
                }
            });
        } else {
            // Regular form submission with FormData
            const formData = new FormData(transactionForm);
            
            $.ajax({
                url: transactionForm.action,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                success: function(response) {
                    if (response.success) {
                        alert('Успешно ажурирање на дневни трансакции.');
                        // Redirect with params
                        setTimeout(() => {
                            window.location.href = '/daily-transactions/create?' + 
                                'company_id=' + selectedCompanyId + 
                                '&date=' + selectedDate;
                        }, 1000);
                    } else {
                        alert('Грешка при зачувување.');
                        submitButton.disabled = false;
                    }
                },
                error: function(xhr) {
                    console.error('Error response:', xhr);
                    alert('Грешка при зачувување. Обидете се повторно.');
                    submitButton.disabled = false;
                }
            });
        }
        
        // Save to localStorage for later reference
        localStorage.setItem('selectedDate', selectedDate);
        localStorage.setItem('selectedCompany', selectedCompanyId);
        
        return false; // Prevent any default form submission
    });
});

// Helper functions for offline capabilities
function isOnline() {
    return navigator.onLine;
}

function storeOfflineTransaction(formData) {
    console.log('Storing transaction offline');
    const OFFLINE_STORAGE_KEY = 'offline_transactions';
    const transactions = JSON.parse(localStorage.getItem(OFFLINE_STORAGE_KEY) || '[]');
    transactions.push({
        data: Object.fromEntries(formData),
        timestamp: new Date().getTime()
    });
    localStorage.setItem(OFFLINE_STORAGE_KEY, JSON.stringify(transactions));
}

function syncOfflineTransactions() {
    console.log('Syncing offline transactions');
    const OFFLINE_STORAGE_KEY = 'offline_transactions';
    const transactions = JSON.parse(localStorage.getItem(OFFLINE_STORAGE_KEY) || '[]');
    if (transactions.length === 0) return;

    transactions.forEach((transaction, index) => {
        const formData = new FormData();
        Object.entries(transaction.data).forEach(([key, value]) => {
            formData.append(key, value);
        });

        $.ajax({
            url: '/daily-transactions/store',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function() {
                // Remove this transaction from storage on success
                transactions.splice(index, 1);
                localStorage.setItem(OFFLINE_STORAGE_KEY, JSON.stringify(transactions));
                alert('Офлајн трансакцијата е успешно синхронизирана.');
            },
            error: function(xhr) {
                console.error('Failed to sync transaction:', transaction, xhr);
            }
        });
    });
}

// Listen for online/offline events
window.addEventListener('online', function() {
    console.log('Online event triggered');
    syncOfflineTransactions();
});

window.addEventListener('offline', function() {
    console.log('Offline event triggered');
    alert('Нема интернет конекција. Трансакциите ќе бидат зачувани локално.');
});

// Check for offline transactions on page load
if (isOnline()) {
    syncOfflineTransactions();
}
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    const companySelect = document.getElementById('company_id');
    const dateInput = document.getElementById('transaction_date');
    const formCompanyId = document.getElementById('form_company_id');
    const formTransactionDate = document.getElementById('form_transaction_date');

    // Set initial values when page loads
    formCompanyId.value = companySelect.value;
    formTransactionDate.value = dateInput.value;

    // Update hidden input when company selection changes
    companySelect.addEventListener('change', function() {
        console.log('Company changed to:', this.value);
        formCompanyId.value = this.value;
    });

    // Update hidden input when date changes
    dateInput.addEventListener('change', function() {
        formTransactionDate.value = this.value;
    });
});


    // Keep the selected date when form is submitted
    document.getElementById('transactionForm').addEventListener('submit', function() {
        localStorage.setItem('selectedDate', dateInput.value);
        localStorage.setItem('selectedCompany', companySelect.value);
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to calculate the total for a row
    function calculateRowTotal(index) {
        const delivered = parseInt(document.querySelector(`input[name="transactions[${index}][delivered]"]`).value) || 0;
        const returned = parseInt(document.querySelector(`input[name="transactions[${index}][returned]"]`).value) || 0;
        const gratis = parseInt(document.querySelector(`input[name="transactions[${index}][gratis]"]`).value) || 0;
        
        const total = delivered - returned - gratis;
        document.querySelector(`#total-${index}`).textContent = total;
    }

    // Add event listeners to all input fields
    document.querySelectorAll('.delivered-input, .returned-input, .gratis-input').forEach(input => {
        const row = input.getAttribute('data-row');
        
        // Clear value on focus
        input.addEventListener('focus', function() {
            if (this.value === '0') {
                this.value = '';
            }
        });

        // Reset to 0 if empty on blur
        input.addEventListener('blur', function() {
            if (this.value === '') {
                this.value = '0';
                calculateRowTotal(row);
            }
        });

        // Calculate total on input
        input.addEventListener('input', () => calculateRowTotal(row));
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Get references to key elements
    const filterForm = document.getElementById('filterForm');
    const companySelect = document.getElementById('company_id');
    const dateInput = document.getElementById('transaction_date');
    
    if (!filterForm || !companySelect || !dateInput) return;
    
    // Store initial date value to localStorage
    if (dateInput.value) {
        localStorage.setItem('lastTransactionDate', dateInput.value);
    }
    
    // Override the inline onchange handler
    companySelect.removeAttribute('onchange');
    
    // Custom handler for company select change
    companySelect.addEventListener('change', function() {
        // Store current date
        const currentDate = dateInput.value || localStorage.getItem('lastTransactionDate');
        
        if (currentDate) {
            // Ensure there's a date parameter in the URL
            const url = new URL(filterForm.action, window.location.origin);
            url.searchParams.set('company_id', this.value);
            url.searchParams.set('date', currentDate);
            
            // Navigate directly instead of form submission
            window.location.href = url.toString();
        } else {
            // If no date, normal form submission
            filterForm.submit();
        }
    });
    
    // Handle date changes directly
    dateInput.addEventListener('change', function() {
        // Store the date in localStorage
        localStorage.setItem('lastTransactionDate', this.value);
        
        // If company is selected, submit the form to refresh data
        if (companySelect.value) {
            const url = new URL(filterForm.action, window.location.origin);
            url.searchParams.set('company_id', companySelect.value);
            url.searchParams.set('date', this.value);
            
            window.location.href = url.toString();
        }
    });
    
    // Check if we need to restore date from localStorage
    window.addEventListener('load', function() {
        const storedDate = localStorage.getItem('lastTransactionDate');
        
        // If URL has no date parameter but localStorage has a date
        if (storedDate && (!dateInput.value || dateInput.value === new Date().toISOString().slice(0, 10))) {
            console.log('Restoring date from localStorage:', storedDate);
            dateInput.value = storedDate;
            
            // If company is already selected, refresh with the stored date
            if (companySelect.value) {
                const url = new URL(filterForm.action, window.location.origin);
                url.searchParams.set('company_id', companySelect.value);
                url.searchParams.set('date', storedDate);
                
                // Only redirect if URL would change
                if (url.toString() !== window.location.href) {
                    window.location.href = url.toString();
                }
            }
        }
    });
});
document.addEventListener('DOMContentLoaded', function() {
    // Get references to key elements
    const filterForm = document.getElementById('filterForm');
    const companySelect = document.getElementById('company_id');
    const dateInput = document.getElementById('transaction_date');
    
    if (!filterForm || !companySelect || !dateInput) return;
    
    // Store initial date value to localStorage
    if (dateInput.value) {
        localStorage.setItem('lastTransactionDate', dateInput.value);
    }
    
    // Override the inline onchange handler
    companySelect.removeAttribute('onchange');
    
    // Custom handler for company select change
    companySelect.addEventListener('change', function() {
        // Store current date
        const currentDate = dateInput.value || localStorage.getItem('lastTransactionDate');
        
        if (currentDate) {
            // Ensure there's a date parameter in the URL
            const url = new URL(filterForm.action, window.location.origin);
            url.searchParams.set('company_id', this.value);
            url.searchParams.set('date', currentDate);
            
            // Navigate directly instead of form submission
            window.location.href = url.toString();
        } else {
            // If no date, normal form submission
            filterForm.submit();
        }
    });
    
    // Handle date changes directly
    dateInput.addEventListener('change', function() {
        // Store the date in localStorage
        localStorage.setItem('lastTransactionDate', this.value);
        
        // If company is selected, submit the form to refresh data
        if (companySelect.value) {
            const url = new URL(filterForm.action, window.location.origin);
            url.searchParams.set('company_id', companySelect.value);
            url.searchParams.set('date', this.value);
            
            window.location.href = url.toString();
        }
    });
    
    // Check if we need to restore date from localStorage
    window.addEventListener('load', function() {
        const storedDate = localStorage.getItem('lastTransactionDate');
        
        // If URL has no date parameter but localStorage has a date
        if (storedDate && (!dateInput.value || dateInput.value === new Date().toISOString().slice(0, 10))) {
            console.log('Restoring date from localStorage:', storedDate);
            dateInput.value = storedDate;
            
            // If company is already selected, refresh with the stored date
            if (companySelect.value) {
                const url = new URL(filterForm.action, window.location.origin);
                url.searchParams.set('company_id', companySelect.value);
                url.searchParams.set('date', storedDate);
                
                // Only redirect if URL would change
                if (url.toString() !== window.location.href) {
                    window.location.href = url.toString();
                }
            }
        }
    });
});
// document.addEventListener('DOMContentLoaded', function() {
//     const companySelect = document.getElementById('company_id');
//     const dateInput = document.getElementById('transaction_date');
//     const filterForm = document.getElementById('filterForm');
    
//     // Store initial date
//     const initialDate = dateInput ? dateInput.value : '';
//     localStorage.setItem('lastSelectedDate', initialDate);
    
//     // Handle date input change
//     if (dateInput) {
//         // Add event listener for date changes
//         dateInput.addEventListener('change', function() {
//             // Store the selected date
//             localStorage.setItem('lastSelectedDate', this.value);
            
//             // Add a hidden input to the form to ensure date is included
//             ensureDateParameter(this.value, filterForm);
            
//             // Submit the form to update with the new date
//             filterForm.submit();
//         });
//     }
    
//     // Ensure parameters are preserved during form submission
//     if (filterForm) {
//         filterForm.addEventListener('submit', function(e) {
//             // Don't prevent default - we want the normal form submission
            
//             // But ensure the date parameter is included
//             if (dateInput && dateInput.value) {
//                 ensureDateParameter(dateInput.value, this);
//             } else {
//                 // If no date in the input, try to get from localStorage
//                 const savedDate = localStorage.getItem('lastSelectedDate');
//                 if (savedDate) {
//                     ensureDateParameter(savedDate, this);
//                 }
//             }
            
//             // Log what we're submitting
//             console.log('Submitting form with date:', dateInput.value);
//         });
//     }
    
//     // Check if we need to restore date from localStorage
//     if (dateInput) {
//         const savedDate = localStorage.getItem('lastSelectedDate');
//         if (savedDate && savedDate !== dateInput.value) {
//             console.log('Restoring date from localStorage:', savedDate);
//             dateInput.value = savedDate;
            
//             // If company is selected, auto-submit to refresh with correct date
//             if (companySelect && companySelect.value) {
//                 // Short delay to allow other scripts to initialize
//                 setTimeout(function() {
//                     ensureDateParameter(savedDate, filterForm);
//                     filterForm.submit();
//                 }, 100);
//             }
//         }
//     }
    
//     // Helper function to ensure date parameter exists in the form
//     function ensureDateParameter(dateValue, form) {
//         // Check if we already have a hidden date field
//         let hiddenDate = form.querySelector('input[type="hidden"][name="date"]');
        
//         if (!hiddenDate) {
//             // Create a hidden field for the date
//             hiddenDate = document.createElement('input');
//             hiddenDate.type = 'hidden';
//             hiddenDate.name = 'date';
//             form.appendChild(hiddenDate);
//         }
        
//         // Set the value
//         hiddenDate.value = dateValue;
//         console.log('Added hidden date parameter:', dateValue);
//     }
// });
// document.addEventListener('DOMContentLoaded', function() {
//     const companySelect = document.getElementById('company_id');
//     const dateInput = document.getElementById('transaction_date');
//     const filterForm = document.getElementById('filterForm');

//     if (companySelect && filterForm) {
//         companySelect.addEventListener('change', function() {
//             // Make sure hidden fields are updated first
//             const formCompanyId = document.getElementById('form_company_id');
//             if (formCompanyId) {
//                 formCompanyId.value = this.value;
//             }
            
//             // Then submit the form
//             filterForm.submit();
//         });
//     }

//     if (dateInput && filterForm) {
//         dateInput.addEventListener('change', function() {
//             // Update hidden fields if present
//             const formTransactionDate = document.getElementById('form_transaction_date');
//             if (formTransactionDate) {
//                 formTransactionDate.value = this.value;
//             }
            
//             // Submit the form
//             filterForm.submit();
//         });
//     }
// });
</script>


<script src="{{ asset('js/transliteration.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dailyTransactionsButton = document.getElementById('dailyTransactionsButton');
    const oldBreadSalesButton = document.getElementById('oldBreadSalesButton');
    const dailyTransactionsSection = document.getElementById('dailyTransactionsSection');
    const oldBreadSalesSection = document.getElementById('oldBreadSalesSection');

    // Show daily transactions table by default
    dailyTransactionsSection.classList.remove('hidden');
    oldBreadSalesSection.classList.add('hidden');

    // Toggle to show daily transactions
    dailyTransactionsButton.addEventListener('click', function() {
        dailyTransactionsSection.classList.remove('hidden');
        oldBreadSalesSection.classList.add('hidden');
    });

    // Toggle to show old bread sales
    oldBreadSalesButton.addEventListener('click', function() {
        oldBreadSalesSection.classList.remove('hidden');
        dailyTransactionsSection.classList.add('hidden');
    });

    // Handle save button for old bread sales
    document.getElementById('saveOldBreadSales').addEventListener('click', function() {
        // Implement save logic for old bread sales here
        alert('Продажбата на стар леб е зачувана.');
    });
});
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const oldBreadInputs = document.querySelectorAll('input[name^="old_bread_sold"][name$="[sold]"]');

        oldBreadInputs.forEach(function (input) {
            input.addEventListener('focus', function () {
                if (input.value === '0') {
                    input.value = '';
                }
            });

            input.addEventListener('blur', function () {
                if (input.value === '') {
                    input.value = '';
                }
            });
        });
    });
</script>
<script>

    // Add this at the top of your script section to prevent infinite loops
let isProcessing = false;

// Create a debounced function to limit execution frequency
function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this, args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(function() {
            func.apply(context, args);
        }, wait);
    };
}

// Prevent duplicate event handlers
function safeAddEventListener(element, event, handler) {
    if (!element) return;
    
    // Remove any existing handlers to prevent duplicates
    element.removeEventListener(event, handler);
    
    // Add the new handler
    element.addEventListener(event, handler);
}

// Add a function to force cleanup if page seems stuck
setTimeout(function() {
    if (document.readyState !== 'complete') {
        console.warn('Page load timeout - refreshing Select2');
        try {
            $('#company_id').select2('destroy');
            $('#company_id').select2();
        } catch (e) {}
    }
}, 5000);

// // ddocument.addEventListener('DOMContentLoaded', function() {
//     const transactionForm = document.getElementById('transactionForm');
//     const updateExistingCheckbox = document.getElementById('update_existing_transaction');
    
//     // Skip if form doesn't exist on this page
//     if (!transactionForm) return;
    
//     // Set up form submission handler
//     transactionForm.addEventListener('submit', function(e) {
//         e.preventDefault();
        
//         const formData = new FormData(this);
//         const companyId = formData.get('company_id');
//         const transactionDate = formData.get('transaction_date');
        
//         // Validation
//         if (!companyId) {
//             alert('Ве молиме изберете компанија');
//             return false;
//         }
        
//         // Handle differently based on checkbox
//         if (updateExistingCheckbox && updateExistingCheckbox.checked) {
//             // Process for update existing transactions
//             const transactions = [];
            
//             // Find all transaction inputs and group by row
//             const inputElements = document.querySelectorAll('input[name^="transactions"]');
//             const transactionsByRow = {};
            
//             inputElements.forEach(input => {
//                 const match = input.name.match(/transactions\[(\d+)\]\[(\w+)\]/);
//                 if (match) {
//                     const rowIndex = match[1];
//                     const field = match[2];
                    
//                     if (!transactionsByRow[rowIndex]) {
//                         transactionsByRow[rowIndex] = {};
//                     }
                    
//                     // Parse number values, keep bread_type_id as string
//                     transactionsByRow[rowIndex][field] = field === 'bread_type_id' 
//                         ? input.value 
//                         : parseInt(input.value) || 0;
//                 }
//             });
            
//             // Convert to array and filter out zero deliveries
//             Object.values(transactionsByRow).forEach(transaction => {
//                 if (transaction.delivered > 0) {
//                     transactions.push(transaction);
//                 }
//             });
            
//             // Create request payload
//             const payload = {
//                 company_id: companyId,
//                 transaction_date: transactionDate,
//                 transactions: transactions
//             };
            
//             console.log('Update payload:', payload);
            
//             // Send using fetch API
//             fetch(window.location.origin + '/update-daily-transaction', {
//                 method: 'POST',
//                 headers: {
//                     'Content-Type': 'application/json',
//                     'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
//                 },
//                 body: JSON.stringify(payload)
//             })
//             .then(response => {
//                 if (!response.ok) {
//                     throw new Error('Network response was not ok: ' + response.status);
//                 }
//                 return response.json();
//             })
//             .then(data => {
//                 if (data.success) {
//                     alert('Трансакциите се успешно ажурирани.');
//                     window.location.href = '/daily-transactions/create?' + 
//                         'company_id=' + companyId + 
//                         '&date=' + transactionDate;
//                 } else {
//                     alert(data.message || 'Грешка при ажурирање.');
//                 }
//             })
//             .catch(error => {
//                 console.error('Error updating transactions:', error);
//                 alert('Грешка при комуникација со серверот.');
//             });
//         } else {
//             // Regular form submission for new transactions
//             fetch(transactionForm.action, {
//                 method: 'POST',
//                 body: formData,
//                 headers: {
//                     'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
//                 }
//             })
//             .then(response => {
//                 if (!response.ok) {
//                     throw new Error('Network response was not ok: ' + response.status);
//                 }
//                 return response.json();
//             })
//             .then(data => {
//                 if (data.success) {
//                     alert('Успешно ажурирање на дневни трансакции.');
//                     window.location.href = '/daily-transactions/create?' + 
//                         'company_id=' + companyId + 
//                         '&date=' + transactionDate;
//                 } else {
//                     alert(data.message || 'Грешка при зачувување.');
//                 }
//             })
//             .catch(error => {
//                 console.error('Error saving transactions:', error);
//                 alert('Грешка при зачувување. Обидете се повторно.');
//             });
//         }
        
//         return false;
//     });
// });
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all number input fields
    const numberInputs = document.querySelectorAll('input[type="number"]');

    // Add mobile-friendly attributes to each input
    numberInputs.forEach(input => {
        input.addEventListener('focus', function() {
            // Ensure the numeric keypad is shown
            this.setAttribute('inputmode', 'numeric');
            this.setAttribute('pattern', '[0-9]*');
        });
    });
});

// Simple fix for toggling between daily transactions and old bread sales
document.addEventListener('DOMContentLoaded', function() {
    // Get references to buttons and sections
    const dailyTransactionsButton = document.getElementById('dailyTransactionsButton');
    const oldBreadSalesButton = document.getElementById('oldBreadSalesButton');
    const dailyTransactionsSection = document.getElementById('dailyTransactionsSection');
    const oldBreadSalesSection = document.getElementById('oldBreadSalesSection');
    
    // Store current company ID when switching to old bread sales
    oldBreadSalesButton.addEventListener('click', function() {
        // Show old bread section, hide daily transactions
        oldBreadSalesSection.classList.remove('hidden');
        dailyTransactionsSection.classList.add('hidden');
        
        // Update button styles
        oldBreadSalesButton.classList.remove('bg-gray-500');
        oldBreadSalesButton.classList.add('bg-blue-500');
        dailyTransactionsButton.classList.remove('bg-blue-500');
        dailyTransactionsButton.classList.add('bg-gray-500');
        
        // Save company selection and clear it
        const companySelect = document.getElementById('company_id');
        if (companySelect) {
            // Save current value
            localStorage.setItem('saved_company_id', companySelect.value);
            
            // Clear the company selection
            if (window.jQuery && $('#company_id').select2) {
                try {
                    $('#company_id').val('').trigger('change');
                } catch (e) {
                    companySelect.value = '';
                }
            } else {
                companySelect.value = '';
            }
        }
    });
    
    // Restore company ID when switching back to daily transactions
    dailyTransactionsButton.addEventListener('click', function() {
        // Show daily transactions, hide old bread
        dailyTransactionsSection.classList.remove('hidden');
        oldBreadSalesSection.classList.add('hidden');
        
        // Update button styles
        dailyTransactionsButton.classList.remove('bg-gray-500');
        dailyTransactionsButton.classList.add('bg-blue-500');
        oldBreadSalesButton.classList.remove('bg-blue-500');
        oldBreadSalesButton.classList.add('bg-gray-500');
        
        // Restore company selection
        const companySelect = document.getElementById('company_id');
        if (companySelect) {
            const savedCompanyId = localStorage.getItem('saved_company_id');
            if (savedCompanyId) {
                if (window.jQuery && $('#company_id').select2) {
                    try {
                        $('#company_id').val(savedCompanyId).trigger('change');
                    } catch (e) {
                        companySelect.value = savedCompanyId;
                    }
                } else {
                    companySelect.value = savedCompanyId;
                }
            }
        }
    });
});
</script>



<style>
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



@endsection