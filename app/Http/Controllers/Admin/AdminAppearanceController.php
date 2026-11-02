<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $request->validate([
            'site_logo' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:3072',
            'site_favicon' => 'nullable|file|mimes:ico,png,jpg,jpeg,svg|max:1024',
        ]);

        // Handle Site Logo Upload
        if ($request->hasFile('site_logo') && $request->file('site_logo')->isValid()) {
            $path = $request->file('site_logo')->store('settings', 'public');
            SiteSetting::set('site_logo', $path, 'brand', 'image', 'شعار الموقع');
        }

        // Handle Favicon Upload
        if ($request->hasFile('site_favicon') && $request->file('site_favicon')->isValid()) {
            $path = $request->file('site_favicon')->store('settings', 'public');
            SiteSetting::set('site_favicon', $path, 'brand', 'image', 'أيقونة المتصفح Favicon');
        }

        $inputs = $request->except(['_token', 'site_logo', 'site_favicon']);

        foreach ($inputs as $key => $value) {
            $group = 'general';
            $type = 'text';

            if (str_starts_with($key, 'color_') || str_starts_with($key, 'theme_')) {
                $group = 'appearance';
                $type = 'color';
            } elseif (str_starts_with($key, 'hero_')) {
                $group = 'hero';
            } elseif (str_starts_with($key, 'mobile_')) {
                $group = 'mobile';
                $type = is_numeric($value) || $value === '1' || $value === '0' ? 'boolean' : 'text';
            } elseif (str_starts_with($key, 'contact_')) {
                $group = 'contact';
            } elseif (str_starts_with($key, 'social_')) {
                $group = 'social';
            } elseif (str_starts_with($key, 'footer_')) {
                $group = 'footer';
            } elseif (str_starts_with($key, 'listings_')) {
                $group = 'moderation';
                $type = 'boolean';
            }

            SiteSetting::set($key, $value, $group, $type);
        }

        return back()->with('success', 'تم حفظ وتحديث إعدادات المظهر والواجهة للموقع والتطبيق بنجاح!');
    }
}

