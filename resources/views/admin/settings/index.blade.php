@extends('layouts.app')
@section('title', 'Ajustes')
@section('content')
<div class="page-head settings-page-head">
    <div>
        <p class="eyebrow">Configuración personal</p>
        <h1>Ajustes de interfaz</h1>
        <p class="subhead">Adapta la navegación, el color y la apariencia de tu espacio de trabajo.</p>
    </div>
</div>

@php($useSystemColor = (bool) old('use_system_color', !$user->accent_color))
@php($sidebarColor = old('sidebar_color', $user->sidebar_color ?: 'blue'))
@php($sidebarPalettes = ['blue' => 'Azul', 'green' => 'Verde', 'violet' => 'Violeta', 'amber' => 'Ámbar', 'graphite' => 'Grafito'])
<form class="interface-preferences-card card" method="POST" action="{{ route('admin.settings.update') }}" data-loading-form="1">
    @csrf
    @method('PATCH')
    <input type="hidden" name="interface_preferences" value="1">

    <div class="interface-preferences-head">
        <div class="interface-preferences-icon"><x-icon name="sliders-horizontal" size="19" /></div>
        <div>
            <p class="eyebrow">Tu espacio de trabajo</p>
            <h2>Preferencias de interfaz</h2>
            <p class="card-description">Estos cambios se aplican a tu cuenta, sin modificar la configuración de otros usuarios.</p>
        </div>
    </div>

    <div class="interface-preferences-grid">
        <div class="interface-preference-option">
            <div class="interface-preference-copy">
                <strong>Menú lateral</strong>
                <p>Ocúltalo para aprovechar todo el ancho de pantalla. Si lo ocultas, podrás volver a mostrarlo con el botón «Menú» de la barra superior.</p>
            </div>
            <label class="interface-select-label" for="sidebar_visible">Visibilidad del menú</label>
            <select class="field interface-select" id="sidebar_visible" name="sidebar_visible">
                <option value="1" @selected((string) old('sidebar_visible', $user->sidebar_visible ? '1' : '0') === '1')>Mostrar menú lateral</option>
                <option value="0" @selected((string) old('sidebar_visible', $user->sidebar_visible ? '1' : '0') === '0')>Ocultar menú lateral</option>
            </select>
            @error('sidebar_visible')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div class="interface-preference-option">
            <div class="interface-preference-copy">
                <strong>Color de interfaz</strong>
                <p>Elige el color de botones y elementos activos o utiliza el color institucional definido por administración.</p>
            </div>
            <div class="interface-color-controls">
                <label class="interface-color-picker" for="user_accent_color">
                    <input id="user_accent_color" type="color" name="accent_color" value="{{ old('accent_color', $user->accent_color ?: $branding['primary_color']) }}" @disabled($useSystemColor)>
                    <span>Elegir color</span>
                </label>
                <label class="interface-system-color">
                    <input type="checkbox" id="use_system_color" name="use_system_color" value="1" @checked($useSystemColor)>
                    <span>Usar color institucional</span>
                </label>
            </div>
            @error('accent_color')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div class="interface-preference-option sidebar-palette-panel">
            <div class="interface-preference-copy">
                <strong>Color del menú lateral</strong>
                <p>Selecciona uno de los cinco tonos. El cambio solo afecta al menú de tu cuenta.</p>
            </div>
            <div class="sidebar-palette" role="radiogroup" aria-label="Color del menú lateral">
                @foreach($sidebarPalettes as $value => $label)
                    <label class="sidebar-palette-option">
                        <input type="radio" name="sidebar_color" value="{{ $value }}" @checked($sidebarColor === $value)>
                        <span class="sidebar-palette-swatch swatch-{{ $value }}" aria-hidden="true"></span>
                        <span class="sidebar-palette-label">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            @error('sidebar_color')<p class="field-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="interface-preferences-footer">
        <p>El modo claro u oscuro se cambia desde la barra superior.</p>
        <button class="btn" type="submit"><x-icon name="check" size="15" /> Guardar preferencias</button>
    </div>
