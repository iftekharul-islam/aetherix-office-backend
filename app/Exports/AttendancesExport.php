<?php

namespace App\Exports;

use App\Models\User;
use App\Models\MachineAttendance;
use App\Models\AttendanceNote;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AttendancesExport implements FromCollection, WithHeadings
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection(): Collection
    {
        $request = $this->request;

        // Build user query first with all filters (matching summary approach)
        $userQuery = User::nonAdmin()->with(['department.division', 'supervisor'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->input('search');
                $q->where(function ($q2) use ($search) {
                    $q2->where('name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%")
                        ->orWhere('employee_id', 'like', "%$search%");
                });
            })
            ->when($request->filled('user_id'), fn($q) => $q->where('id', $request->user_id))
            ->when(
                $request->filled('department_id'),
                fn($q) => $q->where('department_id', $request->department_id)
            )
            ->when(
                $request->filled('division_id'),
                fn($q) => $q->whereHas('department', fn($q2) => $q2->where('division_id', $request->division_id))
            );

        // Get users
        $users = $userQuery->get();

        // Determine date range
        $dateRange = [];
        if ($request->filled('from') && $request->filled('to')) {
            $from = Carbon::parse($request->from);
            $to = Carbon::parse($request->to);
            while ($from->lte($to)) {
                $dateRange[] = $from->format('Y-m-d');
                $from->addDay();
            }
        } elseif ($request->filled('date')) {
            $dateRange = [$request->date];
        }

        // Fetch attendances for these users and date range
        $attendanceQuery = MachineAttendance::whereIn('user_id', $users->pluck('id'))
            ->where('is_deleted', false)
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->type));

        if ($request->filled('from') && $request->filled('to')) {
            $from = $request->input('from') . ' 00:00:00';
            $to = $request->input('to') . ' 23:59:59';
            $attendanceQuery->whereBetween('datetime', [$from, $to]);
        } elseif ($request->filled('date')) {
            $attendanceQuery->whereDate('datetime', $request->date);
        }

        $attendances = $attendanceQuery->get();

        // If no date range specified, extract unique dates from attendances
        if (empty($dateRange)) {
            $dateRange = $attendances->map(function ($item) {
                return $item->datetime instanceof Carbon
                    ? $item->datetime->format('Y-m-d')
                    : date('Y-m-d', strtotime($item->datetime));
            })->unique()->sort()->values()->toArray();
        }

        // Fetch notes for users and date range
        $notesQuery = AttendanceNote::whereIn('user_id', $users->pluck('id'));
        if (!empty($dateRange)) {
            $notesQuery->whereIn('date', $dateRange);
        }
        $notes = $notesQuery->get()->keyBy(function ($item) {
            $dateString = Carbon::parse($item->date)->format('Y-m-d');
            return $item->user_id . '_' . $dateString;
        });

        // Group attendances by user_id and date
        $attendancesByUserDate = $attendances->groupBy(function ($item) {
            $dateString = $item->datetime instanceof Carbon
                ? $item->datetime->format('Y-m-d')
                : date('Y-m-d', strtotime($item->datetime));
            return $item->user_id . '_' . $dateString;
        });

        // Build final data structure
        $allRecords = collect();
        foreach ($users as $user) {
            foreach ($dateRange as $date) {
                $key = $user->id . '_' . $date;
                $dayAttendances = $attendancesByUserDate->get($key, collect());

                $firstCheckin = $dayAttendances->min('datetime');
                $lastCheckout = $dayAttendances->max('datetime');

                // Get note for this user + date
                $noteRecord = $notes->get($key);

                // Calculate status
                $status = $this->calculateStatus($firstCheckin, $date, $user);

                // Calculate actual worked hours
                $workedMinutes = $this->calculateWorkedMinutes($dayAttendances);
                $workedHours = floor($workedMinutes / 60);
                $workedMins = $workedMinutes % 60;

                // Calculate expected duty hours
                $expectedDutyHours = $user->department?->expected_duty_hours ?? 9;
                $expectedWholeHours = floor($expectedDutyHours);
                $expectedMins = round(($expectedDutyHours - $expectedWholeHours) * 60);

                // Calculate extra/less duty hours
                $expectedMinutes = $expectedDutyHours * 60;
                $diffMinutes = $workedMinutes - $expectedMinutes;
                $isNegative = $diffMinutes < 0;
                $absDiffMinutes = abs($diffMinutes);
                $diffHours = floor($absDiffMinutes / 60);
                $diffMins = $absDiffMinutes % 60;
                $sign = $isNegative ? '- ' : '';

                $allRecords->push([
                    'Date' => $date,
                    'Name' => $user->name,
                    'Email' => $user->email,
                    'Employee ID' => $user->employee_id ?? 'N/A',
                    'Department' => $user->department?->name ?? 'N/A',
                    'Division' => $user->department?->division?->name ?? 'N/A',
                    'First Check-in' => $firstCheckin ? ($firstCheckin instanceof Carbon ? $firstCheckin->format('h:i A') : date('h:i A', strtotime($firstCheckin))) : 'N/A',
                    'Last Checkout' => $lastCheckout ? ($lastCheckout instanceof Carbon ? $lastCheckout->format('h:i A') : date('h:i A', strtotime($lastCheckout))) : 'N/A',
                    'Status' => $status,
                    'Expected Duty Hours' => "{$expectedWholeHours}h {$expectedMins}m",
                    'Actual Worked Hours' => $workedMinutes > 0 ? "{$workedHours}h {$workedMins}m" : 'N/A',
                    'Extra/Less Duty Hours' => $workedMinutes > 0 ? "{$sign}{$diffHours}h {$diffMins}m" : 'N/A',
                    'Note' => $noteRecord?->note ?? 'N/A',
                ]);
            }
        }

        // Apply sorting
        if ($request->filled('sort_by') && $request->filled('sort_order')) {
            $sortOrder = $request->sort_order === 'asc' ? 'sortBy' : 'sortByDesc';
            $allRecords = $allRecords->$sortOrder($request->sort_by);
        } else {
            // Default: sort by date desc
            $allRecords = $allRecords->sortByDesc('Date');
        }

        return $allRecords->values();
    }

    /**
     * Calculate attendance status
     */
    private function calculateStatus($firstCheckin, $date, $user)
    {
        $officeStartTime = $user->department?->office_start_time;

        if (!$officeStartTime || !$firstCheckin) {
            return 'Absent';
        }

        list($startHour, $startMinute, $startSecond) = array_map('intval', explode(':', $officeStartTime));
        
        $checkinDate = $firstCheckin instanceof Carbon 
            ? $firstCheckin 
            : Carbon::parse($firstCheckin);

        $attendanceDate = Carbon::parse($date);
        $officeStartDate = $attendanceDate->copy()->setTime($startHour, $startMinute, $startSecond ?? 0);

        // Handle night shift (same as frontend logic)
        if ($startHour >= 18 && $checkinDate->hour < 12) {
            $officeStartDate->subDay();
        }

        // Calculate difference in minutes (SIGNED, matching frontend logic)
        // Frontend uses: differenceInMinutes(checkinDate, officeStartDate)
        // This means: checkinDate - officeStartDate
        $diffMinutes = floor(($checkinDate->timestamp - $officeStartDate->timestamp) / 60);

        // Get thresholds from user's department (matching frontend)
        $onTimeThreshold = $user->department?->on_time_threshold_minutes ?? 1;
        $delayThreshold = $user->department?->delay_threshold_minutes ?? 5;
        $extremeDelayThreshold = $user->department?->extreme_delay_threshold_minutes ?? 15;

        if ($diffMinutes <= $onTimeThreshold) {
            return 'On Time';
        } elseif ($diffMinutes <= $delayThreshold) {
            return 'Delay';
        } elseif ($diffMinutes <= $extremeDelayThreshold) {
            return 'Extreme Delay';
        } else {
            return 'Extreme Delay';
        }
    }

    /**
     * Calculate total worked minutes from attendance details
     * Using floor() to match JavaScript's behavior more closely
     */
    private function calculateWorkedMinutes($dayAttendances)
    {
        if ($dayAttendances->isEmpty()) {
            return 0;
        }

        $sortedAttendances = $dayAttendances->sortBy(function ($item) {
            return $item->datetime instanceof Carbon
                ? $item->datetime->timestamp
                : strtotime($item->datetime);
        })->values();

        $totalMinutes = 0;

        // Pair consecutive entries: even index = checkin, odd index = checkout
        for ($i = 0; $i < $sortedAttendances->count() - 1; $i += 2) {
            $checkinTime = $sortedAttendances[$i]->datetime instanceof Carbon
                ? $sortedAttendances[$i]->datetime
                : Carbon::parse($sortedAttendances[$i]->datetime);

            $checkoutTime = $sortedAttendances[$i + 1]->datetime instanceof Carbon
                ? $sortedAttendances[$i + 1]->datetime
                : Carbon::parse($sortedAttendances[$i + 1]->datetime);

            // Use floor to match JavaScript's differenceInMinutes behavior
            $totalMinutes += floor(($checkoutTime->timestamp - $checkinTime->timestamp) / 60);
        }

        return $totalMinutes;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Name',
            'Email',
            'Employee ID',
            'Department',
            'Division',
            'First Check-in',
            'Last Checkout',
            'Status',
            'Expected Duty Hours',
            'Actual Worked Hours',
            'Extra/Less Duty Hours',
            'Note',
        ];
    }
}