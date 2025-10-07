<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }



    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json(['message' => 'Invalid Credentials'], 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
                'user' => $user,
            ]);
        } catch (\Throwable $e) {
            // Log the error message for debugging
            info('Login Error: ' . $e->getMessage());

            // Return a generic error response
            return response()->json([
                'message' => 'Something went wrong. Please try again later.'
            ], 500);
        }
    }




    // public function register(Request $request)
    // {

    //     $request->validate([
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|string|email|max:255|unique:users',
    //         'password' => 'required|string|min:6|confirmed',
    //     ]);


    //     $user = User::create([
    //         'name' => $request->name,
    //         'email' => $request->email,
    //         'password' => Hash::make($request->password),
    //     ]);


    //     auth()->login($user);

    //     return response()->json([
    //         'message' => 'User registered successfully!',
    //         'user' => $user,
    //     ], 201);
    // }


    public function logout(Request $request)
    {
        try {
            // $request->user()->tokens()->delete();

            // info('User logged out successfully', ['user_id' => $request->user()->id]);

            // return response()->json([
            //     'message' => 'Logged out successfully'
            // ]);


            $user = $request->user();

            if (!$user) {
                return response()->json(['message' => 'No authenticated user'], 401);
            }

            $user->tokens()->delete(); 

            return response()->json(['message' => 'Logged out successfully']);
        } catch (\Exception $e) {
            info('User logged out successfully', $e->getMessage());

            return response()->json([
                'message' => 'Logout failed. Please try again.'
            ], 500);
        }
    }
}
