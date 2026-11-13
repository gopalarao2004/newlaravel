<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogManageController extends Controller
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

    // Update an existing blog
    public function update(Request $request, $postid)
    {
        // Validate incoming data
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'shortdesc' => 'sometimes|required|string|max:255',
            'longpara' => 'sometimes|required|string',
            'keywords' => 'sometimes|nullable|array',
            'keywords.*' => 'string',
            'image' => 'sometimes|nullable|image|mimes:png,jpg,jpeg,webp|max:5120',
        ]);

        // Find only the authenticated user's blog
        $blog = $request->user()
            ->blogs()
            ->with('details')
            ->where('postid', $postid)
            ->firstOrFail();

        $oldImagePath = $blog->details?->image;
        $newImagePath = null;

        // Upload replacement image to S3
        if ($request->hasFile('image')) {
            $image = $request->file('image');

            $originalName = pathinfo(
                $image->getClientOriginalName(),
                PATHINFO_FILENAME
            );

            $filename = Str::uuid() . '_'
                . Str::slug($originalName) . '.'
                . $image->extension();

            $newImagePath = $image->storeAs(
                'blogs',
                $filename,
                's3'
            );

            if (!$newImagePath) {
                return response()->json([
                    'message' => 'Image upload failed'
                ], 500);
            }
        }

        try {
            $blog = DB::transaction(function () use (
                $blog,
                $validated,
                $newImagePath
            ) {
                // Update main blog fields
                $blog->update(
                    array_intersect_key($validated, array_flip([
                        'title',
                        'shortdesc'
                    ]))
                );

                // Prepare blog details
                $detailsData = array_intersect_key(
                    $validated,
                    array_flip([
                        'longpara',
                        'keywords'
                    ])
                );

                // Update image only when a new image is uploaded
                if ($newImagePath) {
                    $detailsData['image'] = $newImagePath;
                }

                // Update or create blog details
                if (!empty($detailsData)) {
                    $blog->details()->updateOrCreate(
                        [],
                        $detailsData
                    );
                }

                return $blog->load('details');
            });

        } catch (\Throwable $e) {

            // Delete newly uploaded S3 image if database update fails
            if ($newImagePath) {
                Storage::disk('s3')->delete($newImagePath);
            }

            throw $e;
        }

        // Delete old S3 image after successful database update
        if ($newImagePath && $oldImagePath) {
            Storage::disk('s3')->delete($oldImagePath);
        }

        return response()->json([
            'message' => 'Blog updated successfully',

            'blog' => [
                'postid' => $blog->postid,
                'title' => $blog->title,
                'shortdesc' => $blog->shortdesc,

                'longpara' => $blog->details?->longpara,
                'keywords' => $blog->details?->keywords,

                'image_url' => $this->imageUrl(
                    $blog->details?->image
                ),
            ]
        ], 200);
    }


    // Delete an existing blog
    public function destroy(Request $request, $postid)
    {
        // Find only the authenticated user's blog
        $blog = $request->user()
            ->blogs()
            ->with('details')
            ->where('postid', $postid)
            ->firstOrFail();

        // Get S3 image path before deleting database records
        $imagePath = $blog->details?->image;

        // Delete database records inside a transaction
        DB::transaction(function () use ($blog) {

            // Delete blog details first
            $blog->details()->delete();

            // Delete main blog record
            $blog->delete();
        });

        // Delete image from S3 after successful database deletion
        if ($imagePath) {
            Storage::disk('s3')->delete($imagePath);
        }

        return response()->json([
            'message' => 'Blog deleted successfully',
            'postid' => $postid
        ], 200);
    }
}
