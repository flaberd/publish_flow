<div class="contents">
    <button type="button" wire:click="openModal" class="flex flex-col items-center gap-1 text-gray-500 hover:text-gray-300">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-6 w-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        <span class="text-[11px]">Publish</span>
    </button>

    @if ($showModal)
        <div
            class="fixed inset-0 z-50 flex items-end justify-center bg-black/70 sm:items-center"
            wire:click.self="closeModal"
        >
            <div class="flex max-h-[90vh] w-full max-w-sm flex-col rounded-t-2xl bg-neutral-900 p-4 sm:rounded-2xl" role="dialog" aria-modal="true">
                <div class="mb-3 flex shrink-0 items-center justify-between">
                    <h2 class="text-base font-semibold text-white">New post</h2>

                    <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-white" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="-mx-1 space-y-5 overflow-y-auto px-1 pb-1">
                    {{-- Connected accounts --}}
                    <div>
                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500">Post to</p>

                        @if ($accounts->isEmpty())
                            <p class="text-sm text-gray-400">
                                No accounts connected yet.
                                <a href="{{ route('accounts') }}" wire:navigate class="text-blue-400 hover:underline">Connect one</a>.
                            </p>
                        @else
                            <div class="flex flex-wrap gap-3">
                                @foreach ($accounts as $account)
                                    @php $enabled = in_array($account->id, $enabledAccountIds, true); @endphp

                                    <button
                                        type="button"
                                        wire:click="toggleAccount({{ $account->id }})"
                                        wire:key="pub-account-{{ $account->id }}"
                                        class="flex flex-col items-center gap-1"
                                        aria-pressed="{{ $enabled ? 'true' : 'false' }}"
                                        title="{{ $account->username ?? ucfirst($account->provider) }}"
                                    >
                                        <span @class([
                                            'relative flex h-11 w-11 items-center justify-center rounded-full ring-2',
                                            'ring-blue-500' => $enabled,
                                            'opacity-40 ring-transparent' => ! $enabled,
                                        ])>
                                            @if ($account->avatar_url)
                                                <img src="{{ $account->avatar_url }}" alt="" class="h-full w-full rounded-full object-cover">
                                            @else
                                                <span class="flex h-full w-full items-center justify-center rounded-full bg-white/10 text-sm font-semibold">
                                                    {{ strtoupper(substr($account->username ?? $account->provider, 0, 1)) }}
                                                </span>
                                            @endif
                                        </span>

                                        <span class="max-w-[4.5rem] truncate text-[11px] text-gray-400">
                                            {{ $account->username ?? ucfirst($account->provider) }}
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        @error('enabledAccountIds')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Content upload --}}
                    <div>
                        @if ($media)
                            <div class="relative overflow-hidden rounded-xl bg-white/5">
                                <button
                                    type="button"
                                    wire:click="removeMedia"
                                    class="absolute right-2 top-2 z-10 flex h-7 w-7 items-center justify-center rounded-full bg-black/70 text-white"
                                    aria-label="Remove media"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </button>

                                @if (str_starts_with((string) $media->getMimeType(), 'video/'))
                                    <video src="{{ $media->temporaryUrl() }}" controls class="max-h-64 w-full object-contain"></video>
                                @else
                                    <img src="{{ $media->temporaryUrl() }}" alt="" class="max-h-64 w-full object-contain">
                                @endif
                            </div>
                        @else
                            <label class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-white/20 px-4 py-8 text-center hover:border-white/40">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-8 w-8 text-gray-500">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 7.5 12 3m0 0L7.5 7.5M12 3v13.5" />
                                </svg>
                                <span class="text-sm text-gray-400">Tap to upload a photo or video</span>
                                <input type="file" wire:model="media" accept="image/*,video/*" class="hidden">
                            </label>
                        @endif

                        <div wire:loading wire:target="media" class="mt-2 text-xs text-gray-500">Uploading…</div>

                        @error('media')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Caption --}}
                    <div>
                        <textarea
                            wire:model="caption"
                            rows="3"
                            placeholder="Write a caption…"
                            class="w-full rounded-xl border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-gray-500 focus:border-blue-500 focus:ring-blue-500"
                        ></textarea>

                        @error('caption')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Per-network settings --}}
                    @php $enabledAccounts = $accounts->whereIn('id', $enabledAccountIds); @endphp

                    @if ($enabledAccounts->isNotEmpty())
                        <div class="space-y-3">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Network settings</p>

                            @foreach ($enabledAccounts as $account)
                                <div class="rounded-xl bg-white/5 px-3 py-3" wire:key="pub-settings-{{ $account->id }}">
                                    <p class="mb-2 text-xs font-medium text-gray-300">
                                        {{ $account->username ?? ucfirst($account->provider) }}
                                        <span class="text-gray-500">· {{ ucfirst($account->provider) }}</span>
                                    </p>

                                    @if ($this->isInstagram($account))
                                        <select
                                            wire:model="settings.{{ $account->id }}.post_type"
                                            class="w-full rounded-lg border-white/10 bg-white/5 px-2 py-1.5 text-sm text-white focus:border-blue-500 focus:ring-blue-500"
                                        >
                                            <option value="feed">Feed post</option>
                                            <option value="reel">Reel</option>
                                            <option value="story">Story</option>
                                        </select>
                                    @else
                                        <p class="text-xs text-gray-500">No extra settings for this network.</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Date & time --}}
                    <div>
                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500">When</p>

                        <div class="flex gap-2">
                            <input
                                type="date"
                                wire:model.live="scheduledDate"
                                class="min-w-0 flex-1 rounded-lg border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-blue-500 focus:ring-blue-500"
                            >
                            <input
                                type="time"
                                wire:model.live="scheduledTime"
                                class="min-w-0 flex-1 rounded-lg border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                        @error('scheduledDate')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                        @error('scheduledTime')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <button
                    type="button"
                    wire:click="submit"
                    wire:loading.attr="disabled"
                    wire:target="submit,media"
                    class="mt-4 shrink-0 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
                >
                    {{ $publishLabel }}
                </button>
            </div>
        </div>
    @endif
</div>
