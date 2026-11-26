<?php

namespace App\Models;

use App\Models\Concerns\ScopesToCurrentInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumOperation extends Model
{
    use ScopesToCurrentInstitution;

    protected static function institutionScopeRelation(): string
    {
        return 'task.curriculum.course';
    }


    protected $fillable = ['curriculum_task_id', 'operation_code', 'description', 'operation_type', 'position'];
    public function task(): BelongsTo { return $this->belongsTo(CurriculumTask::class, 'curriculum_task_id'); }
}
