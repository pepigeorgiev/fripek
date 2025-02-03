// Store for offline transactions
const OFFLINE_STORE = 'offlineTransactions';

document.addEventListener('DOMContentLoaded', () => {
    const transactionForm = document.getElementById('transaction-form');
    const offlineIndicator = document.createElement('div');
    
    // Setup offline indicator
    offlineIndicator.id = 'offline-indicator';
    offlineIndicator.style.cssText = 'display:none; position:fixed; top:0; left:0; right:0; background:#ff4444; color:white; text-align:center; padding:10px; z-index:9999;';
    offlineIndicator.textContent = 'Вие сте офлајн';
    document.body.prepend(offlineIndicator);

    // Monitor online/offline status
    window.addEventListener('online', handleOnlineStatus);
    window.addEventListener('offline', handleOfflineStatus);
    
    // Check initial status
    if (!navigator.onLine) {
        handleOfflineStatus();
    }

    if (transactionForm) {
        transactionForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(transactionForm);
            const transaction = {
                company_id: formData.get('company_id'),
                transaction_date: formData.get('transaction_date'),
                bread_type_id: formData.get('bread_type_id'),
                delivered: formData.get('delivered'),
                returned: formData.get('returned'),
                timestamp: new Date().toISOString()
            };

            try {
                if (navigator.onLine) {
                    await submitTransaction(transaction);
                } else {
                    await storeOfflineTransaction(transaction);
                    showOfflineNotification();
                }
            } catch (error) {
                console.error('Error:', error);
                showErrorNotification();
            }
        });
    }
});

function handleOfflineStatus() {
    document.getElementById('offline-indicator').style.display = 'block';
}

function handleOnlineStatus() {
    document.getElementById('offline-indicator').style.display = 'none';
    syncOfflineTransactions();
}

async function storeOfflineTransaction(transaction) {
    const offlineTransactions = JSON.parse(localStorage.getItem(OFFLINE_STORE) || '[]');
    offlineTransactions.push(transaction);
    localStorage.setItem(OFFLINE_STORE, JSON.stringify(offlineTransactions));
}

async function syncOfflineTransactions() {
    const offlineTransactions = JSON.parse(localStorage.getItem(OFFLINE_STORE) || '[]');
    
    if (offlineTransactions.length === 0) return;

    const successfulSyncs = [];
    
    for (const transaction of offlineTransactions) {
        try {
            await submitTransaction(transaction);
            successfulSyncs.push(transaction);
        } catch (error) {
            console.error('Sync error:', error);
        }
    }

    // Remove successfully synced transactions
    if (successfulSyncs.length > 0) {
        const remaining = offlineTransactions.filter(
            t => !successfulSyncs.find(s => s.timestamp === t.timestamp)
        );
        localStorage.setItem(OFFLINE_STORE, JSON.stringify(remaining));
        
        showSuccessNotification(successfulSyncs.length);
    }
}

async function submitTransaction(transaction) {
    const response = await fetch('/daily-transactions', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(transaction)
    });

    if (!response.ok) {
        throw new Error('Network response was not ok');
    }

    return response.json();
}

function showOfflineNotification() {
    const notification = document.createElement('div');
    notification.className = 'notification offline';
    notification.innerHTML = `
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4">
            <p>Вие сте офлајн. Трансакцијата ќе биде зачувана и синхронизирана кога ќе бидете онлајн.</p>
        </div>
    `;
    document.querySelector('.container').prepend(notification);
    setTimeout(() => notification.remove(), 5000);
}

function showSuccessNotification(count) {
    const notification = document.createElement('div');
    notification.className = 'notification success';
    notification.innerHTML = `
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
            <p>Успешно синхронизирани ${count} трансакции.</p>
        </div>
    `;
    document.querySelector('.container').prepend(notification);
    setTimeout(() => notification.remove(), 5000);
}

function showErrorNotification() {
    const notification = document.createElement('div');
    notification.className = 'notification error';
    notification.innerHTML = `
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
            <p>Се случи грешка. Ве молиме обидете се повторно.</p>
        </div>
    `;
    document.querySelector('.container').prepend(notification);
    setTimeout(() => notification.remove(), 5000);
} 