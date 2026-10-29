<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminCategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::withCount(['subcategories', 'listings'])->orderBy('sort_order')->get();
        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:100',
            'name_en' => 'nullable|string|max:100',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['slug'] = Str::slug($validated['name_en'] ?: $validated['name_ar']) . '-' . rand(100, 999);
        $validated['is_active'] = true;

        Category::create($validated);

        return back()->with('success', 'تمت إضافة القسم الرئيسي بنجاح.');
    }

    public function storeSubcategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name_ar' => 'required|string|max:100',
            'name_en' => 'nullable|string|max:100',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['slug'] = Str::slug($validated['name_en'] ?: $validated['name_ar']) . '-' . rand(100, 999);
        $validated['is_active'] = true;

        Subcategory::create($validated);

        return back()->with('success', 'تمت إضافة القسم الفرعي بنجاح.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return back()->with('success', 'تم حذف القسم بنجاح.');
    }

    public function destroySubcategory(int $id): RedirectResponse
    {
        $sub = Subcategory::findOrFail($id);
        $sub->delete();

        return back()->with('success', 'تم حذف القسم الفرعي بنجاح.');
    }
}

