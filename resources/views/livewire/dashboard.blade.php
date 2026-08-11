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
        @if (session('status'))
            <div class="mx-4 mt-4 rounded-lg bg-green-600/20 px-3 py-2 text-sm text-green-300">{{ session('status') }}</div>
        @endif

        <div class="grid grid-cols-7 bg-gradient-to-r from-purple-950/50 via-purple-950/20 to-black">
            @foreach ($weekDays as $weekDay)
                <div class="py-3 text-center text-sm text-gray-400">{{ $weekDay }}</div>
            @endforeach
        </div>

        <div class="grid grid-cols-7">
            @foreach ($weeks as $weekIndex => $week)
                @foreach ($week as $dayIndex => $day)
                    <div @class([
                        'flex flex-col items-center gap-1 border-b border-r border-white/5 py-3',
                        'bg-purple-950/10' => ! $day['inCurrentMonth'],
                    ])>
                        <span @class([
                            'flex h-9 w-9 items-center justify-center rounded-full text-base',
                            'text-gray-600' => ! $day['inCurrentMonth'],
                            'bg-blue-600 font-semibold text-white' => $day['isToday'],
                        ])>
                            {{ $day['date'] }}
                        </span>

                        @if ($day['providerCounts']->isNotEmpty())
                            <div class="flex flex-wrap items-center justify-center gap-1">
                                @foreach ($day['providerCounts'] as $provider => $count)
                                    <span class="relative" wire:key="day-{{ $weekIndex }}-{{ $dayIndex }}-{{ $provider }}">
                                        <x-social-icon :provider="$provider" class="h-3.5 w-3.5" />

                                        @if ($count > 1)
                                            <span class="absolute -right-1 -top-1 flex h-2.5 w-2.5 items-center justify-center rounded-full bg-blue-600 text-[7px] font-bold leading-none text-white">
                                                {{ $count }}
                                            </span>
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        @endif
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

        <livewire:publish-dialog />

        @for ($i = 3; $i <= 5; $i++)
            <button type="button" class="flex flex-col items-center gap-1 text-gray-500">
                <span class="h-6 w-6 rounded-md border border-current/40"></span>
                <span class="text-[11px]">Item {{ $i }}</span>
            </button>
        @endfor
    </nav>
</div>
