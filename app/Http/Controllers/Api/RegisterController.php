<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        // Validate all registration fields
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:users,name',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',

            'address' => 'required|string|max:255',
            'phone_no' => 'required|string|max:20',
            'nickname' => 'required|string|max:100',
        ], [
            'name.unique' => 'This username is already taken.',
            'email.email' => 'Please enter a valid email address.',
            'password.confirmed' => 'Passwords do not match.',
        ]);

        try {

            $user = DB::transaction(function () use ($validated) {

                // Insert into users table
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                ]);

                // Insert into user_information table
                $user->information()->create([
                    'address' => $validated['address'],
                    'phone_no' => $validated['phone_no'],
                    'nickname' => $validated['nickname'],
                ]);

                return $user;
            });

            return response()->json([
                'message' => 'Registration successful!',
                'data' => [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]
            ], 201);

        } catch (\Throwable $e) {

            report($e);

            return response()->json([
                'message' => 'Registration failed. Please try again.'
            ], 500);
        }
    }
}

