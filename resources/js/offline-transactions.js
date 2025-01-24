document.addEventListener('DOMContentLoaded', () => {
    const transactionForm = document.getElementById('transaction-form');

    if (transactionForm) {
        transactionForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(transactionForm);
            const transaction = {
                company_id: formData.get('company_id'),
                transaction_date: formData.get('transaction_date'),
                transactions: [] // Build your transactions array
            };

            try {
                if (navigator.onLine) {
                    // If online, submit normally
                    await submitTransaction(transaction);
                } else {
                    // If offline, store locally
                    await storeOfflineTransaction(transaction);
                    showOfflineNotification();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    }
});

function showOfflineNotification() {
    alert('Вие сте офлајн. Трансакцијата ќе биде зачувана и синхронизирана кога ќе бидете онлајн.');
} 