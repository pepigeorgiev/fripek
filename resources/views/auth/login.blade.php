@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold mb-6 text-center">Login</h2>
        
        <form method="POST" action="{{ route('login') }}" id="loginForm">
            @csrf
            
            <div class="mb-4">
                <label for="email" class="block text-gray-700 text-sm font-bold mb-2">
                    Email
                </label>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('email') border-red-500 @enderror"
                    required autocomplete="email" autofocus>
                @error('email')
                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mb-6">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">
                    Password
                </label>
                <input id="password" type="password" name="password"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('password') border-red-500 @enderror"
                    required autocomplete="current-password">
                @error('password')
                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="flex items-center justify-between">
                <button type="submit" id="loginButton"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Login
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle form submission
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            // Store email in sessionStorage in case we need to refresh due to CSRF
            const emailInput = document.getElementById('email');
            if (emailInput && emailInput.value) {
                sessionStorage.setItem('login_email', emailInput.value);
            }
        });
    }
    
    // Restore email from sessionStorage if available
    const emailInput = document.getElementById('email');
    const storedEmail = sessionStorage.getItem('login_email');
    
    if (emailInput && storedEmail) {
        emailInput.value = storedEmail;
        // Focus on password field instead
        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            passwordInput.focus();
        }
    }
    
    // Handle 419 errors specifically for the login page
    const originalFetch = window.fetch;
    window.fetch = async function(url, options) {
        try {
            const response = await originalFetch(url, options);
            
            // Check for 419 status (CSRF token expired)
            if (response.status === 419) {
                console.log('CSRF token expired during login, refreshing page...');
                
                // First try to get a fresh token
                try {
                    const tokenResponse = await originalFetch('/api/refresh-csrf', {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    const tokenData = await tokenResponse.json();
                    
                    if (tokenData.token) {
                        // Update CSRF token in the form
                        const tokenInput = document.querySelector('input[name="_token"]');
                        if (tokenInput) {
                            tokenInput.value = tokenData.token;
                            
                            // Resubmit the form automatically
                            const loginButton = document.getElementById('loginButton');
                            if (loginButton) {
                                loginButton.click();
                                return response; // Prevent page reload
                            }
                        }
                    }
                } catch (tokenError) {
                    console.error('Error refreshing token:', tokenError);
                }
                
                // If the above fails, reload the page
                alert('Сесијата е истечена. Страницата ќе се освежи автоматски.');
                window.location.reload();
                return new Response(JSON.stringify({ error: 'Session expired, refreshing page' }));
            }
            
            return response;
        } catch (error) {
            console.error('Fetch error during login:', error);
            throw error;
        }
    };
    
    // Also intercept XHR for older code
    const originalXhrOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function(method, url, async, user, password) {
        this.addEventListener('load', function() {
            if (this.status === 419) {
                console.log('CSRF token expired in login XHR, refreshing page...');
                alert('Сесијата е истечена. Страницата ќе се освежи автоматски.');
                window.location.reload();
            }
        });
        return originalXhrOpen.apply(this, arguments);
    };

    // Clear login email from storage after successful login or if leaving login page
    window.addEventListener('beforeunload', function() {
        // Only clear if we're on a successful login (navigating away from login page)
        if (document.querySelector('form[action*="login"]')) {
            sessionStorage.removeItem('login_email');
        }
    });
});
</script>
@endsection