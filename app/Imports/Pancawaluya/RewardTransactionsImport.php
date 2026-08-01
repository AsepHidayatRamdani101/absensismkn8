<?php

namespace App\Imports\Pancawaluya;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\RewardCategory;
use App\Models\RewardItem;
use App\Models\Student;
use App\Services\Pancawaluya\RewardTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RewardTransactionsImport implements ToCollection, WithHeadingRow
{
    private array $previewRows = [];

    public function __construct(private readonly RewardTransactionService $service, private readonly bool $preview = true) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $student = Student::query()->where('nis', (string) ($row['nis'] ?? ''))->orWhere('nisn', (string) ($row['nisn'] ?? ''))->first();
            $item = RewardItem::query()->where('code', (string) ($row['reward_code'] ?? ''))->first();
            $category = RewardCategory::query()->where('code', (string) ($row['category_code'] ?? ''))->first();
            $academicYear = AcademicYear::query()->where('tahun_ajaran', (string) ($row['academic_year'] ?? ''))->first();
            $classroom = Classroom::query()->where('nama_kelas', (string) ($row['class_name'] ?? ''))->first();

            $payload = [
                'academic_year_id' => $academicYear?->id,
                'semester' => (string) ($row['semester'] ?? ''),
                'transaction_date' => (string) ($row['transaction_date'] ?? ''),
                'student_id' => $student?->id,
                'classroom_id' => $classroom?->id,
                'reward_category_id' => $category?->id,
                'reward_item_id' => $item?->id,
                'source' => (string) ($row['source'] ?? 'Import Excel'),
                'description' => (string) ($row['description'] ?? ''),
                'status' => (string) ($row['status'] ?? 'pending'),
            ];

            $this->previewRows[] = $payload;

            if ($this->preview) {
                continue;
            }

            $this->service->create($payload, Request::create('/pancawaluya/reward-transactions/import', 'POST', $payload));
        }
    }

    public function getPreviewRows(): array
    {
        return $this->previewRows;
    }

    public function getSuccessMessage(): string
    {
        return 'Import transaksi reward selesai (' . count($this->previewRows) . ' baris diproses).';
    }
}
