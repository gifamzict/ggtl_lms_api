<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Course;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Get reviews for a course
     */
    public function index($courseId)
    {
        $reviews = Review::where('course_id', $courseId)
            ->with('user')
            ->latest()
            ->paginate(10);

        return response()->json($reviews);
    }

    /**
     * Submit a review
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $user = $request->user();

        // Check if user is enrolled
        if (!$user->isEnrolledIn($validated['course_id'])) {
            return response()->json([
                'message' => 'You must be enrolled in this course to leave a review'
            ], 403);
        }

        // Check if already reviewed
        $existing = Review::where('user_id', $user->id)
            ->where('course_id', $validated['course_id'])
            ->first();

        if ($existing) {
            // Update existing review
            $existing->update([
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]);

            return response()->json($existing);
        }

        // Create new review
        $review = Review::create([
            'user_id' => $user->id,
            'course_id' => $validated['course_id'],
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        $review->load('user');

        return response()->json($review, 201);
    }

    /**
     * Delete a review
     */
    public function destroy(Request $request, $id)
    {
        $review = Review::findOrFail($id);

        // Check ownership
        if ($review->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully'
        ]);
    }
}
