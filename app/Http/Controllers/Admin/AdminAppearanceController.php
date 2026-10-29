<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAppearanceController extends Controller
{
    public function index(): View
    {
        $settings = SiteSetting::all()->pluck('value', 'key')->toArray();

        return view('admin.appearance.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $inputs = $request->except(['_token']);

        foreach ($inputs as $key => $value) {
            $group = 'general';
            $type = 'text';

            if (str_starts_with($key, 'color_')) {
                $group = 'appearance';
                $type = 'color';
            } elseif (str_starts_with($key, 'hero_')) {
                $group = 'hero';
            } elseif (str_starts_with($key, 'mobile_')) {
                $group = 'mobile';
                $type = is_numeric($value) || $value === '1' || $value === '0' ? 'boolean' : 'text';
            } elseif (str_starts_with($key, 'contact_')) {
                $group = 'contact';
            }

            SiteSetting::set($key, $value, $group, $type);
        }

        return back()->with('success', 'تم حفظ وتحديث إعدادات المظهر والواجهة للموقع والتطبيق بنجاح!');
    }
}

