@extends('layouts.app')
@section('title', 'Ajustes del sistema')
@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">Administración</p>
        <h1>Ajustes del sistema</h1>
        <p class="subhead">Personaliza el nombre, logotipo y colores que identifican la aplicación.</p>
    </div>
</div>

<section class="card" style="max-width:900px">
    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" data-loading-form="1">
        @csrf
        @method('PATCH')
        <div class="form-grid">
            <div class="form-field">
                <label for="system_name">Nombre del sistema</label>
                <input class="field" id="system_name" name="system_name" value="{{ old('system_name', $branding['name']) }}" maxlength="80" required>
                @error('system_name')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-field">
                <label for="system_short_name">Nombre corto / iniciales</label>
                <input class="field" id="system_short_name" name="system_short_name" value="{{ old('system_short_name', $branding['short_name']) }}" maxlength="8" required>
                @error('system_short_name')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-field">
                <label for="system_subtitle">Descripción breve</label>
                <input class="field" id="system_subtitle" name="system_subtitle" value="{{ old('system_subtitle', $branding['subtitle']) }}" maxlength="120">
                @error('system_subtitle')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-field">
                <label for="logo">Logotipo (PNG, JPG o WEBP, máximo 2 MB)</label>
                <input class="field" id="logo" type="file" name="logo" accept=".png,.jpg,.jpeg,.webp">
                @error('logo')<p class="field-error">{{ $message }}</p>@enderror
                @if($branding['logo_path'])
                    <div style="margin-top:10px"><img src="{{ asset('storage/'.$branding['logo_path']) }}" alt="Logo actual" style="max-width:90px;max-height:70px;object-fit:contain"></div>
                @endif
            </div>
            <div class="form-field">
                <label for="primary_color">Color principal</label>
                <input class="field" id="primary_color" type="color" name="primary_color" value="{{ old('primary_color', $branding['primary_color']) }}" style="height:44px;padding:4px">
                @error('primary_color')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-field">
                <label for="primary_hover">Color principal al pasar el cursor</label>
                <input class="field" id="primary_hover" type="color" name="primary_hover" value="{{ old('primary_hover', $branding['primary_hover']) }}" style="height:44px;padding:4px">
                @error('primary_hover')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="settings-accordion" style="margin-top:18px;padding:13px 15px">
            <strong>Vista previa</strong>
            <div style="display:flex;align-items:center;gap:12px;margin-top:10px">
                <span id="brand-preview-mark" class="brand-mark" style="background:{{ $branding['primary_color'] }}">{{ $branding['short_name'] }}</span>
                <div><strong id="brand-preview-name">{{ $branding['name'] }}</strong><div id="brand-preview-subtitle" class="muted">{{ $branding['subtitle'] }}</div></div>
            </div>
            <button type="button" class="btn" id="brand-preview-button" style="margin-top:12px;background:{{ $branding['primary_color'] }}">Botón de ejemplo</button>
        </div>
        <div class="dialog-footer" style="padding:16px 0 0">
            <button class="btn" type="submit">Guardar identidad del sistema</button>
        </div>
    </form>
</section>
@endsection
@push('scripts')
<script>
(() => {
    const fields = ['system_name','system_short_name','system_subtitle','primary_color'];
    const update = () => {
        const get = id => document.getElementById(id);
        get('brand-preview-mark').textContent = get('system_short_name').value || 'GD';
        get('brand-preview-name').textContent = get('system_name').value || 'Gestión Docente';
        get('brand-preview-subtitle').textContent = get('system_subtitle').value || 'Organización académica';
        get('brand-preview-mark').style.background = get('primary_color').value;
        get('brand-preview-button').style.background = get('primary_color').value;
    };
    fields.forEach(id => document.getElementById(id)?.addEventListener('input', update));
})();
</script>
@endpush
