<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Services\Content\StaticContent;
use App\Services\Preferences\PreferenceService;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

// Konfigurasi aplikasi yang boleh diubah admin. @2026-Alifiandra
class SettingController extends Controller
{
    public function edit(Request $request, PreferenceService $prefs, StaticContent $content)
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);

        return view('admin.settings', [
            'methods' => $prefs->methods(),
            'cities' => $content->cities(),
            'values' => [
                'prayer.default_method' => AppSetting::get('prayer.default_method', config('almara.default_prayer_method')),
                'hijri.adjustment' => (int) AppSetting::get('hijri.adjustment', config('almara.hijri_adjustment')),
                'location.default_city' => AppSetting::get('location.default_city', config('almara.default_city')),
            ],
        ]);
    }

    public function update(Request $request, PreferenceService $prefs, StaticContent $content)
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);
        $data = $request->validate([
            'default_method' => ['required', Rule::in(array_keys($prefs->methods()))],
            'hijri_adjustment' => ['required', 'integer', 'between:-2,2'],
            'default_city' => ['required', Rule::in(array_column($content->cities(), 'slug'))],
        ]);
        AppSetting::put('prayer.default_method', $data['default_method'], $request->user()->id);
        AppSetting::put('hijri.adjustment', (int) $data['hijri_adjustment'], $request->user()->id);
        AppSetting::put('location.default_city', $data['default_city'], $request->user()->id);
        Cache::forget('prayer_methods');
        Audit::log('settings.updated', null, $data);

        return back()->with('status', 'Pengaturan aplikasi disimpan.');
    }
}
