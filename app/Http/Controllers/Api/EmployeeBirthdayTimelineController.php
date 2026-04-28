<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EmployeeBirthdayTimelineController extends Controller
{
    /**
     * Upcoming birthdays for active / on-leave employees (portal timeline).
     * Any authenticated dashboard user may view (internal directory).
     */
    public function index(Request $request)
    {
        $user = $request->user()->loadMissing('employee');
        $today = Carbon::today();

        $rows = Employee::query()
            ->whereIn('status', ['active', 'on_leave'])
            ->whereNotNull('date_of_birth')
            ->with(['department:id,name', 'designation:id,name'])
            ->get([
                'id',
                'user_id',
                'full_name',
                'profile_photo_path',
                'date_of_birth',
                'department_id',
                'designation_id',
                'status',
            ]);

        $out = [];
        foreach ($rows as $e) {
            $dob = Carbon::parse($e->date_of_birth)->startOfDay();
            $next = $this->nextBirthdayOnOrAfter($dob, $today);
            $daysUntil = (int) $today->diffInDays($next, true);

            if ($daysUntil > 90) {
                continue;
            }

            $turnsAge = $dob->diffInYears($next);
            $myId = $user->employee?->id;

            $out[] = [
                'employee_id' => $e->id,
                'full_name' => $e->full_name,
                'profile_photo_url' => $e->profilePhotoUrl(),
                'department_name' => $e->department?->name,
                'designation_name' => $e->designation?->name,
                'next_birthday_on' => $next->toDateString(),
                'days_until' => $daysUntil,
                'is_today' => $next->isSameDay($today),
                'turns_age' => $turnsAge,
                'is_me' => $myId !== null && (int) $e->id === (int) $myId,
            ];
        }

        usort($out, function (array $a, array $b) {
            if ($a['days_until'] === $b['days_until']) {
                return strcmp((string) $a['full_name'], (string) $b['full_name']);
            }

            return $a['days_until'] <=> $b['days_until'];
        });

        return response()->json([
            'data' => array_values($out),
        ]);
    }

    private function nextBirthdayOnOrAfter(Carbon $dob, Carbon $today): Carbon
    {
        $year = (int) $today->year;

        for ($i = 0; $i <= 1; $i++) {
            $y = $year + $i;
            try {
                $candidate = Carbon::createFromDate($y, (int) $dob->month, (int) $dob->day)->startOfDay();
            } catch (\Throwable) {
                $candidate = Carbon::createFromDate($y, (int) $dob->month, 1)->endOfMonth()->startOfDay();
            }
            if ($candidate->greaterThanOrEqualTo($today)) {
                return $candidate;
            }
        }

        return $today->copy()->addYear();
    }
}
