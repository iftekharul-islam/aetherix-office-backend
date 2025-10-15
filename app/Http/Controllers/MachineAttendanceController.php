<?php

namespace App\Http\Controllers;

use App\Models\MachineAttendance;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class MachineAttendanceController extends Controller
{
    /**
     * Display a listing of the machine attendances.
     */
    public function index(Request $request)
    {
        $query = MachineAttendance::with('user');

        // Global search / filter
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('employee_id', 'like', "%$search%");
            })->orWhere('uid', $search)
                ->orWhere('attendance_id', $search)
                ->orWhere('type', 'like', "%$search%");
        }

        // Optional: Filter by type, user_id, date, etc.
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // if ($request->has('from') && $request->has('to')) {
        //     $query->whereBetween('datetime', [$request->input('from'), $request->input('to')]);
        // }

        if ($request->has('from') && $request->has('to')) {
            $from = $request->input('from') . ' 00:00:00';
            $to = $request->input('to') . ' 23:59:59';
            $query->whereBetween('datetime', [$from, $to]);
        }

        // Sorting
        if ($request->has('sort_by') && $request->has('sort_order')) {
            $query->orderBy($request->input('sort_by'), $request->input('sort_order'));
        } else {
            $query->latest();
        }

        // Pagination
        $perPage = $request->input('per_page', 10); // default 10 per page
        $attendances = $query->paginate($perPage);

        return response()->json($attendances);
    }

    // enhanced version of index
    //   public function summary(Request $request)
    // {
    //     try {
    //         // Base query with user, department, division
    //         $query = MachineAttendance::with(['user.department.division'])
    //             ->when($request->filled('search'), function ($q) use ($request) {
    //                 $search = $request->input('search');
    //                 $q->whereHas('user', function ($q2) use ($search) {
    //                     $q2->where('name', 'like', "%$search%")
    //                         ->orWhere('email', 'like', "%$search%")
    //                         ->orWhere('employee_id', 'like', "%$search%");
    //                 })
    //                 ->orWhere('attendance_id', 'like', "%$search%")
    //                 ->orWhere('type', 'like', "%$search%");
    //             })
    //             ->when($request->filled('user_id'), fn($q) => $q->where('user_id', $request->user_id))
    //             ->when($request->filled('type'), fn($q) => $q->where('type', $request->type))
    //             ->when($request->filled('department_id'), fn($q) =>
    //                 $q->whereHas('user.department', fn($q2) => $q2->where('id', $request->department_id))
    //             )
    //             ->when($request->filled('division_id'), fn($q) =>
    //                 $q->whereHas('user.department.division', fn($q2) => $q2->where('id', $request->division_id))
    //             )
    //             ->when($request->filled('from') && $request->filled('to'), fn($q) =>
    //                 $q->whereBetween('datetime', [
    //                     $request->from . ' 00:00:00',
    //                     $request->to . ' 23:59:59'
    //                 ])
    //             );

    //         // Default sorting (newest first)
    //         if ($request->filled('sort_by') && $request->filled('sort_order')) {
    //             $query->orderBy($request->sort_by, $request->sort_order);
    //         } else {
    //             $query->latest('datetime');
    //         }

    //         $attendances = $query->get();

    //         $summary = $attendances->groupBy(function ($item) {
    //             return $item->user_id . '_' . $item->datetime->format('Y-m-d');
    //         })->map(function ($group) {
    //             $firstCheckin = $group->where('type', 'checkin')->min('datetime');
    //             $lastCheckout = $group->where('type', 'checkout')->max('datetime');

    //             $workedSeconds = strtotime($lastCheckout) - strtotime($firstCheckin);
    //             $workedHours = $workedSeconds > 0 ? round($workedSeconds / 3600, 2) : 0;

    //             $officeHours = $group->first()->user->department->office_hours ?? 8;
    //             $extraLessHours = round($workedHours - $officeHours, 2);

    //             $status = 'On Time';
    //             $firstCheckinHour = date('H', strtotime($firstCheckin));
    //             $lateThreshold = $group->first()->user->department->office_start_hour ?? 9;

    //             if ($workedHours == 0) {
    //                 $status = 'Absent';
    //             } elseif ($firstCheckinHour > $lateThreshold + 2) {
    //                 $status = 'Extremely Late';
    //             } elseif ($firstCheckinHour > $lateThreshold) {
    //                 $status = 'Late';
    //             }

    //             return [
    //                 'date' => date('Y-m-d', strtotime($group->first()->datetime)),
    //                 'user' => [
    //                     'id' => $group->first()->user->id,
    //                     'name' => $group->first()->user->name,
    //                     'email' => $group->first()->user->email,
    //                     'department' => $group->first()->user->department->name ?? null,
    //                     'division' => $group->first()->user->department->division->name ?? null,
    //                 ],
    //                 'first_checkin' => $firstCheckin,
    //                 'last_checkout' => $lastCheckout,
    //                 'worked_hours' => $workedHours,
    //                 'extra_less_hours' => $extraLessHours,
    //                 'status' => $status,
    //             ];
    //         })->values();

    //         $perPage = $request->input('per_page', 10);
    //         $page = $request->input('page', 1);
    //         $paginated = $summary->forPage($page, $perPage);

    //         return response()->json([
    //             'data' => $paginated,
    //             'total' => $summary->count(),
    //             'per_page' => $perPage,
    //             'current_page' => (int)$page,
    //             'last_page' => ceil($summary->count() / $perPage)
    //         ]);

    //     } catch (\Throwable $e) {
    //         info('Summary error: ' . $e->getMessage());
    //         return response()->json([
    //             'error' => 'Something went wrong',
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }





    // public function summary(Request $request)
    // {

    //     info(("Request Data: " . json_encode($request->all())));

    //     try {
    //         // Base query with relationships and filters
    //         $query = MachineAttendance::with(['user.department.division'])
    //             ->when($request->filled('search'), function ($q) use ($request) {
    //                 $search = $request->input('search');
    //                 $q->whereHas('user', function ($q2) use ($search) {
    //                     $q2->where('name', 'like', "%$search%")
    //                         ->orWhere('email', 'like', "%$search%")
    //                         ->orWhere('employee_id', 'like', "%$search%");
    //                 })
    //                     ->orWhere('attendance_id', 'like', "%$search%")
    //                     ->orWhere('type', 'like', "%$search%");
    //             })
    //             ->where('is_deleted', false)
    //             ->when($request->filled('user_id'), fn($q) => $q->where('user_id', $request->user_id))
    //             ->when($request->filled('type'), fn($q) => $q->where('type', $request->type))
    //             ->when(
    //                 $request->filled('department_id'),
    //                 fn($q) =>
    //                 $q->whereHas('user.department', fn($q2) => $q2->where('id', $request->department_id))
    //             )
    //             ->when(
    //                 $request->filled('division_id'),
    //                 fn($q) =>
    //                 $q->whereHas('user.department.division', fn($q2) => $q2->where('id', $request->division_id))
    //             )
    //             ->when($request->filled('from') && $request->filled('to'), function ($q) use ($request) {
    //                 $from = $request->input('from') . ' 00:00:00';
    //                 $to = $request->input('to') . ' 23:59:59';
    //                 $q->whereBetween('datetime', [$from, $to]);
    //             })
    //             ->when($request->filled('date') && !$request->filled('from') && !$request->filled('to'), function ($q) use ($request) {
    //                 $q->whereDate('datetime', $request->date);
    //             });


    //         // Sorting

    //         if ($request->filled('sort_by') && $request->filled('sort_order')) {
    //             if ($request->sort_by === 'date') {

    //                 $query->orderByRaw("DATE(datetime) {$request->sort_order}, TIME(datetime) ASC");
    //             } else {

    //                 $query->orderBy($request->sort_by, $request->sort_order);
    //             }
    //         } else {
    //             // Default sorting: date descending, time ascending
    //             $query->orderByRaw('DATE(datetime) DESC, TIME(datetime) ASC');
    //         }

    //         // Fetch all attendances
    //         $attendances = $query->get();

    //         // Group by user + date
    //         $grouped = $attendances->groupBy(function ($item) {
    //             $dateString = $item->datetime instanceof \Carbon\Carbon
    //                 ? $item->datetime->format('Y-m-d')
    //                 : date('Y-m-d', strtotime($item->datetime));
    //             return $item->user_id . '_' . $dateString;
    //         });

    //         // Pagination
    //         $perPage = $request->input('per_page', 10);
    //         $page = $request->input('page', 1);
    //         $sliced = $grouped->slice(($page - 1) * $perPage, $perPage)->values();

    //         $paginator = new LengthAwarePaginator(
    //             $sliced,
    //             $grouped->count(),
    //             $perPage,
    //             $page,
    //             ['path' => $request->url(), 'query' => $request->query()]
    //         );

    //         // Prepare response data
    //         $data = $sliced->map(function ($group) {
    //             // ! don't delete this two,
    //             // $firstCheckin = $group->where('type', 'checkin')->min('datetime');
    //             // $lastCheckout = $group->where('type', 'checkout')->max('datetime');


    //             // ! this two will be deleted after completion
    //             $firstCheckin = $group->min('datetime');
    //             $lastCheckout = $group->max('datetime');

    //             $firstItem = $group->first();

    //             return [
    //                 'date' => $firstItem->datetime instanceof \Carbon\Carbon
    //                     ? $firstItem->datetime->format('Y-m-d')
    //                     : date('Y-m-d', strtotime($firstItem->datetime)),
    //                 'user' => [
    //                     'id' => $firstItem->user->id,
    //                     'name' => $firstItem->user->name,
    //                     'email' => $firstItem->user->email,
    //                     'employee_id' => $firstItem->user->employee_id ?? null,
    //                     'department' => $firstItem->user->department->name ?? null,
    //                     'division' => $firstItem->user->department->division->name ?? null,
    //                     'supervisor' => $firstItem->user->supervisor->name ?? null,
    //                 ],
    //                 'first_checkin' => $firstCheckin instanceof \Carbon\Carbon ? $firstCheckin->toDateTimeString() : $firstCheckin,
    //                 'last_checkout' => $lastCheckout instanceof \Carbon\Carbon ? $lastCheckout->toDateTimeString() : $lastCheckout,
    //                 // 'details' => $group
    //                 //     ->where('is_deleted', false)
    //                 //     ->map(fn($item) => [
    //                 //         'id' => $item->id,
    //                 //         'datetime' => $item->datetime instanceof \Carbon\Carbon
    //                 //             ? $item->datetime->toDateTimeString()
    //                 //             : $item->datetime,
    //                 //         'type' => $item->type,
    //                 //     ])->values(),
    //                 'details' => $group
    //                     ->where('is_deleted', false)
    //                     ->sortBy(function ($item) {
    //                         return $item->datetime instanceof \Carbon\Carbon
    //                             ? $item->datetime->timestamp
    //                             : strtotime($item->datetime);
    //                     })
    //                     ->map(fn($item) => [
    //                         'id' => $item->id,
    //                         'datetime' => $item->datetime instanceof \Carbon\Carbon
    //                             ? $item->datetime->toDateTimeString()
    //                             : $item->datetime,
    //                         'type' => $item->type,
    //                     ])->values(),
    //             ];
    //         });

    //         return response()->json([
    //             'data' => $data,
    //             'total' => $grouped->count(),
    //             'per_page' => $perPage,
    //             'current_page' => (int)$page,
    //             'last_page' => ceil($grouped->count() / $perPage)
    //         ]);
    //     } catch (\Throwable $e) {
    //         return response()->json([
    //             'error' => 'Something went wrong',
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }


    public function summary(Request $request)
    {
        info(("Request Data: " . json_encode($request->all())));

        try {
            // Base query with relationships and filters
            $query = MachineAttendance::with(['user.department.division', 'user.supervisor'])
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
                ->when($request->filled('date') && !$request->filled('from') && !$request->filled('to'), function ($q) use ($request) {
                    $q->whereDate('datetime', $request->date);
                });

            // Sorting
            if ($request->filled('sort_by') && $request->filled('sort_order')) {
                if ($request->sort_by === 'date') {
                    $query->orderByRaw("DATE(datetime) {$request->sort_order}, TIME(datetime) ASC");
                } else {
                    $query->orderBy($request->sort_by, $request->sort_order);
                }
            } else {
                // Default sorting: date descending, time ascending
                $query->orderByRaw('DATE(datetime) DESC, TIME(datetime) ASC');
            }

            // Fetch all attendances
            $attendances = $query->get();

            // Group by user + date
            $grouped = $attendances->groupBy(function ($item) {
                $dateString = $item->datetime instanceof \Carbon\Carbon
                    ? $item->datetime->format('Y-m-d')
                    : date('Y-m-d', strtotime($item->datetime));
                return $item->user_id . '_' . $dateString;
            });


            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            $sliced = $grouped->slice(($page - 1) * $perPage, $perPage)->values();


            $data = $sliced->map(function ($group) {
                $firstItem = $group->first();


                if (!$firstItem || !$firstItem->user) {
                    return null;
                }

                $firstCheckin = $group->min('datetime');
                $lastCheckout = $group->max('datetime');

                return [
                    'date' => $firstItem->datetime instanceof \Carbon\Carbon
                        ? $firstItem->datetime->format('Y-m-d')
                        : date('Y-m-d', strtotime($firstItem->datetime)),
                    'user' => [
                        'id' => $firstItem->user->id,
                        'name' => $firstItem->user->name,
                        'email' => $firstItem->user->email,
                        'employee_id' => $firstItem->user->employee_id ?? null,
                        'department' => $firstItem->user->department?->name ?? null,
                        'division' => $firstItem->user->department?->division?->name ?? null,
                        'supervisor' => $firstItem->user->supervisor?->name ?? null,
                        'office_start_time' => $firstItem->user->department?->office_start_time ?? null,
                    ],
                    'first_checkin' => $firstCheckin instanceof \Carbon\Carbon ? $firstCheckin->toDateTimeString() : $firstCheckin,
                    'last_checkout' => $lastCheckout instanceof \Carbon\Carbon ? $lastCheckout->toDateTimeString() : $lastCheckout,
                    'details' => $group
                        ->where('is_deleted', false)
                        ->sortBy(function ($item) {
                            return $item->datetime instanceof \Carbon\Carbon
                                ? $item->datetime->timestamp
                                : strtotime($item->datetime);
                        })
                        ->map(fn($item) => [
                            'id' => $item->id,
                            'datetime' => $item->datetime instanceof \Carbon\Carbon
                                ? $item->datetime->toDateTimeString()
                                : $item->datetime,
                            'type' => $item->type,
                            'note' => $item->note,
                        ])->values(),
                ];
            })->filter(); // Remove null values

            return response()->json([
                'data' => $data->values(),
                'total' => $grouped->count(),
                'per_page' => $perPage,
                'current_page' => (int)$page,
                'last_page' => ceil($grouped->count() / $perPage)
            ]);
        } catch (\Throwable $e) {
            Log::error('Attendance summary error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }
    }







    /**
     * Store a newly created machine attendance.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'uid' => 'nullable|integer|unique:machine_attendances,uid',
            'attendance_id' => 'nullable|integer',
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:checkin,checkout',
            'datetime' => 'required|date',
            'note' => 'nullable|string|max:500',
        ]);

        $attendance = MachineAttendance::create($validated);

        return response()->json([
            'message' => 'Attendance created successfully.',
            'data' => $attendance
        ], 201);
    }

    /**
     * Display a specific machine attendance.
     */
    public function show(MachineAttendance $machineAttendance)
    {
        return response()->json($machineAttendance->load('user'));
    }

    /**
     * Update the specified machine attendance.
     */
    public function update(Request $request, MachineAttendance $machineAttendance)
    {
        $validated = $request->validate([
            'uid' => 'sometimes|integer|unique:machine_attendances,uid,' . $machineAttendance->id,
            'attendance_id' => 'sometimes|integer',
            'user_id' => 'sometimes|exists:users,id',
            'type' => 'sometimes|in:checkin,checkout',
            'datetime' => 'sometimes|date',
        ]);

        $machineAttendance->update($validated);

        return response()->json([
            'message' => 'Attendance updated successfully.',
            'data' => $machineAttendance
        ]);
    }

    /**
     * Remove the specified machine attendance.
     */
    public function destroy(MachineAttendance $machineAttendance)
    {
        $machineAttendance->delete();

        return response()->json([
            'message' => 'Attendance deleted successfully.'
        ]);
    }



    public function softDelete(MachineAttendance $attendance)
    {
        try {
            $attendance->is_deleted = true;
            $attendance->save();

            return response()->json([
                'message' => 'Attendance marked as deleted successfully.',
                'data' => $attendance
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to mark attendance as deleted.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
