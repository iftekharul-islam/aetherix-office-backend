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
            ->where('is_deleted', false)
            ->when($request->filled('user_id'), fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('from') && $request->filled('to'), function ($q) use ($request) {
                $from = $request->input('from') . ' 00:00:00';
                $to = $request->input('to') . ' 23:59:59';
                $q->whereBetween('datetime', [$from, $to]);
            });

        $attendances = $query->orderBy('datetime', 'asc')->get();

        $grouped = $attendances->groupBy(function ($item) {
            $date = $item->datetime instanceof \Carbon\Carbon
                ? $item->datetime->format('Y-m-d')
                : date('Y-m-d', strtotime($item->datetime));
            return $item->user_id . '_' . $date;
        });

        $rows = [];

        foreach ($grouped as $group) {
            $group = $group->sortBy('datetime')->values();
            $first = $group->first();

            // Safety check: skip if item or user is null
            if (!$first || !$first->user) {
                continue;
            }

            $max = ceil($group->count() / 2);

            for ($i = 0; $i < $max; $i++) {
                $checkin = $group->get($i * 2);
                $checkout = $group->get($i * 2 + 1);

                $rows[] = [
                    'Date'        => $i === 0 ? date('Y-m-d', strtotime($first->datetime)) : '-',
                    'Name'        => $i === 0 ? $first->user->name : null,
                    'Employee ID' => $i === 0 ? ($first->user->employee_id ?? '-') : '-',
                    'Email'       => $i === 0 ? $first->user->email : null,
                    'Department'  => $i === 0 ? ($first->user->department?->name ?? '-') : '-',
                    'Division'    => $i === 0 ? ($first->user->department?->division?->name ?? '-') : '-',
                    'Check-in'    => $checkin ? date('h:i A', strtotime($checkin->datetime)) : '-',
                    'Check-out'   => $checkout ? date('h:i A', strtotime($checkout->datetime)) : '-',
                ];
            }
        }

        return collect($rows);
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
            'Check-in Details',
            'Check-out Details',
        ];
    }
}