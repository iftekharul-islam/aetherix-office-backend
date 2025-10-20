<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        return Department::with('division', 'head')->get();
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'division_id' => 'required|exists:divisions,id',
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:20|unique:departments',
                'description' => 'nullable|string',
                'head_id' => 'nullable|exists:users,id',
                'office_start_time' => 'nullable|string',
                'expected_duty_hours' => 'nullable|numeric|min:0|max:24',
                'on_time_threshold_minutes' => 'nullable|integer|min:0|max:60',
                'delay_threshold_minutes' => 'nullable|integer|min:0|max:120',
                'extreme_delay_threshold_minutes' => 'nullable|integer|min:0|max:180',
            ]);

            // Convert time format from "2:53 PM" to "14:53:00"
            if (!empty($data['office_start_time'])) {
                $data['office_start_time'] = \Carbon\Carbon::createFromFormat('g:i A', $data['office_start_time'])->format('H:i:s');
            }

            $department = Department::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Department created successfully.',
                'data' => $department
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            info('Validation failed while creating department', ['errors' => $e->errors()]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            info('Error creating department', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while creating department.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Department $department)
    {
        return $department->load('division', 'head');
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'division_id' => 'sometimes|required|exists:divisions,id',
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:20|unique:departments,code,' . $department->id,
            'description' => 'nullable|string',
            'head_id' => 'nullable|exists:users,id',
            'office_start_time' => 'nullable|string',
            'expected_duty_hours' => 'nullable|numeric|min:0|max:24',
            'on_time_threshold_minutes' => 'nullable|integer|min:0|max:60',
            'delay_threshold_minutes' => 'nullable|integer|min:0|max:120',
            'extreme_delay_threshold_minutes' => 'nullable|integer|min:0|max:180',
        ]);

        // Convert time format from "2:53 PM" to "14:53:00"
        if (!empty($data['office_start_time'])) {
            $data['office_start_time'] = \Carbon\Carbon::createFromFormat('g:i A', $data['office_start_time'])->format('H:i:s');
        }

        $department->update($data);

        return response()->json($department);
    }

    public function destroy(Department $department)
    {
        $department->delete();
        return response()->json(['message' => 'Department deleted successfully']);
    }
}