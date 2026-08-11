<div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="w-full max-w-sm rounded-lg border border-gray-200 bg-white p-8 shadow-sm space-y-6">
        <h1 class="text-xl font-semibold text-gray-900 text-center">Sign in</h1>

        <form wire:submit.prevent="authenticate" class="space-y-4">
            <div>
                <label for="login" class="block text-sm font-medium text-gray-700">Login</label>
                <input
                    id="login"
                    type="text"
                    wire:model="login"
                    autocomplete="username"
                    autofocus
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                @error('login')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input
                    id="password"
                    type="password"
                    wire:model="password"
                    autocomplete="current-password"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" wire:model="remember" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                Remember me
            </label>

            <button
                type="submit"
                class="w-full inline-flex justify-center items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                wire:loading.attr="disabled"
            >
                Sign in
            </button>
        </form>
    </div>
</div>
