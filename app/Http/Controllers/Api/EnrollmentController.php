<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Course;
use App\Models\LessonProgress;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    /**
     * Get user's enrollments (my courses)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $enrollments = Enrollment::where('user_id', $user->id)
            ->with(['course.instructor', 'course.lessons'])
            ->latest('enrolled_at')
            ->get();

        return response()->json($enrollments);
    }

    /**
     * Enroll in a course (called after payment)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
        ]);

        $user = $request->user();

        // Check if already enrolled
        $existing = Enrollment::where('user_id', $user->id)
            ->where('course_id', $validated['course_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Already enrolled in this course',
                'enrollment' => $existing
            ], 200);
        }

        $enrollment = Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $validated['course_id'],
            'enrolled_at' => now(),
        ]);

        $enrollment->load('course');

        return response()->json($enrollment, 201);
    }

    /**
     * Get enrollment details
     */
    public function show(Request $request, $courseId)
    {
        $user = $request->user();

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->with(['course.lessons'])
            ->firstOrFail();

        // Get lesson progress
        $lessonIds = $enrollment->course->lessons->pluck('id');
        $progress = LessonProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonIds)
            ->get()
            ->keyBy('lesson_id');

        $enrollment->course->lessons->transform(function ($lesson) use ($progress) {
            $lesson->user_progress = $progress->get($lesson->id);
            return $lesson;
        });

        return response()->json($enrollment);
    }
}
