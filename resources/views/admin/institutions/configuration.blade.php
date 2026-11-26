@extends('layouts.app')

@section('title', 'Configuración institucional')

@section('content')
<div class="page-head institution-config-head">
    <div>
        <p class="eyebrow">Administración · Tenant SENATI</p>
        <h1>Configuración institucional</h1>
        <p class="subhead">Administra la identidad, estructura académica y reglas propias de esta institución.</p>
    </div>
    <a class="btn btn-secondary" href="{{ route('admin.institution.catalog') }}"><x-icon name="book-open" size="15" /> Ver catálogo</a>
</div>

<div class="institution-config-summary">
    <div class="card institution-config-summary-item">
        <span>Unidades registradas</span><strong>{{ $units->count() }}</strong>
    </div>
    <div class="card institution-config-summary-item">
        <span>Tipos de periodo</span><strong>{{ $periodTypes->count() }}</strong>
    </div>
    <div class="card institution-config-summary-item">
        <span>Carreras y programas</span><strong>{{ $programs->count() }}</strong>
    </div>
    <div class="card institution-config-summary-item">
        <span>Duración de semestre de referencia</span><strong>{{ $semesterWeeks }} <small>semanas</small></strong>
    </div>
</div>

<section class="card institution-config-card">
    <div class="institution-config-section-head">
        <div>
            <p class="eyebrow">01 · Identidad</p>
            <h2>Datos de la institución</h2>
            <p>El código interno no cambia para preservar las relaciones existentes.</p>
        </div>
        <span class="institution-code-chip">{{ $institution->code }}</span>
    </div>
    <form method="POST" action="{{ route('admin.institution.settings.update') }}" class="institution-config-form">
        @csrf
        @method('PATCH')
        <div class="institution-config-grid">
            <div class="form-field">
                <label for="institution_name">Nombre visible</label>
                <input class="field" id="institution_name" name="name" value="{{ old('name', $institution->name) }}" required maxlength="255">
                @error('name')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-field">
                <label for="legal_name">Razón social</label>
                <input class="field" id="legal_name" name="legal_name" value="{{ old('legal_name', $institution->legal_name) }}" maxlength="255">
                @error('legal_name')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-field">
                <label for="country_code">País (ISO)</label>
                <input class="field" id="country_code" name="country_code" value="{{ old('country_code', $institution->country_code) }}" maxlength="2" required data-uppercase>
                @error('country_code')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-field">
                <label for="timezone">Zona horaria</label>
                <select class="field" id="timezone" name="timezone" required>
                    @foreach(['America/Lima' => 'Lima (Perú)', 'America/Bogota' => 'Bogotá (Colombia)', 'America/Santiago' => 'Santiago (Chile)', 'America/Mexico_City' => 'Ciudad de México', 'America/Guayaquil' => 'Guayaquil (Ecuador)', 'UTC' => 'UTC'] as $timezone => $label)
                        <option value="{{ $timezone }}" @selected(old('timezone', $institution->timezone) === $timezone)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('timezone')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="institution-config-form-footer">
            <span>Los datos identifican al tenant, no cambian el nombre comercial de la plataforma.</span>
            <button class="btn" type="submit"><x-icon name="check" size="15" /> Guardar institución</button>
        </div>
    </form>
</section>

<section class="card institution-config-card">
    <div class="institution-config-section-head">
        <div>
            <p class="eyebrow">02 · Reglas académicas</p>
            <h2>Duración de los periodos de estudio</h2>
            <p>Son opciones permitidas para las mallas; la duración concreta se asigna a cada carrera.</p>
        </div>
    </div>
    <form method="POST" action="{{ route('admin.institution.rules.update') }}" class="institution-config-form">
        @csrf
        @method('PATCH')
        <div class="institution-rules-grid">
            <div class="form-field">
                <label for="semester_duration_weeks">Semanas por semestre (referencia)</label>
                <input class="field" type="number" id="semester_duration_weeks" name="semester_duration_weeks" min="1" max="52" value="{{ old('semester_duration_weeks', $semesterWeeks) }}" required>
                <small class="help">No define las fechas de dictado; solo la duración de referencia del semestre.</small>
                @error('semester_duration_weeks')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-field">
                <label>Duraciones admitidas por carrera</label>
                <div class="institution-duration-options">
                    @foreach([4,5,6,8,10,12] as $duration)
                        <label class="institution-duration-option">
                            <input type="checkbox" name="career_duration_options[]" value="{{ $duration }}" @checked(in_array($duration, old('career_duration_options', $durationOptions)))>
                            <span>{{ $duration }} semestres</span>
                        </label>
                    @endforeach
                </div>
                @error('career_duration_options')<p class="field-error">{{ $message }}</p>@enderror
                @error('career_duration_options.*')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="institution-config-form-footer">
            <span>No existe una cantidad única de ciclos para todas las carreras de SENATI.</span>
            <button class="btn" type="submit"><x-icon name="check" size="15" /> Guardar reglas</button>
        </div>
    </form>
