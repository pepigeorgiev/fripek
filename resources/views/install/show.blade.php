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
        let deferredPrompt = null;

        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('Install prompt detected');
            // Prevent Chrome 67 and earlier from automatically showing the prompt
            e.preventDefault();
            // Stash the event so it can be triggered later
            deferredPrompt = e;
            
            // Show the install button
            const installButton = document.getElementById('pwa-install');
            const statusDiv = document.getElementById('install-status');
            
            installButton.style.display = 'inline-block';
            statusDiv.textContent = 'Апликацијата е подготвена за инсталација';
            
            console.log('Install button should be visible now');
        });

        document.getElementById('pwa-install').addEventListener('click', async () => {
            console.log('Install button clicked');
            const statusDiv = document.getElementById('install-status');
            
            if (!deferredPrompt) {
                console.log('No installation prompt available');
                statusDiv.textContent = 'Инсталацијата не е достапна. Користете Chrome или Safari.';
                return;
            }

            try {
                // Show the install prompt
                await deferredPrompt.prompt();
                // Wait for the user to respond to the prompt
                const choiceResult = await deferredPrompt.userChoice;
                
                if (choiceResult.outcome === 'accepted') {
                    statusDiv.textContent = 'Апликацијата се инсталира...';
                } else {
                    statusDiv.textContent = 'Инсталацијата е откажана';
                }
                
                // Clear the deferredPrompt so it can be garbage collected
                deferredPrompt = null;
            } catch (error) {
                console.error('Installation error:', error);
                statusDiv.textContent = 'Грешка при инсталација. Обидете се повторно.';
            }
        });

        // Check if already installed
        if (window.matchMedia('(display-mode: standalone)').matches) {
            const statusDiv = document.getElementById('install-status');
            const installButton = document.getElementById('pwa-install');
            
            statusDiv.textContent = 'Апликацијата е веќе инсталирана';
            installButton.style.display = 'none';
        }
    </script>
@endsection 