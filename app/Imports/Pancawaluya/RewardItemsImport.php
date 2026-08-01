<?php

namespace App\Imports\Pancawaluya;

use App\Models\CharacterDimension;
use App\Models\CharacterMapping;
use App\Models\RewardCategory;
use App\Models\RewardItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RewardItemsImport implements ToCollection, WithHeadingRow
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
                'category_code' => trim((string) ($row['kode_kategori'] ?? $row['category_code'] ?? '')),
                'code' => trim((string) ($row['kode_reward'] ?? $row['code'] ?? '')),
                'name' => trim((string) ($row['nama_reward'] ?? $row['name'] ?? '')),
                'point' => (int) ($row['point'] ?? 0),
                'dimension_code' => trim((string) ($row['kode_dimensi'] ?? $row['dimension_code'] ?? '')),
                'weight' => (float) ($row['weight'] ?? 1),
                'description' => trim((string) ($row['deskripsi'] ?? $row['description'] ?? '')),
                'is_active' => $this->normalizeStatus((string) ($row['status'] ?? 'Aktif')),
            ];

            $validator = Validator::make($data, [
                'category_code' => ['required', 'string', 'max:30'],
                'code' => ['required', 'string', 'max:40'],
                'name' => ['required', 'string', 'max:150'],
                'point' => ['required', 'integer'],
                'dimension_code' => ['required', 'string', 'max:30'],
                'weight' => ['required', 'numeric', 'min:0'],
                'description' => ['nullable', 'string', 'max:1000'],
                'is_active' => ['required', 'boolean'],
            ]);

            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . ($index + 2) . ': ' . $validator->errors()->first(),
                ]);
            }

            $category = RewardCategory::query()->where('code', $data['category_code'])->first();
            if (!$category) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . ($index + 2) . ': kategori reward dengan kode ' . $data['category_code'] . ' tidak ditemukan.',
                ]);
            }

            $dimension = CharacterDimension::query()->where('code', $data['dimension_code'])->first();
            if (!$dimension) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . ($index + 2) . ': dimensi karakter dengan kode ' . $data['dimension_code'] . ' tidak ditemukan.',
                ]);
            }

            $this->previewRows[] = $data;

            if ($this->previewOnly) {
                continue;
            }

            $item = RewardItem::query()->withTrashed()->firstOrNew(['code' => $data['code']]);
            $exists = $item->exists;

            if ($exists && $item->trashed()) {
                $item->restore();
            }

            $item->fill([
                'reward_category_id' => $category->id,
                'code' => $data['code'],
                'name' => $data['name'],
                'point' => $data['point'],
                'description' => $data['description'],
                'is_active' => $data['is_active'],
            ]);
            $item->save();

            CharacterMapping::query()
                ->where('mappable_type', RewardItem::class)
                ->where('mappable_id', $item->id)
                ->delete();

            CharacterMapping::query()->create([
                'mappable_type' => RewardItem::class,
                'mappable_id' => $item->id,
                'character_dimension_id' => $dimension->id,
                'weight' => $data['weight'],
                'is_active' => true,
            ]);

            if ($exists) {
                $this->updatedCount++;
            } else {
                $this->createdCount++;
            }
        }
    }

    public function getSuccessMessage(): string
    {
        return "Import reward selesai. Dibuat {$this->createdCount}, diperbarui {$this->updatedCount}.";
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
