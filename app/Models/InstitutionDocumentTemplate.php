<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionDocumentTemplate extends Model
{
    protected $fillable = ['institution_id', 'institution_instruction_type_id', 'document_type', 'name', 'version', 'disk', 'file_path', 'original_filename', 'mime_type', 'mapping_config', 'status', 'valid_from', 'valid_until'];

    protected function casts(): array { return ['mapping_config' => 'array', 'valid_from' => 'date', 'valid_until' => 'date']; }

    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
    public function instructionType(): BelongsTo { return $this->belongsTo(InstitutionInstructionType::class, 'institution_instruction_type_id'); }
}
