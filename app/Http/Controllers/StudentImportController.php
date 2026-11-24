<?php

namespace App\Http\Controllers;

use App\Models\CourseModality;
use App\Models\TeachingGroup;
use App\Services\StudentImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StudentImportController extends Controller
{
    public function preview(Request $request, CourseModality $modality, StudentImportService $service)
    {
        $this->owned($modality);
        $request->validate(['roster' => 'required|file|max:10240']);

        $file = $request->file('roster');
        $stored = $file->store('imports/pending/'.$modality->id, 'local');

        try {
            $preview = $service->preview(Storage::disk('local')->path($stored), $file->getClientOriginalExtension());
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($stored);
            throw $exception;
        }

        $token = bin2hex(random_bytes(20));
        session()->put('pending_roster_'.$token, [
            'modality_id' => $modality->id,
            'path' => $stored,
            'filename' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
        ]);

        return view('modalities.import-preview', compact('modality', 'preview', 'token'));
    }

    public function confirm(Request $request, CourseModality $modality, StudentImportService $service)
    {
        $this->owned($modality);
        $request->validate(['token' => 'required|string']);

        $key = 'pending_roster_'.$request->input('token');
        $pending = session()->pull($key);

        abort_unless($pending && (int) $pending['modality_id'] === $modality->id, 419, 'La vista previa expiró. Vuelve a cargar el archivo.');

        $batch = $service->importStored(
            $modality,
            $pending['path'],
            $pending['filename'],
            $pending['extension'],
            auth()->id()
        );

        return redirect()->route('modalities.show', $modality)->with(
            'success',
            "Importación completada: {$batch->created_count} alumnos nuevos, {$batch->updated_count} actualizados y {$batch->skipped_count} omitidos."
        );
    }

    public function previewGroup(Request $request, TeachingGroup $group, StudentImportService $service)
    {
        $this->ownedGroup($group);
        $request->validate(['roster' => 'required|file|max:10240']);

        $file = $request->file('roster');
        $stored = $file->store('imports/pending/groups/'.$group->id, 'local');

        try {
            $preview = $service->preview(Storage::disk('local')->path($stored), $file->getClientOriginalExtension());
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($stored);
            throw $exception;
        }

        $token = bin2hex(random_bytes(20));
        session()->put('pending_group_roster_'.$token, [
            'group_id' => $group->id,
            'path' => $stored,
            'filename' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
        ]);

        $group->load('cycle');

        return view('groups.students-preview', compact('group', 'preview', 'token'));
    }

    public function confirmGroup(Request $request, TeachingGroup $group, StudentImportService $service)
    {
        $this->ownedGroup($group);
        $request->validate(['token' => 'required|string']);

        $key = 'pending_group_roster_'.$request->input('token');
        $pending = session()->get($key);

        abort_unless(
            $pending && (int) ($pending['group_id'] ?? 0) === $group->id,
            419,
            'La vista previa expiró. Vuelve a seleccionar el archivo de alumnos.'
        );

        $batch = $service->importGroupStored(
            $group,
            $pending['path'],
            $pending['filename'],
            $pending['extension'],
            auth()->id()
        );

        session()->forget($key);

        return redirect()->route('groups.show', $group)->with(
            'success',
            "Padrón del grupo actualizado: {$batch->created_count} alumnos nuevos, {$batch->updated_count} actualizados y {$batch->skipped_count} omitidos. Se sincronizaron las matrículas de sus cursos."
        );
    }

    private function owned(CourseModality $modality): void
    {
        abort_unless(
            $modality->assignment->group->professor_id === auth()->user()->professor->id,
            404
        );
    }

    private function ownedGroup(TeachingGroup $group): void
    {
        abort_unless($group->professor_id === auth()->user()->professor->id, 404);
    }
}
