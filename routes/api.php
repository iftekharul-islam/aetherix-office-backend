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


Route::get('/products', function () {
    return [
        [
            'id' => 1,
            'name' => 'Laptop',
            'price' => 1200,
            'in_stock' => true,
        ],
        [
            'id' => 2,
            'name' => 'Smartphone',
            'price' => 800,
            'in_stock' => false,
        ],
        [
            'id' => 3,
            'name' => 'Headphones',
            'price' => 150,
            'in_stock' => true,
        ],
    ];
});
Route::get('/products4', function () {
    return [
        [
            'id' => 1,
            'name' => 'Laptop',
            'price' => 1200,
            'in_stock' => true,
        ],
        [
            'id' => 2,
            'name' => 'Smartphone',
            'price' => 800,
            'in_stock' => false,
        ],
        [
            'id' => 3,
            'name' => 'Headphones',
            'price' => 150,
            'in_stock' => true,
        ],
    ];
});
// Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login.post');



Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::apiResource("users", UserController::class);
    Route::get('/user', [UserController::class, 'me']);

    Route::apiResource('divisions', DivisionController::class);
    Route::apiResource('departments', DepartmentController::class);
    Route::apiResource('employees', EmployeeController::class);
    Route::get('machine-attendances/summary', [MachineAttendanceController::class, 'summary']);
    Route::apiResource('machine-attendances', MachineAttendanceController::class);


    Route::get('/export/users', [ExportController::class, 'exportUsers']);
    Route::get('/export/departments', [ExportController::class, 'exportDepartments']);
    Route::get('/export/divisions', [ExportController::class, 'exportDivisions']);
    Route::get('/export/attendances', [ExportController::class, 'exportAttendances']);
    Route::get('/export/attendance-details', [ExportController::class, 'exportAttendanceDetails']);
    Route::patch('attendance/{attendance}/soft-delete', [MachineAttendanceController::class, 'softDelete']);
});






// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


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
