<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLogCharacter extends Model
{
    use HasFactory;
    use HasUuid;

    protected $table = 'audit_log_characters';

    protected $fillable = [
        'uuid',
        'entity_type',
        'entity_id',
        'action',
        'before_payload',
        'after_payload',
        'performed_by',
        'performed_role',
        'source',
        'ip_address',
        'browser',
        'user_agent',
    ];

    protected $casts = [
        'before_payload' => 'array',
        'after_payload' => 'array',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
