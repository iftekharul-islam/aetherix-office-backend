<?php

namespace App\Http\Controllers;

use App\Models\Division;
use Illuminate\Http\Request;

class DivisionController extends Controller
{
    public function index()
    {
        return Division::with('departments', 'head')->get();
    }

    // public function store(Request $request)
    // {
    //     $data = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'code' => 'required|string|max:20|unique:divisions',
    //         'description' => 'nullable|string',
    //         'head_id' => 'nullable|exists:users,id',
    //     ]);

    //     $division = Division::create($data);

    //     return response()->json($division, 201);
    // }

    public function store(Request $request)
{
    try {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:divisions',
            'description' => 'nullable|string',
            'head_id' => 'nullable|exists:users,id',
        ]);

        $division = Division::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Division created successfully.',
            'data' => $division
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        info(info('division error: ' . $e->getMessage()));
            
       
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $e->errors()
        ], 422);

    } catch (\Exception $e) {
         info('division error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong while creating division.',
            'error' => $e->getMessage()
        ], 500);
    }
}


    public function show(Division $division)
    {
        return $division->load('departments', 'head');
    }

    public function update(Request $request, Division $division)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:20|unique:divisions,code,' . $division->id,
            'description' => 'nullable|string',
            'head_id' => 'nullable|exists:users,id',
        ]);

        $division->update($data);

        return response()->json($division);
    }

    public function destroy(Division $division)
    {
        $division->delete();
        return response()->json(['message' => 'Division deleted successfully']);
    }
}
