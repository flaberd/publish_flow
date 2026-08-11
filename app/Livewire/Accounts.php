<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Accounts extends Component
{
    public function with(): array
    {
        $workspace = Auth::user()->currentWorkspace;

        return [
            'workspace' => $workspace,
            'accounts' => $workspace?->socialAccounts()->orderBy('provider')->get() ?? collect(),
        ];
    }

    public function disconnect(int $accountId): void
    {
        Auth::user()->currentWorkspace?->socialAccounts()->where('id', $accountId)->delete();
    }
}
