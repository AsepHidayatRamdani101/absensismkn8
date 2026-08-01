<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentCharacterStatistic extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'uuid',
        'student_id',
        'academic_year_id',
        'semester',
        'reward_count',
        'violation_count',
        'reward_weighted_total',
        'violation_weighted_total',
        'character_score_total',
        'last_calculated_at',
    ];

    protected $casts = [
        'reward_count' => 'integer',
        'violation_count' => 'integer',
        'reward_weighted_total' => 'decimal:2',
        'violation_weighted_total' => 'decimal:2',
        'character_score_total' => 'decimal:2',
        'last_calculated_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
