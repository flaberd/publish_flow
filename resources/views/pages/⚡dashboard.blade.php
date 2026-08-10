<?php

use Livewire\Component;

new class extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }
};
?>

<div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="rounded-lg border border-gray-200 bg-white p-8 shadow-sm text-center space-y-4">
        <h1 class="text-xl font-semibold text-gray-900">Livewire + Tailwind</h1>
        <p class="text-gray-600">Count: <span class="font-mono">{{ $count }}</span></p>
        <button
            type="button"
            wire:click="increment"
            class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500"
        >
            Increment
        </button>
    </div>
</div>
