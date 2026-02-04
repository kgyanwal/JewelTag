
<div class="lock-screen-container" x-data="{ 
    idleTime: 0,
    init() {
        setInterval(() => {
            this.idleTime++;
            if (this.idleTime >= 10) {
                window.location.reload();
            }
        }, 60000);
    },
    resetTimer() { this.idleTime = 0; }
}" @mousemove.window="resetTimer()" @keydown.window="resetTimer()">

    <style>
        .pin-input { font-size: 3rem !important; letter-spacing: 1.5rem; text-align: center; }
        .lock-card { background: white; border-radius: 2rem; padding: 3rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
    </style>

    <div class="flex items-center justify-center min-h-screen bg-gray-100">
        <div class="lock-card w-full max-w-md">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Security Check</h1>
                <p class="text-gray-500">Enter your 4-digit PIN to unlock the terminal</p>
            </div>

            <form wire:submit.prevent="verify" class="space-y-6">
                <input type="password" 
                       wire:model.live="pin_code" 
                       maxlength="4" 
                       class="pin-input block w-full rounded-xl border-gray-300 focus:ring-primary-600"
                       placeholder="****"
                       autofocus>

                <button type="submit" class="w-full py-4 bg-primary-600 text-white rounded-xl font-bold text-lg hover:bg-primary-700">
                    Verify & Unlock
                </button>

                <button type="button" wire:click="logoutMaster" class="w-full text-gray-400 hover:text-red-600 transition">
                    Logout Master Account
                </button>
            </form>
        </div>
    </div>
</div>
