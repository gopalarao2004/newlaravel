<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    public function upload(Request $request)
    {
        // Validate image
        $request->validate([
            'image' => 'required|image|mimes:png,jpg,jpeg,webp|max:5120',
        ]);

        // Get uploaded image
        $image = $request->file('image');

        // Get original filename without extension
        $originalName = pathinfo(
            $image->getClientOriginalName(),
            PATHINFO_FILENAME
        );

        // Generate unique filename
        $filename = Str::uuid() . '_' .
                    Str::slug($originalName) . '.' .
                    $image->getClientOriginalExtension();

        // Upload image directly to AWS S3
        $path = $image->storeAs('blogs', $filename, 's3');

        // Return response
        return response()->json([
            'message' => 'Image uploaded successfully',
            'image_path' => $path,
        ], 201);
    }
}
