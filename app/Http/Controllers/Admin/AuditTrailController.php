<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;

class AuditTrailController extends Controller
{
    private AuditTrailService $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    public function index(Request $request)
    {
        $filters = [
            'search' => $request->query('search'),
            'personnel_role' => $request->query('personnel_role'),
            'action_type' => $request->query('action_type'),
            'module_name' => $request->query('module_name'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];

        $auditLogs = $this->auditTrailService->getAuditLogs($filters);
        $options = $this->auditTrailService->getFilterOptions();

        return view('admin.audit-trail.index', compact('auditLogs', 'filters', 'options'));
    }
}