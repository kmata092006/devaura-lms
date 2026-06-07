<?php

namespace App\Http\Middleware;

use App\Services\AuditTrailService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditTrailLogger
{
    /**
     * Records successful write actions after the request is handled.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if ($this->shouldRecord($request, $response)) {
            app(AuditTrailService::class)->record(
                $this->getActionType($request),
                $this->getModuleName($request),
                $this->getActionDetails($request),
                $request
            );
        }

        return $response;
    }

    /**
     * Decides whether the request should be saved in the audit trail.
     */
    private function shouldRecord(Request $request, mixed $response): bool
    {
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return false;
        }

        if ($request->is('admin/audit-trail*')) {
            return false;
        }

        if (method_exists($response, 'getStatusCode') && $response->getStatusCode() >= 400) {
            return false;
        }

        return true;
    }

    /**
     * Infers the audit action type from route, path, and HTTP method.
     */
    private function getActionType(Request $request): string
    {
        $routeName = $request->route()?->getName() ?? '';
        $path = $request->path();
        $method = $request->method();

        if (Str::contains($routeName, ['login']) || Str::contains($path, ['login'])) {
            return 'LOGIN';
        }

        if (Str::contains($routeName, ['logout']) || Str::contains($path, ['logout'])) {
            return 'LOGOUT';
        }

        if (Str::contains($routeName, ['time-in', 'timeIn']) || Str::contains($path, ['time-in'])) {
            return 'TIME IN';
        }

        if (Str::contains($routeName, ['time-out', 'timeOut']) || Str::contains($path, ['time-out'])) {
            return 'TIME OUT';
        }

        if (Str::contains($routeName, ['borrow']) || Str::contains($path, ['borrow'])) {
            return 'BORROW';
        }

        if (Str::contains($routeName, ['return']) || Str::contains($path, ['return'])) {
            return 'RETURN';
        }

        if ($method === 'DELETE') {
            return 'DELETE';
        }

        if (in_array($method, ['PUT', 'PATCH'], true)) {
            return 'UPDATE';
        }

        return 'CREATE';
    }

    /**
     * Maps the request path to the affected LMS module.
     */
    private function getModuleName(Request $request): string
    {
        $path = $request->path();

        return match (true) {
            Str::contains($path, 'admin/attendance') => 'Attendance Logs',
            Str::contains($path, 'admin/departments') => 'Departments',
            Str::contains($path, 'admin/personnel') => 'Library Personnel',
            Str::contains($path, 'admin/members') => 'Library Members',
            Str::contains($path, 'admin/books') => 'Book Management',
            Str::contains($path, 'admin/borrow') => 'Borrow Book',
            Str::contains($path, 'admin/return') => 'Return Book',
            Str::contains($path, 'admin/reports') => 'Reports',
            Str::contains($path, 'attendance') => 'Attendance Kiosk',
            Str::contains($path, 'login') => 'Authentication',
            Str::contains($path, 'logout') => 'Authentication',
            default => 'System',
        };
    }

    /**
     * Builds safe audit details while excluding passwords and framework fields.
     */
    private function getActionDetails(Request $request): string
    {
        $routeName = $request->route()?->getName() ?? 'unknown route';

        $safeData = collect($request->except([
            '_token',
            '_method',
            'password',
            'password_confirmation',
        ]))
            ->map(function ($value) {
                if (is_array($value)) {
                    return json_encode($value);
                }

                return (string) $value;
            })
            ->filter()
            ->map(function ($value, $key) {
                return "{$key}: {$value}";
            })
            ->values()
            ->implode(', ');

        if (!$safeData) {
            return "Action performed through route: {$routeName}.";
        }

        return "Action performed through route: {$routeName}. Data: {$safeData}.";
    }
}
