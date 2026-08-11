<?php

namespace App\Livewire;

use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class Dashboard extends Component
{
    public function with(): array
    {
        $today = Carbon::today();
        $cursor = $today->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $gridStart = $cursor->copy();
        $gridEnd = $cursor->copy()->addWeeks(6)->subDay();

        $workspace = Auth::user()->currentWorkspace;

        $postsByDay = ($workspace?->posts()
            ->with('socialAccounts')
            ->whereBetween('scheduled_at', [$gridStart, $gridEnd->copy()->endOfDay()])
            ->get() ?? collect())
            ->groupBy(fn (Post $post) => $post->scheduled_at->toDateString());

        $weeks = [];

        for ($week = 0; $week < 6; $week++) {
            $days = [];

            for ($day = 0; $day < 7; $day++) {
                $dayPosts = $postsByDay->get($cursor->toDateString(), collect());

                $days[] = [
                    'date' => $cursor->day,
                    'inCurrentMonth' => $cursor->month === $today->month,
                    'isToday' => $cursor->isSameDay($today),
                    'providerCounts' => $dayPosts->flatMap->socialAccounts->countBy('provider'),
                ];

                $cursor->addDay();
            }

            $weeks[] = $days;
        }

        return [
            'weekDays' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            'weeks' => $weeks,
            'monthLabel' => $today->format('F Y'),
        ];
    }

    #[On('post-published')]
    public function refreshCalendar(): void
    {
        // Re-rendering picks up the newly created post; no state to change here.
    }

    public function logout(): void
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $this->redirect(route('login'), navigate: false);
    }
}
