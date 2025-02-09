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

        <form id="transactionForm" action="{{ route('daily-transactions.store') }}" method="POST">
            @csrf
            <input type="hidden" name="company_id" id="form_company_id">
            <input type="hidden" name="transaction_date" id="form_transaction_date">
            
            <!-- Mobile-optimized table -->
            <div class="overflow-x-auto -mx-2 md:mx-0">
                <table class="w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-2 md:px-6 py-2 md:py-3 bg-gray-50 text-left text-xs md:text-sm font-bold text-gray-700 uppercase">
                                Тип
                            </th>
                            <th class="px-2 md:px-6 py-2 md:py-3 bg-gray-50 text-center text-xs md:text-sm font-bold text-gray-700 uppercase">
                                Исп
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
                                    min="0" value="0">
                            </td>
                            <td class="px-1 md:px-6 py-2 md:py-4">
                                <input type="number" 
                                    name="transactions[{{ $index }}][returned]" 
                                    class="returned-input block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-center text-base md:text-lg" 
                                    data-row="{{ $index }}"
                                    min="0" value="0">
                            </td>
                            <td class="px-1 md:px-6 py-2 md:py-4">
                                <input type="number" 
                                    name="transactions[{{ $index }}][gratis]" 
                                    class="gratis-input block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-center text-base md:text-lg" 
                                    data-row="{{ $index }}"
                                    min="0" value="0">
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

// Add offline storage handling
const OFFLINE_STORAGE_KEY = 'offline_transactions';

// Check if we're online
function isOnline() {
    return navigator.onLine;
}

// Store transaction offline
function storeOfflineTransaction(formData) {
    console.log('Storing transaction offline');
    const transactions = JSON.parse(localStorage.getItem(OFFLINE_STORAGE_KEY) || '[]');
    transactions.push({
        data: Object.fromEntries(formData),
        timestamp: new Date().getTime()
    });
    localStorage.setItem(OFFLINE_STORAGE_KEY, JSON.stringify(transactions));
}

// Sync offline transactions when back online
function syncOfflineTransactions() {
    console.log('Syncing offline transactions');
    const transactions = JSON.parse(localStorage.getItem(OFFLINE_STORAGE_KEY) || '[]');
    if (transactions.length === 0) return;

    transactions.forEach((transaction, index) => {
        const formData = new FormData();
        Object.entries(transaction.data).forEach(([key, value]) => {
            formData.append(key, value);
        });

        $.ajax({
            url: '{{ route("daily-transactions.store") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function() {
                transactions.splice(index, 1);
                localStorage.setItem(OFFLINE_STORAGE_KEY, JSON.stringify(transactions));
                alert('Офлајн трансакцијата е успешно синхронизирана.');
            },
            error: function() {
                console.error('Failed to sync transaction:', transaction);
            }
        });
    });
}

