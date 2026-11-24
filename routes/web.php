<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CycleController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\CourseAssignmentController;
use App\Http\Controllers\QuickCourseEntryController;
use App\Http\Controllers\CourseModalityController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\StudentImportController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\AdminProfessorController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\AuditLogController;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'professor'])->group(function () {
    Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/perfil', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    Route::get('/ciclos', [CycleController::class, 'index'])->name('cycles.index');
    Route::get('/ciclos/crear', [CycleController::class, 'create'])->name('cycles.create');
    Route::post('/ciclos', [CycleController::class, 'store'])->name('cycles.store');
    Route::patch('/ciclos/{cycle}', [CycleController::class, 'update'])->name('cycles.update');
    Route::delete('/ciclos/{cycle}', [CycleController::class, 'destroy'])->name('cycles.destroy');
    Route::get('/ciclos/{cycle}', [CycleController::class, 'show'])->name('cycles.show');

    Route::get('/ciclos/{cycle}/grupos/crear', [GroupController::class, 'create'])->name('groups.create');
    Route::post('/ciclos/{cycle}/grupos', [GroupController::class, 'store'])->name('groups.store');
    Route::patch('/grupos/{group}', [GroupController::class, 'update'])->name('groups.update');
    Route::patch('/grupos/{group}/carga-horaria', [GroupController::class, 'updateWorkload'])->name('groups.workload.update');
    Route::patch('/grupos/{group}/charlas/{talk}', [GroupController::class, 'updateTalkExecution'])->name('groups.talks.update');
    Route::post('/grupos/{group}/charlas-5-minutos/excel', [DocumentController::class, 'groupFiveMinuteTalks'])->name('documents.group-talks');
    Route::delete('/grupos/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');
    Route::patch('/grupos/{group}/alumnos/{student}', [GroupController::class, 'updateStudent'])->name('groups.students.update');
    Route::get('/grupos/{group}', [GroupController::class, 'show'])->name('groups.show');

    Route::get('/grupos/{group}/cursos/crear', [CourseAssignmentController::class, 'create'])->name('assignments.create');
    Route::get('/grupos/{group}/carga-rapida', [QuickCourseEntryController::class, 'create'])->name('assignments.quick');
    Route::post('/grupos/{group}/carga-rapida', [QuickCourseEntryController::class, 'store'])->name('assignments.quick.store');
    Route::post('/grupos/{group}/cursos', [CourseAssignmentController::class, 'store'])->name('assignments.store');
    Route::delete('/asignaciones/{assignment}', [CourseAssignmentController::class, 'destroy'])->name('assignments.destroy');
    Route::post('/grupos/{group}/alumnos/previsualizar', [StudentImportController::class, 'previewGroup'])->name('groups.students.preview');
    Route::post('/grupos/{group}/alumnos/confirmar', [StudentImportController::class, 'confirmGroup'])->name('groups.students.confirm');

    Route::get('/asignaciones/{assignment}/malla-curricular', [CurriculumController::class, 'show'])->name('curricula.show');
    Route::post('/asignaciones/{assignment}/malla-curricular/extraer', [CurriculumController::class, 'extract'])->name('curricula.extract');
    Route::get('/asignaciones/{assignment}/malla-curricular/extraer', [CurriculumController::class, 'extractGet'])->name('curricula.extract.get');
    Route::get('/asignaciones/{assignment}/malla-curricular/prompt/{token}', [CurriculumController::class, 'showPrompt'])->name('curricula.prompt');
    Route::post('/asignaciones/{assignment}/malla-curricular/previsualizar', [CurriculumController::class, 'preview'])->name('curricula.preview');
    Route::post('/asignaciones/{assignment}/malla-curricular/confirmar', [CurriculumController::class, 'confirm'])->name('curricula.confirm');
    Route::get('/asignaciones/{assignment}/malla-curricular/ejemplo', [CurriculumController::class, 'downloadExample'])->name('curricula.example');
    Route::post('/mallas/{curriculum}/aprobar-sugerencias', [CurriculumController::class, 'approveSuggestions'])->name('curricula.approve');

    Route::get('/modalidades/{modality}', [CourseModalityController::class, 'show'])->name('modalities.show');
    Route::get('/modalidades/{modality}/evaluaciones', [EvaluationController::class, 'index'])->name('evaluations.index');
    Route::post('/modalidades/{modality}/evaluaciones', [EvaluationController::class, 'store'])->name('evaluations.store');
    Route::put('/evaluaciones/{evaluation}/calificaciones', [EvaluationController::class, 'saveGrades'])->name('evaluations.grades');
    Route::put('/modalidades/{modality}', [CourseModalityController::class, 'update'])->name('modalities.update');
    Route::post('/modalidades/{modality}/calcular', [CourseModalityController::class, 'rebuild'])->name('modalities.rebuild');

    Route::patch('/sesiones/{session}', [SessionController::class, 'update'])->name('sessions.update');
    Route::post('/modalidades/{modality}/alumnos/previsualizar', [StudentImportController::class, 'preview'])->name('students.preview');
    Route::post('/modalidades/{modality}/alumnos/confirmar', [StudentImportController::class, 'confirm'])->name('students.confirm');
    Route::post('/modalidades/{modality}/documentos/{type}', [DocumentController::class, 'generate'])->whereIn('type', ['plan', 'registro', 'evaluacion'])->name('documents.generate');
    Route::post('/ciclos/{cycle}/distribucion', [DocumentController::class, 'distribution'])->name('documents.distribution');
    Route::get('/documentos/{document}/descargar', [DocumentController::class, 'download'])->name('documents.download');

    Route::get('/feriados', [HolidayController::class, 'index'])->name('holidays.index');
    Route::post('/feriados', [HolidayController::class, 'store'])->name('holidays.store');

    Route::prefix('administracion')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/profesores', [AdminProfessorController::class, 'index'])->name('professors.index');
        Route::post('/profesores', [AdminProfessorController::class, 'store'])->name('professors.store');
        Route::patch('/profesores/{user}', [AdminProfessorController::class, 'update'])->name('professors.update');
        Route::patch('/profesores/{user}/estado', [AdminProfessorController::class, 'toggleStatus'])->name('professors.status');
        Route::delete('/profesores/{user}', [AdminProfessorController::class, 'destroy'])->name('professors.destroy');
        Route::get('/auditoria', [AuditLogController::class, 'index'])->name('audit.index');
        Route::get('/configuracion', [SystemSettingController::class, 'edit'])->name('settings.edit');
        Route::patch('/configuracion', [SystemSettingController::class, 'update'])->name('settings.update');
    });
});
