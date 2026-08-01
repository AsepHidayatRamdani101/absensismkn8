<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
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
            'nis' => $this->nis,
            'nisn' => $this->nisn,
            'nama_lengkap' => $this->nama_lengkap,
            'jenis_kelamin' => $this->jenis_kelamin,
            'classroom' => [
                'id' => $this->classroom?->id,
                'nama_kelas' => $this->classroom?->nama_kelas,
                'tingkat' => $this->classroom?->tingkat,
                'jurusan' => $this->classroom?->major?->nama_jurusan,
            ],
        ];
    }
}
