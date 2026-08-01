<?php

namespace App\Http\Controllers\Pancawaluya;

use App\Exports\Pancawaluya\ViolationItemsExport;
use App\Exports\TemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pancawaluya\BulkIdsRequest;
use App\Http\Requests\Pancawaluya\ImportMasterRequest;
use App\Http\Requests\Pancawaluya\StoreViolationItemRequest;
use App\Http\Requests\Pancawaluya\UpdateViolationItemRequest;
use App\Imports\Pancawaluya\ViolationItemsImport;
use App\Models\CharacterDimension;
use App\Models\ViolationCategory;
use App\Models\ViolationItem;
use App\Services\Pancawaluya\ViolationItemService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ViolationController extends Controller
{
    public function __construct(private readonly ViolationItemService $service) {}

    public function index(Request $request)
    {
        return view('admin.pancawaluya.violations.index', [
            'statusFilter' => (string) $request->input('status', ''),
            'searchFilter' => (string) $request->input('search', ''),
            'categoryFilter' => (string) $request->input('category_id', ''),
            'dimensionFilter' => (string) $request->input('dimension_id', ''),
            'categories' => ViolationCategory::query()->orderBy('name')->get(),
            'dimensions' => CharacterDimension::query()->orderBy('name')->get(),
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
            'category_id' => (string) $request->input('category_id', ''),
            'dimension_id' => (string) $request->input('dimension_id', ''),
            'only_trashed' => (bool) $request->boolean('only_trashed'),
        ];

        $paginator = $this->service->paginate($filters, $length);

        $rows = collect($paginator->items())->values()->map(function (ViolationItem $row, int $index) use ($start) {
            $isTrashed = $row->trashed();
            $mapping = $row->mappings->first();

            return [
                'checkbox' => '<input type="checkbox" class="check-item" value="' . $row->id . '">',
                'no' => $start + $index + 1,
                'code' => e($row->code),
                'category' => e((string) ($row->category?->name ?? '-')),
                'name' => e($row->name),
                'point' => e((string) $row->point),
                'dimension' => e((string) ($mapping?->dimension?->name ?? '-')),
                'weight' => e((string) ($mapping?->weight ?? '-')),
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
        return view('admin.pancawaluya.violations.create', [
            'categories' => ViolationCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'dimensions' => CharacterDimension::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreViolationItemRequest $request)
    {
        $this->service->create($request->validated(), $request);

        return redirect()->route('pancawaluya.violations.index')->with('success', 'Master pelanggaran berhasil ditambahkan.');
    }

    public function edit(ViolationItem $violation)
    {
        $violation->load('mappings');

        return view('admin.pancawaluya.violations.edit', [
            'violation' => $violation,
            'categories' => ViolationCategory::query()->orderBy('name')->get(),
            'dimensions' => CharacterDimension::query()->orderBy('name')->get(),
            'selectedMapping' => $violation->mappings->first(),
        ]);
    }

    public function update(UpdateViolationItemRequest $request, ViolationItem $violation)
    {
        $this->service->update($violation, $request->validated(), $request);

        return redirect()->route('pancawaluya.violations.index')->with('success', 'Master pelanggaran berhasil diperbarui.');
    }

    public function destroy(Request $request, ViolationItem $violation)
    {
        $this->service->softDelete($violation, $request);

        return response()->json([
            'success' => true,
            'message' => 'Master pelanggaran berhasil dihapus (soft delete).',
        ]);
    }

    public function bulkDelete(BulkIdsRequest $request)
    {
        $count = $this->service->bulkSoftDelete($request->validated('ids'), $request);

        return response()->json([
            'success' => true,
            'message' => $count . ' master pelanggaran berhasil dihapus.',
        ]);
    }

    public function restore(Request $request, int $id)
    {
        abort_if(!$this->service->restore($id, $request), 404);

        return response()->json([
            'success' => true,
            'message' => 'Master pelanggaran berhasil dipulihkan.',
        ]);
    }

    public function bulkRestore(BulkIdsRequest $request)
    {
        $count = $this->service->bulkRestore($request->validated('ids'), $request);

        return response()->json([
            'success' => true,
            'message' => $count . ' master pelanggaran berhasil dipulihkan.',
        ]);
    }

    public function forceDelete(Request $request, int $id)
    {
        abort_if(!$this->service->forceDelete($id, $request), 404);

        return response()->json([
            'success' => true,
            'message' => 'Master pelanggaran dihapus permanen.',
        ]);
    }

    public function exportExcel(Request $request)
    {
        $rows = $this->service->allForExport($this->exportFilters($request), true);

        return Excel::download(new ViolationItemsExport($rows), 'master-violations.xlsx');
    }

    public function exportCsv(Request $request)
    {
        $rows = $this->service->allForExport($this->exportFilters($request), true);

        return Excel::download(new ViolationItemsExport($rows), 'master-violations.csv');
    }

    public function exportPdf(Request $request)
    {
        $rows = $this->service->allForExport($this->exportFilters($request), true);

        $pdf = Pdf::loadView('admin.pancawaluya.violations.export_pdf', ['rows' => $rows])->setPaper('a4', 'landscape');

        return $pdf->download('master-violations.pdf');
    }

    public function print(Request $request)
    {
        $rows = $this->service->allForExport($this->exportFilters($request), true);

        return view('admin.pancawaluya.violations.print', ['rows' => $rows]);
    }

    public function import(ImportMasterRequest $request)
    {
        $preview = $request->boolean('preview');
        $importer = new ViolationItemsImport($preview);

        try {
            DB::transaction(function () use ($request, $importer): void {
                Excel::import($importer, $request->file('file'));
            });
        } catch (Throwable $exception) {
            return redirect()->route('pancawaluya.violations.index')->withErrors([
                'file' => $exception->getMessage(),
            ]);
        }

        if ($preview) {
            return redirect()->route('pancawaluya.violations.index')->with('success', 'Preview data berhasil dimuat (' . count($importer->getPreviewRows()) . ' baris).');
        }

        return redirect()->route('pancawaluya.violations.index')->with('success', $importer->getSuccessMessage());
    }

    public function template()
    {
        return Excel::download(
            new TemplateExport(
                ['kode_kategori', 'kode_pelanggaran', 'nama_pelanggaran', 'point', 'kode_dimensi', 'weight', 'deskripsi', 'status'],
                [['VIO-TAT', 'VIO-001', 'Terlambat Datang', '10', 'BENER', '-1', 'Pelanggaran ketertiban waktu', 'Aktif']]
            ),
            'template-violations.xlsx'
        );
    }

    private function exportFilters(Request $request): array
    {
        return [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'category_id' => $request->string('category_id')->toString(),
            'dimension_id' => $request->string('dimension_id')->toString(),
            'only_trashed' => $request->boolean('only_trashed'),
        ];
    }

    private function actionButtons(ViolationItem $row, bool $isTrashed): string
    {
        if ($isTrashed) {
            return '<button class="btn btn-success btn-xs btn-restore" data-id="' . $row->id . '"><i class="fas fa-undo"></i></button> '
                . '<button class="btn btn-danger btn-xs btn-force-delete" data-id="' . $row->id . '"><i class="fas fa-times"></i></button>';
        }

        return '<a href="' . route('pancawaluya.violations.edit', $row) . '" class="btn btn-warning btn-xs"><i class="fas fa-edit"></i></a> '
            . '<button class="btn btn-danger btn-xs btn-delete" data-id="' . $row->id . '"><i class="fas fa-trash"></i></button>';
    }
}
