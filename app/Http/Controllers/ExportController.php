<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceDetailsExport;
use App\Exports\AttendancesExport;
use App\Exports\UsersExport;
use App\Exports\DepartmentsExport;
use App\Exports\DivisionsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

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

    public function exportAttendances(Request $request)
    {
        return Excel::download(new AttendancesExport($request), 'attendances.xlsx');
    }


    public function exportAttendanceDetails(Request $request)
    {
        return Excel::download(new AttendanceDetailsExport($request), 'attendance_details.xlsx');
    }
}
