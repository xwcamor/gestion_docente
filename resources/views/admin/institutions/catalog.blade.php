@extends('layouts.app')

@section('title', 'Catálogo institucional')

@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">Administración SaaS</p>
        <h1>Catálogo institucional</h1>
        <p class="subhead">Estructura académica y oferta capturada de {{ $institution->name }}.</p>
    </div>
    <span class="badge badge-green">Referencia cargada</span>
</div>

<div class="institution-catalog-stats">
    <article class="card institution-stat"><span>Periodos capturados</span><strong>{{ $periods->count() }}</strong></article>
    <article class="card institution-stat"><span>Asignaturas (códigos)</span><strong>{{ number_format($courseCount) }}</strong></article>
    <article class="card institution-stat"><span>Secciones / NRC</span><strong>{{ number_format($offeringCount) }}</strong></article>
    <article class="card institution-stat"><span>NRC por vincular a grupos</span><strong>{{ number_format($pendingGroupCount) }}</strong></article>
    <article class="card institution-stat"><span>Plantillas Excel cargadas</span><strong>{{ number_format($templateCount) }}</strong></article>
</div>

<section class="card institution-catalog-section">
    <div class="institution-section-head">
        <div><h2>Tipos de impartición</h2><p>Componentes y modalidades configurados específicamente para esta institución.</p></div>
    </div>
    <div class="institution-type-list">
        @foreach($institution->instructionTypes()->orderBy('sort_order')->get() as $type)
            <div class="institution-type-row">
                <span class="institution-type-code">{{ $type->code }}</span>
                <div><strong>{{ $type->name }}</strong><small>{{ $type->description }}</small></div>
                <span class="badge {{ $type->is_active ? 'badge-green' : 'badge-gray' }}">{{ $type->is_active ? 'Activo' : 'Inactivo' }}</span>
            </div>
        @endforeach
    </div>
</section>

<section class="card institution-catalog-section">
    <div class="institution-section-head">
        <div><h2>@if($showSenatiReference) Periodos de Blackboard @else Periodos institucionales @endif</h2><p>Se conserva el código, el periodo académico y su unidad de origen.</p></div>
    </div>
    @forelse($periods as $period)
        <details class="institution-period" @if($loop->first) open @endif>
            <summary>
                <div><strong>{{ $period->code }}</strong><small>{{ $period->source_label ?: 'Unidad sin clasificar' }} · {{ $period->academic_year }}</small></div>
                <span class="institution-period-count">{{ $period->offerings->count() }} NRC</span>
            </summary>
            <div class="institution-offerings-wrap">
                <div class="table-scroll">
                    <table class="data-table institution-offerings">
                        <thead><tr><th>@if($showSenatiReference) Identificador Blackboard @else Identificador de origen @endif</th><th>Asignatura</th><th>Componente</th><th>Estado observado</th><th>Docente visible</th><th>Grupo</th></tr></thead>
                        <tbody>
                            @foreach($period->offerings->sortBy(fn ($offering) => [$offering->course_code_snapshot, $offering->nrc]) as $offering)
                                <tr>
                                    <td><code>{{ $offering->external_identifier }}</code></td>
                                    <td>
                                        <strong>{{ $offering->course_name_snapshot }}</strong>
                                        <small>{{ $offering->course_code_snapshot }}</small>
                                        @if($showSenatiReference && data_get($offering->course?->metadata, 'observed_group_pattern'))
                                            <small>Dos salones observados: uno con TEC+TAL y otro solo TEC; NRC por salón pendiente.</small>
                                        @endif
                                    </td>
                                    <td>{{ $offering->instructionType?->code ?: 'Sin tipo' }}</td>
                                    <td>
                                        @if($offering->source_status === 'open')
                                            <span class="badge badge-green">Abierto</span>
                                        @elseif($offering->source_status === 'finished')
                                            <span class="badge badge-gray">Finalizado</span>
                                        @else
                                            <span class="badge badge-yellow">No visible</span>
                                        @endif
                                    </td>
                                    <td>{{ $offering->instructor_label ?: 'No visible' }}</td>
                                    <td><span class="badge badge-yellow">Pendiente de vincular</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </details>
    @empty
        <p class="institution-catalog-note">No hay periodos institucionales registrados todavía.</p>
    @endforelse
