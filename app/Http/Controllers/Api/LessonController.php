<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Course;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    /**
     * Get lessons for a course
     */
    public function index($courseId)
    {
        $lessons = Lesson::where('course_id', $courseId)
            ->orderBy('position')
            ->get();

        return response()->json($lessons);
    }

    /**
     * Admin: Create lesson
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'course_id' => 'required|exists:courses,id',
            'video_source' => 'required|in:UPLOAD,DRIVE,YOUTUBE,VIMEO',
            'video_url' => 'required|string',
            'duration' => 'required|integer|min:0',
            'position' => 'sometimes|integer|min:0',
            'is_preview' => 'sometimes|boolean',
        ]);

        // Auto-set position if not provided
        if (!isset($validated['position'])) {
            $maxPosition = Lesson::where('course_id', $validated['course_id'])->max('position');
            $validated['position'] = $maxPosition ? $maxPosition + 1 : 0;
        }

        $lesson = Lesson::create($validated);

        return response()->json($lesson, 201);
    }

    /**
     * Admin: Update lesson
     */
    public function update(Request $request, $id)
    {
        $lesson = Lesson::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'video_source' => 'sometimes|in:UPLOAD,DRIVE,YOUTUBE,VIMEO',
            'video_url' => 'sometimes|string',
            'duration' => 'sometimes|integer|min:0',
            'position' => 'sometimes|integer|min:0',
            'is_preview' => 'sometimes|boolean',
        ]);

        $lesson->update($validated);

        return response()->json($lesson);
    }

    /**
     * Admin: Delete lesson
     */
    public function destroy($id)
    {
        $lesson = Lesson::findOrFail($id);
        $lesson->delete();

        return response()->json([
            'message' => 'Lesson deleted successfully'
        ]);
    }

    /**
     * Admin: Reorder lessons
     */
    public function reorder(Request $request, $courseId)
    {
        $validated = $request->validate([
            'lessons' => 'required|array',
            'lessons.*.id' => 'required|exists:lessons,id',
            'lessons.*.position' => 'required|integer|min:0',
        ]);

        foreach ($validated['lessons'] as $lessonData) {
            Lesson::where('id', $lessonData['id'])
                ->where('course_id', $courseId)
                ->update(['position' => $lessonData['position']]);
        }

        return response()->json([
            'message' => 'Lessons reordered successfully'
        ]);
    }
}
