@extends('layouts.admin')

@section('title', 'مراقبة التعليقات | إدارة مزاد سوريا')

@section('content')

<div class="mb-6">
    <h1 class="text-xl font-black text-white">مراقبة التعليقات والاستفسارات</h1>
    <p class="text-xs text-slate-400 mt-0.5">مراجعة استفسارات الزوار والبائعين واعتمادها أو حذفها</p>
</div>

<div class="bg-slate-800/80 rounded-2xl border border-slate-700/80 overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-right text-xs">
            <thead class="bg-slate-900/60 text-slate-400 font-bold border-b border-slate-700">
                <tr>
                    <th class="p-3.5">الكاتب</th>
                    <th class="p-3.5">السلعة</th>
                    <th class="p-3.5">نص التعليق</th>
                    <th class="p-3.5">التاريخ</th>
                    <th class="p-3.5 text-center">الإجراء</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/50">
                @foreach($comments as $c)
                    <tr class="hover:bg-slate-700/30">
                        <td class="p-3.5">
                            <span class="font-bold text-white block">{{ $c->user->name ?? 'مستخدم' }}</span>
                            <span class="text-[10px] text-slate-400">{{ $c->user->email ?? '' }}</span>
                        </td>
                        <td class="p-3.5">
                            @if($c->listing)
                                <a href="{{ route('listings.show', $c->listing->slug) }}" target="_blank" class="font-bold text-amber-400 hover:underline line-clamp-1">
                                    {{ $c->listing->title }}
                                </a>
                            @else
                                <span class="text-slate-500">سلعة محذوفة</span>
                            @endif
                        </td>
                        <td class="p-3.5 text-slate-300 max-w-md">
                            {{ $c->comment }}
                        </td>
                        <td class="p-3.5 text-slate-400 text-[10px]">
                            {{ $c->created_at->diffForHumans() }}
                        </td>
                        <td class="p-3.5 text-center">
                            <form action="{{ route('admin.comments.destroy', $c->id) }}" method="POST" onsubmit="return confirm('حذف هذا التعليق؟');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 bg-red-950 text-red-400 hover:bg-red-900 rounded" title="حذف التعليق">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="p-4 border-t border-slate-700">
        {{ $comments->links() }}
    </div>
</div>

@endsection

