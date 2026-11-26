<?php

namespace App\Models;

use App\Models\Concerns\ScopesToCurrentInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumTask extends Model
{
    use ScopesToCurrentInstitution;

    protected static function institutionScopeRelation(): string
    {
        return 'curriculum.course';
    }


    protected $fillable = ['curriculum_id', 'task_code', 'title', 'position'];
    public function curriculum(): BelongsTo { return $this->belongsTo(Curriculum::class); }
    public function operations(): HasMany { return $this->hasMany(CurriculumOperation::class)->orderBy('position'); }
    public function weeks(): HasMany { return $this->hasMany(CurriculumWeek::class); }
}
