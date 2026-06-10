<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(Category::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:80|unique:categories,name',
            'color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $category = Category::create([
            'name'  => trim($request->name),
            'color' => $request->color ?? '#6366f1',
        ]);

        ActivityLogger::categoryCreated($category->id, [
            'name'  => $category->name,
            'color' => $category->color,
        ]);

        return response()->json($category, 201);
    }

    public function destroy(Category $category)
    {
        $logDetails = [
            'name'  => $category->name,
            'color' => $category->color,
        ];
        $categoryId = $category->id;

        // Détache les EDL (met category_id à null via nullOnDelete en DB)
        $category->delete();

        ActivityLogger::categoryDeleted($categoryId, $logDetails);

        return response()->json(['deleted' => true]);
    }
}

