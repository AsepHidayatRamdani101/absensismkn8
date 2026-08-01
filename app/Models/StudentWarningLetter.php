<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentWarningLetter extends Model
{
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'student_id',
        'academic_year_id',
        'semester',
        'sp_level',
        'violation_weighted_total',
        'is_manual_override',
        'status',
        'issued_at',
        'expires_at',
        'note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'violation_weighted_total' => 'decimal:2',
        'is_manual_override' => 'boolean',
        'issued_at' => 'date',
        'expires_at' => 'date',
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
