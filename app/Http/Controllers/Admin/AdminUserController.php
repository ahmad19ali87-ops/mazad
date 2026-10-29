<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::withCount(['listings', 'followers']);

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('q')) {
            $q = '%' . $request->q . '%';
            $query->where(function ($b) use ($q) {
                $b->where('name', 'like', $q)
                  ->orWhere('email', 'like', $q)
                  ->orWhere('phone', 'like', $q);
            });
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function toggleVerification(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $user->is_verified = !$user->is_verified;
        $user->save();

        $msg = $user->is_verified ? 'تم توثيق حساب المستخدم بنجاح ومنحه شارة التوثيق.' : 'تم إلغاء توثيق الحساب.';
        return back()->with('success', $msg);
    }

    public function updateRole(Request $request, int $id): RedirectResponse
    {
        $request->validate(['role' => 'required|in:admin,seller,user']);
        $user = User::findOrFail($id);
        $user->role = $request->role;
        $user->save();

        return back()->with('success', 'تم تحديث دور المستخدم إلى ' . $request->role);
    }

    public function destroy(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        if ($user->id === 1 || $user->email === 'admin@mazad.sy') {
            return back()->with('error', 'لا يمكن حذف الحساب الإداري الرئيسي.');
        }

        $user->delete();
        return back()->with('success', 'تم حذف المستخدم وجميع بياناته بنجاح.');
    }
}

