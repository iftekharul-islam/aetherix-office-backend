<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceDetailsExport;
use App\Exports\AttendancesExport;
use App\Exports\UsersExport;
use App\Exports\DepartmentsExport;
use App\Exports\DivisionsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExportController extends Controller
{
    public function exportUsers()
    {
        // return Excel::download(new UsersExport(), 'users.xlsx');
        return Excel::download(new UsersExport(), 'users.csv', \Maatwebsite\Excel\Excel::CSV);
    }

    public function exportDepartments()
    {
        return Excel::download(new DepartmentsExport(), 'departments.xlsx');
    }

    public function exportDivisions()
    {
        return Excel::download(new DivisionsExport(), 'divisions.xlsx');
    }

    // public function exportAttendances(Request $request)
    // {
    //     return Excel::download(new AttendancesExport($request), 'attendances.xlsx');
    // }

    // In your controller
    public function exportAttendances(Request $request)
    {
        try {
            return Excel::download(
                new AttendancesExport($request),
                'attendances.xlsx'
            );
        } catch (\Exception $e) {
            Log::error('Attendance export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }


    public function exportAttendanceDetails(Request $request)
    {
        return Excel::download(new AttendanceDetailsExport($request), 'attendance_details.xlsx');
    }
}
