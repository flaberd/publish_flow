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

            <h1 class="text-lg font-semibold">Privacy Policy</h1>
        </div>
    </header>

    <main class="flex-1 overflow-y-auto px-4 pb-10">
        <div class="space-y-5 text-sm leading-relaxed text-gray-300">
            <p class="text-xs text-gray-500">Last updated: {{ now()->format('F j, Y') }}</p>

            <p>
                This Privacy Policy explains what information {{ config('app.name') }} (the
                "Service") collects and how it is used.
            </p>

            <section class="space-y-2">
                <h2 class="text-base font-semibold text-white">1. Information we collect</h2>
                <p>
                    When you connect a social media account (such as Instagram or TikTok), the
                    Service stores the access token and basic profile information (username, display
                    name, avatar) returned by that platform, so it can publish content on your behalf.
                    We also store the media and captions you upload for scheduling and publishing.
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-base font-semibold text-white">2. How we use it</h2>
                <p>
                    Connected-account data is used solely to publish or schedule the content you
                    create to the accounts you explicitly enable. We do not sell your data or share
                    it with third parties other than the platform you are publishing to.
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-base font-semibold text-white">3. Data retention</h2>
                <p>
                    Access tokens and account data are retained until you disconnect the account from
                    the Accounts page, at which point they are deleted. Uploaded media is removed
                    after it has been published or after a limited retention period.
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-base font-semibold text-white">4. Third-party platforms</h2>
                <p>
                    Use of connected platforms (Instagram, TikTok, etc.) is also subject to their own
                    privacy policies and terms of service.
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-base font-semibold text-white">5. Your choices</h2>
                <p>
                    You can disconnect any connected account at any time, which revokes the Service's
                    access and deletes the stored token.
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-base font-semibold text-white">6. Contact</h2>
                <p>
                    Questions about this policy can be sent to the operator of this deployment of the
                    Service.
                </p>
            </section>
        </div>
    </main>
</div>
