<?php

namespace App\Http\Controllers\Pancawaluya;

use App\Exports\Pancawaluya\ViolationCategoriesExport;
use App\Exports\TemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pancawaluya\BulkIdsRequest;
use App\Http\Requests\Pancawaluya\ImportMasterRequest;
use App\Http\Requests\Pancawaluya\StoreViolationCategoryRequest;
use App\Http\Requests\Pancawaluya\UpdateViolationCategoryRequest;
use App\Imports\Pancawaluya\ViolationCategoriesImport;
use App\Models\ViolationCategory;
use App\Services\Pancawaluya\ViolationCategoryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ViolationCategoryController extends Controller
{
    public function __construct(private readonly ViolationCategoryService $service) {}

    public function index(Request $request)
    {
        return view('admin.pancawaluya.violation_categories.index', [
            'statusFilter' => (string) $request->input('status', ''),
            'searchFilter' => (string) $request->input('search', ''),
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
            'status' => (string) $request->input('status', ''),
            'only_trashed' => (bool) $request->boolean('only_trashed'),
        ];

        $paginator = $this->service->paginate($filters, $length);

        $rows = collect($paginator->items())->values()->map(function (ViolationCategory $row, int $index) use ($start) {
            $isTrashed = $row->trashed();

            return [
                'checkbox' => '<input type="checkbox" class="check-item" value="' . $row->id . '">',
                'no' => $start + $index + 1,
                'code' => e($row->code),
                'name' => e($row->name),
                'description' => e((string) ($row->description ?? '-')),
                'status' => $row->is_active
                    ? '<span class="badge badge-success">Aktif</span>'
                    : '<span class="badge badge-secondary">Nonaktif</span>',
                'created_by' => e((string) ($row->created_by ?? '-')),
                'updated_at' => e(optional($row->updated_at)->format('d-m-Y H:i') ?? '-'),
                'actions' => $this->actionButtons($row, $isTrashed),
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
        return view('admin.pancawaluya.violation_categories.create');
    }

    public function store(StoreViolationCategoryRequest $request)
    {
        $this->service->create($request->validated(), $request);

        return redirect()->route('pancawaluya.violation-categories.index')->with('success', 'Kategori pelanggaran berhasil ditambahkan.');
    }

    public function edit(ViolationCategory $violationCategory)
    {
        return view('admin.pancawaluya.violation_categories.edit', [
            'violationCategory' => $violationCategory,
        ]);
    }

    public function update(UpdateViolationCategoryRequest $request, ViolationCategory $violationCategory)
    {
        $this->service->update($violationCategory, $request->validated(), $request);

        return redirect()->route('pancawaluya.violation-categories.index')->with('success', 'Kategori pelanggaran berhasil diperbarui.');
    }

    public function destroy(Request $request, ViolationCategory $violationCategory)
    {
        $this->service->softDelete($violationCategory, $request);

        return response()->json([
            'success' => true,
            'message' => 'Kategori pelanggaran berhasil dihapus (soft delete).',
        ]);
    }

    public function bulkDelete(BulkIdsRequest $request)
    {
        $count = $this->service->bulkSoftDelete($request->validated('ids'), $request);

        return response()->json([
            'success' => true,
            'message' => $count . ' kategori pelanggaran berhasil dihapus.',
        ]);
    }

    public function restore(Request $request, int $id)
    {
        abort_if(!$this->service->restore($id, $request), 404);

        return response()->json([
            'success' => true,
            'message' => 'Kategori pelanggaran berhasil dipulihkan.',
        ]);
    }

    public function bulkRestore(BulkIdsRequest $request)
    {
        $count = $this->service->bulkRestore($request->validated('ids'), $request);

        return response()->json([
            'success' => true,
            'message' => $count . ' kategori pelanggaran berhasil dipulihkan.',
        ]);
    }

    public function forceDelete(Request $request, int $id)
    {
        abort_if(!$this->service->forceDelete($id, $request), 404);

        return response()->json([
            'success' => true,
            'message' => 'Kategori pelanggaran dihapus permanen.',
        ]);
    }

    public function exportExcel(Request $request)
    {
        $rows = $this->service->allForExport([
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'only_trashed' => $request->boolean('only_trashed'),
        ], true);

        return Excel::download(new ViolationCategoriesExport($rows), 'master-violation-categories.xlsx');
    }

    public function exportCsv(Request $request)
    {
        $rows = $this->service->allForExport([
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'only_trashed' => $request->boolean('only_trashed'),
        ], true);

        return Excel::download(new ViolationCategoriesExport($rows), 'master-violation-categories.csv');
    }

    public function exportPdf(Request $request)
    {
        $rows = $this->service->allForExport([
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'only_trashed' => $request->boolean('only_trashed'),
        ], true);

        $pdf = Pdf::loadView('admin.pancawaluya.violation_categories.export_pdf', [
            'rows' => $rows,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('master-violation-categories.pdf');
    }

    public function print(Request $request)
    {
        $rows = $this->service->allForExport([
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'only_trashed' => $request->boolean('only_trashed'),
        ], true);

        return view('admin.pancawaluya.violation_categories.print', [
            'rows' => $rows,
        ]);
    }

    public function import(ImportMasterRequest $request)
    {
        $preview = $request->boolean('preview');
        $importer = new ViolationCategoriesImport($preview);

        try {
            DB::transaction(function () use ($request, $importer): void {
                Excel::import($importer, $request->file('file'));
            });
        } catch (Throwable $exception) {
            return redirect()->route('pancawaluya.violation-categories.index')->withErrors([
                'file' => $exception->getMessage(),
            ]);
        }

        if ($preview) {
            return redirect()->route('pancawaluya.violation-categories.index')->with('success', 'Preview data berhasil dimuat (' . count($importer->getPreviewRows()) . ' baris).');
        }

        return redirect()->route('pancawaluya.violation-categories.index')->with('success', $importer->getSuccessMessage());
    }

    public function template()
    {
        return Excel::download(
            new TemplateExport(
                ['kode', 'nama_kategori', 'deskripsi', 'status'],
                [['VIO-TAT', 'Pelanggaran Tata Tertib', 'Kategori pelanggaran ketertiban', 'Aktif']]
            ),
            'template-violation-categories.xlsx'
        );
    }

    private function actionButtons(ViolationCategory $row, bool $isTrashed): string
    {
        if ($isTrashed) {
            return '<button class="btn btn-success btn-xs btn-restore" data-id="' . $row->id . '"><i class="fas fa-undo"></i></button> '
                . '<button class="btn btn-danger btn-xs btn-force-delete" data-id="' . $row->id . '"><i class="fas fa-times"></i></button>';
        }

        return '<a href="' . route('pancawaluya.violation-categories.edit', $row) . '" class="btn btn-warning btn-xs"><i class="fas fa-edit"></i></a> '
            . '<button class="btn btn-danger btn-xs btn-delete" data-id="' . $row->id . '"><i class="fas fa-trash"></i></button>';
    }
}
