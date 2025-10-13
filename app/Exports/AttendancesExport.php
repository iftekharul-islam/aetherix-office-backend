<?php

namespace App\Exports;

use App\Models\MachineAttendance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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

        $query = MachineAttendance::with(['user.department.division'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->input('search');
                $q->whereHas('user', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%")
                        ->orWhere('employee_id', 'like', "%$search%");
                })
                    ->orWhere('attendance_id', 'like', "%$search%")
                    ->orWhere('type', 'like', "%$search%");
            })
            ->where('is_deleted', false)
            ->when($request->filled('user_id'), fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->type))
            ->when(
                $request->filled('department_id'),
                fn($q) =>
                $q->whereHas('user.department', fn($q2) => $q2->where('id', $request->department_id))
            )
            ->when(
                $request->filled('division_id'),
                fn($q) =>
                $q->whereHas('user.department.division', fn($q2) => $q2->where('id', $request->division_id))
            )
            ->when($request->filled('from') && $request->filled('to'), function ($q) use ($request) {
                $from = $request->input('from') . ' 00:00:00';
                $to = $request->input('to') . ' 23:59:59';
                $q->whereBetween('datetime', [$from, $to]);
            })
            ->when(
                $request->filled('date') && !$request->filled('from') && !$request->filled('to'),
                fn($q) => $q->whereDate('datetime', $request->date)
            );

        $attendances = $query->get();

        $grouped = $attendances->groupBy(function ($item) {
            $dateString = $item->datetime instanceof \Carbon\Carbon
                ? $item->datetime->format('Y-m-d')
                : date('Y-m-d', strtotime($item->datetime));
            return $item->user_id . '_' . $dateString;
        });

        $data = $grouped->map(function ($group) {
            $firstCheckin = $group->where('type', 'checkin')->min('datetime');
            $lastCheckout = $group->where('type', 'checkout')->max('datetime');
            $firstItem = $group->first();

            return [
                'Date' => $firstItem->datetime instanceof \Carbon\Carbon
                    ? $firstItem->datetime->format('Y-m-d')
                    : date('Y-m-d', strtotime($firstItem->datetime)),
                'User ID' => $firstItem->user->id,
                'Name' => $firstItem->user->name,
                'Email' => $firstItem->user->email,
                'Employee ID' => $firstItem->user->employee_id ?? null,
                'Department' => $firstItem->user->department->name ?? null,
                'Division' => $firstItem->user->department->division->name ?? null,
                'First Check-in' => $firstCheckin instanceof \Carbon\Carbon
                    ? $firstCheckin->format('h:i A')
                    : date('h:i A', strtotime($firstCheckin)),
                'Last Checkout' => $lastCheckout instanceof \Carbon\Carbon
                    ? $lastCheckout->format('h:i A')
                    : date('h:i A', strtotime($lastCheckout)),
            ];
        });


        return collect($data);
    }

    public function headings(): array
    {
        return [
            'Date',
            'User ID',
            'Name',
            'Email',
            'Employee ID',
            'Department',
            'Division',
            'First Check-in',
            'Last Checkout',
        ];
    }
}
