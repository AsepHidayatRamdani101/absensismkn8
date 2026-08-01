<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentCharacterScore extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'uuid',
        'student_id',
        'character_dimension_id',
        'reward_score_total',
        'violation_score_total',
        'score_total',
        'last_calculated_at',
    ];

    protected $casts = [
        'reward_score_total' => 'decimal:2',
        'violation_score_total' => 'decimal:2',
        'score_total' => 'decimal:2',
        'last_calculated_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function dimension(): BelongsTo
    {
        return $this->belongsTo(CharacterDimension::class, 'character_dimension_id');
    }
}