</section>

<section class="card institution-catalog-section">
    <div class="institution-section-head">
        <div><h2>Programas vigentes</h2><p>Programas vigentes registrados en el catálogo de {{ $institution->name }}.</p></div>
    </div>
    <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>Unidad</th><th>Programa</th><th>Duración</th><th>Fuente</th></tr></thead>
            <tbody>
                @forelse($activePrograms as $program)
                    @php $currentVersion = $program->versions->firstWhere('is_current', true); @endphp
                    <tr>
                        <td>{{ $program->unit?->name ?: 'Sin unidad' }}</td>
                        <td><strong>{{ $program->name }}</strong></td>
                        <td>{{ $currentVersion?->duration_semesters ? $currentVersion->duration_semesters.' semestres' : 'Pendiente de verificar' }}</td>
                        <td><a href="{{ $program->source_url }}" target="_blank" rel="noopener">Fuente oficial</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4">No hay programas vigentes registrados para esta institución.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($showSenatiReference)
        <p class="institution-note">SENATI reportó 91 carreras de formación profesional y 14 escuelas en su memoria institucional 2025. Esta pantalla no pretende ser la lista completa: muestra siete programas vigentes de la Escuela Superior con duración oficial de ocho semestres y {{ $historicalPrograms }} nombres ETIA históricos de 2022. El catálogo vigente completo de Formación Profesional debe importarse desde el documento oficial actualizado.</p>
        <p class="institution-note">{{ $historicalPrograms }} nombres históricos de ETIA (memoria 2022) se conservaron como referencia y no se ofrecen como carreras vigentes.</p>
    @endif
</section>

@if($showSenatiReference)
<section class="card institution-catalog-section">
    <div class="institution-section-head">
        <div>
            <h2>Formación Profesional · catálogo 2025</h2>
            <p>{{ $trainingPrograms->count() }} nombres recuperados de la tabla oficial, agrupados según las escuelas que aparecen en la memoria.</p>
        </div>
        <span class="badge badge-gray">{{ $reportedTrainingCareerCount }} reportadas</span>
    </div>
    <div class="institution-catalog-note">
        La Memoria 2025 informa {{ $reportedTrainingCareerCount }} carreras en ejecución y 14 escuelas. La extracción legible de la tabla permitió registrar {{ $trainingPrograms->count() }} nombres; {{ $unresolvedTrainingNames }} quedan pendientes de recuperar de la fuente completa. No se inventaron. La memoria también muestra 91 como cifra institucional general de carreras, por lo que se conservan ambas cifras con su contexto.
    </div>
    @foreach($trainingPrograms->groupBy(fn ($program) => $program->unit?->name ?: 'Escuela sin clasificar') as $schoolName => $programs)
        <details class="institution-period">
            <summary>
                <div><strong>{{ $schoolName }}</strong><small>Referencia 2025 · Duración individual pendiente de malla</small></div>
                <span class="institution-period-count">{{ $programs->count() }} de {{ data_get($programs->first()->unit?->metadata, 'reported_career_count', $programs->count()) }} nombres</span>
            </summary>
            <div class="institution-program-list">
                @foreach($programs as $program)
                    <div class="institution-program-row">
                        <span>{{ $program->name }}</span>
                        <small>Duración: por validar en su versión de malla</small>
                    </div>
                @endforeach
            </div>
        </details>
    @endforeach
    <div class="institution-catalog-note">
        El reglamento consultado contempla carreras de 4, 5, 6 u 8 semestres. Esa es la lista de duraciones admitidas como referencia para SENATI; no determina por sí sola cuántos semestres tiene cada una de estas 85 carreras.
    </div>
</section>
@endif

