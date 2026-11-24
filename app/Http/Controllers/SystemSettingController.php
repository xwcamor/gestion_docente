<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SystemSettingController extends Controller
{
    public function edit()
    {
        $branding = SystemSetting::branding();

        return view('admin.settings.index', compact('branding'));
    }

    public function update(Request $request, AuditLogger $audit)
    {
        $data = $request->validate([
            'system_name' => ['required', 'string', 'max:80'],
            'system_short_name' => ['required', 'string', 'max:8'],
            'system_subtitle' => ['nullable', 'string', 'max:120'],
            'primary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'primary_hover' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $before = SystemSetting::branding();
        foreach ([
            'system_name' => 'system_name',
            'system_short_name' => 'system_short_name',
            'system_subtitle' => 'system_subtitle',
            'primary_color' => 'primary_color',
            'primary_hover' => 'primary_hover',
        ] as $field => $key) {
            SystemSetting::updateOrCreate(['key' => $key], ['value' => $data[$field] ?? '']);
        }

        if ($request->hasFile('logo')) {
            $oldPath = SystemSetting::valueFor('system_logo');
            $newPath = $request->file('logo')->store('system-brand', 'public');
            SystemSetting::updateOrCreate(['key' => 'system_logo'], ['value' => $newPath]);
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $after = SystemSetting::branding();
        $audit->record('configuracion', 'updated', 'Se actualizaron la identidad visual y los datos del sistema.', null, $before, $after);

        return redirect()->route('admin.settings.edit')->with('success', 'La identidad del sistema fue actualizada.');
    }
}
