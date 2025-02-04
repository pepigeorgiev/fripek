// Store for offline transactions
const OFFLINE_STORE = 'offlineTransactions';

document.addEventListener('DOMContentLoaded', () => {
    const transactionForm = document.getElementById('transaction-form');

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
                // Store transaction when offline
                const offlineTransactions = JSON.parse(localStorage.getItem('offlineTransactions') || '[]');
                offlineTransactions.push(transaction);
                localStorage.setItem('offlineTransactions', JSON.stringify(offlineTransactions));
                
                // Show offline save message
                const message = document.createElement('div');
                message.className = 'fixed bottom-4 right-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded shadow-lg z-50';
                message.textContent = 'Трансакцијата ќе биде зачувана кога ќе бидете онлајн';
                document.body.appendChild(message);
                setTimeout(() => message.remove(), 3000);
                
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
                    // Show success message
                    const message = document.createElement('div');
                    message.className = 'fixed bottom-4 right-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-lg z-50';
                    message.textContent = 'Трансакцијата е успешно зачувана';
                    document.body.appendChild(message);
                    setTimeout(() => message.remove(), 3000);
                    
                    // Clear form
                    transactionForm.reset();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    }
});

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