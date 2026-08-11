<div class="flex min-h-screen flex-col bg-black text-white" style="padding-top: env(safe-area-inset-top); padding-bottom: env(safe-area-inset-bottom);">
    {{-- Header: workspace switcher, plus room for more calendar controls later --}}
    <header>
        <livewire:workspace-switcher />

        <div class="flex items-center justify-between px-4 pb-4 pt-3">
            <h1 class="text-lg font-semibold">{{ $monthLabel }}</h1>

            <button
                type="button"
                wire:click="logout"
                class="flex h-9 w-9 items-center justify-center rounded-full text-gray-400 hover:text-white"
                aria-label="Sign out"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 21H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3M16 17l5-5-5-5M21 12H9" />
                </svg>
            </button>
        </div>
    </header>

    <main class="flex-1 overflow-y-auto">
        <div class="grid grid-cols-7 bg-gradient-to-r from-purple-950/50 via-purple-950/20 to-black">
            @foreach ($weekDays as $weekDay)
                <div class="py-3 text-center text-sm text-gray-400">{{ $weekDay }}</div>
            @endforeach
        </div>

        <div class="grid grid-cols-7">
            @foreach ($weeks as $week)
                @foreach ($week as $day)
                    <div @class([
                        'flex items-center justify-center border-b border-r border-white/5 py-5',
                        'bg-purple-950/10' => ! $day['inCurrentMonth'],
                    ])>
                        <span @class([
                            'flex h-9 w-9 items-center justify-center rounded-full text-base',
                            'text-gray-600' => ! $day['inCurrentMonth'],
                            'bg-blue-600 font-semibold text-white' => $day['isToday'],
                        ])>
                            {{ $day['date'] }}
                        </span>
                    </div>
                @endforeach
            @endforeach
        </div>
    </main>

    {{-- Reserved space for the primary navigation menu (5 items) --}}
    <nav class="grid grid-cols-5 border-t border-white/10 px-2 py-3">
        <a href="{{ route('accounts') }}" wire:navigate class="flex flex-col items-center gap-1 text-gray-500 hover:text-gray-300">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-6 w-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m6.531 4.132a4.5 4.5 0 0 1-1.242-7.244l4.5-4.5a4.5 4.5 0 0 1 6.364 6.364l-1.757 1.757" />
            </svg>
            <span class="text-[11px]">Accounts</span>
        </a>

        @for ($i = 2; $i <= 5; $i++)
            <button type="button" class="flex flex-col items-center gap-1 text-gray-500">
                <span class="h-6 w-6 rounded-md border border-current/40"></span>
                <span class="text-[11px]">Item {{ $i }}</span>
            </button>
        @endfor
    </nav>
</div>
