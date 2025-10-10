<?php

namespace App\Http\Controllers;


use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;


class UserController extends Controller
{
    public function index()
    {
        $users = User::with('department.division', 'supervisor')->get();
        return response()->json($users);
    }



    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'machine_id' => 'nullable',
                'employee_id' => 'nullable|string|unique:users,employee_id',
                'department_id' => 'nullable|exists:departments,id',
                'supervisor_id' => 'nullable|integer|exists:users,id',
                'role' => 'nullable|string|in:employee,admin,supervisor',
                'password' => 'required|string|min:6',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'machine_id' => $validated['machine_id'],
                'employee_id' => $validated['employee_id'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'supervisor_id' => $validated['supervisor_id'] ?? null,
                'role' => $validated['role'],
                'password' => Hash::make($validated['password']),
            ]);

            return response()->json([
                'message' => 'User created successfully!',
                'user' => $user
            ]);
        } catch (ValidationException $e) {

            Log::info('User validation failed: ' . json_encode($e->errors()));
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::info('User creation error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create user'], 500);
        }
    }

    public function show(User $user)
    {
        $user->load('department.division', 'supervisor');
        return response()->json($user);
    }


    // Update an existing user
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
                'employee_id' => ['sometimes', 'required', 'string', 'max:20', Rule::unique('users')->ignore($user->id)],
                'machine_id' => 'sometimes|nullable|string|max:20',
                'department_id' => 'nullable|exists:departments,id',
                'supervisor_id' => 'nullable|integer|exists:users,id',
                'role' => 'sometimes|nullable|string|in:employee,admin,supervisor',
                'password' => 'nullable|string|min:6',
            ]);

            
            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);

            return response()->json([
                'message' => 'User updated successfully!',
                'user' => $user
            ]);
        } catch (ValidationException $e) {
            Log::info('User validation failed: ' . json_encode($e->errors()));
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::info('User update error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update user'], 500);
        }
    }



    // Delete a user
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully!']);
    }


    public function me(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }
            return response()->json($user->only('id', 'name', 'email'));
        } catch (\Exception $e) {
            Log::error('Fetch authenticated user error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch user'], 500);
        }
    }
}
