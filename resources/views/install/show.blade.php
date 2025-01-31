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
        let deferredPrompt;

        window.addEventListener('load', async () => {
            const installButton = document.getElementById('pwa-install');
            const statusDiv = document.getElementById('install-status');

            // Hide install button initially
            installButton.style.display = 'none';

            // Check if app is already installed
            if (window.matchMedia('(display-mode: standalone)').matches) {
                statusDiv.textContent = 'App is already installed';
                return;
            }

            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                deferredPrompt = e;
                installButton.style.display = 'block';
                statusDiv.textContent = 'App is ready to install!';
            });

            installButton.addEventListener('click', async () => {
                if (!deferredPrompt) {
                    statusDiv.textContent = 'Installation not available. Please use Chrome or Edge browser.';
                    return;
                }

                try {
                    const result = await deferredPrompt.prompt();
                    const choice = await deferredPrompt.userChoice;
                    
                    if (choice.outcome === 'accepted') {
                        statusDiv.textContent = 'Installing...';
                    } else {
                        statusDiv.textContent = 'Installation cancelled';
                    }
                    
                    deferredPrompt = null;
                } catch (error) {
                    console.error('Installation error:', error);
                    statusDiv.textContent = 'Installation failed. Please try again.';
                }
            });
        });
    </script>
@endsection 