<?php

namespace App\Http\Controllers\Pancawaluya;

use App\Exports\Pancawaluya\RewardCategoriesExport;
use App\Exports\TemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pancawaluya\BulkIdsRequest;
use App\Http\Requests\Pancawaluya\ImportMasterRequest;
use App\Http\Requests\Pancawaluya\StoreRewardCategoryRequest;
use App\Http\Requests\Pancawaluya\UpdateRewardCategoryRequest;
use App\Imports\Pancawaluya\RewardCategoriesImport;
use App\Models\RewardCategory;
use App\Services\Pancawaluya\RewardCategoryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class RewardCategoryController extends Controller
{
    public function __construct(private readonly RewardCategoryService $service) {}

    public function index(Request $request)
    {
        return view('admin.pancawaluya.reward_categories.index', [
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

        $rows = collect($paginator->items())->values()->map(function (RewardCategory $row, int $index) use ($start) {
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
        return view('admin.pancawaluya.reward_categories.create');
    }

    public function store(StoreRewardCategoryRequest $request)
    {
        $this->service->create($request->validated(), $request);

        return redirect()->route('pancawaluya.reward-categories.index')->with('success', 'Kategori reward berhasil ditambahkan.');
    }

    public function edit(RewardCategory $rewardCategory)
    {
        return view('admin.pancawaluya.reward_categories.edit', [
            'rewardCategory' => $rewardCategory,
        ]);
    }

    public function update(UpdateRewardCategoryRequest $request, RewardCategory $rewardCategory)
    {
        $this->service->update($rewardCategory, $request->validated(), $request);

        return redirect()->route('pancawaluya.reward-categories.index')->with('success', 'Kategori reward berhasil diperbarui.');
    }

    public function destroy(Request $request, RewardCategory $rewardCategory)
    {
        $this->service->softDelete($rewardCategory, $request);

        return response()->json([
            'success' => true,
            'message' => 'Kategori reward berhasil dihapus (soft delete).',
        ]);
    }

    public function bulkDelete(BulkIdsRequest $request)
    {
        $count = $this->service->bulkSoftDelete($request->validated('ids'), $request);

        return response()->json([
            'success' => true,
            'message' => $count . ' kategori reward berhasil dihapus.',
        ]);
    }

    public function restore(Request $request, int $id)
    {
        abort_if(!$this->service->restore($id, $request), 404);

        return response()->json([
            'success' => true,
            'message' => 'Kategori reward berhasil dipulihkan.',
        ]);
    }

    public function bulkRestore(BulkIdsRequest $request)
    {
        $count = $this->service->bulkRestore($request->validated('ids'), $request);

        return response()->json([
            'success' => true,
            'message' => $count . ' kategori reward berhasil dipulihkan.',
        ]);
    }

    public function forceDelete(Request $request, int $id)
    {
        abort_if(!$this->service->forceDelete($id, $request), 404);

        return response()->json([
            'success' => true,
            'message' => 'Kategori reward dihapus permanen.',
        ]);
    }

    public function exportExcel(Request $request)
    {
        $rows = $this->service->allForExport([
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'only_trashed' => $request->boolean('only_trashed'),
        ], true);

        return Excel::download(new RewardCategoriesExport($rows), 'master-reward-categories.xlsx');
    }

    public function exportCsv(Request $request)
    {
        $rows = $this->service->allForExport([
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'only_trashed' => $request->boolean('only_trashed'),
        ], true);

        return Excel::download(new RewardCategoriesExport($rows), 'master-reward-categories.csv');
    }

    public function exportPdf(Request $request)
    {
        $rows = $this->service->allForExport([
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'only_trashed' => $request->boolean('only_trashed'),
        ], true);

        $pdf = Pdf::loadView('admin.pancawaluya.reward_categories.export_pdf', [
            'rows' => $rows,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('master-reward-categories.pdf');
    }

    public function print(Request $request)
    {
        $rows = $this->service->allForExport([
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'only_trashed' => $request->boolean('only_trashed'),
        ], true);

        return view('admin.pancawaluya.reward_categories.print', [
            'rows' => $rows,
        ]);
    }

    public function import(ImportMasterRequest $request)
    {
        $preview = $request->boolean('preview');
        $importer = new RewardCategoriesImport($preview);

        try {
            DB::transaction(function () use ($request, $importer): void {
                Excel::import($importer, $request->file('file'));
            });
        } catch (Throwable $exception) {
            return redirect()->route('pancawaluya.reward-categories.index')->withErrors([
                'file' => $exception->getMessage(),
            ]);
        }

        if ($preview) {
            return redirect()->route('pancawaluya.reward-categories.index')->with('success', 'Preview data berhasil dimuat (' . count($importer->getPreviewRows()) . ' baris).');
        }

        return redirect()->route('pancawaluya.reward-categories.index')->with('success', $importer->getSuccessMessage());
    }

    public function template()
    {
        return Excel::download(
            new TemplateExport(
                ['kode', 'nama_kategori', 'deskripsi', 'status'],
                [['RWD-AKT', 'Reward Akhlak', 'Kategori reward untuk akhlak', 'Aktif']]
            ),
            'template-reward-categories.xlsx'
        );
    }

    private function actionButtons(RewardCategory $row, bool $isTrashed): string
    {
        if ($isTrashed) {
            return '<button class="btn btn-success btn-xs btn-restore" data-id="' . $row->id . '"><i class="fas fa-undo"></i></button> '
                . '<button class="btn btn-danger btn-xs btn-force-delete" data-id="' . $row->id . '"><i class="fas fa-times"></i></button>';
        }

        return '<a href="' . route('pancawaluya.reward-categories.edit', $row) . '" class="btn btn-warning btn-xs"><i class="fas fa-edit"></i></a> '
            . '<button class="btn btn-danger btn-xs btn-delete" data-id="' . $row->id . '"><i class="fas fa-trash"></i></button>';
    }
}
