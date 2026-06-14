<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    private ReportService $reportService;

    /**
     * Injects the report service used by the reports page.
     */
    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Shows the selected report with validated and normalized filters.
     */
    public function index(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'report_type' => ['nullable', 'string', 'max:50'],
                'search' => ['nullable', 'string', 'max:255'],
                'role' => ['nullable', 'string', 'max:50'],
                'status' => ['nullable', 'string', 'max:50'],
                'genre_id' => ['nullable', 'integer', 'min:1'],
                'author_id' => ['nullable', 'integer', 'min:1'],
                'publisher_id' => ['nullable', 'integer', 'min:1'],
                'date_from' => [
                    'nullable',
                    'date_format:Y-m-d',
                    'before_or_equal:today',
                ],
                'date_to' => [
                    'nullable',
                    'date_format:Y-m-d',
                    'before_or_equal:today',
                    function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                        $dateFrom = $request->input('date_from');

                        if ($dateFrom && $value < $dateFrom) {
                            $fail('Date To must be the same as or later than Date From.');
                        }
                    },
                ],
            ],
            [
                'date_from.date_format' => 'Date From must be a valid date.',
                'date_from.before_or_equal' => 'Date From cannot be later than today.',
                'date_to.date_format' => 'Date To must be a valid date.',
                'date_to.before_or_equal' => 'Date To cannot be later than today.',
            ]
        );

        if ($validator->fails()) {
            $safeFilters = $request->except(['date_from', 'date_to', 'page']);

            return redirect()
                ->route('admin.reports.index', $safeFilters)
                ->withErrors($validator)
                ->withInput();
        }

        $data = $this->reportService->getReportPageData($validator->validated());

        return view('admin.reports.index', $data);
    }
}