</section>

<section class="card institution-config-card">
    <div class="institution-config-section-head">
        <div>
            <p class="eyebrow">03 · Calendario institucional</p>
            <h2>Sistemas de periodos</h2>
            <p>Se distinguen los códigos de Blackboard del semestre de avance de la malla curricular.</p>
        </div>
    </div>
    <div class="institution-config-records">
        @foreach($periodTypes as $periodType)
            <form method="POST" action="{{ route('admin.institution.period-types.update', $periodType) }}" class="institution-config-record">
                @csrf
                @method('PATCH')
                <div class="institution-record-title">
                    <strong>{{ $periodType->name }}</strong>
                    <span class="institution-code-chip">{{ $periodType->code }}</span>
                    <small>{{ $periodType->periods_count }} periodos registrados</small>
                </div>
                <div class="institution-record-fields">
                    <div class="form-field">
                        <label>Nombre</label>
                        <input class="field" name="name" value="{{ old('name', $periodType->name) }}" maxlength="120" required>
                    </div>
                    <div class="form-field">
                        <label>Unidad académica</label>
                        <select class="field" name="period_unit" required>
                            @foreach(['period' => 'Periodo institucional', 'semester' => 'Semestre', 'trimester' => 'Trimestre', 'quarter' => 'Cuatrimestre', 'module' => 'Módulo', 'year' => 'Año académico', 'term' => 'Término'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('period_unit', $periodType->period_unit) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Patrón de código</label>
                        <input class="field" name="code_pattern" value="{{ old('code_pattern', $periodType->code_pattern) }}" maxlength="80" placeholder="Ej. YYYYNN">
                    </div>
                    <div class="form-field">
                        <label>Duración estándar (semanas)</label>
                        <input class="field" type="number" name="default_duration_weeks" min="1" max="52" value="{{ old('default_duration_weeks', $periodType->default_duration_weeks) }}" placeholder="Sin definir">
                    </div>
                </div>
                <div class="institution-record-footer">
                    <label class="institution-inline-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $periodType->is_active))> Activo</label>
                    <button class="btn btn-secondary btn-sm" type="submit">Guardar tipo</button>
                </div>
                @error('name')<p class="field-error">{{ $message }}</p>@enderror
            </form>
        @endforeach
    </div>
</section>

<section class="card institution-config-card">
    <div class="institution-config-section-head">
        <div>
            <p class="eyebrow">04 · Organización</p>
            <h2>Unidades académicas</h2>
            <p>Se conserva el rótulo de origen cuando Blackboard no permite determinar la jerarquía real.</p>
        </div>
        <span class="institution-config-count">{{ $units->count() }} unidades</span>
    </div>
    <div class="institution-config-records">
        @foreach($units as $unit)
            <form method="POST" action="{{ route('admin.institution.units.update', $unit) }}" class="institution-unit-record">
                @csrf
                @method('PATCH')
                <div class="institution-unit-meta">
                    <strong>{{ $unit->name }}</strong>
                    <small>{{ $unit->code ?: 'Sin código' }} · {{ $unit->unit_type }}</small>
                    <span class="institution-unit-counts">{{ $unit->programs_count }} programas · {{ $unit->courses_count }} asignaturas · {{ $unit->offerings_count }} NRC</span>
                </div>
                <div class="institution-unit-edit">
                    <div class="form-field">
                        <label>Nombre visible</label>
                        <input class="field" name="name" value="{{ old('name', $unit->name) }}" maxlength="255" required>
                    </div>
                    <label class="institution-inline-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $unit->is_active))> Activa</label>
                    <button class="btn btn-secondary btn-sm" type="submit">Guardar</button>
                </div>
                @if($unit->verification_status === 'school_label_inferred')
                    <small class="institution-warning-note">Nombre provisional: confirmar denominación oficial.</small>
                @elseif($unit->verification_status === 'source_label_unclassified')
                    <small class="institution-warning-note">Rótulo observado en Blackboard; tipo de unidad pendiente de confirmar.</small>
                @endif
            </form>
        @endforeach
    </div>
</section>

<section class="card institution-config-card">
    <div class="institution-config-section-head">
        <div>
            <p class="eyebrow">05 · Duración de carreras</p>
            <h2>Semestres por carrera y versión de malla</h2>
            <p>La duración se configura en cada versión. Los registros históricos conservan su estado y no se convierten automáticamente en carreras vigentes.</p>
        </div>
        <span class="institution-config-count">{{ $programs->count() }} programas</span>
    </div>

    @foreach($programs->groupBy(fn ($program) => $program->unit?->name ?: 'Sin unidad asignada') as $unitName => $unitPrograms)
        <details class="institution-program-group">
            <summary>
                <div><strong>{{ $unitName }}</strong><small>{{ $unitPrograms->count() }} carreras o programas</small></div>
                <span class="institution-period-count">{{ $unitPrograms->whereIn('status', ['active', 'reference_2025'])->count() }} referencias no históricas</span>
            </summary>
            <div class="institution-program-config-list">
                @foreach($unitPrograms as $program)
                    <article class="institution-program-config">
                        <div class="institution-program-config-heading">
                            <div>
                                <strong>{{ $program->name }}</strong>
                                <small>{{ $program->code ?: 'Código no registrado' }} · {{ str($program->status)->replace('_', ' ')->title() }}</small>
                            </div>
                        </div>
                        @forelse($program->versions as $version)
                            <form method="POST" action="{{ route('admin.institution.program-versions.update', [$program, $version]) }}" class="institution-version-form">
                                @csrf
                                @method('PATCH')
                                <div class="institution-version-label">
                                    <strong>{{ $version->version_code }}</strong>
                                    @if($version->is_current)<span class="badge badge-green">Vigente</span>@endif
                                    <small>{{ $version->source_year ?: 'Año no indicado' }}</small>
                                </div>
                                <div class="institution-version-fields">
                                    <div class="form-field">
                                        <label>Semestres</label>
                                        <select class="field" name="duration_semesters">
                                            <option value="">Pendiente de malla</option>
                                            @foreach([4,5,6,8,10,12] as $duration)
                                                <option value="{{ $duration }}" @selected((string) old('duration_semesters', $version->duration_semesters) === (string) $duration)>{{ $duration }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-field">
                                        <label>Vigente desde</label>
                                        <input class="field" type="date" name="valid_from" value="{{ old('valid_from', $version->valid_from?->format('Y-m-d')) }}">
                                    </div>
                                    <div class="form-field">
                                        <label>Vigente hasta</label>
                                        <input class="field" type="date" name="valid_until" value="{{ old('valid_until', $version->valid_until?->format('Y-m-d')) }}">
                                    </div>
                                    <div class="form-field">
                                        <label>Estado de versión</label>
                                        <select class="field" name="status">
                                            @foreach(['current' => 'Vigente', 'reference_2025' => 'Referencia 2025', 'historical_reference' => 'Histórica', 'draft' => 'Borrador', 'archived' => 'Archivada'] as $value => $label)
                                                <option value="{{ $value }}" @selected(old('status', $version->status) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="institution-version-footer">
                                    <label class="institution-inline-check"><input type="checkbox" name="is_current" value="1" @checked(old('is_current', $version->is_current))> Es la versión actual</label>
                                    <button class="btn btn-secondary btn-sm" type="submit">Guardar duración</button>
                                </div>
                            </form>
                        @empty
                            <p class="institution-warning-note">No tiene una versión de malla registrada todavía.</p>
                        @endforelse
                    </article>
                @endforeach
            </div>
        </details>
    @endforeach
</section>

<section class="institution-config-notice">
    <x-icon name="info" />
    <p>Los cambios aquí afectan la configuración de referencia del tenant SENATI. No alteran las fechas de ciclos existentes, los grupos, los NRC ni los horarios. La duración de una carrera solo se considera definida cuando se valida con su malla curricular oficial.</p>
</section>
@endsection

@push('styles')
<style>
.institution-config-head{max-width:1280px}
.institution-config-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:15px}
.institution-config-summary-item{padding:14px 16px;display:grid;gap:7px}
.institution-config-summary-item span{font-size:11px;color:var(--muted)}
.institution-config-summary-item strong{font-size:22px;line-height:1.2;color:var(--ink)}
.institution-config-summary-item strong small{font-size:11px;font-weight:500;color:var(--muted)}
.institution-config-card{padding:18px;margin-bottom:14px;min-width:0}
.institution-config-section-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:16px}
.institution-config-section-head h2{font-size:16px;margin:0 0 4px}
.institution-config-section-head p:not(.eyebrow){font-size:11px;color:var(--muted);margin:0;line-height:1.55}
.institution-code-chip,.institution-config-count{display:inline-flex;align-items:center;padding:6px 9px;border:1px solid var(--line);border-radius:7px;font-size:10px;font-weight:700;color:var(--ink-soft);white-space:nowrap}
.institution-config-form{display:grid;gap:15px}
.institution-config-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px 16px}
.institution-rules-grid{display:grid;grid-template-columns:minmax(220px,.8fr) minmax(0,1.2fr);gap:22px}
.institution-duration-options{display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin-top:7px}
.institution-duration-option{display:inline-flex;align-items:center;gap:7px;padding:9px 11px;border:1px solid var(--line);border-radius:8px;font-size:11px;cursor:pointer}
.institution-duration-option input,.institution-inline-check input{accent-color:var(--accent)}
.institution-config-form-footer,.institution-record-footer,.institution-version-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding-top:13px;border-top:1px solid var(--line)}
.institution-config-form-footer>span{font-size:10px;color:var(--muted)}
.institution-config-records{display:grid}
.institution-config-record,.institution-unit-record{padding:14px 0;border-top:1px solid var(--line)}
.institution-record-title{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px}
.institution-record-title strong{font-size:13px}
.institution-record-title small{font-size:10px;color:var(--muted)}
.institution-record-fields{display:grid;grid-template-columns:1.1fr 1fr 1fr .8fr;gap:10px}
.institution-inline-check{display:inline-flex;align-items:center;gap:7px;color:var(--muted);font-size:11px}
.institution-unit-meta{display:grid;gap:4px;margin-bottom:10px}
.institution-unit-meta strong{font-size:12px}
.institution-unit-meta small,.institution-unit-counts{font-size:10px;color:var(--muted)}
.institution-unit-edit{display:grid;grid-template-columns:minmax(0,1fr) auto auto;align-items:end;gap:10px}
.institution-warning-note{display:block;color:#9a6716;font-size:10px;margin-top:7px}
.institution-program-group{border-top:1px solid var(--line)}
.institution-program-group>summary{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:13px 2px;cursor:pointer;list-style:none}
.institution-program-group>summary::-webkit-details-marker{display:none}
.institution-program-group>summary>div{display:grid;gap:4px}
.institution-program-group>summary strong{font-size:13px}
.institution-program-group>summary small{font-size:10px;color:var(--muted)}
.institution-program-config-list{padding-bottom:12px}
.institution-program-config{padding:12px 0;border-top:1px solid var(--line)}
.institution-program-config-heading{margin-bottom:10px}
.institution-program-config-heading>div{display:grid;gap:4px}
.institution-program-config-heading strong{font-size:12px}
.institution-program-config-heading small{font-size:10px;color:var(--muted)}
.institution-version-form{padding:10px;background:var(--surface-soft);border:1px solid var(--line);border-radius:9px;margin-top:8px}
.institution-version-label{display:flex;align-items:center;gap:9px;flex-wrap:wrap;margin-bottom:10px}
.institution-version-label strong{font-size:11px}
.institution-version-label small{font-size:10px;color:var(--muted)}
.institution-version-fields{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:9px}
.institution-version-footer{margin-top:10px;padding-top:10px}
.institution-config-notice{display:flex;align-items:flex-start;gap:10px;padding:13px 15px;border:1px solid var(--line);border-radius:10px;color:var(--muted)}
.institution-config-notice p{margin:0;font-size:11px;line-height:1.6}
@media(max-width:1050px){.institution-config-summary{grid-template-columns:repeat(2,minmax(0,1fr))}.institution-record-fields{grid-template-columns:repeat(2,minmax(0,1fr))}.institution-version-fields{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:680px){.institution-config-card{padding:13px}.institution-config-grid,.institution-rules-grid,.institution-record-fields,.institution-version-fields{grid-template-columns:minmax(0,1fr)}.institution-unit-edit{grid-template-columns:minmax(0,1fr) auto}.institution-unit-edit .form-field{grid-column:1/-1}.institution-program-group>summary{align-items:flex-start;flex-direction:column}.institution-config-form-footer{align-items:stretch;flex-direction:column}.institution-config-form-footer .btn{width:100%}}
</style>
@endpush
