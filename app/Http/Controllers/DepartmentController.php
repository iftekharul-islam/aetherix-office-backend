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
            ]);

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
        ]);

        $department->update($data);

        return response()->json($department);
    }

    public function destroy(Department $department)
    {
        $department->delete();
        return response()->json(['message' => 'Department deleted successfully']);
    }
}