// Form submission handling
$('#transactionForm').on('submit', function(e) {
    e.preventDefault();
    console.log('Form submitted');
    
    const selectedCompanyId = $('#company_id').val();
    const selectedDate = $('#transaction_date').val();

    if (!selectedCompanyId) {
        alert('Ве молиме изберете компанија');
        return false;
    }

    const $form = $(this);
    const formData = new FormData(this);
    
    if (!isOnline()) {
        storeOfflineTransaction(formData);
        alert('Нема интернет конекција. Трансакцијата е зачувана локално.');
        return;
    }

    $form.find('button[type="submit"]').prop('disabled', true);

    $.ajax({
        url: $form.attr('action'),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            alert('Успешно ажурирање на дневни трансакции.');
            setTimeout(() => {
                window.location.href = '/daily-transactions/create?' + 
                    'company_id=' + selectedCompanyId + 
                    '&date=' + selectedDate;
            }, 1000);
        },
       
        complete: function() {
            $form.find('button[type="submit"]').prop('disabled', false);
        }
    });
});

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
$(document).ready(function() {
    console.log('Document ready');
    if (isOnline()) {
        syncOfflineTransactions();
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const companySelect = document.getElementById('company_id');
    const dateInput = document.getElementById('transaction_date');
    const formCompanyId = document.getElementById('form_company_id');
    const formTransactionDate = document.getElementById('form_transaction_date');

    // Set initial values
    formCompanyId.value = companySelect.value;
    formTransactionDate.value = dateInput.value;

    // Update hidden form fields when selections change
    companySelect.addEventListener('change', function() {
        formCompanyId.value = this.value;
    });

    dateInput.addEventListener('change', function() {
        formTransactionDate.value = this.value;
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



<script>
$(document).ready(function() {
    $('#company_id').select2({
        placeholder: 'Пребарувај компанија...',
        allowClear: true,
        width: '100%',
        minimumInputLength: 0,
        dropdownParent: $('body')
    });

    $('#transactionForm').on('submit', function(e) {
        e.preventDefault();
        
        const selectedCompanyId = $('#company_id').val();
        const selectedDate = $('#transaction_date').val();
        
        $('#form_company_id').val(selectedCompanyId);
        $('#form_transaction_date').val(selectedDate);

        if (!selectedCompanyId) {
            alert('Ве молиме изберете компанија');
            return false;
        }

        const $form = $(this);
        const formData = $form.serialize();
        const url = $form.attr('action');

        $form.find('button[type="submit"]').prop('disabled', true);

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                alert('Успешно ажурирање на дневни трансакции.');
                window.location.href = '/daily-transactions/create?' + 
                    'company_id=' + selectedCompanyId + 
                    '&date=' + selectedDate;
            },
            error: function(xhr) {
                alert('Грешка при зачувување. Обидете се повторно.');
            },
            complete: function() {
                $form.find('button[type="submit"]').prop('disabled', false);
            }
        });
    });
});

// Latin to Cyrillic mapping for Macedonian
const latinToCyrillic = {
    'a': 'а', 'b': 'б', 'v': 'в', 'g': 'г', 'd': 'д',
    'gj': 'ѓ', 'e': 'е', 'zh': 'ж', 'z': 'з', 'dz': 'ѕ',
    'i': 'и', 'j': 'ј', 'k': 'к', 'l': 'л', 'lj': 'љ',
    'm': 'м', 'n': 'н', 'nj': 'њ', 'o': 'о', 'p': 'п',
    'r': 'р', 's': 'с', 't': 'т', 'kj': 'ќ', 'u': 'у',
    'f': 'ф', 'h': 'х', 'c': 'ц', 'ch': 'ч', 'dzh': 'џ',
    'sh': 'ш'
};

document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('company_id');
    let searchBuffer = '';
    let searchTimeout;

    // Store original options for filtering
    const originalOptions = Array.from(select.options);

    // Function to convert Latin text to Cyrillic
    function convertLatinToCyrillic(text) {
        text = text.toLowerCase();
        let result = '';
        for (let i = 0; i < text.length; i++) {
            // Check for two-character combinations first
            let twoChar = text.slice(i, i + 2);
            if (latinToCyrillic[twoChar]) {
                result += latinToCyrillic[twoChar];
                i++; // Skip next character
            } else {
                // Check single characters
                let oneChar = text[i];
                result += latinToCyrillic[oneChar] || oneChar;
            }
        }
        return result;
    }

    // Function to normalize text for comparison
    function normalizeText(text) {
        // Convert to lowercase and handle both Latin and Cyrillic
        const latinVersion = text.toLowerCase();
        const cyrillicVersion = convertLatinToCyrillic(latinVersion);
        return [latinVersion, cyrillicVersion];
    }

    // Function to filter and display matching options
    function filterOptions(searchText) {
        const [latinSearch, cyrillicSearch] = normalizeText(searchText);
        
        // Clear current options
        select.options.length = 0;
        
        // Add default option
        select.add(new Option('Изберете компанија', ''));
        
        // Filter and add matching options
        originalOptions.forEach(option => {
            if (option.value === '') return; // Skip default option
            
            const [latinOption, cyrillicOption] = normalizeText(option.text);
            
            if (latinOption.includes(latinSearch) || 
                cyrillicOption.includes(cyrillicSearch) ||
                latinOption.includes(cyrillicSearch) ||
                cyrillicOption.includes(latinSearch)) {
                select.add(new Option(option.text, option.value, false, false));
            }
        });
    }

    // Handle keyboard input
    select.addEventListener('keydown', function(e) {
        // Only handle alphanumeric keys and space
        if (e.key.match(/^[a-zA-Z0-9\s]$/)) {
            e.preventDefault(); // Prevent default select behavior
            
            searchBuffer += e.key;
            
            // Clear previous timeout
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            
            // Filter options immediately
            filterOptions(searchBuffer);
            
            // Clear search buffer after 1 second of no input
            searchTimeout = setTimeout(() => {
                searchBuffer = '';
            }, 1000);
        } else if (e.key === 'Backspace') {
            e.preventDefault();
            searchBuffer = searchBuffer.slice(0, -1);
            filterOptions(searchBuffer);
        }
    });

    // Reset options when focus is lost
    select.addEventListener('blur', function() {
        setTimeout(() => {
            searchBuffer = '';
            select.options.length = 0;
            originalOptions.forEach(option => {
                select.add(new Option(option.text, option.value));
            });
        }, 200);
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const selectBox = document.getElementById("company_id");

    // Save original options to restore later
    const options = Array.from(selectBox.options);

    // Mapping of Latin to Cyrillic letters for Macedonian
    const translitMap = {
        'a': 'а', 'b': 'б', 'v': 'в', 'g': 'г', 'd': 'д', 'e': 'е', 'zh': 'ж', 'z': 'з', 
        'i': 'и', 'j': 'ј', 'k': 'к', 'l': 'л', 'm': 'м', 'n': 'н', 'o': 'о', 'p': 'п', 
        'r': 'р', 's': 'с', 't': 'т', 'u': 'у', 'f': 'ф', 'h': 'х', 'c': 'ц', 'ch': 'ч', 
        'sh': 'ш', 'dj': 'џ', 'gj': 'ѓ', 'kj': 'ќ'
    };

    // Function to convert Latin to Cyrillic
    function transliterate(input) {
        return input.toLowerCase().replace(/ch|sh|dj|gj|kj|zh|[a-z]/g, function(match) {
            return translitMap[match] || match;
        });
    }

    // Event listener to filter options when typing
    selectBox.addEventListener("input", function () {
        const query = transliterate(this.value); // Convert to Cyrillic
        selectBox.innerHTML = ""; // Clear existing options

        // Add "Select a company" option first
        const defaultOption = document.createElement("option");
        defaultOption.value = "";
        defaultOption.textContent = "Изберете компанија";
        selectBox.appendChild(defaultOption);

        options.forEach(option => {
            const companyName = option.textContent.toLowerCase();
            if (companyName.includes(query) || option.value === "") {
                selectBox.appendChild(option);
            }
        });
    });
});

</script>







@endsection