<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumOperation extends Model
{
    protected $fillable = ['curriculum_task_id', 'operation_code', 'description', 'operation_type', 'position'];
    public function task(): BelongsTo { return $this->belongsTo(CurriculumTask::class, 'curriculum_task_id'); }
}
