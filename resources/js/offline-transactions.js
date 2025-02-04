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

    // Check connection status
    function updateOnlineStatus() {
        if (navigator.onLine) {
            offlineIndicator.style.display = 'none';
            syncOfflineTransactions();
        } else {
            offlineIndicator.style.display = 'block';
        }
    }

    // Monitor online/offline status
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    
    // Initial check
    updateOnlineStatus();

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

            if (!navigator.onLine) {
                // Store offline transaction
                const offlineTransactions = JSON.parse(localStorage.getItem('offlineTransactions') || '[]');
                offlineTransactions.push(transaction);
                localStorage.setItem('offlineTransactions', JSON.stringify(offlineTransactions));
                
                // Show offline save message
                const message = document.createElement('div');
                message.className = 'fixed bottom-0 left-0 right-0 bg-yellow-100 border-t-4 border-yellow-500 text-yellow-700 p-4';
                message.innerHTML = 'Трансакцијата ќе биде зачувана и синхронизирана кога ќе бидете онлајн.';
                document.body.appendChild(message);
                
                // Clear form
                transactionForm.reset();
                
                return;
            }

            try {
                const response = await fetch('/daily-transactions', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(transaction)
                });

                if (response.ok) {
                    transactionForm.reset();
                    showMessage('Трансакцијата е успешно зачувана', 'success');
                } else {
                    showMessage('Се случи грешка. Ве молиме обидете се повторно.', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Се случи грешка. Ве молиме обидете се повторно.', 'error');
            }
        });
    }
});

function showMessage(text, type) {
    const message = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-100' : 'bg-red-100';
    const borderColor = type === 'success' ? 'border-green-500' : 'border-red-500';
    const textColor = type === 'success' ? 'text-green-700' : 'text-red-700';
    
    message.className = `fixed bottom-0 left-0 right-0 ${bgColor} border-t-4 ${borderColor} ${textColor} p-4`;
    message.innerHTML = text;
    document.body.appendChild(message);
    setTimeout(() => message.remove(), 3000);
}

async function syncOfflineTransactions() {
    const offlineTransactions = JSON.parse(localStorage.getItem('offlineTransactions') || '[]');
    if (offlineTransactions.length === 0) return;

    for (const transaction of offlineTransactions) {
        try {
            const response = await fetch('/daily-transactions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(transaction)
            });

            if (response.ok) {
                // Remove synced transaction
                const remaining = offlineTransactions.filter(t => t.timestamp !== transaction.timestamp);
                localStorage.setItem('offlineTransactions', JSON.stringify(remaining));
                
                showMessage('Офлајн трансакциите се успешно синхронизирани', 'success');
            }
        } catch (error) {
            console.error('Sync error:', error);
        }
    }
} 