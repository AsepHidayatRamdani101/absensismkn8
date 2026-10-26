<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PjjSubmissionFile extends Model
{
    protected $fillable = [
        'pjj_submission_id',
        'path',
        'original_name',
        'size',
    ];

    public function submission()
    {
        return $this->belongsTo(PjjSubmission::class, 'pjj_submission_id');
    }
}
