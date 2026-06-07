<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;

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
     * Shows the selected report with normalized filters.
     */
    public function index(Request $request)
    {
        $data = $this->reportService->getReportPageData($request->all());

        return view('admin.reports.index', $data);
    }
}
