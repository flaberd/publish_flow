<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public function with(): array
    {
        $today = Carbon::today();
        $cursor = $today->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);

        $weeks = [];

        for ($week = 0; $week < 6; $week++) {
            $days = [];

            for ($day = 0; $day < 7; $day++) {
                $days[] = [
                    'date' => $cursor->day,
                    'inCurrentMonth' => $cursor->month === $today->month,
                    'isToday' => $cursor->isSameDay($today),
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

    public function logout(): void
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $this->redirect(route('login'), navigate: false);
    }
}
