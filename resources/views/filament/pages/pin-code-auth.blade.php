<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Security Check | Diamond Square</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body, html {
            height: 100%;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            overflow: hidden;
        }
        
        .lock-screen-container {
        margin-top: -80px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
            position: relative;
        }
        
        .lock-screen-container::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 70%);
            border-radius: 50%;
        }
        
        .lock-screen-container::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -15%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 70%);
            border-radius: 50%;
        }
        
        .lock-screen-card {
            background: white;
            border-radius: 24px;
            padding: 3rem 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            position: relative;
            z-index: 10;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        @media (max-width: 640px) {
            .lock-screen-card {
                padding: 2.5rem 2rem;
                margin: 0 1rem;
            }
        }
        
        .lock-icon {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
        }
        
        .lock-icon svg {
            width: 32px;
            height: 32px;
        }
        
        .title {
            font-size: 1.875rem;
            font-weight: 700;
            color: #1f2937;
            text-align: center;
            margin-bottom: 0.5rem;
            letter-spacing: -0.025em;
        }
        
        .subtitle {
            color: #6b7280;
            text-align: center;
            margin-bottom: 2rem;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        .pin-input-container {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .pin-input {
            width: 100%;
            padding: 1rem 1.25rem;
            font-size: 1.5rem !important;
            letter-spacing: 0.5rem;
            text-align: center;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            transition: all 0.2s;
            background: #f9fafb;
            font-weight: 600;
            color: #1f2937;
        }
        
        .pin-input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .pin-input::placeholder {
            color: #9ca3af;
            letter-spacing: normal;
            font-size: 0.875rem;
        }
        
        .verify-button {
            width: 100%;
            padding: 0.875rem 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 0.5rem;
        }
        
        .verify-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .verify-button:active {
            transform: translateY(0);
        }
        
        .logout-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: #ef4444;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s;
            cursor: pointer;
        }
        
        .logout-link:hover {
            color: #dc2626;
            text-decoration: underline;
        }
        
        .error-message {
            color: #ef4444;
            font-size: 0.875rem;
            text-align: center;
            margin-top: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.5rem;
            background: #fef2f2;
            border-radius: 8px;
            border: 1px solid #fecaca;
        }
        
        .user-info {
            text-align: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .user-avatar {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.125rem;
            margin: 0 auto 0.75rem;
        }
        
        .user-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }
        
        .user-role {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .loading-spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @livewireStyles
</head>
<body>
    <div class="lock-screen-container">
        <div class="lock-screen-card">
            <div class="user-info">
                <div class="user-avatar">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="user-name">{{ auth()->user()->name }}</div>
                <div class="user-role">POS Station</div>
            </div>
            
            <div class="lock-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            
            <h1 class="title">Security Check</h1>
            <p class="subtitle">Please enter your 4-digit POS PIN to unlock the station.</p>
            
            <form wire:submit.prevent="verify">
                <div class="pin-input-container">
                    <input 
                        type="password" 
                        wire:model="pin_code"
                        id="pin_code"
                        class="pin-input"
                        placeholder="Enter 4-digit PIN"
                        maxlength="4"
                        autofocus
                        autocomplete="off"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        wire:loading.attr="disabled"
                    />
                </div>
                
                @if(session()->has('filament.notifications'))
                    @php
                        $notification = collect(session('filament.notifications'))->first();
                    @endphp
                    @if($notification && $notification['status'] === 'danger')
                        <div class="error-message">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            {{ $notification['title'] }}
                        </div>
                    @endif
                @endif
                
                <button type="submit" class="verify-button" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="verify">Verify & Unlock</span>
                    <span wire:loading wire:target="verify">
                        <svg class="h-5 w-5 text-white mx-auto loading-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                </button>
                
                <a href="{{ filament()->getLogoutUrl() }}" class="logout-link">
                    Log out of session
                </a>
            </form>
        </div>
    </div>
    
    <script src="//unpkg.com/alpinejs" defer></script>
    @livewireScripts
    
    <script>
        // Auto-focus the PIN input
        document.addEventListener('DOMContentLoaded', function() {
            const pinInput = document.getElementById('pin_code');
            if (pinInput) {
                pinInput.focus();
                
                // Auto-submit after 4 digits
                pinInput.addEventListener('input', function(e) {
                    if (e.target.value.length === 4) {
                        // Small delay to ensure the value is captured
                        setTimeout(() => {
                            Livewire.dispatch('verify');
                        }, 100);
                    }
                });
                
                // Only allow numeric input
                pinInput.addEventListener('keydown', function(e) {
                    // Allow: backspace, delete, tab, escape, enter
                    if ([46, 8, 9, 27, 13].includes(e.keyCode) ||
                        // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                        (e.keyCode === 65 && e.ctrlKey === true) ||
                        (e.keyCode === 67 && e.ctrlKey === true) ||
                        (e.keyCode === 86 && e.ctrlKey === true) ||
                        (e.keyCode === 88 && e.ctrlKey === true) ||
                        // Allow: home, end, left, right
                        (e.keyCode >= 35 && e.keyCode <= 39)) {
                        return;
                    }
                    
                    // Ensure it's a number and stop the keypress if not
                    if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                        e.preventDefault();
                    }
                });
                
                // Format as PIN (adds space after every digit)
                pinInput.addEventListener('keyup', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 4) value = value.substring(0, 4);
                    e.target.value = value;
                });
            }
        });
        
        // Clear error when user starts typing
        document.addEventListener('livewire:load', function() {
            Livewire.on('error-cleared', () => {
                const errorDiv = document.querySelector('.error-message');
                if (errorDiv) {
                    errorDiv.remove();
                }
            });
        });
    </script>
</body>
</html>