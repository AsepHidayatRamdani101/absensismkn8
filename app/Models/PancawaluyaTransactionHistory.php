<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PancawaluyaTransactionHistory extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'uuid',
        'reference_type',
        'reference_id',
        'student_id',
        'classroom_id',
        'academic_year_id',
        'semester',
        'transaction_date',
        'action',
        'status',
        'score_before',
        'score_after',
        'payload_before',
        'payload_after',
        'reason',
        'actor_id',
        'actor_role',
        'source',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'score_before' => 'decimal:2',
        'score_after' => 'decimal:2',
        'payload_before' => 'array',
        'payload_after' => 'array',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
