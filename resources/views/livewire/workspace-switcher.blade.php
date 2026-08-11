<div class="border-b border-white/10 px-4 py-3">
    <button
        type="button"
        wire:click="openModal"
        class="flex w-full items-center justify-between rounded-lg bg-white/5 px-3 py-2 text-sm font-medium text-white"
    >
        <span class="truncate">{{ $currentWorkspace?->name ?? 'Select workspace' }}</span>

        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-4 w-4 shrink-0 text-gray-400">
            <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
        </svg>
    </button>

    @if ($showModal)
        <div
            class="fixed inset-0 z-50 flex items-end justify-center bg-black/70 sm:items-center"
            wire:click.self="closeModal"
        >
            <div class="w-full max-w-sm rounded-t-2xl bg-neutral-900 p-4 sm:rounded-2xl" role="dialog" aria-modal="true">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-white">Workspaces</h2>

                    <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-white" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <ul class="mb-4 max-h-64 space-y-1 overflow-y-auto">
                    @foreach ($workspaces as $workspace)
                        <li wire:key="workspace-{{ $workspace->id }}">
                            @if ($editingWorkspaceId === $workspace->id)
                                <form wire:submit.prevent="renameWorkspace" class="flex items-center gap-1 px-1 py-1">
                                    <input
                                        type="text"
                                        wire:model="editingName"
                                        autofocus
                                        class="min-w-0 flex-1 rounded-lg border-white/10 bg-white/5 px-2 py-1.5 text-sm text-white focus:border-blue-500 focus:ring-blue-500"
                                    >

                                    <button type="submit" class="shrink-0 rounded-lg p-1.5 text-green-400 hover:bg-white/5" aria-label="Save">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                    </button>

                                    <button type="button" wire:click="cancelEditing" class="shrink-0 rounded-lg p-1.5 text-gray-400 hover:bg-white/5" aria-label="Cancel">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </form>
                                @error('editingName')
                                    <p class="mt-1 px-1 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            @else
                                <div class="flex items-center gap-1">
                                    <button
                                        type="button"
                                        wire:click="selectWorkspace({{ $workspace->id }})"
                                        @class([
                                            'flex min-w-0 flex-1 items-center justify-between rounded-lg px-3 py-2 text-left text-sm',
                                            'bg-blue-600 text-white' => $currentWorkspace?->is($workspace),
                                            'text-gray-300 hover:bg-white/5' => ! $currentWorkspace?->is($workspace),
                                        ])
                                    >
                                        <span class="truncate">{{ $workspace->name }}</span>

                                        @if ($currentWorkspace?->is($workspace))
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4 shrink-0">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                            </svg>
                                        @endif
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="startEditing({{ $workspace->id }})"
                                        class="shrink-0 rounded-lg p-2 text-gray-400 hover:bg-white/5 hover:text-white"
                                        aria-label="Rename {{ $workspace->name }}"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-4 w-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487a1.94 1.94 0 1 1 2.744 2.744L7.5 19.337 3.75 20.25l.913-3.75L16.862 4.487Z" />
                                        </svg>
                                    </button>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>

                <form wire:submit.prevent="createWorkspace" class="flex items-start gap-2">
                    <div class="flex-1">
                        <input
                            type="text"
                            wire:model="newWorkspaceName"
                            placeholder="New workspace name"
                            class="w-full rounded-lg border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-gray-500 focus:border-blue-500 focus:ring-blue-500"
                        >
                        @error('newWorkspaceName')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="shrink-0 rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-500"
                        wire:loading.attr="disabled"
                    >
                        Create
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
