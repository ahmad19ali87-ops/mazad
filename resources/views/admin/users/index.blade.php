@extends('layouts.admin')

@section('title', 'إدارة المستخدمين والبائعين | إدارة مزاد سوريا')

@section('content')

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h1 class="text-xl font-black text-white">إدارة المستخدمين والبائعين</h1>
        <p class="text-xs text-slate-400 mt-0.5">توثيق الحسابات، ترقية البائعين، وإدارة الصلاحيات</p>
    </div>
</div>

<div class="bg-slate-800/80 rounded-2xl border border-slate-700/80 overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-right text-xs">
            <thead class="bg-slate-900/60 text-slate-400 font-bold border-b border-slate-700">
                <tr>
                    <th class="p-3.5">المستخدم</th>
                    <th class="p-3.5">البريد والهاتف</th>
                    <th class="p-3.5">الدور</th>
                    <th class="p-3.5">التوثيق</th>
                    <th class="p-3.5">السلع المعروضة</th>
                    <th class="p-3.5">المتابعين</th>
                    <th class="p-3.5 text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/50">
                @foreach($users as $user)
                    <tr class="hover:bg-slate-700/30">
                        <td class="p-3.5">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-full bg-slate-700 text-white font-bold flex items-center justify-center">
                                    {{ mb_substr($user->name, 0, 1) }}
                                </div>
                                <div>
                                    <span class="font-bold text-white block">{{ $user->name }}</span>
                                    <span class="text-[10px] text-slate-400">{{ $user->governorate ?? 'غير محدد' }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="p-3.5 text-slate-300">
                            <div>{{ $user->email }}</div>
                            <div class="text-[10px] text-slate-400">{{ $user->phone }}</div>
                        </td>
                        <td class="p-3.5">
                            <form action="{{ route('admin.users.role', $user->id) }}" method="POST" class="inline">
                                @csrf
                                <select name="role" class="bg-slate-900 border border-slate-700 text-white rounded px-2 py-1 text-[11px] cursor-pointer" onchange="this.form.submit()">
                                    <option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>مستخدم عادي</option>
                                    <option value="seller" {{ $user->role === 'seller' ? 'selected' : '' }}>بائع معتمد</option>
                                    <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>مدير نظام</option>
                                </select>
                            </form>
                        </td>
                        <td class="p-3.5">
                            <form action="{{ route('admin.users.verify', $user->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-2.5 py-1 rounded-lg text-[10px] font-bold transition flex items-center gap-1 {{ $user->is_verified ? 'bg-blue-900/60 text-blue-400 border border-blue-800' : 'bg-slate-700 text-slate-400' }}">
                                    <i class="fa-solid fa-circle-check"></i>
                                    <span>{{ $user->is_verified ? 'موثق رسمياً' : 'غير موثق' }}</span>
                                </button>
                            </form>
                        </td>
                        <td class="p-3.5 font-bold text-slate-300">
                            {{ $user->listings_count }} سلعة
                        </td>
                        <td class="p-3.5 font-bold text-amber-400">
                            {{ $user->followers_count }} متابع
                        </td>
                        <td class="p-3.5 text-center">
                            @if($user->id !== 1 && $user->email !== 'admin@mazad.sy')
                                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('حذف هذا المستخدم وجميع سلعته وبياناته نهائياً؟');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 bg-red-950 text-red-400 hover:bg-red-900 rounded" title="حذف المستخدم">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            @else
                                <span class="text-[10px] text-slate-500 font-bold">حساب رئيسي</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="p-4 border-t border-slate-700">
        {{ $users->links() }}
    </div>
</div>

@endsection

