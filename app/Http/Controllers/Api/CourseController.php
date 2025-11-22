<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    /**
     * Get all published courses (public)
     */
    public function index(Request $request)
    {
        $query = Course::published()
            ->with(['instructor', 'category', 'reviews']);

        // Filter by category
        if ($request->has('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Search by title or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Limit results
        $limit = $request->get('limit', 12);

        $courses = $query->paginate($limit);

        // Add calculated fields
        $courses->getCollection()->transform(function ($course) {
            $course->average_rating = $course->averageRating();
            $course->enrollment_count = $course->enrollmentCount();
            return $course;
        });

        return response()->json($courses);
    }

    /**
     * Get single course by slug (public)
     */
    public function show($slug)
    {
        $course = Course::where('slug', $slug)
            ->published()
            ->with(['instructor', 'category', 'lessons', 'reviews.user'])
            ->firstOrFail();

        $course->average_rating = $course->averageRating();
        $course->enrollment_count = $course->enrollmentCount();

        return response()->json($course);
    }

    /**
     * Get course for learning (requires enrollment)
     */
    public function learn(Request $request, $slug)
    {
        $user = $request->user();
        $course = Course::where('slug', $slug)->firstOrFail();

        // Check enrollment
        if (!$user->isEnrolledIn($course->id)) {
            return response()->json([
                'message' => 'You are not enrolled in this course'
            ], 403);
        }

        $course->load(['instructor', 'lessons']);

        // Get user's progress for each lesson
        $lessons = $course->lessons->map(function ($lesson) use ($user) {
            $progress = $lesson->progressForUser($user->id);
            $lesson->user_progress = $progress;
            return $lesson;
        });

        $course->lessons = $lessons;

        return response()->json($course);
    }

    /**
     * Admin: Get all courses (including drafts)
     */
    public function adminIndex(Request $request)
    {
        $query = Course::with(['instructor', 'category']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $courses = $query->latest()->paginate(15);

        return response()->json($courses);
    }

    /**
     * Admin: Create new course
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'sometimes|string|unique:courses,slug',
            'description' => 'nullable|string',
            'thumbnail_url' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:DRAFT,PUBLISHED,ARCHIVED',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        // Auto-generate slug if not provided
        if (!isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $validated['instructor_id'] = $request->user()->id;

        $course = Course::create($validated);

        return response()->json($course, 201);
    }

    /**
     * Admin: Update course
     */
    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|unique:courses,slug,' . $id,
            'description' => 'nullable|string',
            'thumbnail_url' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:DRAFT,PUBLISHED,ARCHIVED',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $course->update($validated);

        return response()->json($course);
    }

    /**
     * Admin: Delete course
     */
    public function destroy($id)
    {
        $course = Course::findOrFail($id);
        $course->delete();

        return response()->json([
            'message' => 'Course deleted successfully'
        ]);
    }
}
