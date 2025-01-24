@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="text-center">
                        <h3 class="text-lg font-medium mb-4">
                            Инсталирајте ја Фри-пек апликацијата на вашиот уред
                        </h3>
                        
                        <div class="space-y-4">
                            <p class="text-gray-600">
                                Со инсталирање на апликацијата ќе добиете:
                            </p>
                            
                            <ul class="list-disc list-inside text-left max-w-md mx-auto space-y-2">
                                <li>Побрз пристап до системот</li>
                                <li>Работа без интернет конекција</li>
                                <li>Полесно внесување на трансакции</li>
                                <li>Брз пристап до извештаи</li>
                            </ul>

                            <button id="pwa-install" 
                                    class="mt-6 px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors duration-200">
                                <i class="fas fa-download mr-2"></i>
                                Инсталирај апликација
                            </button>

                            <div id="install-status" class="mt-4 text-sm text-gray-600"></div>

                            <div id="not-available" class="hidden mt-6 text-gray-500">
                                Инсталацијата не е достапна во моментов. 
                                Проверете дали користите поддржан прелистувач (Chrome, Edge, Safari).
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const installButton = document.getElementById('pwa-install');
            const statusDiv = document.getElementById('install-status');
            let deferredPrompt;

            // Log initial state
            console.log('PWA Install Script Started');

            // Check if manifest icons exist
            fetch('/images/icon-192x192.png')
                .then(response => {
                    if (!response.ok) {
                        console.error('Icon 192x192 not found');
                        statusDiv.textContent = 'Missing required icons';
                    }
                })
                .catch(error => console.error('Icon check failed:', error));

            window.addEventListener('beforeinstallprompt', (e) => {
                console.log('Install prompt detected');
                
                e.preventDefault();
                deferredPrompt = e;
                installButton.classList.remove('hidden');
                statusDiv.textContent = 'Installation available';
            });

            installButton.addEventListener('click', async () => {
                console.log('Install button clicked');
                
                if (deferredPrompt) {
                    try {
                        console.log('Showing install prompt');
                        
                        deferredPrompt.prompt();
                        const { outcome } = await deferredPrompt.userChoice;
                        
                        console.log(`User response: ${outcome}`);
                        
                        if (outcome === 'accepted') {
                            statusDiv.textContent = 'Installation successful!';
                        } else {
                            statusDiv.textContent = 'Installation cancelled';
                        }
                        
                        deferredPrompt = null;
                    } catch (error) {
                        console.error('Installation error:', error);
                        statusDiv.textContent = `Installation error: ${error.message}`;
                    }
                } else {
                    console.log('No installation prompt available');
                    statusDiv.textContent = 'Installation not available. Make sure you\'re using a supported browser and have required icons.';
                }
            });

            // Check if already installed
            if (window.matchMedia('(display-mode: standalone)').matches) {
                console.log('App is already installed');
                statusDiv.textContent = 'App is already installed';
            }

            // Check service worker registration
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('ServiceWorker registration successful:', registration);
                    })
                    .catch(error => {
                        console.error('ServiceWorker registration failed:', error);
                    });
            }
        });
    </script>
@endsection 