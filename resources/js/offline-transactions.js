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
    window.addEventListener('online', () => {
        offlineIndicator.style.display = 'none';
        // Automatically sync when coming online
        syncOfflineTransactions();
    });
    
    window.addEventListener('offline', () => {
        offlineIndicator.style.display = 'block';
    });
    
    // Check initial status
    if (!navigator.onLine) {
        offlineIndicator.style.display = 'block';
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
                    // If online, submit directly
                    await submitTransaction(transaction);
                    showSuccessMessage('Трансакцијата е успешно зачувана');
                    transactionForm.reset();
                } else {
                    // If offline, store locally
                    await storeOfflineTransaction(transaction);
                    showOfflineMessage();
                    transactionForm.reset();
                }
            } catch (error) {
                console.error('Error:', error);
                showErrorMessage('Се случи грешка. Ве молиме обидете се повторно.');
            }
        });
    }
});

async function storeOfflineTransaction(transaction) {
    const offlineTransactions = JSON.parse(localStorage.getItem(OFFLINE_STORE) || '[]');
    offlineTransactions.push(transaction);
    localStorage.setItem(OFFLINE_STORE, JSON.stringify(offlineTransactions));
}

async function syncOfflineTransactions() {
    const offlineTransactions = JSON.parse(localStorage.getItem(OFFLINE_STORE) || '[]');
    
    if (offlineTransactions.length === 0) return;

    let successCount = 0;
    const failedTransactions = [];

    for (const transaction of offlineTransactions) {
        try {
            await submitTransaction(transaction);
            successCount++;
        } catch (error) {
            console.error('Sync error:', error);
            failedTransactions.push(transaction);
        }
    }

    // Update localStorage with only failed transactions
    localStorage.setItem(OFFLINE_STORE, JSON.stringify(failedTransactions));

    if (successCount > 0) {
        showSuccessMessage(`${successCount} трансакции успешно синхронизирани`);
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

function showOfflineMessage() {
    const message = document.createElement('div');
    message.className = 'fixed top-16 left-0 right-0 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 z-50';
    message.innerHTML = 'Трансакцијата е зачувана локално и ќе биде синхронизирана автоматски кога ќе бидете онлајн';
    document.body.appendChild(message);
    setTimeout(() => message.remove(), 3000);
}

function showSuccessMessage(text) {
    const message = document.createElement('div');
    message.className = 'fixed top-16 left-0 right-0 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 z-50';
    message.innerHTML = text;
    document.body.appendChild(message);
    setTimeout(() => message.remove(), 3000);
}

function showErrorMessage(text) {
    const message = document.createElement('div');
    message.className = 'fixed top-16 left-0 right-0 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 z-50';
    message.innerHTML = text;
    document.body.appendChild(message);
    setTimeout(() => message.remove(), 3000);
} 