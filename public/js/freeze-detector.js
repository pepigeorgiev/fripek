
(function() {
    // Configuration
    const HEARTBEAT_INTERVAL = 2000; // 2 seconds
    const FREEZE_THRESHOLD = 6000;   // 6 seconds without heartbeat = frozen
    const SERVER_LOG_ENDPOINT = '/api/log/app-freeze'; // Endpoint to log freezes
    const MAX_TRACKED_ITEMS = 10; // Maximum number of actions/requests to store
    
    // State variables
    let lastHeartbeat = Date.now();
    let heartbeatInterval = null;
    let checkFrozenInterval = null;
    let recoveryButton = null;
    let lastUserActions = [];
    let lastNetworkRequests = [];
    
    // Initialize the detector
    function init() {
        console.log('Freeze detector initialized');
        
        // Check if coming back from a freeze
        checkPreviousFreeze();
        
        // Start the heartbeat system
        startHeartbeatSystem();
        
        // Add the always-available recovery button (initially hidden)
        addRecoveryButton();
        
        // Start monitoring user actions and network requests
        startMonitoring();
    }
    
    // Start monitoring user actions and network
    function startMonitoring() {
        // Track user interactions
        trackUserActions();
        
        // Monitor network requests
        monitorNetworkRequests();
    }
    
    // Track user interactions
    function trackUserActions() {
        const trackEvent = (eventType, target) => {
            try {
                // Get a readable identifier for the element
                let elementId = target.id || '';
                let elementClass = target.className || '';
                let elementTag = target.tagName || '';
                let elementText = target.innerText ? target.innerText.substring(0, 20) : '';
                
                lastUserActions.push({
                    timestamp: Date.now(),
                    type: eventType,
                    element: {
                        id: elementId,
                        class: elementClass,
                        tag: elementTag,
                        text: elementText
                    },
                    path: window.location.pathname
                });
                
                // Keep history limited
                if (lastUserActions.length > MAX_TRACKED_ITEMS) {
                    lastUserActions.shift();
                }
            } catch (error) {
                console.error('Error tracking user interaction:', error);
            }
        };
        
        // Add listeners for common interactions
        document.addEventListener('click', e => trackEvent('click', e.target), true);
        document.addEventListener('input', e => trackEvent('input', e.target), true);
        document.addEventListener('submit', e => trackEvent('submit', e.target), true);
    }
    
    // Monitor network requests
    function monitorNetworkRequests() {
        try {
            // Intercept fetch
            const originalFetch = window.fetch;
            window.fetch = function(url, options) {
                const startTime = Date.now();
                lastNetworkRequests.push({
                    timestamp: startTime,
                    type: 'fetch',
                    url: url.toString(),
                    method: options?.method || 'GET',
                    status: 'pending'
                });
                
                // Keep history limited
                if (lastNetworkRequests.length > MAX_TRACKED_ITEMS) {
                    lastNetworkRequests.shift();
                }
                
                const requestIndex = lastNetworkRequests.length - 1;
                
                return originalFetch.apply(this, arguments)
                    .then(response => {
                        if (requestIndex >= 0 && requestIndex < lastNetworkRequests.length) {
                            lastNetworkRequests[requestIndex].status = response.status;
                            lastNetworkRequests[requestIndex].duration = Date.now() - startTime;
                        }
                        return response;
                    })
                    .catch(error => {
                        if (requestIndex >= 0 && requestIndex < lastNetworkRequests.length) {
                            lastNetworkRequests[requestIndex].status = 'error';
                            lastNetworkRequests[requestIndex].error = error.message;
                            lastNetworkRequests[requestIndex].duration = Date.now() - startTime;
                        }
                        throw error;
                    });
            };
            
            // Intercept XMLHttpRequest
            const originalXhrOpen = XMLHttpRequest.prototype.open;
            const originalXhrSend = XMLHttpRequest.prototype.send;
            
            XMLHttpRequest.prototype.open = function(method, url) {
                this._freezeMonitorMethod = method;
                this._freezeMonitorUrl = url;
                this._freezeMonitorStartTime = Date.now();
                return originalXhrOpen.apply(this, arguments);
            };
            
            XMLHttpRequest.prototype.send = function() {
                lastNetworkRequests.push({
                    timestamp: this._freezeMonitorStartTime || Date.now(),
                    type: 'xhr',
                    url: this._freezeMonitorUrl,
                    method: this._freezeMonitorMethod || 'unknown',
                    status: 'pending'
                });
                
                // Keep history limited
                if (lastNetworkRequests.length > MAX_TRACKED_ITEMS) {
                    lastNetworkRequests.shift();
                }
                
                const requestIndex = lastNetworkRequests.length - 1;
                
                this.addEventListener('loadend', () => {
                    if (requestIndex >= 0 && requestIndex < lastNetworkRequests.length) {
                        lastNetworkRequests[requestIndex].status = this.status;
                        lastNetworkRequests[requestIndex].duration = Date.now() - (this._freezeMonitorStartTime || Date.now());
                    }
                });
                
                return originalXhrSend.apply(this, arguments);
            };
        } catch (error) {
            console.error('Error setting up network monitoring:', error);
        }
    }
    
    // Start the heartbeat system
    function startHeartbeatSystem() {
        // Clear any existing intervals
        if (heartbeatInterval) clearInterval(heartbeatInterval);
        if (checkFrozenInterval) clearInterval(checkFrozenInterval);
        
        // Initial heartbeat
        lastHeartbeat = Date.now();
        updateHeartbeat();
        
        // Set up intervals on different timers
        heartbeatInterval = setInterval(updateHeartbeat, HEARTBEAT_INTERVAL);
        checkFrozenInterval = setInterval(checkIfFrozen, HEARTBEAT_INTERVAL);
    }
    
    // Update the heartbeat timestamp
    function updateHeartbeat() {
        lastHeartbeat = Date.now();
        try {
            localStorage.setItem('app_heartbeat', lastHeartbeat.toString());
        } catch (error) {
            console.error('Error updating heartbeat:', error);
        }
    }
    
    // Check if the app is frozen
    function checkIfFrozen() {
        try {
            const now = Date.now();
            const timeSinceHeartbeat = now - lastHeartbeat;
            
            // If it's been too long since the last heartbeat, the app is frozen
            if (timeSinceHeartbeat > FREEZE_THRESHOLD) {
                console.warn(`Freeze detected! No heartbeat for ${Math.round(timeSinceHeartbeat / 1000)} seconds`);
                
                // Collect data and log to server before showing UI
                const diagnosticData = collectDiagnosticData();
                logFreezeToServer(diagnosticData);
                
                // Mark app as frozen and show recovery UI
                markAppAsFrozen(diagnosticData);
                showRecoveryUI();
            }
        } catch (error) {
            console.error('Error checking for freeze:', error);
        }
    }
    
    // Collect diagnostic data
    function collectDiagnosticData() {
        try {
            const diagnosticData = {
                url: window.location.href,
                timestamp: Date.now(),
                userAgent: navigator.userAgent,
                lastUserActions: lastUserActions,
                lastNetworkRequests: lastNetworkRequests,
                screenWidth: window.innerWidth,
                screenHeight: window.innerHeight,
                devicePixelRatio: window.devicePixelRatio || 1
            };
            
            // Add memory info if available
            if (window.performance?.memory) {
                diagnosticData.memory = {
                    usedJSHeapSize: window.performance.memory.usedJSHeapSize,
                    totalJSHeapSize: window.performance.memory.totalJSHeapSize,
                    jsHeapSizeLimit: window.performance.memory.jsHeapSizeLimit
                };
            }
            
            return diagnosticData;
        } catch (error) {
            console.error('Error collecting diagnostic data:', error);
            return {
                error: 'Failed to collect diagnostic data',
                timestamp: Date.now(),
                url: window.location.href
            };
        }
    }
    
    // Log freeze to server
    function logFreezeToServer(diagnosticData) {
        try {
            // Use sendBeacon if available (works during unload)
            if (navigator.sendBeacon) {
                const blob = new Blob(
                    [JSON.stringify({
                        event: 'app_freeze',
                        data: diagnosticData
                    })], 
                    { type: 'application/json' }
                );
                navigator.sendBeacon(SERVER_LOG_ENDPOINT, blob);
                console.log('Freeze logged to server via sendBeacon');
            } else {
                // Fallback to fetch with keepalive
                fetch(SERVER_LOG_ENDPOINT, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        event: 'app_freeze',
                        data: diagnosticData
                    }),
                    keepalive: true
                })
                .then(() => console.log('Freeze logged to server via fetch'))
                .catch(err => console.error('Failed to log freeze to server:', err));
            }
        } catch (error) {
            console.error('Error logging freeze to server:', error);
        }
    }
    
    // Mark the app as frozen
    function markAppAsFrozen(diagnosticData) {
        try {
            localStorage.setItem('app_state', 'frozen');
            localStorage.setItem('app_frozen_at', Date.now().toString());
            localStorage.setItem('app_frozen_url', window.location.href);
            localStorage.setItem('app_freeze_diagnostic', JSON.stringify(diagnosticData || {}));
        } catch (error) {
            console.error('Error marking app as frozen:', error);
        }
    }
    
    // Check if the app was previously frozen
    function checkPreviousFreeze() {
        try {
            const appState = localStorage.getItem('app_state');
            
            if (appState === 'frozen') {
                const frozenAt = parseInt(localStorage.getItem('app_frozen_at') || '0');
                const timeSinceFrozen = Date.now() - frozenAt;
                const diagnosticData = JSON.parse(localStorage.getItem('app_freeze_diagnostic') || '{}');
                
                // Report recovery to server
                logRecoveryToServer(diagnosticData, timeSinceFrozen);
                
                // Only show recovery UI if it was frozen recently (within last 30 minutes)
                if (timeSinceFrozen < 30 * 60 * 1000) {
                    console.warn('App was previously frozen, showing recovery UI');
                    showRecoveryUI();
                } else {
                    // It's been a while, clear the frozen state
                    clearFrozenState();
                }
            }
        } catch (error) {
            console.error('Error checking previous freeze:', error);
        }
    }
    
    // Log recovery to server
    function logRecoveryToServer(diagnosticData, timeSinceFrozen) {
        try {
            fetch(SERVER_LOG_ENDPOINT, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    event: 'app_recovery',
                    data: {
                        ...diagnosticData,
                        recoveryTimestamp: Date.now(),
                        freezeDuration: timeSinceFrozen,
                        recoveryUrl: window.location.href
                    }
                })
            })
            .then(() => console.log('Recovery reported to server'))
            .catch(err => console.error('Failed to report recovery to server:', err));
        } catch (error) {
            console.error('Error reporting recovery to server:', error);
        }
    }
    
    // Clear the frozen state
    function clearFrozenState() {
        try {
            localStorage.removeItem('app_state');
            localStorage.removeItem('app_frozen_at');
            localStorage.removeItem('app_frozen_url');
            localStorage.removeItem('app_freeze_diagnostic');
        } catch (error) {
            console.error('Error clearing frozen state:', error);
        }
    }
    
    // Add a recovery button that's always accessible
    function addRecoveryButton() {
        try {
            // Create the button
            recoveryButton = document.createElement('button');
            recoveryButton.id = 'app-recovery-button';
            recoveryButton.innerHTML = '🔄';
            recoveryButton.title = 'Ресетирај ја апликацијата ако е замрзната';
            
            // Style the button
            recoveryButton.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                width: 50px;
                height: 50px;
                border-radius: 50%;
                background-color: rgba(255, 59, 48, 0.8);
                color: white;
                font-size: 20px;
                border: none;
                cursor: pointer;
                z-index: 2147483647;
                display: none;
                align-items: center;
                justify-content: center;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            `;
            
            // Add the button to the document
            document.body.appendChild(recoveryButton);
            
            // Add click event to reset the app
            recoveryButton.addEventListener('click', resetApp);
            
            // Make the button appear after 10 seconds (for manual recovery if needed)
            setTimeout(() => {
                if (recoveryButton) {
                    recoveryButton.style.display = 'flex';
                    
                    // Make it disappear again after 5 seconds
                    setTimeout(() => {
                        if (recoveryButton) {
                            recoveryButton.style.display = 'none';
                        }
                    }, 5000);
                }
            }, 10000);
        } catch (error) {
            console.error('Error adding recovery button:', error);
        }
    }
    
    // Show the recovery UI
    function showRecoveryUI() {
        try {
            // Make the recovery button visible
            if (recoveryButton) {
                recoveryButton.style.display = 'flex';
            }
            
            // Create full screen overlay
            const overlay = document.createElement('div');
            overlay.id = 'freeze-recovery-overlay';
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.9);
                z-index: 2147483646;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-family: Arial, sans-serif;
            `;
            
            // Create dialog
            const dialog = document.createElement('div');
            dialog.style.cssText = `
                background-color: #333;
                padding: 20px;
                border-radius: 8px;
                max-width: 80%;
                text-align: center;
            `;
            
            // Add content
            dialog.innerHTML = `
                <div style="font-size: 48px; margin-bottom: 20px;">❄️</div>
                <h2 style="margin: 0 0 15px 0; font-size: 22px;">Апликацијата е замрзната</h2>
                <p style="margin: 0 0 25px 0; font-size: 16px;">Апликацијата престана да одговара. Потребно е да ја ресетираме.</p>
                <button id="recovery-reset-button" style="background-color: #ff3b30; color: white; border: none; border-radius: 8px; padding: 12px 20px; width: 100%; font-size: 16px; font-weight: bold; cursor: pointer; margin-bottom: 10px;">Ресетирај ја апликацијата</button>
            `;
            
            // Add to document
            overlay.appendChild(dialog);
            document.body.appendChild(overlay);
            
            // Add reset event to button
            const resetButton = document.getElementById('recovery-reset-button');
            if (resetButton) {
                resetButton.addEventListener('click', resetApp);
            }
        } catch (error) {
            console.error('Error showing recovery UI:', error);
            
            // If we can't show the UI, at least make the recovery button visible
            if (recoveryButton) {
                recoveryButton.style.display = 'flex';
            }
        }
    }
    
    // Reset the app
    function resetApp() {
        try {
            console.log('Resetting app...');
            
            // Log reset to server
            logResetToServer();
            
            // Show a simple loading message
            const loader = document.createElement('div');
            loader.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.95);
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: Arial, sans-serif;
                z-index: 2147483647;
            `;
            
            loader.innerHTML = `
                <div style="text-align: center;">
                    <div style="width: 40px; height: 40px; border: 4px solid rgba(255, 255, 255, 0.3); border-radius: 50%; border-top: 4px solid white; margin: 0 auto 20px auto;"></div>
                    <div>Ресетирање...</div>
                </div>
            `;
            
            // Add animation keyframes
            const style = document.createElement('style');
            style.textContent = `
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                #freeze-recovery-overlay div div:first-child {
                    animation: spin 1s linear infinite;
                }
            `;
            
            document.head.appendChild(style);
            document.body.appendChild(loader);
            
            // Clear frozen state
            clearFrozenState();
            
            // Clear all intervals and timeouts
            clearAllTimers();
            
            // Reset the app
            setTimeout(() => {
                // Try a sequence of increasingly aggressive reset methods
                try {
                    // Method 1: Simple reload with cache busting
                    window.location.href = window.location.href.split('?')[0] + '?reset=' + Date.now();
                    
                    // Method 2: If method 1 fails, schedule a fallback
                    setTimeout(() => {
                        try {
                            // Redirect to the main dashboard with force reload flag
                            window.location.href = window.location.origin + '/daily-transactions/create?forceReset=1&t=' + Date.now();
                        } catch (e) {
                            console.error('Method 2 failed:', e);
                            
                            // Method 3: Last resort
                            setTimeout(() => {
                                window.location.reload(true);
                            }, 100);
                        }
                    }, 2000);
                } catch (error) {
                    console.error('Error during reset:', error);
                    window.location.reload(true);
                }
            }, 1000);
        } catch (error) {
            console.error('Error in resetApp:', error);
            
            // Last resort
            window.location.reload(true);
        }
    }
    
    // Log reset to server
    function logResetToServer() {
        try {
            if (navigator.sendBeacon) {
                const blob = new Blob(
                    [JSON.stringify({
                        event: 'app_manual_reset',
                        data: {
                            timestamp: Date.now(),
                            url: window.location.href,
                            userAgent: navigator.userAgent,
                            lastUserActions: lastUserActions,
                            lastNetworkRequests: lastNetworkRequests
                        }
                    })], 
                    { type: 'application/json' }
                );
                navigator.sendBeacon(SERVER_LOG_ENDPOINT, blob);
                console.log('Reset logged to server via beacon');
            } else {
                // Fallback to fetch with keepalive
                fetch(SERVER_LOG_ENDPOINT, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        event: 'app_manual_reset',
                        data: {
                            timestamp: Date.now(),
                            url: window.location.href,
                            userAgent: navigator.userAgent,
                            lastUserActions: lastUserActions,
                            lastNetworkRequests: lastNetworkRequests
                        }
                    }),
                    keepalive: true
                })
                .then(() => console.log('Reset logged to server via fetch'))
                .catch(err => console.error('Failed to log reset to server:', err));
            }
        } catch (error) {
            console.error('Error logging reset to server:', error);
        }
    }
    
    // Clear all timers
    function clearAllTimers() {
        // Clear our intervals
        if (heartbeatInterval) clearInterval(heartbeatInterval);
        if (checkFrozenInterval) clearInterval(checkFrozenInterval);
        
        // Try to clear all timers (might not work if the page is frozen)
        const highestId = setTimeout(() => {}, 0);
        for (let i = 1; i < highestId; i++) {
            clearTimeout(i);
            clearInterval(i);
        }
    }
    
    // Start when the page loads
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        // DOM already loaded, initialize immediately
        init();
    }
    
    // Also listen for visibilitychange to detect when the page is shown again
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') {
            // Page is now visible, check if we were frozen
            checkPreviousFreeze();
            
            // Restart heartbeat system
            startHeartbeatSystem();
        }
    });
    
    // Clean up when the page unloads
    window.addEventListener('beforeunload', function() {
        // Clear the frozen state if we're unloading normally
        clearFrozenState();
    });
})();