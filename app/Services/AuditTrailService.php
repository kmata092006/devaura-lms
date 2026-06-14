<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class AuditTrailService
{
    /**
     * Stores an audit trail entry without interrupting the original action.
     */
    public function record(
        string $actionType,
        string $moduleName,
        ?string $actionDetails = null,
        ?Request $request = null,
        array $actor = []
    ): void {
        try {
            $request = $request ?? request();

            DB::table('audit_trail')->insert([
                'personnel_id' => $actor['personnel_id'] ?? session('personnel_id'),
                'username' => $actor['username'] ?? session('username'),
                'personnel_role' => $actor['personnel_role'] ?? session('personnel_role'),
                'action_type' => strtoupper($actionType),
                'module_name' => $moduleName,
                'action_details' => $actionDetails,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            // Audit failures must not interrupt the original system action.
        }
    }

    /**
     * Returns filtered audit trail records for the admin audit page.
     */
    public function getAuditLogs(array $filters): Collection
    {
        $query = DB::table('audit_trail')
            ->select(
                'audit_id',
                'personnel_id',
                'username',
                'personnel_role',
                'action_type',
                'module_name',
                'action_details',
                'ip_address',
                'created_at'
            );

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);

            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('personnel_role', 'like', "%{$search}%")
                    ->orWhere('action_type', 'like', "%{$search}%")
                    ->orWhere('module_name', 'like', "%{$search}%")
                    ->orWhere('action_details', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['personnel_role'])) {
            $query->where('personnel_role', $filters['personnel_role']);
        }

        if (!empty($filters['action_type'])) {
            $query->where('action_type', $filters['action_type']);
        }

        if (!empty($filters['module_name'])) {
            $query->where('module_name', $filters['module_name']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('audit_id')
            ->get()
            ->map(function ($log) {
                $log->display_details = $this->buildDisplayDetails(
                    $log->action_type,
                    $log->module_name
                );

                return $log;
            });
    }

    /**
     * Builds safe audit summaries without exposing raw request fields.
     */
    private function buildDisplayDetails(string $actionType, string $moduleName): string
    {
        return match (strtoupper($actionType)) {
            'LOGIN' => 'User signed in to the system.',
            'LOGOUT' => 'User signed out of the system.',
            'TIME IN' => 'An attendance time-in action was recorded.',
            'TIME OUT' => 'An attendance time-out action was recorded.',
            'BORROW' => 'A book borrowing transaction was recorded.',
            'RETURN' => 'A book return action was processed.',
            'CREATE' => "A new record was created in {$moduleName}.",
            'UPDATE' => "A record was updated in {$moduleName}.",
            'DELETE' => "A record was deleted from {$moduleName}.",
            default => "A system action was completed in {$moduleName}.",
        };
    }

    /**
     * Builds distinct filter options from existing audit records.
     */
    public function getFilterOptions(): array
    {
        return [
            'roles' => DB::table('audit_trail')
                ->whereNotNull('personnel_role')
                ->distinct()
                ->orderBy('personnel_role')
                ->pluck('personnel_role'),

            'actions' => DB::table('audit_trail')
                ->distinct()
                ->orderBy('action_type')
                ->pluck('action_type'),

            'modules' => DB::table('audit_trail')
                ->distinct()
                ->orderBy('module_name')
                ->pluck('module_name'),
        ];
    }
}
