<?php

namespace App\Livewire;

use App\Models\SocialAccount;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Accounts extends Component
{
    public function with(): array
    {
        $workspace = Auth::user()->currentWorkspace;

        $accounts = $workspace?->socialAccounts()->orderBy('provider')->get() ?? collect();

        return [
            'workspace' => $workspace,
            'accounts' => $accounts,
            'hasInstagram' => $accounts->contains('provider', SocialAccount::PROVIDER_INSTAGRAM),
            'hasTiktok' => $accounts->contains('provider', SocialAccount::PROVIDER_TIKTOK),
        ];
    }

    public function disconnect(int $accountId): void
    {
        Auth::user()->currentWorkspace?->socialAccounts()->where('id', $accountId)->delete();
    }
}
