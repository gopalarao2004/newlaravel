<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\UserBlog;

class BlogController extends Controller
{
    // Generate CloudFront image URL
    private function imageUrl($imagePath)
    {
        if (!$imagePath) {
            return null;
        }

        return rtrim(config('app.cdn_url'), '/') . '/' . ltrim($imagePath, '/');
    }

    // Create a new blog
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'shortdesc' => 'required|string|max:255',
            'longpara' => 'required|string',
            'keywords' => 'nullable|array',
            'keywords.*' => 'string',
            'image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:5120',
        ]);

        $imagePath = null;

        // Upload image to S3
        if ($request->hasFile('image')) {

            $image = $request->file('image');

            $originalName = pathinfo(
                $image->getClientOriginalName(),
                PATHINFO_FILENAME
            );

            $filename = Str::uuid() . '_' .
                Str::slug($originalName) . '.' .
                $image->extension();

            $imagePath = $image->storeAs(
                'blogs',
                $filename,
                's3'
            );
        }

        try {

            $blog = DB::transaction(function () use (
                $request,
                $validated,
                $imagePath
            ) {

                $blog = $request->user()->blogs()->create([
                    'title' => $validated['title'],
                    'shortdesc' => $validated['shortdesc'],
                ]);

                $blog->details()->create([
                    'longpara' => $validated['longpara'],
                    'keywords' => $validated['keywords'] ?? [],
                    'image' => $imagePath,
                ]);

                return $blog->load('details');
            });

        } catch (\Throwable $e) {

            // Delete uploaded S3 image if database insertion fails
            if ($imagePath) {
                Storage::disk('s3')->delete($imagePath);
            }

            throw $e;
        }

        return response()->json([
            'message' => 'Blog created successfully',
            'blog' => $blog,
            'image_url' => $this->imageUrl($imagePath),
        ], 201);
    }


    // Fetch authenticated user's blogs (Dashboard)
    public function index(Request $request)
    {
        $blogs = $request->user()
            ->blogs()
            ->with('details')
            ->latest()
            ->get();

        if ($blogs->isEmpty()) {
            return response()->json([
                'message' => 'There are no posts available. Create your first blog!',
                'blogs' => []
            ], 200);
        }

        $data = $blogs->map(function ($blog) {

            return [
                'postid' => $blog->postid,
                'title' => $blog->title,
                'shortdesc' => $blog->shortdesc,

                'image_url' => $this->imageUrl(
                    $blog->details?->image
                ),

                'created_at' => $blog->created_at,
                'updated_at' => $blog->updated_at,
            ];
        });

        return response()->json([
            'message' => 'Dashboard blogs fetched successfully',
            'blogs' => $data
        ], 200);
    }


    // Fetch a particular blog (Read More)
    public function show(Request $request, $postid)
    {
        $blog = $request->user()
            ->blogs()
            ->with('details')
            ->where('postid', $postid)
            ->first();

        if (!$blog) {
            return response()->json([
                'message' => 'No blog post found with ID ' . $postid,
                'blog' => null
            ], 404);
        }

        // Increment view count
        $blog->details()->increment('viewcount');

        // Reload updated details
        $blog->load('details');

        return response()->json([
            'message' => 'Blog details fetched successfully',

            'blog' => [
                'postid' => $blog->postid,
                'title' => $blog->title,
                'shortdesc' => $blog->shortdesc,
                'longpara' => $blog->details->longpara,
                'keywords' => $blog->details->keywords,
                'viewcount' => $blog->details->viewcount,

                'image_url' => $this->imageUrl(
                    $blog->details->image
                ),
            ]
        ], 200);
    }
}
