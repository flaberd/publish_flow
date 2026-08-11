<div class="flex min-h-screen flex-col bg-black text-white" style="padding-top: env(safe-area-inset-top); padding-bottom: env(safe-area-inset-bottom);">
    <header>
        <div class="flex items-center gap-3 px-4 pb-4 pt-3">
            <a
                href="{{ route('dashboard') }}"
                wire:navigate
                class="flex h-9 w-9 items-center justify-center rounded-full text-gray-400 hover:text-white"
                aria-label="Back"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>

            <h1 class="text-lg font-semibold">Accounts</h1>
        </div>
    </header>

    <main class="flex-1 overflow-y-auto px-4 pb-6">
        @if (session('status'))
            <div class="mb-4 rounded-lg bg-green-600/20 px-3 py-2 text-sm text-green-300">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-lg bg-red-600/20 px-3 py-2 text-sm text-red-300">{{ session('error') }}</div>
        @endif

        <div class="space-y-2">
            @forelse ($accounts as $account)
                <div class="flex items-center gap-3 rounded-xl bg-white/5 px-3 py-3" wire:key="account-{{ $account->id }}">
                    @if ($account->avatar_url)
                        <img src="{{ $account->avatar_url }}" alt="" class="h-10 w-10 shrink-0 rounded-full object-cover">
                    @else
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/10 text-sm font-semibold">
                            {{ strtoupper(substr($account->username ?? $account->provider, 0, 1)) }}
                        </div>
                    @endif

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-white">{{ $account->username ?? $account->name ?? 'Instagram account' }}</p>
                        <p class="text-xs text-gray-400">{{ ucfirst($account->provider) }}</p>
                    </div>

                    <button
                        type="button"
                        wire:click="disconnect({{ $account->id }})"
                        wire:confirm="Disconnect this account?"
                        class="shrink-0 rounded-lg px-2 py-1 text-xs font-medium text-red-400 hover:bg-white/5"
                    >
                        Disconnect
                    </button>
                </div>
            @empty
                <p class="py-8 text-center text-sm text-gray-500">No accounts connected to this workspace yet.</p>
            @endforelse
        </div>

        @unless ($hasInstagram)
            <a
                href="{{ route('instagram.connect') }}"
                class="mt-4 flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-purple-600 via-pink-600 to-orange-500 px-4 py-3 text-sm font-semibold text-white"
            >
                Connect Instagram
            </a>
        @endunless
    </main>
</div>
