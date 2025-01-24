<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Navigation Links -->
                <!-- ... existing navigation links ... -->
            </div>

            <!-- Add this right after your main navigation links -->
            @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                <div class="flex items-center">
                    <button id="pwa-install" 
                            class="hidden px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition-colors duration-200 ease-in-out shadow-sm">
                        <i class="fas fa-download mr-2"></i> 
                        Инсталирај апликација
                    </button>
                </div>
            @endif

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ml-6">
                <!-- Add PWA Install Button -->
                @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                    <button id="pwa-install" 
                            class="hidden mr-4 px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition">
                        <i class="fas fa-download mr-2"></i> Инсталирај апликација
                    </button>
                @endif

                <x-dropdown align="right" width="48">
                    <!-- ... existing dropdown content ... -->
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-mr-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="...">
                    <!-- ... existing hamburger button ... -->
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <!-- Add PWA Install Button for mobile -->
        @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
            <div class="pt-2 pb-3 space-y-1">
                <button id="pwa-install-mobile" 
                        class="hidden w-full px-4 py-2 bg-blue-500 text-white text-left hover:bg-blue-600 transition">
                    <i class="fas fa-download mr-2"></i> Инсталирај апликација
                </button>
            </div>
        @endif

        <!-- ... existing responsive menu items ... -->
    </div>
</nav>

<!-- Add this right after your navigation, before the main content -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 my-4">
    @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
        <div id="pwa-banner" class="hidden bg-blue-100 border-l-4 border-blue-500 p-4 mb-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-mobile-alt text-blue-500"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        Инсталирајте ја апликацијата на вашиот телефон за полесен пристап
                    </p>
                </div>
                <div class="ml-auto">
                    <button id="pwa-install-banner" 
                            class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 transition-colors duration-200 ease-in-out">
                        Инсталирај
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Add this script to handle both desktop and mobile install buttons -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const installButton = document.getElementById('pwa-install');
    const installButtonMobile = document.getElementById('pwa-install-mobile');
    
    function showInstallButton() {
        if (installButton) installButton.classList.remove('hidden');
        if (installButtonMobile) installButtonMobile.classList.remove('hidden');
    }
    
    function hideInstallButton() {
        if (installButton) installButton.classList.add('hidden');
        if (installButtonMobile) installButtonMobile.classList.add('hidden');
    }

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        window.deferredPrompt = e;
        showInstallButton();
    });

    [installButton, installButtonMobile].forEach(button => {
        if (button) {
            button.addEventListener('click', async () => {
                if (window.deferredPrompt) {
                    window.deferredPrompt.prompt();
                    const { outcome } = await window.deferredPrompt.userChoice;
                    window.deferredPrompt = null;
                    hideInstallButton();
                }
            });
        }
    });
});
</script>

<!-- Add this in your navigation menu where appropriate -->
@if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
    <x-nav-link :href="route('install.show')" :active="request()->routeIs('install.show')">
        <i class="fas fa-mobile-alt mr-2"></i>
        {{ __('Инсталирај апликација') }}
    </x-nav-link>
@endif