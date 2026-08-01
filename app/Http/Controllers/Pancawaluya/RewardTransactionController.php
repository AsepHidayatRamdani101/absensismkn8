<?php

namespace App\Http\Controllers\Pancawaluya;

use App\Exports\Pancawaluya\RewardTransactionsExport;
use App\Exports\TemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pancawaluya\BulkIdsRequest;
use App\Http\Requests\Pancawaluya\ImportMasterRequest;
use App\Http\Requests\Pancawaluya\StoreRewardTransactionRequest;
use App\Http\Requests\Pancawaluya\UpdateRewardTransactionRequest;
use App\Imports\Pancawaluya\RewardTransactionsImport;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\RewardCategory;
use App\Models\RewardItem;
use App\Models\Student;
use App\Models\RewardTransaction;
use App\Services\Pancawaluya\RewardTransactionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class RewardTransactionController extends Controller
{
    public function __construct(private readonly RewardTransactionService $service) {}

    public function index(Request $request)
    {
        return view('admin.pancawaluya.reward_transactions.index', [
            'academicYears' => AcademicYear::query()->orderByDesc('id')->get(),
            'classrooms' => Classroom::query()->orderBy('nama_kelas')->get(),
            'categories' => RewardCategory::query()->orderBy('name')->get(),
            'items' => RewardItem::query()->orderBy('name')->get(),
            'statusFilter' => (string) $request->input('status', ''),
        ]);
    }

    public function datatable(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = max(min((int) $request->input('length', 10), 100), 10);
        $page = (int) floor($start / $length) + 1;
        $request->merge(['page' => $page]);

        $filters = [
            'search' => (string) data_get($request->input('search', []), 'value', ''),
            'academic_year_id' => (string) $request->input('academic_year_id', ''),
            'semester' => (string) $request->input('semester', ''),
            'classroom_id' => (string) $request->input('classroom_id', ''),
            'student_id' => (string) $request->input('student_id', ''),
            'category_id' => (string) $request->input('category_id', ''),
            'item_id' => (string) $request->input('item_id', ''),
            'status' => (string) $request->input('status', ''),
            'source' => (string) $request->input('source', ''),
            'from_date' => (string) $request->input('from_date', ''),
            'to_date' => (string) $request->input('to_date', ''),
            'only_trashed' => (bool) $request->boolean('only_trashed'),
        ];

        $paginator = $this->service->paginate($filters, $length);

        $rows = collect($paginator->items())->values()->map(function (RewardTransaction $row, int $index) use ($start) {
            return [
                'checkbox' => '<input type="checkbox" class="check-item" value="' . $row->id . '">',
                'no' => $start + $index + 1,
                'date' => e(optional($row->transaction_date)->format('d-m-Y')),
                'student' => e((string) ($row->student?->nama_lengkap ?? '-')),
                'class' => e((string) ($row->classroom?->nama_kelas ?? '-')),
                'category' => e((string) ($row->rewardCategory?->name ?? '-')),
                'item' => e((string) ($row->rewardItem?->name ?? '-')),
                'point' => e((string) $row->point),
                'dimensions' => e(collect((array) $row->dimension_payload)->pluck('dimension_name')->join(', ')),
                'source' => e((string) $row->source),
                'creator' => e((string) ($row->creator?->name ?? '-')),
                'status' => '<span class="badge badge-' . $this->statusBadgeColor((string) $row->status) . '">' . e((string) $row->status) . '</span>',
                'actions' => $this->actions($row),
            ];
        })->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $paginator->total(),
            'recordsFiltered' => $paginator->total(),
            'data' => $rows,
        ]);
    }

    public function create()
    {
        return view('admin.pancawaluya.reward_transactions.create', $this->formData());
    }

    public function store(StoreRewardTransactionRequest $request)
    {
        $this->service->create($request->validated(), $request);

        return redirect()->route('pancawaluya.reward-transactions.index')->with('success', 'Transaksi reward berhasil dibuat.');
    }

    public function edit(RewardTransaction $reward_transaction)
    {
        return view('admin.pancawaluya.reward_transactions.edit', $this->formData() + ['row' => $reward_transaction]);
    }

    public function update(UpdateRewardTransactionRequest $request, RewardTransaction $reward_transaction)
    {
        $this->service->update($reward_transaction, $request->validated(), $request);

        return redirect()->route('pancawaluya.reward-transactions.index')->with('success', 'Transaksi reward berhasil diperbarui.');
    }

    public function destroy(Request $request, RewardTransaction $reward_transaction)
    {
        $this->service->softDelete($reward_transaction, $request);

        return response()->json(['success' => true, 'message' => 'Transaksi reward dihapus (soft delete).']);
    }

    public function restore(Request $request, int $id)
    {
        abort_if(!$this->service->restore($id, $request), 404);

        return response()->json(['success' => true, 'message' => 'Transaksi reward dipulihkan.']);
    }

    public function forceDelete(Request $request, int $id)
    {
        abort_if(!$this->service->forceDelete($id, $request), 404);

        return response()->json(['success' => true, 'message' => 'Transaksi reward dihapus permanen.']);
    }

    public function bulkDelete(BulkIdsRequest $request)
    {
        $count = $this->service->bulkSoftDelete($request->validated('ids'), $request);

        return response()->json(['success' => true, 'message' => $count . ' transaksi reward dihapus.']);
    }

    public function bulkRestore(BulkIdsRequest $request)
    {
        $count = $this->service->bulkRestore($request->validated('ids'), $request);

        return response()->json(['success' => true, 'message' => $count . ' transaksi reward dipulihkan.']);
    }

    public function exportExcel(Request $request)
    {
        return Excel::download(new RewardTransactionsExport($this->service->allForExport($this->filters($request), true)), 'reward-transactions.xlsx');
    }

    public function exportCsv(Request $request)
    {
        return Excel::download(new RewardTransactionsExport($this->service->allForExport($this->filters($request), true)), 'reward-transactions.csv');
    }

    public function exportPdf(Request $request)
    {
        $rows = $this->service->allForExport($this->filters($request), true);
        return Pdf::loadView('admin.pancawaluya.reward_transactions.export_pdf', ['rows' => $rows])->setPaper('a4', 'landscape')->download('reward-transactions.pdf');
    }

    public function print(Request $request)
    {
        $rows = $this->service->allForExport($this->filters($request), true);
        return view('admin.pancawaluya.reward_transactions.print', ['rows' => $rows]);
    }

    public function import(ImportMasterRequest $request)
    {
        $preview = $request->boolean('preview');
        $importer = new RewardTransactionsImport($this->service, $preview);

        try {
            DB::transaction(function () use ($request, $importer): void {
                Excel::import($importer, $request->file('file'));
            });
        } catch (Throwable $exception) {
            return redirect()->route('pancawaluya.reward-transactions.index')->withErrors([
                'file' => $exception->getMessage(),
            ]);
        }

        if ($preview) {
            return redirect()->route('pancawaluya.reward-transactions.index')->with('success', 'Preview import berhasil (' . count($importer->getPreviewRows()) . ' baris).');
        }

        return redirect()->route('pancawaluya.reward-transactions.index')->with('success', $importer->getSuccessMessage());
    }

    public function template()
    {
        return Excel::download(
            new TemplateExport(
                ['academic_year', 'semester', 'transaction_date', 'nis', 'class_name', 'category_code', 'reward_code', 'source', 'description', 'status'],
                [['2025/2026', 'Ganjil', '2026-08-01', '1234567890', 'X TKJ 1', 'RWD-AKT', 'RWD-001', 'Observasi Guru', 'Aksi positif', 'pending']]
            ),
            'template-reward-transactions.xlsx'
        );
    }

    public function studentOptions(Request $request)
    {
        $keyword = (string) $request->input('q', '');

        $rows = Student::query()
            ->with(['classroom.major'])
            ->when($keyword !== '', function ($query) use ($keyword): void {
                $query->where(function ($q) use ($keyword): void {
                    $q->where('nis', 'like', '%' . $keyword . '%')
                        ->orWhere('nisn', 'like', '%' . $keyword . '%')
                        ->orWhere('nama_lengkap', 'like', '%' . $keyword . '%')
                        ->orWhereHas('classroom', function ($c) use ($keyword): void {
                            $c->where('nama_kelas', 'like', '%' . $keyword . '%')
                                ->orWhereHas('major', function ($m) use ($keyword): void {
                                    $m->where('nama_jurusan', 'like', '%' . $keyword . '%')
                                        ->orWhere('kode_jurusan', 'like', '%' . $keyword . '%');
                                });
                        });
                });
            })
            ->orderBy('nama_lengkap')
            ->limit(20)
            ->get()
            ->map(fn(Student $student) => [
                'id' => $student->id,
                'text' => $student->nis . ' - ' . $student->nama_lengkap . ' (' . ($student->classroom?->nama_kelas ?? '-') . ')',
                'classroom_id' => $student->classroom_id,
            ]);

        return response()->json(['results' => $rows]);
    }

    public function rewardItemPreview(Request $request)
    {
        $item = RewardItem::query()->with(['category', 'mappings.dimension'])->findOrFail((int) $request->input('reward_item_id'));

        $dimensions = $item->mappings->where('is_active', true)->map(fn($mapping) => [
            'dimension_id' => $mapping->character_dimension_id,
            'dimension_name' => (string) ($mapping->dimension?->name ?? '-'),
            'weight' => (float) $mapping->weight,
            'point' => (int) $item->point,
            'weighted_point' => (float) $item->point * (float) $mapping->weight,
        ])->values();

        return response()->json([
            'category_id' => $item->reward_category_id,
            'category_name' => (string) ($item->category?->name ?? '-'),
            'point' => (int) $item->point,
            'weight_total' => (float) $dimensions->sum('weight'),
            'dimensions' => $dimensions,
        ]);
    }

    private function formData(): array
    {
        return [
            'academicYears' => AcademicYear::query()->orderByDesc('id')->get(),
            'students' => Student::query()->with('classroom')->orderBy('nama_lengkap')->limit(100)->get(),
            'classrooms' => Classroom::query()->orderBy('nama_kelas')->get(),
            'categories' => RewardCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'items' => RewardItem::query()->where('is_active', true)->orderBy('name')->get(),
            'statuses' => ['draft', 'pending', 'validated', 'approved', 'rejected'],
        ];
    }

    private function filters(Request $request): array
    {
        return [
            'search' => (string) data_get($request->input('search', []), 'value', $request->input('search', '')),
            'academic_year_id' => $request->string('academic_year_id')->toString(),
            'semester' => $request->string('semester')->toString(),
            'classroom_id' => $request->string('classroom_id')->toString(),
            'student_id' => $request->string('student_id')->toString(),
            'category_id' => $request->string('category_id')->toString(),
            'item_id' => $request->string('item_id')->toString(),
            'status' => $request->string('status')->toString(),
            'source' => $request->string('source')->toString(),
            'from_date' => $request->string('from_date')->toString(),
            'to_date' => $request->string('to_date')->toString(),
            'only_trashed' => $request->boolean('only_trashed'),
        ];
    }

    private function statusBadgeColor(string $status): string
    {
        return match ($status) {
            'approved' => 'success',
            'validated' => 'info',
            'rejected' => 'danger',
            'draft' => 'secondary',
            default => 'warning',
        };
    }

    private function actions(RewardTransaction $row): string
    {
        if ($row->trashed()) {
            return '<button class="btn btn-success btn-xs btn-restore" data-id="' . $row->id . '"><i class="fas fa-undo"></i></button> '
                . '<button class="btn btn-danger btn-xs btn-force-delete" data-id="' . $row->id . '"><i class="fas fa-times"></i></button>';
        }

        return '<a href="' . route('pancawaluya.reward-transactions.edit', $row) . '" class="btn btn-warning btn-xs"><i class="fas fa-edit"></i></a> '
            . '<button class="btn btn-danger btn-xs btn-delete" data-id="' . $row->id . '"><i class="fas fa-trash"></i></button>';
    }
}
