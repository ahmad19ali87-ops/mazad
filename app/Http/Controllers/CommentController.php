<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\InAppNotification;
use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function store(Request $request, int $listingId): RedirectResponse|JsonResponse
    {
        if (!Auth::check()) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            return redirect()->route('login')->with('info', 'يرجى تسجيل الدخول أولاً لإضافة تعليق.');
        }

        $validated = $request->validate([
            'comment' => 'required|string|min:2|max:1000',
            'parent_id' => 'nullable|exists:comments,id',
        ], [
            'comment.required' => 'يرجى كتابة نص التعليق أو الاستفسار',
        ]);

        $listing = Listing::findOrFail($listingId);

        if (!$listing->allow_comments) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'التعليقات مغلقة على هذه السلعة'], 403);
            }
            return back()->with('error', 'التعليقات مغلقة على هذه السلعة من قبل البائع.');
        }

        // Sanitize comment text
        $cleanComment = strip_tags($validated['comment']);

        $comment = Comment::create([
            'listing_id' => $listing->id,
            'user_id' => Auth::id(),
            'parent_id' => $validated['parent_id'] ?? null,
            'comment' => $cleanComment,
            'is_approved' => true,
        ]);

        // Notify listing owner if comment is not by owner
        if ($listing->user_id !== Auth::id()) {
            InAppNotification::create([
                'user_id' => $listing->user_id,
                'title' => 'تعليق جديد على سلعتك',
                'message' => 'علق ' . Auth::user()->name . ' على إعلانك "' . $listing->title . '"',
                'type' => 'new_comment',
                'action_url' => '/listings/' . $listing->slug . '#comment-' . $comment->id,
                'data' => ['listing_id' => $listing->id, 'comment_id' => $comment->id],
                'is_read' => false,
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'comment' => $comment->load('user'),
                'message' => 'تمت إضافة التعليق بنجاح.',
            ]);
        }

        return back()->with('success', 'تمت إضافة تعليقك بنجاح.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $comment = Comment::findOrFail($id);

        if (!Auth::check() || (Auth::id() !== $comment->user_id && !Auth::user()->isAdmin())) {
            abort(403);
        }

        $comment->delete();

        return back()->with('success', 'تم حذف التعليق.');
    }
}

