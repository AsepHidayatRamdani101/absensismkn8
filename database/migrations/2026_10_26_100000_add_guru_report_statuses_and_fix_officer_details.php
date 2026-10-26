<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Perbaikan bug absensi guru oleh siswa (KM/Sekretaris/Bendahara):
     * sebelumnya pilihan "Tugas"/"Tanpa Keterangan" untuk guru justru tersimpan
     * sebagai status Izin/Alpha pada absensi siswa yang mengabsen.
     *
     * 1. Tambah nilai 'Tugas' dan 'Tanpa Keterangan' pada enum kehadiran_guru.
     * 2. Backfill kehadiran_guru dari baris attendance_details yang salah.
     * 3. Kembalikan status siswa pengabsen menjadi Hadir.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE teacher_attendances MODIFY COLUMN kehadiran_guru ENUM('Hadir','Izin','Sakit','Cuti','Dinas Luar','Home Visit','Tugas','Tanpa Keterangan') NOT NULL DEFAULT 'Hadir'");

        $officerJabatans = "'ketua_kelas','sekretaris','bendahara'";

        DB::statement("
            UPDATE teacher_attendances ta
            JOIN attendance_details ad ON ad.teacher_attendance_id = ta.id
            JOIN students s ON s.id = ad.student_id
            SET ta.kehadiran_guru = 'Tugas'
            WHERE ad.status = 'Izin'
              AND ad.keterangan = 'Tugas'
              AND s.jabatan_kelas IN ({$officerJabatans})
              AND ta.kehadiran_guru = 'Hadir'
              AND ta.status = 'Draft'
        ");

        DB::statement("
            UPDATE teacher_attendances ta
            JOIN attendance_details ad ON ad.teacher_attendance_id = ta.id
            JOIN students s ON s.id = ad.student_id
            SET ta.kehadiran_guru = 'Tanpa Keterangan'
            WHERE ad.status = 'Alpha'
              AND ad.keterangan = 'Tanpa Keterangan'
              AND s.jabatan_kelas IN ({$officerJabatans})
              AND ta.kehadiran_guru = 'Hadir'
              AND ta.status = 'Draft'
        ");

        DB::statement("
            UPDATE attendance_details ad
            JOIN students s ON s.id = ad.student_id
            SET ad.status = 'Hadir', ad.keterangan = NULL
            WHERE (
                    (ad.status = 'Izin' AND ad.keterangan = 'Tugas')
                 OR (ad.status = 'Alpha' AND ad.keterangan = 'Tanpa Keterangan')
                  )
              AND s.jabatan_kelas IN ({$officerJabatans})
        ");
    }

    public function down(): void
    {
        DB::table('teacher_attendances')
            ->whereIn('kehadiran_guru', ['Tugas', 'Tanpa Keterangan'])
            ->update(['kehadiran_guru' => 'Hadir']);

        DB::statement("ALTER TABLE teacher_attendances MODIFY COLUMN kehadiran_guru ENUM('Hadir','Izin','Sakit','Cuti','Dinas Luar','Home Visit') NOT NULL DEFAULT 'Hadir'");
    }
};
