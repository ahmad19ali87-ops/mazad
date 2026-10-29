<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminCommentController extends Controller
{
    public function index(): View
    {
        $comments = Comment::with(['user', 'listing'])->latest()->paginate(30);
        return view('admin.comments.index', compact('comments'));
    }

    public function toggleApproval(int $id): RedirectResponse
    {
        $comment = Comment::findOrFail($id);
        $comment->is_approved = !$comment->is_approved;
        $comment->save();

        return back()->with('success', 'تم تعديل حالة اعتماد التعليق.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $comment = Comment::findOrFail($id);
        $comment->delete();

        return back()->with('success', 'تم حذف التعليق بنجاح.');
    }
}

