<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    // Retrieve authenticated user's profile
    public function show(Request $request)
    {
        $user = $request->user()->load('information');

        return response()->json([
            'message' => 'Profile retrieved successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'nickname' => $user->information?->nickname,
                'address' => $user->information?->address,
                'phone_no' => $user->information?->phone_no,
            ]
        ], 200);
    }

    // Update authenticated user's profile
    public function update(Request $request)
    {
        $user = $request->user();

        // Validate profile information
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'name')->ignore($user->id),
            ],

            'email' => [
                'required',
                'email',
                'max:255',
            ],
        ]);

        // Update user information
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        // Reload profile information
        $user->load('information');

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'nickname' => $user->information?->nickname,
                'address' => $user->information?->address,
                'phone_no' => $user->information?->phone_no,
            ]
        ], 200);
    }
}
