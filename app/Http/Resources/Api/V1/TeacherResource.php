<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherResource extends JsonResource
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
            'nip' => $this->nip,
            'nama_lengkap' => $this->nama_lengkap,
            'jabatan' => $this->jabatan,
            'jenis_kelamin' => $this->jenis_kelamin,
            'is_wali_kelas' => (bool) $this->is_wali_kelas,
            'wali_classroom_id' => $this->wali_classroom_id,
        ];
    }
}
