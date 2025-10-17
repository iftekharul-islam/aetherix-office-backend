<?php

namespace App\Http\Controllers;

use App\Models\AttendanceNote;
use App\Models\MachineAttendance;
use App\Models\User;
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






    // ! without absent employees

    // public function summary(Request $request)
    // {
    //     info(("Request Data: " . json_encode($request->all())));

    //     try {
    //         // Base query with relationships and filters
    //         $query = MachineAttendance::with(['user.department.division', 'user.supervisor'])
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


    //         $perPage = $request->input('per_page', 10);
    //         $page = $request->input('page', 1);
    //         $sliced = $grouped->slice(($page - 1) * $perPage, $perPage)->values();


    //         $data = $sliced->map(function ($group) {
    //             $firstItem = $group->first();


    //             if (!$firstItem || !$firstItem->user) {
    //                 return null;
    //             }

    //             $firstCheckin = $group->min('datetime');
    //             $lastCheckout = $group->max('datetime');

    //             return [
    //                 'date' => $firstItem->datetime instanceof \Carbon\Carbon
    //                     ? $firstItem->datetime->format('Y-m-d')
    //                     : date('Y-m-d', strtotime($firstItem->datetime)),
    //                 'user' => [
    //                     'id' => $firstItem->user->id,
    //                     'name' => $firstItem->user->name,
    //                     'email' => $firstItem->user->email,
    //                     'employee_id' => $firstItem->user->employee_id ?? null,
    //                     'department' => $firstItem->user->department?->name ?? null,
    //                     'division' => $firstItem->user->department?->division?->name ?? null,
    //                     'supervisor' => $firstItem->user->supervisor?->name ?? null,
    //                     'office_start_time' => $firstItem->user->department?->office_start_time ?? null,
    //                 ],
    //                 'first_checkin' => $firstCheckin instanceof \Carbon\Carbon ? $firstCheckin->toDateTimeString() : $firstCheckin,
    //                 'last_checkout' => $lastCheckout instanceof \Carbon\Carbon ? $lastCheckout->toDateTimeString() : $lastCheckout,
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
    //                         'note' => $item->note,
    //                     ])->values(),
    //             ];
    //         })->filter();

    //         return response()->json([
    //             'data' => $data->values(),
    //             'total' => $grouped->count(),
    //             'per_page' => $perPage,
    //             'current_page' => (int)$page,
    //             'last_page' => ceil($grouped->count() / $perPage)
    //         ]);
    //     } catch (\Throwable $e) {
    //         Log::error('Attendance summary error', [
    //             'message' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);
    //         return response()->json([
    //             'error' => 'Something went wrong',
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }



    // ! with absent employees
    // public function summary(Request $request)
    // {
    //     info("Request Data: " . json_encode($request->all()));

    //     try {
    //         // Build user query first with all filters
    //         $userQuery = User::with(['department.division', 'supervisor'])
    //             ->when($request->filled('search'), function ($q) use ($request) {
    //                 $search = $request->input('search');
    //                 $q->where(function ($q2) use ($search) {
    //                     $q2->where('name', 'like', "%$search%")
    //                         ->orWhere('email', 'like', "%$search%")
    //                         ->orWhere('employee_id', 'like', "%$search%");
    //                 });
    //             })
    //             ->when($request->filled('user_id'), fn($q) => $q->where('id', $request->user_id))
    //             ->when(
    //                 $request->filled('department_id'),
    //                 fn($q) => $q->where('department_id', $request->department_id)
    //             )
    //             ->when(
    //                 $request->filled('division_id'),
    //                 fn($q) => $q->whereHas('department', fn($q2) => $q2->where('division_id', $request->division_id))
    //             );

    //         // Get users
    //         $users = $userQuery->get();

    //         // Determine date range
    //         $dateRange = [];
    //         if ($request->filled('from') && $request->filled('to')) {
    //             $from = \Carbon\Carbon::parse($request->from);
    //             $to = \Carbon\Carbon::parse($request->to);
    //             while ($from->lte($to)) {
    //                 $dateRange[] = $from->format('Y-m-d');
    //                 $from->addDay();
    //             }
    //         } elseif ($request->filled('date')) {
    //             $dateRange = [$request->date];
    //         }

    //         // Fetch attendances for these users and date range
    //         $attendanceQuery = MachineAttendance::whereIn('user_id', $users->pluck('id'))
    //             ->where('is_deleted', false)
    //             ->when($request->filled('type'), fn($q) => $q->where('type', $request->type));

    //         if ($request->filled('from') && $request->filled('to')) {
    //             $from = $request->input('from') . ' 00:00:00';
    //             $to = $request->input('to') . ' 23:59:59';
    //             $attendanceQuery->whereBetween('datetime', [$from, $to]);
    //         } elseif ($request->filled('date')) {
    //             $attendanceQuery->whereDate('datetime', $request->date);
    //         }

    //         $attendances = $attendanceQuery->get();

    //         // If no date range specified, extract unique dates from attendances
    //         if (empty($dateRange)) {
    //             $dateRange = $attendances->map(function ($item) {
    //                 return $item->datetime instanceof \Carbon\Carbon
    //                     ? $item->datetime->format('Y-m-d')
    //                     : date('Y-m-d', strtotime($item->datetime));
    //             })->unique()->sort()->values()->toArray();
    //         }

    //         // Group attendances by user_id and date
    //         $attendancesByUserDate = $attendances->groupBy(function ($item) {
    //             $dateString = $item->datetime instanceof \Carbon\Carbon
    //                 ? $item->datetime->format('Y-m-d')
    //                 : date('Y-m-d', strtotime($item->datetime));
    //             return $item->user_id . '_' . $dateString;
    //         });

    //         // Build final data structure
    //         $allRecords = collect();
    //         foreach ($users as $user) {
    //             foreach ($dateRange as $date) {
    //                 $key = $user->id . '_' . $date;
    //                 $dayAttendances = $attendancesByUserDate->get($key, collect());

    //                 $firstCheckin = $dayAttendances->min('datetime');
    //                 $lastCheckout = $dayAttendances->max('datetime');

    //                 $allRecords->push([
    //                     'date' => $date,
    //                     'user' => [
    //                         'id' => $user->id,
    //                         'name' => $user->name,
    //                         'email' => $user->email,
    //                         'employee_id' => $user->employee_id ?? null,
    //                         'department' => $user->department?->name ?? null,
    //                         'division' => $user->department?->division?->name ?? null,
    //                         'supervisor' => $user->supervisor?->name ?? null,
    //                         'office_start_time' => $user->department?->office_start_time ?? null,
    //                     ],
    //                     'first_checkin' => $firstCheckin ? ($firstCheckin instanceof \Carbon\Carbon ? $firstCheckin->toDateTimeString() : $firstCheckin) : null,
    //                     'last_checkout' => $lastCheckout ? ($lastCheckout instanceof \Carbon\Carbon ? $lastCheckout->toDateTimeString() : $lastCheckout) : null,
    //                     'has_attendance' => $dayAttendances->isNotEmpty(),
    //                     'details' => $dayAttendances
    //                         ->sortBy(function ($item) {
    //                             return $item->datetime instanceof \Carbon\Carbon
    //                                 ? $item->datetime->timestamp
    //                                 : strtotime($item->datetime);
    //                         })
    //                         ->map(fn($item) => [
    //                             'id' => $item->id,
    //                             'datetime' => $item->datetime instanceof \Carbon\Carbon
    //                                 ? $item->datetime->toDateTimeString()
    //                                 : $item->datetime,
    //                             'type' => $item->type,
    //                             'note' => $item->note,
    //                         ])->values(),
    //                 ]);
    //             }
    //         }

    //         // Apply sorting
    //         if ($request->filled('sort_by') && $request->filled('sort_order')) {
    //             $sortOrder = $request->sort_order === 'asc' ? 'sortBy' : 'sortByDesc';
    //             $allRecords = $allRecords->$sortOrder($request->sort_by);
    //         } else {
    //             // Default: sort by date desc
    //             $allRecords = $allRecords->sortByDesc('date');
    //         }

    //         // Pagination
    //         $perPage = $request->input('per_page', 10);
    //         $page = $request->input('page', 1);
    //         $total = $allRecords->count();
    //         $data = $allRecords->slice(($page - 1) * $perPage, $perPage)->values();

    //         return response()->json([
    //             'data' => $data,
    //             'total' => $total,
    //             'per_page' => $perPage,
    //             'current_page' => (int)$page,
    //             'last_page' => ceil($total / $perPage)
    //         ]);
    //     } catch (\Throwable $e) {
    //         Log::error('Attendance summary error', [
    //             'message' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);
    //         return response()->json([
    //             'error' => 'Something went wrong',
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function summary(Request $request)
    {
        info("Request Data: " . json_encode($request->all()));

        try {
            // Build user query first with all filters
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
                $from = \Carbon\Carbon::parse($request->from);
                $to = \Carbon\Carbon::parse($request->to);
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
                    return $item->datetime instanceof \Carbon\Carbon
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
                // Explicitly format the date to ensure Y-m-d format
                $dateString = \Carbon\Carbon::parse($item->date)->format('Y-m-d');
                return $item->user_id . '_' . $dateString;
            });

            // Add this debug logging
            Log::info('Notes fetched:', [
                'count' => $notes->count(),
                'keys' => $notes->keys()->toArray(),
                'sample' => $notes->first()
            ]);

            // Also log what keys you're looking for
            Log::info('Looking for key example:', [
                'sample_key' => $users->first()->id . '_' . $dateRange[0] ?? 'no date'
            ]);

            // Group attendances by user_id and date
            $attendancesByUserDate = $attendances->groupBy(function ($item) {
                $dateString = $item->datetime instanceof \Carbon\Carbon
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

                    $allRecords->push([
                        'date' => $date,
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'employee_id' => $user->employee_id ?? null,
                            'department' => $user->department?->name ?? null,
                            'division' => $user->department?->division?->name ?? null,
                            'supervisor' => $user->supervisor?->name ?? null,
                            'office_start_time' => $user->department?->office_start_time ?? null,
                            'expected_duty_hours' => $user->department?->expected_duty_hours ?? 9,
                            'on_time_threshold_minutes' => $user->department?->on_time_threshold_minutes ?? 1,
                            'delay_threshold_minutes' => $user->department?->delay_threshold_minutes ?? 5,
                            'extreme_delay_threshold_minutes' => $user->department?->extreme_delay_threshold_minutes ?? 15,
                        ],
                        'first_checkin' => $firstCheckin ? ($firstCheckin instanceof \Carbon\Carbon ? $firstCheckin->toDateTimeString() : $firstCheckin) : null,
                        'last_checkout' => $lastCheckout ? ($lastCheckout instanceof \Carbon\Carbon ? $lastCheckout->toDateTimeString() : $lastCheckout) : null,
                        'has_attendance' => $dayAttendances->isNotEmpty(),
                        'note' => $noteRecord?->note ?? null, // ← Note from attendance_notes table
                        'details' => $dayAttendances
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
                    ]);
                }
            }

            // Apply sorting
            if ($request->filled('sort_by') && $request->filled('sort_order')) {
                $sortOrder = $request->sort_order === 'asc' ? 'sortBy' : 'sortByDesc';
                $allRecords = $allRecords->$sortOrder($request->sort_by);
            } else {
                // Default: sort by date desc
                $allRecords = $allRecords->sortByDesc('date');
            }

            // Pagination
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            $total = $allRecords->count();
            $data = $allRecords->slice(($page - 1) * $perPage, $perPage)->values();

            return response()->json([
                'data' => $data,
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => (int)$page,
                'last_page' => ceil($total / $perPage)
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
            'note' => 'sometimes|string|max:500',
        ]);

        $machineAttendance->update($validated);

        return response()->json([
            'message' => 'Attendance updated successfully.',
            'data' => $machineAttendance
        ]);
    }



    public function updateOrCreateNote(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'note' => 'nullable|string|max:500', // ← Changed to nullable
        ]);

        try {
            // If note is empty, delete the record
            if (empty($validated['note'])) {
                AttendanceNote::where('user_id', $validated['user_id'])
                    ->where('date', $validated['date'])
                    ->delete();

                return response()->json([
                    'message' => 'Note deleted successfully.',
                ]);
            }

            $note = AttendanceNote::updateOrCreate(
                [
                    'user_id' => $validated['user_id'],
                    'date' => $validated['date'],
                ],
                [
                    'note' => $validated['note'],
                ]
            );

            return response()->json([
                'message' => 'Note saved successfully.',
                'data' => $note
            ]);
        } catch (\Throwable $e) {
            Log::error('Note update error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to save note',
                'message' => $e->getMessage()
            ], 500);
        }
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
