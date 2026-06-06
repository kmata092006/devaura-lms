<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class AuditTrailService
{
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
            /*
             * Audit logging must never break the main system action.
             * If audit insertion fails, the original action should still continue.
             */
        }
    }

    public function getAuditLogs(array $filters): LengthAwarePaginator
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
                $q->where('audit_id', 'like', "%{$search}%")
                    ->orWhere('personnel_id', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
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
            ->paginate(15)
            ->withQueryString();
    }

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