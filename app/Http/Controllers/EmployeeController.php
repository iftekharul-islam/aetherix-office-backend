<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EmployeeController extends Controller
{

    public function index()
    {
        try {
            $employees = Employee::with('department')->get();
            return response()->json(['success' => true, 'data' => $employees], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $employee = Employee::with('department')->findOrFail($id);
            return response()->json(['success' => true, 'data' => $employee], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|unique:employees,employee_id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        try {
            $employee = Employee::create($request->all());
            return response()->json(['success' => true, 'data' => $employee], 201);
        }
        catch (\Illuminate\Validation\ValidationException $e) {
        info('Validation failed while creating employee', ['errors' => $e->errors()]);

        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $e->errors()
        ], 422);

    } 
         catch (\Exception $e) {
            info("Employee Saving Error", $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

  
    public function update(Request $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);

            $request->validate([
                'employee_id' => 'sometimes|required|unique:employees,employee_id,' . $id,
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:employees,email,' . $id,
                'department_id' => 'nullable|exists:departments,id',
            ]);

            $employee->update($request->all());
            return response()->json(['success' => true, 'data' => $employee], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

  
    public function destroy($id)
    {
        try {
            $employee = Employee::findOrFail($id);
            $employee->delete();
            return response()->json(['success' => true, 'message' => 'Employee deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
