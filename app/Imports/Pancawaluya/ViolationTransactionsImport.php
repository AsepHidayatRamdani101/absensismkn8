<?php

namespace App\Imports\Pancawaluya;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\ViolationCategory;
use App\Models\ViolationItem;
use App\Services\Pancawaluya\ViolationTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ViolationTransactionsImport implements ToCollection, WithHeadingRow
{
    private array $previewRows = [];

    public function __construct(private readonly ViolationTransactionService $service, private readonly bool $preview = true) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $student = Student::query()->where('nis', (string) ($row['nis'] ?? ''))->orWhere('nisn', (string) ($row['nisn'] ?? ''))->first();
            $item = ViolationItem::query()->where('code', (string) ($row['violation_code'] ?? ''))->first();
            $category = ViolationCategory::query()->where('code', (string) ($row['category_code'] ?? ''))->first();
            $academicYear = AcademicYear::query()->where('tahun_ajaran', (string) ($row['academic_year'] ?? ''))->first();
            $classroom = Classroom::query()->where('nama_kelas', (string) ($row['class_name'] ?? ''))->first();

            $payload = [
                'academic_year_id' => $academicYear?->id,
                'semester' => (string) ($row['semester'] ?? ''),
                'transaction_date' => (string) ($row['transaction_date'] ?? ''),
                'student_id' => $student?->id,
                'classroom_id' => $classroom?->id,
                'violation_category_id' => $category?->id,
                'violation_item_id' => $item?->id,
                'source' => (string) ($row['source'] ?? 'Import Excel'),
                'description' => (string) ($row['description'] ?? ''),
                'status' => (string) ($row['status'] ?? 'pending'),
            ];

            $this->previewRows[] = $payload;

            if ($this->preview) {
                continue;
            }

            $this->service->create($payload, Request::create('/pancawaluya/violation-transactions/import', 'POST', $payload));
        }
    }

    public function getPreviewRows(): array
    {
        return $this->previewRows;
    }

    public function getSuccessMessage(): string
    {
        return 'Import transaksi pelanggaran selesai (' . count($this->previewRows) . ' baris diproses).';
    }
}
