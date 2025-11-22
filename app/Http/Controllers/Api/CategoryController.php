<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Get all categories (public)
     */
    public function index()
    {
        $categories = Category::withCount('courses')->get();
        return response()->json($categories);
    }

    /**
     * Admin: Create category
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:categories,name',
            'description' => 'nullable|string',
            'slug' => 'sometimes|string|unique:categories,slug',
        ]);

        if (!isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category = Category::create($validated);

        return response()->json($category, 201);
    }

    /**
     * Admin: Update category
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|unique:categories,name,' . $id,
            'description' => 'nullable|string',
            'slug' => 'sometimes|string|unique:categories,slug,' . $id,
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    /**
     * Admin: Delete category
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully'
        ]);
    }
}
