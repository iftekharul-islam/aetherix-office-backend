<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\MachineAttendanceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebhookController;
use App\Models\Department;
use App\Models\MachineAttendance;

use Illuminate\Support\Facades\Route;


Route::post('/login', [AuthController::class, 'login'])->name('login.post');


// Routes accessible to ALL authenticated users (both admin and regular users)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/user', [UserController::class, 'me']);
    
    // Read-only access for all authenticated users
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{user}', [UserController::class, 'show']);
    
    Route::get('divisions', [DivisionController::class, 'index']);
    Route::get('divisions/{division}', [DivisionController::class, 'show']);
    
    Route::get('departments', [DepartmentController::class, 'index']);
    Route::get('departments/{department}', [DepartmentController::class, 'show']);
    
    Route::get('employees', [EmployeeController::class, 'index']);
    Route::get('employees/{employee}', [EmployeeController::class, 'show']);
    
    Route::get('machine-attendances', [MachineAttendanceController::class, 'index']);
    Route::get('machine-attendances/summary', [MachineAttendanceController::class, 'summary']);
    Route::get('machine-attendances/{machine_attendance}', [MachineAttendanceController::class, 'show']);
});


// Admin-only routes (Create, Update, Delete operations)
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // Users - Admin only operations
    Route::post('users', [UserController::class, 'store']);
    Route::put('users/{user}', [UserController::class, 'update']);
    Route::patch('users/{user}', [UserController::class, 'update']);
    Route::delete('users/{user}', [UserController::class, 'destroy']);
    
    // Divisions - Admin only operations
    Route::post('divisions', [DivisionController::class, 'store']);
    Route::put('divisions/{division}', [DivisionController::class, 'update']);
    Route::patch('divisions/{division}', [DivisionController::class, 'update']);
    Route::delete('divisions/{division}', [DivisionController::class, 'destroy']);
    
    // Departments - Admin only operations
    Route::post('departments', [DepartmentController::class, 'store']);
    Route::put('departments/{department}', [DepartmentController::class, 'update']);
    Route::patch('departments/{department}', [DepartmentController::class, 'update']);
    Route::delete('departments/{department}', [DepartmentController::class, 'destroy']);
    
    // Employees - Admin only operations
    Route::post('employees', [EmployeeController::class, 'store']);
    Route::put('employees/{employee}', [EmployeeController::class, 'update']);
    Route::patch('employees/{employee}', [EmployeeController::class, 'update']);
    Route::delete('employees/{employee}', [EmployeeController::class, 'destroy']);
    
    // Machine Attendances - Admin only operations
    Route::post('machine-attendances', [MachineAttendanceController::class, 'store']);
    Route::put('machine-attendances/{machine_attendance}', [MachineAttendanceController::class, 'update']);
    Route::patch('machine-attendances/{machine_attendance}', [MachineAttendanceController::class, 'update']);
    Route::delete('machine-attendances/{machine_attendance}', [MachineAttendanceController::class, 'destroy']);
    Route::patch('attendance/{attendance}/soft-delete', [MachineAttendanceController::class, 'softDelete']);

    // Export routes - Admin only
    Route::get('/export/users', [ExportController::class, 'exportUsers']);
    Route::get('/export/departments', [ExportController::class, 'exportDepartments']);
    Route::get('/export/divisions', [ExportController::class, 'exportDivisions']);
    Route::get('/export/attendances', [ExportController::class, 'exportAttendances']);
    Route::get('/export/attendance-details', [ExportController::class, 'exportAttendanceDetails']);
    
    // Attendance notes - Admin only
    Route::post('/attendance-notes', [MachineAttendanceController::class, 'updateOrCreateNote']);
});


// Public/webhook routes
Route::get('/machine-attendance/{u_id}', function ($u_id) {
    MachineAttendance::create([
        'attendance_id' => '12345',
        'user_id' => $u_id,
        'type' => 'checkin',
        'datetime' => now(),
    ]);
    return response()->json(['message' => 'Attendance recorded successfully.']);
});

Route::post('/webhook/attendance', [WebhookController::class, 'handleAttendance']);

Route::get('test', function () {
    $data = Department::with(['division', 'head'])->find(1);
    return response()->json($data);
});