</form>

@if(auth()->user()->isAdmin())
<form class="branding-layout" method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" data-loading-form="1">
    @csrf
    @method('PATCH')

    <section class="card branding-editor-card">
        <div class="branding-section-heading">
            <span class="branding-section-icon"><x-icon name="settings" size="18" /></span>
            <div><h2>Identidad de la aplicación</h2><p class="card-description">Nombre, logo y colores principales.</p></div>
        </div>

        <div class="branding-form-grid">
            <div class="form-field">
                <label for="system_name">Nombre del sistema</label>
                <input class="field" id="system_name" name="system_name" value="{{ old('system_name', $branding['name']) }}" maxlength="80" required placeholder="Ej. Gestión Docente">
                @error('system_name')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-field">
                <label for="system_short_name">Nombre corto / iniciales</label>
                <input class="field" id="system_short_name" name="system_short_name" value="{{ old('system_short_name', $branding['short_name']) }}" maxlength="8" required placeholder="Ej. GD">
                @error('system_short_name')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-field branding-full-field">
                <label for="system_subtitle">Descripción breve</label>
                <input class="field" id="system_subtitle" name="system_subtitle" value="{{ old('system_subtitle', $branding['subtitle']) }}" maxlength="120" placeholder="Ej. Organización académica">
                @error('system_subtitle')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="branding-divider"></div>
        <div class="branding-subheading">
            <h3>Logotipo</h3>
            <p>Sube el logo que aparecerá junto al nombre de la aplicación.</p>
        </div>
        <div class="branding-logo-field">
            <div class="branding-logo-preview">
                <img
                    id="brand-preview-logo"
                    src="{{ $branding['logo_path'] ? asset('storage/'.$branding['logo_path']) : '' }}"
                    alt="Vista previa del logotipo"
                    @if(!$branding['logo_path']) hidden @endif
                >
                <span id="brand-preview-mark" class="branding-logo-fallback" @if($branding['logo_path']) hidden @endif>{{ $branding['short_name'] }}</span>
            </div>
            <div class="branding-logo-upload">
                <label for="logo">Seleccionar archivo de imagen</label>
                <input class="field" id="logo" type="file" name="logo" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp">
                <p class="help">Formatos permitidos: PNG, JPG o WEBP. Tamaño máximo: 2 MB.</p>
                @error('logo')<p class="field-error">{{ $message }}</p>@enderror
                @if($branding['logo_path'])
                    <p class="help">El logotipo actual se conservará si no seleccionas otro archivo.</p>
                @endif
            </div>
        </div>

        <div class="branding-divider"></div>
        <div class="branding-subheading">
            <h3>Colores del sistema</h3>
            <p>El color principal se usa en botones, enlaces activos y elementos destacados.</p>
        </div>
        <div class="branding-color-grid">
            <div class="branding-color-field">
                <div class="branding-color-copy">
                    <label for="primary_color">Color principal</label>
                    <small>Botones y elementos destacados</small>
                </div>
                <input class="branding-color-input" id="primary_color" type="color" name="primary_color" value="{{ old('primary_color', $branding['primary_color']) }}" aria-label="Elegir color principal">
                <output id="primary-color-value" for="primary_color">{{ strtoupper($branding['primary_color']) }}</output>
                @error('primary_color')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="branding-color-field">
                <div class="branding-color-copy">
                    <label for="primary_hover">Color al pasar el cursor</label>
                    <small>Estado hover de botones</small>
                </div>
                <input class="branding-color-input" id="primary_hover" type="color" name="primary_hover" value="{{ old('primary_hover', $branding['primary_hover']) }}" aria-label="Elegir color al pasar el cursor">
                <output id="primary-hover-value" for="primary_hover">{{ strtoupper($branding['primary_hover']) }}</output>
                @error('primary_hover')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="branding-form-footer">
            <p>Los ajustes se guardan para todos los usuarios del sistema.</p>
            <button class="btn" type="submit"><x-icon name="check" size="15" /> Guardar ajustes</button>
        </div>
    </section>

    <aside class="branding-preview-column">
        <section class="card branding-preview-card">
            <p class="eyebrow">Vista previa</p>
            <h2>Así se verá tu aplicación</h2>
            <p class="card-description">La muestra cambia mientras editas los campos.</p>

            <div class="branding-app-preview">
                <div class="branding-preview-topbar">
                    <div class="branding-preview-identity">
                        <div class="branding-preview-logo-wrap">
                            <img id="brand-preview-logo-small" src="{{ $branding['logo_path'] ? asset('storage/'.$branding['logo_path']) : '' }}" alt="" @if(!$branding['logo_path']) hidden @endif>
                            <span id="brand-preview-mark-small" class="branding-preview-mark" @if($branding['logo_path']) hidden @endif>{{ $branding['short_name'] }}</span>
                        </div>
                        <div class="branding-preview-title">
                            <strong id="brand-preview-name">{{ $branding['name'] }}</strong>
                            <small id="brand-preview-subtitle">{{ $branding['subtitle'] }}</small>
                        </div>
                    </div>
                    <span class="branding-preview-dot"></span>
                </div>
                <div class="branding-preview-content">
                    <span class="branding-preview-label">EJEMPLO DE INTERFAZ</span>
                    <h3>Panel principal</h3>
                    <p>Resumen de la actividad académica</p>
                    <div class="branding-preview-stat-row">
                        <span></span><span></span><span></span>
                    </div>
                    <button type="button" class="btn branding-preview-button" id="brand-preview-button">Botón principal</button>
                    <button type="button" class="btn btn-secondary branding-preview-secondary">Acción secundaria</button>
                </div>
            </div>
        </section>

        <section class="card branding-info-card">
            <div class="branding-info-icon"><x-icon name="info" size="17" /></div>
            <strong>¿Qué cambia con estos ajustes?</strong>
            <p>El nombre aparece en la barra superior y en el título del navegador. El logotipo reemplaza las iniciales. El color principal se aplica a los botones y a los estados activos del menú.</p>
            <p class="help">Para mostrar el logotipo subido en el servidor local, Laravel debe tener habilitado el enlace público de almacenamiento.</p>
        </section>
    </aside>
