<?php

namespace App\Imports\Pancawaluya;

use App\Models\ViolationCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ViolationCategoriesImport implements ToCollection, WithHeadingRow
{
    private int $createdCount = 0;

    private int $updatedCount = 0;

    private array $previewRows = [];

    public function __construct(private readonly bool $previewOnly = false) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            if (!$this->hasContent($row)) {
                continue;
            }

            $data = [
                'code' => trim((string) ($row['kode'] ?? $row['code'] ?? '')),
                'name' => trim((string) ($row['nama_kategori'] ?? $row['name'] ?? '')),
                'description' => trim((string) ($row['deskripsi'] ?? $row['description'] ?? '')),
                'is_active' => $this->normalizeStatus((string) ($row['status'] ?? 'Aktif')),
            ];

            $validator = Validator::make($data, [
                'code' => ['required', 'string', 'max:30'],
                'name' => ['required', 'string', 'max:120'],
                'description' => ['nullable', 'string', 'max:1000'],
                'is_active' => ['required', 'boolean'],
            ]);

            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . ($index + 2) . ': ' . $validator->errors()->first(),
                ]);
            }

            $this->previewRows[] = $data;

            if ($this->previewOnly) {
                continue;
            }

            $model = ViolationCategory::query()->withTrashed()->firstOrNew(['code' => $data['code']]);
            $exists = $model->exists;

            if ($exists && $model->trashed()) {
                $model->restore();
            }

            $model->fill($data);
            $model->save();

            if ($exists) {
                $this->updatedCount++;
            } else {
                $this->createdCount++;
            }
        }
    }

    public function getSuccessMessage(): string
    {
        return "Import kategori pelanggaran selesai. Dibuat {$this->createdCount}, diperbarui {$this->updatedCount}.";
    }

    public function getPreviewRows(): array
    {
        return $this->previewRows;
    }

    private function normalizeStatus(string $status): bool
    {
        return !in_array(strtolower(trim($status)), ['0', 'nonaktif', 'inactive', 'false', 'tidak aktif'], true);
    }

    private function hasContent(array|Collection $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }
}
