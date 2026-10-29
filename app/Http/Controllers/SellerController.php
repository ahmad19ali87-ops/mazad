<?php

namespace App\Http\Controllers;

use App\Models\Follow;
use App\Models\InAppNotification;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SellerController extends Controller
{
    public function show(int $id): View
    {
        $seller = User::withCount(['listings' => function ($q) {
            $q->where('status', 'active');
        }, 'followers'])->findOrFail($id);

        $listings = Listing::with(['category', 'governorate', 'region', 'images'])
            ->where('user_id', $seller->id)
            ->where('status', 'active')
            ->latest()
            ->paginate(12);

        $isFollowing = Auth::check() && Auth::user()->isFollowing($seller->id);

        return view('sellers.show', compact('seller', 'listings', 'isFollowing'));
    }

    public function toggleFollow(Request $request, int $id): RedirectResponse|JsonResponse
    {
        if (!Auth::check()) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            return redirect()->route('login')->with('info', 'يرجى تسجيل الدخول لتتمكن من متابعة البائع.');
        }

        $follower = Auth::user();

        if ($follower->id === (int)$id) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Cannot follow yourself'], 422);
            }
            return back()->with('error', 'لا يمكنك متابعة نفسك!');
        }

        $seller = User::findOrFail($id);

        $existing = Follow::where('follower_id', $follower->id)
            ->where('seller_id', $seller->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $isFollowing = false;
            $msg = 'تم إلغاء متابعة ' . $seller->name;
        } else {
            Follow::create([
                'follower_id' => $follower->id,
                'seller_id' => $seller->id,
            ]);
            $isFollowing = true;
            $msg = 'تمت متابعة ' . $seller->name . ' بنجاح! ستصلك إشعارات عند إضافة أي سلعة جديدة.';

            // Notify the seller that someone followed them
            InAppNotification::create([
                'user_id' => $seller->id,
                'title' => 'متابع جديد!',
                'message' => 'بدأ ' . $follower->name . ' بمتابعتك.',
                'type' => 'new_follower',
                'action_url' => '/sellers/' . $seller->id,
                'is_read' => false,
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_following' => $isFollowing,
                'followers_count' => $seller->followers()->count(),
                'message' => $msg,
            ]);
        }

        return back()->with('success', $msg);
    }
}

