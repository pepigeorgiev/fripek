<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bread Delivery System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Add Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- PWA Meta Tags -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <link rel="manifest" href="{{ asset('manifest.json') }}" crossorigin="use-credentials">
    <link rel="apple-touch-icon" href="{{ asset('images/icon-192x192.png') }}">

    <!-- Debug meta tag to force HTTPS -->
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">

    <!-- Global Responsive Styles -->
    <style>
        /* Hide sidebar by default on mobile */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
        }

        /* Global responsive rules for all pages */
        .responsive-container {
            @apply w-full px-4 md:px-6 mx-auto;
        }

        /* Make all tables responsive */
        table {
            @apply w-full overflow-x-auto block md:table;
        }
        
        /* Make all form elements responsive */
        input:not([type="checkbox"]):not([type="radio"]),
        select,
        textarea {
            @apply w-full md:w-auto max-w-full;
        }

        /* Make all cards and grids responsive */
        .responsive-grid {
            @apply grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4;
        }

        /* Make all buttons responsive */
        button, .btn {
            @apply w-full md:w-auto;
        }

        /* Make all images responsive */
        img {
            @apply max-w-full h-auto;
        }

        /* Responsive text sizing */
        h1 { @apply text-2xl md:text-3xl lg:text-4xl; }
        h2 { @apply text-xl md:text-2xl lg:text-3xl; }
        h3 { @apply text-lg md:text-xl lg:text-2xl; }

        /* Responsive spacing */
        .responsive-spacing {
            @apply p-4 md:p-6 lg:p-8;
        }

        /* Responsive flex containers */
        .responsive-flex {
            @apply flex flex-col md:flex-row gap-4;
        }

        /* Responsive width classes */
        .responsive-full {
            @apply w-full md:w-auto;
        }

        /* Responsive padding for content sections */
        .content-section {
            @apply py-4 md:py-6 lg:py-8;
        }

        /* Responsive margins */
        .responsive-margin {
            @apply my-4 md:my-6 lg:my-8;
        }

        /* Make all forms responsive */
        form {
            @apply space-y-4 md:space-y-6;
        }

        /* Form group styling */
        .form-group {
            @apply flex flex-col md:flex-row md:items-center gap-2 md:gap-4;
        }

        /* Responsive cards */
        .card {
            @apply bg-white rounded-lg shadow p-4 md:p-6;
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- PWA Debug Script -->
    <script>
        window.addEventListener('load', function() {
            console.log('Checking PWA status...');
            
            // Check if running in standalone mode
            if (window.matchMedia('(display-mode: standalone)').matches) {
                console.log('App is running in standalone mode');
            }

            // Check service worker support
            if ('serviceWorker' in navigator) {
                console.log('Service Worker is supported');
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('ServiceWorker registration successful:', registration);
                    })
                    .catch(err => {
                        console.error('ServiceWorker registration failed:', err);
                    });
            } else {
                console.log('Service Worker is not supported');
            }

            // Log manifest loading
            fetch('{{ asset('manifest.json') }}')
                .then(response => response.json())
                .then(data => {
                    console.log('Manifest loaded successfully:', data);
                })
                .catch(error => {
                    console.error('Error loading manifest:', error);
                });
        });
    </script>

    <!-- PWA Install Script -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('ServiceWorker registration successful with scope: ', registration.scope);
                        
                        // Check if we can install
                        window.addEventListener('beforeinstallprompt', (e) => {
                            e.preventDefault();
                            console.log('Install prompt available');
                            
                            const installButton = document.getElementById('pwa-install');
                            if (installButton) {
                                installButton.style.display = 'block';
                                installButton.addEventListener('click', () => {
                                    console.log('Install button clicked');
                                    e.prompt();
                                });
                            }
                        });
                    })
                    .catch(error => {
                        console.error('ServiceWorker registration failed: ', error);
                    });
            });
        }
    </script>

    <link rel="manifest" href="/manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="application-name" content="Фри-пек">
    <meta name="apple-mobile-web-app-title" content="Фри-пек">
    <meta name="theme-color" content="#ffffff">
    <meta name="msapplication-navbutton-color" content="#ffffff">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="msapplication-starturl" content="/install-app">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="icon" type="image/png" sizes="192x192" href="/images/icon-192x192.png">
    <link rel="apple-touch-icon" type="image/png" sizes="192x192" href="/images/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/images/icon-512x512.png">
    <link rel="apple-touch-icon" type="image/png" sizes="512x512" href="/images/icon-512x512.png">

    <!-- iOS specific tags -->
    <link rel="apple-touch-icon" href="/images/icon-192x192.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="Фри-пек">

    <!-- iOS splash screen images -->
    <link rel="apple-touch-startup-image" href="/images/splash-640x1136.png" media="(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2)">
    <link rel="apple-touch-startup-image" href="/images/splash-750x1334.png" media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2)">
    <link rel="apple-touch-startup-image" href="/images/splash-1242x2208.png" media="(device-width: 414px) and (device-height: 736px) and (-webkit-device-pixel-ratio: 3)">

    <!-- PWA Default Route Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check if running as PWA
            if (window.matchMedia('(display-mode: standalone)').matches) {
                @auth
                    const userRole = '{{ auth()->user()->role }}';
                    
                    // Only redirect if user is a regular user
                    if (userRole === 'user') {
                        // Redirect to daily transactions if on home page
                        if (window.location.pathname === '/' || window.location.pathname === '/dashboard') {
                            window.location.href = 'https://fripekapp.mk/daily-transactions/create';
                        }

                        // Handle when app is resumed/focused
                        document.addEventListener('visibilitychange', function() {
                            if (document.visibilityState === 'visible' && 
                                (window.location.pathname === '/' || window.location.pathname === '/dashboard')) {
                                window.location.href = 'https://fripekapp.mk/daily-transactions/create';
                            }
                        });

                        // Handle back button
                        window.addEventListener('popstate', function() {
                            if (window.location.pathname === '/' || window.location.pathname === '/dashboard') {
                                window.location.href = 'https://fripekapp.mk/daily-transactions/create';
                            }
                        });
                    }
                @endauth
            }
        });

        // Handle app resume from background
        window.addEventListener('focus', function() {
            if (window.matchMedia('(display-mode: standalone)').matches) {
                if (window.location.pathname === '/' || window.location.pathname === '/dashboard') {
                    window.location.href = '{{ route('daily-transactions.create') }}';
                }
            }
        });
    </script>
