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

            <h1 class="text-lg font-semibold">Terms of Service</h1>
        </div>
    </header>

    <main class="flex-1 overflow-y-auto px-4 pb-10">
        <div class="space-y-5 text-sm leading-relaxed text-gray-300">
            <p class="text-xs text-gray-500">Last updated: {{ now()->format('F j, Y') }}</p>

            <p>
                These Terms of Service govern access to and use of {{ config('app.name') }}
                (the "Service"). By using the Service, you agree to these terms.
            </p>

            <section class="space-y-2">
                <h2 class="text-base font-semibold text-white">1. The Service</h2>
                <p>
                    {{ config('app.name') }} lets an authenticated user connect their own social
                    media accounts and schedule or publish content to those accounts on their behalf.
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-base font-semibold text-white">2. Your account</h2>
                <p>
                    You are responsible for the content you upload and publish through the Service,
                    and for complying with the terms of service of each connected platform (e.g.
                    Instagram, TikTok).
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-base font-semibold text-white">3. Connected accounts</h2>
                <p>
                    When you connect a third-party account, the Service acts on your behalf only to
                    the extent of the permissions you grant during that platform's authorization flow.
                    You can revoke access at any time from the Accounts page or from the third-party
                    platform's own settings.
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-base font-semibold text-white">4. Termination</h2>
                <p>
                    You may stop using the Service and disconnect your accounts at any time. We may
                    suspend or terminate access if these terms are violated.
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-base font-semibold text-white">5. Disclaimer</h2>
                <p>
                    The Service is provided "as is", without warranties of any kind, to the extent
                    permitted by law.
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-base font-semibold text-white">6. Contact</h2>
                <p>
                    Questions about these terms can be sent to the operator of this deployment of the
                    Service.
                </p>
            </section>
        </div>
    </main>
</div>