</form>
@endif
@endsection


@push('styles')
<style>
.interface-preferences-card{width:100%;max-width:none;padding:clamp(16px,2vw,24px);margin:0 0 16px}
.interface-preferences-head{display:flex;align-items:flex-start;gap:13px;padding-bottom:17px;border-bottom:1px solid var(--line)}
.interface-preferences-icon{display:grid;place-items:center;width:42px;height:42px;flex:none;border-radius:11px;background:var(--accent-soft);color:var(--accent)}
.interface-preferences-head h2{margin:0;font-size:20px;font-weight:780;letter-spacing:-.025em}
.interface-preferences-head .eyebrow{margin-bottom:4px}
.interface-preferences-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:15px}
.sidebar-palette{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:6px}
.sidebar-palette-option{position:relative;display:grid;justify-items:center;gap:6px;min-width:0;margin:0;padding:9px 5px;border:1px solid var(--line);border-radius:8px;background:var(--surface);cursor:pointer;transition:border-color .14s ease,background .14s ease,box-shadow .14s ease}
.sidebar-palette-option:has(input:checked){border-color:var(--accent);background:var(--accent-soft);box-shadow:0 0 0 2px color-mix(in srgb,var(--accent) 12%,transparent)}
.sidebar-palette-option input{position:absolute;width:1px;height:1px;opacity:0;pointer-events:none}
.sidebar-palette-option:focus-within{outline:2px solid var(--accent);outline-offset:2px}
.sidebar-palette-swatch{display:block;width:100%;height:25px;border:1px solid rgba(35,50,65,.12);border-radius:5px;background:#eaf3fb}
.sidebar-palette-swatch.swatch-blue{background:linear-gradient(90deg,#dcecf8 0 74%,#3f82b5 74%)}
.sidebar-palette-swatch.swatch-green{background:linear-gradient(90deg,#e2f1e7 0 74%,#398761 74%)}
.sidebar-palette-swatch.swatch-violet{background:linear-gradient(90deg,#eee7f8 0 74%,#8065ad 74%)}
.sidebar-palette-swatch.swatch-amber{background:linear-gradient(90deg,#f8efd9 0 74%,#b7832f 74%)}
.sidebar-palette-swatch.swatch-graphite{background:linear-gradient(90deg,#354656 0 74%,#243341 74%)}
.sidebar-palette-label{color:var(--muted);font-size:9px;font-weight:700}
.sidebar-palette-option:has(input:checked) .sidebar-palette-label{color:var(--accent)}
.interface-preference-option{display:grid;align-content:start;gap:10px;min-width:0;padding:15px;border:1px solid var(--line);border-radius:10px;background:var(--surface-soft)}
.interface-preference-copy strong{display:block;color:var(--ink);font-size:13px;font-weight:750}
.interface-preference-copy p{margin:5px 0 0;color:var(--muted);font-size:11px;line-height:1.5}
.interface-select-label{margin:0;color:var(--muted);font-size:10px;font-weight:700}
.interface-select{max-width:360px}
.interface-color-controls{display:flex;align-items:center;gap:14px;flex-wrap:wrap}
.interface-color-picker{display:inline-flex;align-items:center;gap:9px;margin:0;cursor:pointer;color:var(--ink-soft);font-size:11px;font-weight:650}
.interface-color-picker input{width:42px;height:38px;padding:3px;border:1px solid var(--line-strong);border-radius:8px;background:var(--surface);cursor:pointer}
.interface-color-picker input:disabled{opacity:.45;cursor:not-allowed}
.interface-system-color{display:inline-flex;align-items:center;gap:7px;margin:0;color:var(--muted);font-size:10px;font-weight:600}
.interface-system-color input{width:15px;height:15px;accent-color:var(--accent)}
.interface-preferences-footer{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-top:16px;padding-top:14px;border-top:1px solid var(--line)}
.interface-preferences-footer p{margin:0;color:var(--muted);font-size:10px}
@media(max-width:950px){.interface-preferences-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.sidebar-palette-panel{grid-column:1/-1}}
@media(max-width:700px){
 .interface-preferences-grid{grid-template-columns:1fr}.sidebar-palette-panel{grid-column:auto}
 .interface-preferences-footer{align-items:stretch;flex-direction:column}
 .interface-preferences-footer .btn{width:100%}
 .interface-select{max-width:none}
}
</style>
@endpush

@push('styles')
<style>
.settings-page-head,.branding-layout{max-width:1280px}
.branding-layout{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(310px,.82fr);align-items:start;gap:15px}
.branding-editor-card{padding:22px;min-width:0}
.branding-section-heading{display:flex;align-items:center;gap:12px;margin-bottom:20px;padding-bottom:15px;border-bottom:1px solid var(--line)}
.branding-section-icon{width:40px;height:40px;flex:none;display:grid;place-items:center;border-radius:10px;background:var(--accent-soft);color:var(--accent)}
.branding-section-heading h2{margin:0 0 3px}
.branding-section-heading .card-description{margin:0}
.branding-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:4px 16px}
.branding-full-field{grid-column:1/-1}
.branding-divider{height:1px;margin:19px 0;border:0;background:var(--line)}
.branding-subheading h3{margin:0 0 3px}
.branding-subheading p{margin:0;color:var(--muted);font-size:10px;line-height:1.5}
.branding-logo-field{display:grid;grid-template-columns:112px minmax(0,1fr);align-items:center;gap:16px;margin-top:12px}
.branding-logo-preview{width:112px;height:94px;display:grid;place-items:center;overflow:hidden;border:1px dashed var(--line-strong);border-radius:10px;background:var(--surface-soft)}
.branding-logo-preview img{max-width:100%;max-height:100%;width:100%;height:100%;padding:8px;object-fit:contain}
.branding-logo-fallback{width:54px;height:54px;display:grid;place-items:center;border-radius:12px;background:var(--accent-soft);color:var(--accent);font-size:15px;font-weight:850}
.branding-logo-upload label{margin-top:0}
.branding-color-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:11px;margin-top:13px}
.branding-color-field{display:flex;align-items:center;gap:10px;min-width:0;padding:12px;border:1px solid var(--line);border-radius:9px;background:var(--surface-soft)}
.branding-color-copy{flex:1;min-width:0}
.branding-color-copy label{margin:0 0 3px;font-size:11px}
.branding-color-copy small{color:var(--muted);font-size:9px;line-height:1.4}
.branding-color-input{width:42px;height:36px;flex:none;padding:3px;border:1px solid var(--line-strong);border-radius:7px;background:var(--surface);cursor:pointer}
.branding-color-field output{width:62px;flex:none;color:var(--muted);font-family:ui-monospace,monospace;font-size:9px;text-align:right}
.branding-form-footer{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-top:20px;padding-top:15px;border-top:1px solid var(--line)}
.branding-form-footer p{margin:0;color:var(--muted);font-size:10px}
.branding-preview-column{display:grid;gap:13px;min-width:0}
.branding-preview-card{position:sticky;top:80px;padding:20px}
.branding-preview-card h2{margin:2px 0 4px}
.branding-app-preview{margin-top:16px;overflow:hidden;border:1px solid var(--line);border-radius:10px;background:var(--canvas)}
.branding-preview-topbar{min-height:62px;display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px;border-bottom:1px solid var(--line);background:var(--surface)}
.branding-preview-identity{display:flex;align-items:center;gap:9px;min-width:0}
.branding-preview-logo-wrap{width:34px;height:34px;flex:none;display:grid;place-items:center;overflow:hidden;border:1px solid var(--line);border-radius:8px;background:var(--surface-soft)}
.branding-preview-logo-wrap img{width:100%;height:100%;padding:3px;object-fit:contain}
.branding-preview-mark{width:100%;height:100%;display:grid;place-items:center;background:var(--accent);color:#fff;font-size:9px;font-weight:850}
.branding-preview-title{display:grid;gap:2px;min-width:0}
.branding-preview-title strong{overflow:hidden;color:var(--ink);font-size:10px;text-overflow:ellipsis;white-space:nowrap}
.branding-preview-title small{overflow:hidden;color:var(--muted);font-size:8px;text-overflow:ellipsis;white-space:nowrap}
.branding-preview-dot{width:8px;height:8px;flex:none;border-radius:50%;background:#36a66b}
.branding-preview-content{padding:16px}
.branding-preview-label{color:var(--muted);font-size:8px;font-weight:800;letter-spacing:1px}
.branding-preview-content h3{margin:7px 0 3px;color:var(--ink);font-size:14px}
.branding-preview-content p{margin:0 0 13px;color:var(--muted);font-size:9px}
.branding-preview-stat-row{display:grid;grid-template-columns:repeat(3,1fr);gap:7px;margin-bottom:13px}
.branding-preview-stat-row span{height:37px;border:1px solid var(--line);border-radius:6px;background:var(--surface)}
.branding-preview-button{background:var(--accent)!important;border-color:var(--accent)!important}
.branding-preview-button:hover{background:var(--accent-hover)!important;border-color:var(--accent-hover)!important}
.branding-preview-secondary{margin-left:5px}
.branding-info-card{padding:17px}
.branding-info-icon{width:31px;height:31px;display:grid;place-items:center;margin-bottom:10px;border-radius:8px;background:var(--accent-soft);color:var(--accent)}
.branding-info-card>strong{display:block;color:var(--ink-soft);font-size:11px}
.branding-info-card p{margin:6px 0 0;color:var(--muted);font-size:10px;line-height:1.6}
html[data-theme="dark"] .branding-color-field{background:#111d25;border-color:#344650}
html[data-theme="dark"] .branding-logo-preview{background:#111d25;border-color:#3a505d}
html[data-theme="dark"] .branding-preview-stat-row span{background:#1b2a34;border-color:#344650}
html[data-theme="dark"] .branding-preview-content h3{color:#e1eaf0}
html[data-theme="dark"] .branding-info-card>strong{color:#dce7ec}
@media(max-width:950px){.branding-layout{grid-template-columns:minmax(0,1fr)}.branding-preview-card{position:static}.branding-preview-column{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:600px){.branding-editor-card{padding:14px}.branding-form-grid,.branding-color-grid{grid-template-columns:minmax(0,1fr)}.branding-full-field{grid-column:auto}.branding-logo-field{grid-template-columns:1fr}.branding-preview-column{grid-template-columns:minmax(0,1fr)}.branding-form-footer{align-items:stretch;flex-direction:column}.branding-form-footer .btn{width:100%}.branding-preview-secondary{margin:8px 0 0}}
</style>
@endpush

@push('scripts')
<script>
(() => {
    const byId = id => document.getElementById(id);
    const name = byId('system_name');
    const shortName = byId('system_short_name');
    const subtitle = byId('system_subtitle');
    const primary = byId('primary_color');
    const hover = byId('primary_hover');
    const logoInput = byId('logo');
    const previewMainLogo = byId('brand-preview-logo');
    const previewSmallLogo = byId('brand-preview-logo-small');
    const previewMainMark = byId('brand-preview-mark');
    const previewSmallMark = byId('brand-preview-mark-small');
    const previewButton = byId('brand-preview-button');
    if (!previewButton) return;
    let previewObjectUrl = null;

    const syncPreview = () => {
        const shortValue = shortName.value.trim() || 'GD';
        const primaryValue = primary.value || '#176B87';
        const hoverValue = hover.value || '#10546B';

        byId('brand-preview-name').textContent = name.value.trim() || 'Nombre del sistema';
        byId('brand-preview-subtitle').textContent = subtitle.value.trim() || 'Descripción breve';
        previewMainMark.textContent = shortValue;
        previewSmallMark.textContent = shortValue;
        previewMainMark.style.background = primaryValue;
        previewSmallMark.style.background = primaryValue;
        previewButton.style.background = primaryValue;
        previewButton.style.borderColor = primaryValue;
        previewButton.dataset.hoverColor = hoverValue;
        byId('primary-color-value').value = primaryValue.toUpperCase();
        byId('primary-hover-value').value = hoverValue.toUpperCase();
    };

    const showLogo = source => {
        [previewMainLogo, previewSmallLogo].forEach(image => {
            image.src = source;
            image.hidden = false;
        });
        previewMainMark.hidden = true;
        previewSmallMark.hidden = true;
    };

    const showInitials = () => {
        [previewMainLogo, previewSmallLogo].forEach(image => {
            image.removeAttribute('src');
            image.hidden = true;
        });
        previewMainMark.hidden = false;
        previewSmallMark.hidden = false;
    };

    [name, shortName, subtitle, primary, hover].forEach(field => field?.addEventListener('input', syncPreview));
    previewButton.addEventListener('pointerenter', event => {
        event.currentTarget.style.background = hover.value;
        event.currentTarget.style.borderColor = hover.value;
    });
    previewButton.addEventListener('pointerleave', syncPreview);

    logoInput?.addEventListener('change', () => {
        const file = logoInput.files?.[0];
        if (!file) return;
        if (!/^image\/(png|jpeg|webp)$/.test(file.type)) {
            logoInput.value = '';
            return;
        }
        if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
        previewObjectUrl = URL.createObjectURL(file);
        showLogo(previewObjectUrl);
    });

    syncPreview();
})();
</script>
@endpush


@push('scripts')
<script>
(() => {
    const checkbox = document.getElementById('use_system_color');
    const picker = document.getElementById('user_accent_color');
    if (!checkbox || !picker) return;

    const sync = () => { picker.disabled = checkbox.checked; };
    checkbox.addEventListener('change', sync);
    sync();
})();
</script>
@endpush
