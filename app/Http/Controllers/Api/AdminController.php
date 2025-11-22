<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function dashboardStats()
    {
        $totalCourses = Course::count();
        $publishedCourses = Course::where('status', 'PUBLISHED')->count();
        $draftCourses = Course::where('status', 'DRAFT')->count();
        
        $totalStudents = User::where('role', 'STUDENT')->count();
        $totalInstructors = User::where('role', 'INSTRUCTOR')->count();
        
        $totalEnrollments = Enrollment::count();
        $totalRevenue = Enrollment::join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->sum('courses.price');
        
        $totalReviews = Review::count();
        $averageRating = Review::avg('rating');

        // Monthly enrollment data (last 12 months)
        $monthlyData = Enrollment::select(
                DB::raw('DATE_FORMAT(enrolled_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as enrollment_count'),
                DB::raw('SUM(courses.price) as revenue')
            )
            ->join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->where('enrolled_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Top courses by enrollment
        $topCourses = Course::withCount('enrollments')
            ->orderBy('enrollments_count', 'desc')
            ->limit(5)
            ->get();

        // Recent enrollments
        $recentEnrollments = Enrollment::with(['user', 'course'])
            ->latest('enrolled_at')
            ->limit(10)
            ->get();

        return response()->json([
            'stats' => [
                'total_courses' => $totalCourses,
                'published_courses' => $publishedCourses,
                'draft_courses' => $draftCourses,
                'total_students' => $totalStudents,
                'total_instructors' => $totalInstructors,
                'total_enrollments' => $totalEnrollments,
                'total_revenue' => $totalRevenue,
                'total_reviews' => $totalReviews,
                'average_rating' => round($averageRating, 2),
            ],
            'monthly_data' => $monthlyData,
            'top_courses' => $topCourses,
            'recent_enrollments' => $recentEnrollments,
        ]);
    }

    /**
     * Get all students
     */
    public function students(Request $request)
    {
        $query = User::where('role', 'STUDENT')
            ->withCount('enrollments');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $students = $query->latest()->paginate(15);

        return response()->json($students);
    }

    /**
     * Get all admins
     */
    public function admins()
    {
        $admins = User::whereIn('role', ['ADMIN', 'SUPER_ADMIN'])
            ->latest()
            ->get();

        return response()->json($admins);
    }

    /**
     * Promote user to admin
     */
    public function promoteToAdmin(Request $request, $userId)
    {
        $validated = $request->validate([
            'role' => 'required|in:ADMIN,SUPER_ADMIN',
        ]);

        $user = User::findOrFail($userId);

        // Only super admins can create other super admins
        if ($validated['role'] === 'SUPER_ADMIN' && !$request->user()->isSuperAdmin()) {
            return response()->json([
                'message' => 'Only super admins can create other super admins'
            ], 403);
        }

        $user->update(['role' => $validated['role']]);

        return response()->json($user);
    }

    /**
     * Demote admin to student
     */
    public function demoteAdmin(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        // Cannot demote yourself
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'You cannot demote yourself'
            ], 403);
        }

        $user->update(['role' => 'STUDENT']);

        return response()->json($user);
    }

    /**
     * Get all orders/enrollments
     */
    public function orders(Request $request)
    {
        $query = Enrollment::with(['user', 'course']);

        if ($request->has('status')) {
            if ($request->status === 'completed') {
                $query->whereNotNull('completed_at');
            } else if ($request->status === 'in-progress') {
                $query->whereNull('completed_at');
            }
        }

        $orders = $query->latest('enrolled_at')->paginate(20);

        return response()->json($orders);
    }
}
