<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherAttendanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tanggal' => optional($this->tanggal)->toDateString(),
            'pertemuan' => $this->pertemuan,
            'status' => $this->status,
            'teacher' => [
                'id' => $this->teacher?->id,
                'nama_lengkap' => $this->teacher?->nama_lengkap,
            ],
            'subject' => [
                'id' => $this->subject?->id,
                'nama_mapel' => $this->subject?->nama_mapel,
            ],
            'classroom' => [
                'id' => $this->classroom?->id,
                'nama_kelas' => $this->classroom?->nama_kelas,
            ],
        ];
    }
}