</head>
<body class="bg-gray-100">
    <div x-data="{ isOpen: false }" class="flex min-h-screen">
        @auth
        <!-- Mobile menu button -->
        <button 
            @click="isOpen = !isOpen" 
            class="fixed top-4 left-4 z-50 md:hidden bg-white p-2 rounded-lg shadow-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <!-- Sidebar -->
        <aside class="sidebar fixed md:relative w-64 h-screen bg-white shadow-lg transition-transform duration-300 ease-in-out z-40"
               :class="{ 'active': isOpen }">
            <div class="p-4 border-b">
                <h1 class="text-xl font-bold">Фри-пек</h1>
                <p class="text-sm text-gray-600">{{ auth()->user()->name }}</p>
                <p class="text-xs text-gray-500">{{ ucfirst(auth()->user()->role) }}</p>
            </div>

            <nav class="mt-5">
            @if(auth()->user()->role === 'admin-user' || auth()->user()->role === 'admin-admin')
            <a href="{{ route('dashboard') }}" 
                       @click="isOpen = false"
                       class="block px-4 py-2 text-gray-600 hover:bg-gray-100 {{ request()->routeIs('dashboard') ? 'bg-gray-200' : '' }}">
                        Почетна
                    </a>

                    <a href="{{ route('companies.index') }}" 
                       @click="isOpen = false"
                       class="block px-4 py-2 text-gray-600 hover:bg-gray-100 {{ request()->routeIs('companies.*') ? 'bg-gray-200' : '' }}">
                        Компании
                    </a>

                    <a href="{{ route('bread-types.index') }}" 
                       @click="isOpen = false"
                       class="block px-4 py-2 text-gray-600 hover:bg-gray-100 {{ request()->routeIs('bread-types.*') ? 'bg-gray-200' : '' }}">
                        Управување со типови на леб
                    </a>

                    <a href="{{ route('invoice-companies.index') }}" 
                       @click="isOpen = false"
                       class="block px-4 py-2 text-gray-600 hover:bg-gray-100 {{ request()->routeIs('invoice-companies.*') ? 'bg-gray-200' : '' }}">
                        Компании за фактурирање
                    </a>
                @endif

                <a href="{{ route('daily-transactions.create') }}" 
                   @click="isOpen = false"
                   class="block px-4 py-2 text-gray-600 hover:bg-gray-100 {{ request()->routeIs('daily-transactions.*') ? 'bg-gray-200' : '' }}">
                    Дневни трансакции
                </a>
                
                <a href="{{ route('summary.index') }}" 
                   @click="isOpen = false"
                   class="block px-4 py-2 text-gray-600 hover:bg-gray-100 {{ request()->routeIs('summary.*') ? 'bg-gray-200' : '' }}">
                    Дневен извештај
                </a>

                @if(auth()->user()->role === 'admin-user' || auth()->user()->role === 'admin-admin')
                    <a href="{{ route('transaction.history') }}" 
                       @click="isOpen = false"
                       class="block px-4 py-2 text-gray-600 hover:bg-gray-100 {{ request()->routeIs('transaction.history') ? 'bg-gray-200' : '' }}">
                        Историја на промени
                    </a>
                @endif

                @if(auth()->user()->isSuperAdmin())
                    <a href="{{ route('users.manage') }}" 
                       @click="isOpen = false"
                       class="block px-4 py-2 text-gray-600 hover:bg-gray-100 {{ request()->routeIs('users.*') ? 'bg-gray-200' : '' }}">
                        Управување со корисници
                    </a>
                @endif

                @if(auth()->user()->role === 'admin-user' || auth()->user()->role === 'admin-admin')
    <a href="{{ route('install.show') }}" 
       @click="isOpen = false"
       class="block px-4 py-2 text-gray-600 hover:bg-gray-100 {{ request()->routeIs('install.show') ? 'bg-gray-200' : '' }}">
        <i class="fas fa-mobile-alt mr-2"></i>
        Инсталирај апликација
    </a>
