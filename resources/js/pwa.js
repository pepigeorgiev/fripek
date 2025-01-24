if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('ServiceWorker registration successful');
            })
            .catch(err => {
                console.log('ServiceWorker registration failed: ', err);
            });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('PWA Script Loaded');
    
    const installButton = document.getElementById('pwa-install');
    const installBannerButton = document.getElementById('pwa-install-banner');
    const pwaBanner = document.getElementById('pwa-banner');
    
    console.log('Install Button:', installButton);
    console.log('Install Banner Button:', installBannerButton);
    console.log('PWA Banner:', pwaBanner);
    
    let deferredPrompt;

    window.addEventListener('beforeinstallprompt', (e) => {
        console.log('beforeinstallprompt fired');
        e.preventDefault();
        deferredPrompt = e;
        
        // Show the install button and banner
        if (installButton) {
            console.log('Showing install button');
            installButton.classList.remove('hidden');
        }
        if (pwaBanner) {
            console.log('Showing PWA banner');
            pwaBanner.classList.remove('hidden');
        }
    });

    // Check if running in standalone mode
    if (window.matchMedia('(display-mode: standalone)').matches) {
        console.log('Running in standalone mode');
    } else {
        console.log('Not running in standalone mode');
    }

    // Log when the script is fully loaded
    console.log('PWA Script initialization complete');
}); 