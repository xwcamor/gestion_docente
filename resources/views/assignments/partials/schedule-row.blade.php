@php($selectedDays = array_map('intval', $schedule['days'] ?? []))
<div class="quick-schedule-row" data-schedule-row>
    <div class="quick-schedule-days">
        <span class="quick-schedule-label">Días de clase</span>
        <div class="quick-weekdays">
            @foreach([1 => 'L', 2 => 'M', 3 => 'X', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D'] as $day => $label)
                <label class="quick-weekday">
                    <input type="checkbox" name="rows[{{ $rowIndex }}][{{ $prefix }}_schedules][{{ $scheduleIndex }}][days][]" value="{{ $day }}" @checked(in_array($day, $selectedDays, true))>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </div>
    <label class="quick-schedule-field">
        <span>Desde</span>
        <input class="field" type="time" name="rows[{{ $rowIndex }}][{{ $prefix }}_schedules][{{ $scheduleIndex }}][start_time]" value="{{ $schedule['start_time'] ?? '' }}">
    </label>
    <label class="quick-schedule-field">
        <span>Hasta</span>
        <input class="field" type="time" name="rows[{{ $rowIndex }}][{{ $prefix }}_schedules][{{ $scheduleIndex }}][end_time]" value="{{ $schedule['end_time'] ?? '' }}">
    </label>
    <label class="quick-schedule-field">
        <span>Repetir cada</span>
        <select class="field" name="rows[{{ $rowIndex }}][{{ $prefix }}_schedules][{{ $scheduleIndex }}][repeat_interval_weeks]">
            @foreach([1 => '1 semana', 2 => '2 semanas', 3 => '3 semanas', 4 => '4 semanas'] as $interval => $label)
                <option value="{{ $interval }}" @selected((int) ($schedule['repeat_interval_weeks'] ?? 1) === $interval)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="quick-schedule-field">
        <span>Tipo</span>
        <select class="field" name="rows[{{ $rowIndex }}][{{ $prefix }}_schedules][{{ $scheduleIndex }}][instruction_type]">
            <option value="THEORY" @selected(($schedule['instruction_type'] ?? $defaultInstructionType) === 'THEORY')>Teoría</option>
            <option value="LAB" @selected(($schedule['instruction_type'] ?? $defaultInstructionType) === 'LAB')>Taller / práctica</option>
        </select>
    </label>
    <label class="quick-schedule-field quick-schedule-room">
        <span>Aula / ambiente</span>
        <input class="field" name="rows[{{ $rowIndex }}][{{ $prefix }}_schedules][{{ $scheduleIndex }}][classroom]" value="{{ $schedule['classroom'] ?? '' }}" maxlength="100" placeholder="Ej. LAB 105">
    </label>
    <button class="btn btn-ghost btn-sm quick-schedule-remove" type="button" aria-label="Quitar bloque semanal" title="Quitar bloque"><x-icon name="x" /></button>
</div>
