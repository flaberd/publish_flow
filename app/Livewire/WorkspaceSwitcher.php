<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WorkspaceSwitcher extends Component
{
    public bool $showModal = false;

    public string $newWorkspaceName = '';

    public function mount(): void
    {
        $user = $this->user();

        if ($user->workspaces()->doesntExist()) {
            $workspace = $user->workspaces()->create(['name' => 'Workspace 1']);
            $user->update(['current_workspace_id' => $workspace->id]);
        } elseif (! $user->current_workspace_id) {
            $user->update(['current_workspace_id' => $user->workspaces()->first()->id]);
        }
    }

    public function with(): array
    {
        $user = $this->user();

        return [
            'workspaces' => $user->workspaces()->orderBy('name')->get(),
            'currentWorkspace' => $user->currentWorkspace,
        ];
    }

    public function openModal(): void
    {
        $this->newWorkspaceName = '';
        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function selectWorkspace(int $workspaceId): void
    {
        $workspace = $this->user()->workspaces()->findOrFail($workspaceId);

        $this->user()->update(['current_workspace_id' => $workspace->id]);
        $this->showModal = false;

        $this->dispatch('workspace-switched', workspaceId: $workspace->id);
    }

    public function createWorkspace(): void
    {
        $this->validate([
            'newWorkspaceName' => ['required', 'string', 'max:255'],
        ]);

        $workspace = $this->user()->workspaces()->create(['name' => $this->newWorkspaceName]);
        $this->user()->update(['current_workspace_id' => $workspace->id]);

        $this->showModal = false;

        $this->dispatch('workspace-switched', workspaceId: $workspace->id);
    }

    private function user(): User
    {
        return Auth::user();
    }
}
