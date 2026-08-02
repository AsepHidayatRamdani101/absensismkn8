<?php

namespace App\Http\Controllers\Pancawaluya;

use App\Exports\Pancawaluya\RewardItemsExport;
use App\Exports\TemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pancawaluya\BulkIdsRequest;
use App\Http\Requests\Pancawaluya\ImportMasterRequest;
use App\Http\Requests\Pancawaluya\StoreRewardItemRequest;
use App\Http\Requests\Pancawaluya\UpdateRewardItemRequest;
use App\Imports\Pancawaluya\RewardItemsImport;
use App\Models\CharacterDimension;
use App\Models\RewardCategory;
use App\Models\RewardItem;
use App\Services\Pancawaluya\RewardItemService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class RewardController extends Controller
{
    public function __construct(private readonly RewardItemService $service) {}

    public function index(Request $request)
    {
        return view('admin.pancawaluya.rewards.index', [
            'statusFilter' => (string) $request->input('status', ''),
            'searchFilter' => (string) $request->input('search', ''),
            'categoryFilter' => (string) $request->input('category_id', ''),
            'dimensionFilter' => (string) $request->input('dimension_id', ''),
            'categories' => RewardCategory::query()->orderBy('name')->get(),
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

        $rows = collect($paginator->items())->values()->map(function (RewardItem $row, int $index) use ($start) {
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
        return view('admin.pancawaluya.rewards.create', [
            'categories' => RewardCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'dimensions' => CharacterDimension::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreRewardItemRequest $request)
    {
        $this->service->create($request->validated(), $request);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Master reward berhasil ditambahkan.']);
        }

        return redirect()->route('pancawaluya.rewards.index')->with('success', 'Master reward berhasil ditambahkan.');
    }

    public function edit(RewardItem $reward)
    {
        $reward->load('mappings');

        if (request()->ajax()) {
            $mapping = $reward->mappings->first();
            return response()->json([
                'id' => $reward->id,
                'code' => $reward->code,
                'name' => $reward->name,
                'reward_category_id' => $reward->reward_category_id,
                'point' => $reward->point,
                'description' => $reward->description,
                'is_active' => $reward->is_active,
                'character_dimension_id' => $mapping?->character_dimension_id,
                'weight' => $mapping?->weight,
            ]);
        }

        return view('admin.pancawaluya.rewards.edit', [
            'reward' => $reward,
            'categories' => RewardCategory::query()->orderBy('name')->get(),
            'dimensions' => CharacterDimension::query()->orderBy('name')->get(),
            'selectedMapping' => $reward->mappings->first(),
        ]);
    }

    public function update(UpdateRewardItemRequest $request, RewardItem $reward)
    {
        $this->service->update($reward, $request->validated(), $request);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Master reward berhasil diperbarui.']);
        }

        return redirect()->route('pancawaluya.rewards.index')->with('success', 'Master reward berhasil diperbarui.');
    }

    public function destroy(Request $request, RewardItem $reward)
    {
        $this->service->softDelete($reward, $request);

        return response()->json([
            'success' => true,
            'message' => 'Master reward berhasil dihapus (soft delete).',
        ]);
    }

    public function bulkDelete(BulkIdsRequest $request)
    {
        $count = $this->service->bulkSoftDelete($request->validated('ids'), $request);

        return response()->json([
            'success' => true,
            'message' => $count . ' master reward berhasil dihapus.',
        ]);
    }

    public function restore(Request $request, int $id)
    {
        abort_if(!$this->service->restore($id, $request), 404);

        return response()->json([
            'success' => true,
            'message' => 'Master reward berhasil dipulihkan.',
        ]);
    }

    public function bulkRestore(BulkIdsRequest $request)
    {
        $count = $this->service->bulkRestore($request->validated('ids'), $request);

        return response()->json([
            'success' => true,
            'message' => $count . ' master reward berhasil dipulihkan.',
        ]);
    }

    public function forceDelete(Request $request, int $id)
    {
        abort_if(!$this->service->forceDelete($id, $request), 404);

        return response()->json([
            'success' => true,
            'message' => 'Master reward dihapus permanen.',
        ]);
    }

    public function exportExcel(Request $request)
    {
        $rows = $this->service->allForExport($this->exportFilters($request), true);

        return Excel::download(new RewardItemsExport($rows), 'master-rewards.xlsx');
    }

    public function exportCsv(Request $request)
    {
        $rows = $this->service->allForExport($this->exportFilters($request), true);

        return Excel::download(new RewardItemsExport($rows), 'master-rewards.csv');
    }

    public function exportPdf(Request $request)
    {
        $rows = $this->service->allForExport($this->exportFilters($request), true);

        $pdf = Pdf::loadView('admin.pancawaluya.rewards.export_pdf', ['rows' => $rows])->setPaper('a4', 'landscape');

        return $pdf->download('master-rewards.pdf');
    }

    public function print(Request $request)
    {
        $rows = $this->service->allForExport($this->exportFilters($request), true);

        return view('admin.pancawaluya.rewards.print', ['rows' => $rows]);
    }

    public function import(ImportMasterRequest $request)
    {
        $preview = $request->boolean('preview');
        $importer = new RewardItemsImport($preview);

        try {
            DB::transaction(function () use ($request, $importer): void {
                Excel::import($importer, $request->file('file'));
            });
        } catch (Throwable $exception) {
            return redirect()->route('pancawaluya.rewards.index')->withErrors([
                'file' => $exception->getMessage(),
            ]);
        }

        if ($preview) {
            return redirect()->route('pancawaluya.rewards.index')->with('success', 'Preview data berhasil dimuat (' . count($importer->getPreviewRows()) . ' baris).');
        }

        return redirect()->route('pancawaluya.rewards.index')->with('success', $importer->getSuccessMessage());
    }

    public function template()
    {
        return Excel::download(
            new TemplateExport(
                ['kode_kategori', 'kode_reward', 'nama_reward', 'point', 'kode_dimensi', 'weight', 'deskripsi', 'status'],
                [['RWD-AKT', 'RWD-001', 'Membantu Teman', '20', 'BAGEUR', '1', 'Reward sikap sosial', 'Aktif']]
            ),
            'template-rewards.xlsx'
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

    private function actionButtons(RewardItem $row, bool $isTrashed): string
    {
        if ($isTrashed) {
            return '<button class="btn btn-success btn-xs btn-restore" data-id="' . $row->id . '"><i class="fas fa-undo"></i></button> '
                . '<button class="btn btn-danger btn-xs btn-force-delete" data-id="' . $row->id . '"><i class="fas fa-times"></i></button>';
        }

        return '<button class="btn btn-warning btn-xs btn-edit" data-id="' . $row->id . '"><i class="fas fa-edit"></i></button> '
            . '<button class="btn btn-danger btn-xs btn-delete" data-id="' . $row->id . '"><i class="fas fa-trash"></i></button>';
    }
}
