<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserBlog;
use Illuminate\Http\Request;

class PublicBlogController extends Controller
{
    // Generate CloudFront image URL
    private function imageUrl($imagePath)
    {
        if (!$imagePath) {
            return null;
        }

        return rtrim(config('app.cdn_url'), '/') . '/'
            . ltrim($imagePath, '/');
    }

    // Fetch and search public blogs
    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = UserBlog::with(['details', 'user']);

        // Search by title or short description
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('shortdesc', 'LIKE', "%{$search}%");
            });
        }

        $blogs = $query->latest()->paginate(10);

        if ($blogs->isEmpty()) {
            return response()->json([
                'message' => $search
                    ? 'No blogs found matching your search.'
                    : 'No blog posts are available yet.',
                'blogs' => [],
                'pagination' => [
                    'current_page' => $blogs->currentPage(),
                    'last_page' => $blogs->lastPage(),
                    'total' => $blogs->total(),
                    'per_page' => $blogs->perPage(),
                ],
            ], 200);
        }

        $data = $blogs->getCollection()->map(function ($blog) {
            return [
                'postid' => $blog->postid,
                'title' => $blog->title,
                'shortdesc' => $blog->shortdesc,
                'author' => $blog->user->name ?? 'Unknown',

                // CloudFront image URL
                'image_url' => $this->imageUrl(
                    $blog->details?->image
                ),

                'created_at' => $blog->created_at,
            ];
        });

        return response()->json([
            'message' => 'Public blogs fetched successfully',
            'blogs' => $data,
            'pagination' => [
                'current_page' => $blogs->currentPage(),
                'last_page' => $blogs->lastPage(),
                'total' => $blogs->total(),
                'per_page' => $blogs->perPage(),
            ]
        ], 200);
    }


    // Fetch a single public blog
    public function show($postid)
    {
        $blog = UserBlog::with(['details', 'user'])
            ->where('postid', $postid)
            ->first();

        if (!$blog) {
            return response()->json([
                'message' => 'Blog post not found.',
                'blog' => null
            ], 404);
        }

        // Increment view count
        if ($blog->details) {
            $blog->details()->increment('viewcount');
            $blog->load('details');
        }

        return response()->json([
            'message' => 'Public blog details fetched successfully',

            'blog' => [
                'postid' => $blog->postid,
                'title' => $blog->title,
                'shortdesc' => $blog->shortdesc,
                'longpara' => $blog->details?->longpara,
                'keywords' => $blog->details?->keywords ?? [],
                'viewcount' => $blog->details?->viewcount ?? 0,

                // CloudFront image URL
                'image_url' => $this->imageUrl(
                    $blog->details?->image
                ),

                'author' => $blog->user->name ?? 'Unknown',
                'created_at' => $blog->created_at,
            ]
        ], 200);
    }
}
