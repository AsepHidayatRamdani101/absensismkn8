<?php

namespace App\Http\Controllers\Pancawaluya;

use App\Exports\Pancawaluya\TransactionHistoriesExport;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Services\Pancawaluya\TransactionHistoryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TransactionHistoryController extends Controller
{
    public function __construct(private readonly TransactionHistoryService $service) {}

    public function index(Request $request)
    {
        return view('admin.pancawaluya.transaction_histories.index', [
            'academicYears' => AcademicYear::query()->orderByDesc('id')->get(),
            'classrooms' => Classroom::query()->orderBy('nama_kelas')->get(),
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

        $paginator = $this->service->paginate($this->filters($request), $length);

        $rows = collect($paginator->items())->values()->map(function ($row, int $index) use ($start) {
            return [
                'no' => $start + $index + 1,
                'date' => e(optional($row->transaction_date)->format('d-m-Y')),
                'student' => e((string) ($row->student?->nama_lengkap ?? '-')),
                'class' => e((string) ($row->classroom?->nama_kelas ?? '-')),
                'type' => e((string) $row->reference_type),
                'action' => e((string) $row->action),
                'status' => e((string) ($row->status ?? '-')),
                'source' => e((string) ($row->source ?? '-')),
                'actor' => e((string) ($row->actor?->name ?? '-')),
                'score' => e((string) ($row->score_before ?? 0)) . ' -> ' . e((string) ($row->score_after ?? 0)),
            ];
        })->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $paginator->total(),
            'recordsFiltered' => $paginator->total(),
            'data' => $rows,
        ]);
    }

    public function exportExcel(Request $request)
    {
        return Excel::download(new TransactionHistoriesExport($this->service->allForExport($this->filters($request))), 'pancawaluya-transaction-histories.xlsx');
    }

    public function exportCsv(Request $request)
    {
        return Excel::download(new TransactionHistoriesExport($this->service->allForExport($this->filters($request))), 'pancawaluya-transaction-histories.csv');
    }

    public function exportPdf(Request $request)
    {
        $rows = $this->service->allForExport($this->filters($request));

        return Pdf::loadView('admin.pancawaluya.transaction_histories.export_pdf', ['rows' => $rows])->setPaper('a4', 'landscape')->download('pancawaluya-transaction-histories.pdf');
    }

    public function print(Request $request)
    {
        $rows = $this->service->allForExport($this->filters($request));

        return view('admin.pancawaluya.transaction_histories.print', ['rows' => $rows]);
    }

    private function filters(Request $request): array
    {
        return [
            'search' => (string) data_get($request->input('search', []), 'value', $request->input('search', '')),
            'academic_year_id' => $request->string('academic_year_id')->toString(),
            'semester' => $request->string('semester')->toString(),
            'classroom_id' => $request->string('classroom_id')->toString(),
            'student_id' => $request->string('student_id')->toString(),
            'status' => $request->string('status')->toString(),
            'source' => $request->string('source')->toString(),
            'reference_type' => $request->string('reference_type')->toString(),
            'action' => $request->string('action')->toString(),
            'from_date' => $request->string('from_date')->toString(),
            'to_date' => $request->string('to_date')->toString(),
        ];
    }
}
