<?php

namespace App\Exports;

use App\Models\MachineAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AttendanceDetailsExport implements FromCollection, WithHeadings
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection(): Collection
    {
        $request = $this->request;

        $query = MachineAttendance::with(['user.department.division'])
            ->when($request->filled('user_id'), fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('from') && $request->filled('to'), function ($q) use ($request) {
                $from = $request->input('from') . ' 00:00:00';
                $to = $request->input('to') . ' 23:59:59';
                $q->whereBetween('datetime', [$from, $to]);
            });

        $attendances = $query->orderBy('datetime', 'asc')->get();

        // Group by user + date
        $grouped = $attendances->groupBy(function ($item) {
            $date = $item->datetime instanceof \Carbon\Carbon
                ? $item->datetime->format('Y-m-d')
                : date('Y-m-d', strtotime($item->datetime));

            return $item->user_id . '_' . $date;
        });

        $data = $grouped->map(function ($group) {
            // Sort each group by datetime ascending
            $group = $group->sortBy('datetime')->values();
            $firstItem = $group->first();

            $checkins = $group->where('type', 'checkin')
                ->pluck('datetime')
                ->map(fn($d) => date('h:i A', strtotime($d)))
                ->implode(', ');

            $checkouts = $group->where('type', 'checkout')
                ->pluck('datetime')
                ->map(fn($d) => date('h:i A', strtotime($d)))
                ->implode(', ');

            return [
                'Date' => date('Y-m-d', strtotime($firstItem->datetime)),
                'Name' => $firstItem->user->name,
                'Employee ID' => $firstItem->user->employee_id ?? null,
                'Email' => $firstItem->user->email,
                'Department' => $firstItem->user->department->name ?? null,
                'Division' => $firstItem->user->department->division->name ?? null,
                'Check-in' => $checkins,
                'Check-out' => $checkouts,
            ];
        });

        return collect($data->values());
    }


    public function headings(): array
    {
        return [
            'Date',
            'Name',
            'Employee ID',
            'Email',
            'Department',
            'Division',
            'Check-in',
            'Check-out',
        ];
    }
}
