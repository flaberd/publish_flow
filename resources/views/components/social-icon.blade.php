@props(['provider'])

@switch($provider)
    @case('instagram')
        <span {{ $attributes->class(['flex shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-purple-600 via-pink-600 to-orange-500 text-white']) }}>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-2/3 w-2/3">
                <rect x="3" y="3" width="18" height="18" rx="5" />
                <circle cx="12" cy="12" r="3.5" />
                <circle cx="17.2" cy="6.8" r="1" fill="currentColor" stroke="none" />
            </svg>
        </span>
        @break

    @default
        <span {{ $attributes->class(['flex shrink-0 items-center justify-center rounded-full bg-white/20 text-[8px] font-semibold text-white']) }}>
            {{ strtoupper(substr($provider, 0, 1)) }}
        </span>
@endswitch
