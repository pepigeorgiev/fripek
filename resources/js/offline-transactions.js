// Store for offline transactions
const OFFLINE_STORAGE_KEY = 'offline_transactions';

function isOnline() {
    return navigator.onLine;
}

function storeOfflineTransaction(formData) {
    const transactions = JSON.parse(localStorage.getItem(OFFLINE_STORAGE_KEY) || '[]');
    transactions.push({
        data: Object.fromEntries(formData),
        timestamp: new Date().getTime()
    });
    localStorage.setItem(OFFLINE_STORAGE_KEY, JSON.stringify(transactions));
}

function syncOfflineTransactions() {
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

$(document).ready(function() {
    if (isOnline()) {
        syncOfflineTransactions();
    }
});

<div id="offline-indicator" class="hidden fixed top-0 left-0 right-0 bg-red-500 text-white p-4 text-center z-50">
    Вие сте офлајн
</div>

<script>
    function updateOnlineStatus() {
        const indicator = document.getElementById('offline-indicator');
        if (!navigator.onLine) {
            indicator.classList.remove('hidden');
        } else {
            indicator.classList.add('hidden');
            syncOfflineTransactions();
        }
    }

    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    document.addEventListener('DOMContentLoaded', updateOnlineStatus);
</script> 