<section class="card institution-catalog-section">
    <div class="institution-section-head">
        <div><h2>Documentos y funciones</h2><p>Las funciones son configurables por institución. Las plantillas reales se registran cuando se cargan los archivos Excel.</p></div>
    </div>
    <div class="institution-feature-list">
        @foreach($features as $feature)
            @php
                $featureNote = data_get($feature->settings, 'note')
                    ?? (!data_get($feature->settings, 'templates_uploaded', true)
                        ? 'Pendiente cargar los archivos oficiales.'
                        : 'Configuración institucional.');
            @endphp
            <div class="institution-feature-row">
                <div><strong>{{ str($feature->feature_key)->replace('_', ' ')->title() }}</strong><small>{{ $featureNote }}</small></div>
                <span class="badge {{ $feature->enabled ? 'badge-green' : 'badge-gray' }}">{{ $feature->enabled ? 'Habilitado' : 'Deshabilitado' }}</span>
            </div>
        @endforeach
    </div>
</section>

@if($showSenatiReference)
    <section class="institution-catalog-disclaimer">
        <x-icon name="info" />
        <p>Las capturas no muestran los nombres reales de los salones ni permiten emparejar con certeza cada NRC TEC con un NRC TAL. Por eso los 47 NRC se guardaron sin asignarlos a grupos ficticios. Tampoco se importaron horarios: la captura de repetición muestra un ejemplo de calendario, no el horario real de cada asignatura.</p>
    </section>
@endif
@endsection

@push('styles')
<style>
.institution-catalog-stats{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin-bottom:16px}
.institution-stat{padding:16px;display:grid;gap:7px}
.institution-stat span{font-size:11px;color:var(--muted)}
.institution-stat strong{font-size:25px;line-height:1.1;color:var(--ink)}
.institution-catalog-section{padding:18px;margin-bottom:14px;min-width:0}
.institution-section-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:14px}
.institution-section-head h2{margin:0 0 4px;font-size:16px}
.institution-section-head p,.institution-note{margin:0;color:var(--muted);font-size:11px;line-height:1.55}
.institution-type-list,.institution-feature-list{display:grid;gap:0}
.institution-type-row,.institution-feature-row{display:flex;align-items:center;gap:12px;padding:12px 0;border-top:1px solid var(--line)}
.institution-type-row>div,.institution-feature-row>div{display:grid;gap:3px;min-width:0;flex:1}
.institution-type-row small,.institution-feature-row small{color:var(--muted);font-size:10px}
.institution-type-code{min-width:52px;padding:7px 9px;border-radius:7px;background:var(--accent-soft);color:var(--accent);font-weight:800;text-align:center}
.institution-period{border-top:1px solid var(--line)}
.institution-period summary{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:13px 2px;cursor:pointer;list-style:none}
.institution-period summary::-webkit-details-marker{display:none}
.institution-period summary>div{display:grid;gap:4px}
.institution-period summary strong{font-size:13px}
.institution-period summary small{color:var(--muted);font-size:10px}
.institution-period-count{font-size:11px;color:var(--muted);white-space:nowrap}
.institution-offerings-wrap{padding-bottom:12px}
.institution-offerings{min-width:900px}
.institution-offerings code{font-size:10px;white-space:nowrap}
.institution-offerings td strong,.institution-offerings td small{display:block}
.institution-offerings td small{color:var(--muted);font-size:10px;margin-top:3px}
.institution-note{margin-top:12px}
.institution-catalog-note{padding:11px 12px;border-radius:8px;background:var(--surface-muted, #f7f8fa);color:var(--muted);font-size:11px;line-height:1.6;margin-bottom:12px}
.institution-program-list{display:grid;padding:0 2px 8px}
.institution-program-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:8px 0;border-top:1px solid var(--line);font-size:12px}
.institution-program-row small{color:var(--muted);font-size:10px;text-align:right}
@media(max-width:560px){.institution-program-row{align-items:flex-start;flex-direction:column;gap:3px}.institution-program-row small{text-align:left}}
.institution-catalog-disclaimer{display:flex;align-items:flex-start;gap:10px;padding:13px 15px;border:1px solid var(--line);border-radius:10px;color:var(--muted)}
.institution-catalog-disclaimer p{margin:0;font-size:11px;line-height:1.6}
@media(max-width:1100px){.institution-catalog-stats{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:900px){.institution-catalog-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:560px){.institution-catalog-stats{grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:8px}.institution-stat{padding:12px}.institution-stat strong{font-size:21px}.institution-catalog-section{padding:13px}.institution-type-row,.institution-feature-row{align-items:flex-start;flex-wrap:wrap}}
</style>
@endpush