@endif

                <div class="mt-5 pt-4 border-t">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 text-gray-600 hover:bg-gray-100 text-left">
                            Одјава
                        </button>
                    </form>
                </div>
            </nav>
        </aside>
        @endauth

       <!-- Main content area -->
<div class="flex-1">
    @auth
 
    <!-- Mobile overlay -->
    <div 
        x-show="isOpen" 
        @click="isOpen = false" 
        class="fixed inset-0 bg-black bg-opacity-50 z-30 md:hidden">
    </div>
    @endauth

    <!-- Content -->
    <div class="px-1 py-2 md:p-2 mt-6 md:mt-0  {{ !auth()->check() ? 'pt-2' : 'pt-12 md:pt-6' }}">
        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-2 py-2 md:px-4 md:py-3 rounded relative mb-2 md:mb-4">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-2 py-2 md:px-4 md:py-3 rounded relative mb-2 md:mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="md:space-y-4">
            @yield('content')
        </div>
    </div>
</div>

    </div>

    <!-- Replace the offline indicator div -->
    <div id="offline-indicator" class="hidden fixed top-0 left-0 right-0 bg-red-500 text-white p-4 text-center z-50">
        Вие сте офлајн
    </div>

    <!-- Replace the offline handling script -->
    <script>
        // Only show offline indicator, no popup on initial load
        function updateOnlineStatus() {
            const indicator = document.getElementById('offline-indicator');
            if (!navigator.onLine) {
                indicator.classList.remove('hidden');
            } else {
                indicator.classList.add('hidden');
                checkAndSyncTransactions();
            }
        }

        // Check and sync stored transactions when online
        async function checkAndSyncTransactions() {
            const transactions = JSON.parse(localStorage.getItem('offlineTransactions') || '[]');
            if (transactions.length > 0) {
                for (const transaction of transactions) {
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
                            // Remove synced transaction from storage
                            const remaining = transactions.filter(t => t.timestamp !== transaction.timestamp);
                            localStorage.setItem('offlineTransactions', JSON.stringify(remaining));
                            
                            // Show success message only if transaction was synced
                            const message = document.createElement('div');
                            message.className = 'fixed bottom-4 right-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-lg z-50';
                            message.textContent = 'Трансакцијата е успешно синхронизирана';
                            document.body.appendChild(message);
                            setTimeout(() => message.remove(), 3000);
                        }
                    } catch (error) {
                        console.error('Sync error:', error);
                    }
                }
            }
        }

        // Listen for online/offline events
        window.addEventListener('online', updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);
        
        // Initial check - only show indicator if offline
        document.addEventListener('DOMContentLoaded', updateOnlineStatus);
    </script>

    <!-- Register Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            // First unregister any existing service workers
            navigator.serviceWorker.getRegistrations()
                .then(async registrations => {
                    for (const registration of registrations) {
                        await registration.unregister();
                    }
                    console.log('Old service workers removed');
                    
                    // Register new service worker
                    return navigator.serviceWorker.register('/sw.js');
                })
                .then(() => {
                    console.log('New service worker registered');
                })
                .catch(error => {
                    console.error('Service Worker error:', error);
                });
        }

        // Listen for install prompt
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            // Store the event for later use
            window.deferredPrompt = e;
            console.log('Install prompt ready');
        });
    </script>

    <script>
        navigator.serviceWorker.getRegistrations().then(function(registrations) {
            console.log('Service Worker Registrations:', registrations);
        });
    </script>

    <div id="ios-install-instructions" style="display: none;">
        <p>To install this app on your iPhone:</p>
        <ol>
            <li>Tap the Share button <span style="font-size: 1.5em;">⎙</span></li>
            <li>Scroll down and tap "Add to Home Screen"</li>
            <li>Tap "Add" in the top right</li>
        </ol>
    </div>

    <script>
        // Show iOS instructions if needed
        if (navigator.userAgent.match(/iPhone|iPad|iPod/)) {
            document.getElementById('ios-install-instructions')?.style.display = 'block';
            
            // Log that we're on iOS
            fetch('/pwa-install-log', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    event: 'platform',
                    details: 'iOS Device Detected'
                })
            });
        }
    </script>

    <script>
        // Add this to your layout file temporarily
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(function(registrations) {
                for(let registration of registrations) {
                    registration.unregister();
                }
            });
        }
    </script>

    <!-- PWA and Navigation Script -->
    <script>
        // Debug information
        const debugInfo = {
            isPWA: window.matchMedia('(display-mode: standalone)').matches,
            currentPath: window.location.pathname,
            previousPath: document.referrer,
            @auth
            userRole: '{{ auth()->user()->role }}',
            @endauth
            timestamp: new Date().toISOString()
        };

        // Log every navigation
        console.log('=== Navigation Debug ===', debugInfo);

        // Track redirects
        let redirectCount = 0;
        const originalReplace = window.location.replace;
        const originalAssign = window.location.assign;

        window.location.replace = function() {
            console.log('Redirect detected (replace):', {
                from: window.location.pathname,
                to: arguments[0],
                stack: new Error().stack
            });
            return originalReplace.apply(this, arguments);
        };

        window.location.assign = function() {
            console.log('Redirect detected (assign):', {
                from: window.location.pathname,
                to: arguments[0],
                stack: new Error().stack
            });
            return originalAssign.apply(this, arguments);
        };

        // Track all click events
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            if (link) {
                console.log('Link clicked:', {
                    href: link.href,
                    text: link.textContent,
                    timestamp: new Date().toISOString()
                });
            }
        });

        // Track history changes
        window.addEventListener('popstate', function(e) {
            console.log('History changed:', {
                state: e.state,
                newPath: window.location.pathname,
                timestamp: new Date().toISOString()
            });
        });

        // Log when PWA is launched
        window.addEventListener('load', function() {
            console.log('App loaded:', {
                standalone: window.matchMedia('(display-mode: standalone)').matches,
                @auth
                userRole: '{{ auth()->user()->role }}',
                @endauth
                path: window.location.pathname
            });
        });

        // Track service worker events
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.ready.then(registration => {
                console.log('Service Worker ready:', {
                    scope: registration.scope
                });
            });

            navigator.serviceWorker.addEventListener('message', function(event) {
                console.log('Service Worker message:', event.data);
            });
        }
    </script>
</body>
</html>