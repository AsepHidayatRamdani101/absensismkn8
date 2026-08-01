<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Student extends Model
{
    public const JABATAN_KETUA_KELAS = 'ketua_kelas';
    public const JABATAN_SEKRETARIS = 'sekretaris';
    public const JABATAN_BENDAHARA = 'bendahara';

    public const ALLOWED_JABATAN_FOR_TEACHER_ATTENDANCE = [
        self::JABATAN_KETUA_KELAS,
        self::JABATAN_SEKRETARIS,
        self::JABATAN_BENDAHARA,
    ];

    public const JABATAN_LABELS = [
        self::JABATAN_KETUA_KELAS => 'KM',
        self::JABATAN_SEKRETARIS => 'Sekretaris',
        self::JABATAN_BENDAHARA => 'Bendahara',
    ];

    protected $fillable = [
        'nis',
        'nisn',
        'nama_lengkap',
        'nama_orang_tua_wali',
        'jenis_kelamin',
        'classroom_id',
        'jabatan_kelas',
        'alamat',
        'no_hp',
        'no_hp_orang_tua',
        'tinggi_badan',
        'berat_badan',
        'foto',
        'qr_token',
    ];

    protected $casts = [
        'tinggi_badan' => 'decimal:2',
        'berat_badan' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $student): void {
            if (!empty($student->qr_token)) {
                return;
            }

            $student->qr_token = (string) Str::uuid();
        });
    }

    public function ensureQrToken(): string
    {
        if (!empty($this->qr_token)) {
            return (string) $this->qr_token;
        }

        do {
            $token = (string) Str::uuid();
        } while (self::query()->where('qr_token', $token)->exists());

        // Persist only qr_token so transient attributes (e.g. username_akun, has_account)
        // from list views never leak into SQL update statements.
        self::query()
            ->whereKey($this->getKey())
            ->update(['qr_token' => $token]);

        $this->setAttribute('qr_token', $token);
        $this->syncOriginalAttribute('qr_token');

        return $token;
    }

    public function canSubmitTeacherAttendance(): bool
    {
        return in_array($this->jabatan_kelas, self::ALLOWED_JABATAN_FOR_TEACHER_ATTENDANCE, true);
    }

    public function hasMinimumIdentityForProtectedMenus(): bool
    {
        return trim((string) $this->no_hp_orang_tua) !== '';
    }

    public function getJabatanKelasLabelAttribute(): string
    {
        return self::JABATAN_LABELS[$this->jabatan_kelas] ?? '-';
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function attendanceDetails()
    {
        return $this->hasMany(AttendanceDetail::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(StudentLeaveRequest::class);
    }
}
