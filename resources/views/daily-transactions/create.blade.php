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

<!-- Remove container padding for mobile -->
<div class="mx-auto md:p-0">
    <h1 class="text-xl md:text-2xl font-bold mb-2 md:mb-4 px-2 md:px-0">Дневни Трансакции</h1>
    
    <div class="bg-white p-2 md:p-6 rounded shadow">
        <!-- Mobile-friendly grid -->
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
// ddocument.addEventListener('DOMContentLoaded', function() {
    const transactionForm = document.getElementById('transactionForm');
    const updateExistingCheckbox = document.getElementById('update_existing_transaction');
    
    // Skip if form doesn't exist on this page
    if (!transactionForm) return;
    
    // Set up form submission handler
    transactionForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const companyId = formData.get('company_id');
        const transactionDate = formData.get('transaction_date');
        
        // Validation
        if (!companyId) {
            alert('Ве молиме изберете компанија');
            return false;
        }
        
        // Handle differently based on checkbox
        if (updateExistingCheckbox && updateExistingCheckbox.checked) {
            // Process for update existing transactions
            const transactions = [];
            
            // Find all transaction inputs and group by row
            const inputElements = document.querySelectorAll('input[name^="transactions"]');
            const transactionsByRow = {};
            
            inputElements.forEach(input => {
                const match = input.name.match(/transactions\[(\d+)\]\[(\w+)\]/);
                if (match) {
                    const rowIndex = match[1];
                    const field = match[2];
                    
                    if (!transactionsByRow[rowIndex]) {
                        transactionsByRow[rowIndex] = {};
                    }
                    
                    // Parse number values, keep bread_type_id as string
                    transactionsByRow[rowIndex][field] = field === 'bread_type_id' 
                        ? input.value 
                        : parseInt(input.value) || 0;
                }
            });
            
            // Convert to array and filter out zero deliveries
            Object.values(transactionsByRow).forEach(transaction => {
                if (transaction.delivered > 0) {
                    transactions.push(transaction);
                }
            });
            
            // Create request payload
            const payload = {
                company_id: companyId,
                transaction_date: transactionDate,
                transactions: transactions
            };
            
            console.log('Update payload:', payload);
            
            // Send using fetch API
            fetch(window.location.origin + '/update-daily-transaction', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Трансакциите се успешно ажурирани.');
                    window.location.href = '/daily-transactions/create?' + 
                        'company_id=' + companyId + 
                        '&date=' + transactionDate;
                } else {
                    alert(data.message || 'Грешка при ажурирање.');
                }
            })
            .catch(error => {
                console.error('Error updating transactions:', error);
                alert('Грешка при комуникација со серверот.');
            });
        } else {
            // Regular form submission for new transactions
            fetch(transactionForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Успешно ажурирање на дневни трансакции.');
                    window.location.href = '/daily-transactions/create?' + 
                        'company_id=' + companyId + 
                        '&date=' + transactionDate;
                } else {
                    alert(data.message || 'Грешка при зачувување.');
                }
            })
            .catch(error => {
                console.error('Error saving transactions:', error);
                alert('Грешка при зачувување. Обидете се повторно.');
            });
        }
        
        return false;
    });
});
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