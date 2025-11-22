<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LessonProgress;
use App\Models\Lesson;
use Illuminate\Http\Request;

class LessonProgressController extends Controller
{
    /**
     * Update lesson progress
     */
    public function update(Request $request, $lessonId)
    {
        $validated = $request->validate([
            'watched_duration' => 'required|integer|min:0',
            'completed' => 'sometimes|boolean',
        ]);

        $user = $request->user();
        $lesson = Lesson::findOrFail($lessonId);

        // Check if user is enrolled in the course
        if (!$user->isEnrolledIn($lesson->course_id)) {
            return response()->json([
                'message' => 'You are not enrolled in this course'
            ], 403);
        }

        $progress = LessonProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $lessonId,
            ],
            [
                'watched_duration' => $validated['watched_duration'],
                'completed' => $validated['completed'] ?? false,
                'completed_at' => ($validated['completed'] ?? false) ? now() : null,
            ]
        );

        return response()->json($progress);
    }

    /**
     * Get progress for a lesson
     */
    public function show(Request $request, $lessonId)
    {
        $user = $request->user();

        $progress = LessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $lessonId)
            ->first();

        if (!$progress) {
            return response()->json([
                'completed' => false,
                'watched_duration' => 0,
            ]);
        }

        return response()->json($progress);
    }
}
