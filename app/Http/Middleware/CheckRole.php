<?php

namespace App\Http\Middleware;

use App\Services\RoleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Maps route prefixes to the permission names stored in the database.
     */
    private array $moduleMap = [
        'admin.dashboard' => 'Dashboard',
        'admin.attendance' => 'Attendance Logs',
        'admin.departments' => 'Departments',
        'admin.personnel' => 'Library Personnel',
        'admin.members' => 'Library Members',
        'admin.books' => 'Book Management',
        'admin.borrow' => 'Borrow Book',
        'admin.return' => 'Return Book',
        'admin.book-list' => 'Book List / Search',
        'admin.reports' => 'Report',
        'admin.audit-trail' => 'Audit Trail',
        'admin.roles' => 'Role Permission',
    ];

    /**
     * Receives the service used for module permission lookups.
     */
    public function __construct(private RoleService $roleService)
    {
    }

    /**
     * Requires an authenticated session and checks database-backed access.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->session()->has('personnel_id')) {
            return redirect()->route('login')->withErrors([
                'login' => 'Please log in first.',
            ]);
        }

        $currentRole = strtolower(trim((string) $request->session()->get('personnel_role')));
        $routeName = $request->route()?->getName();
        $requestedModule = $this->resolveModule($routeName);

        if ($requestedModule === 'Role Permission' && $currentRole !== 'admin') {
            return $this->deny($request);
        }

        if ($requestedModule !== null) {
            if (!$this->roleService->hasPermission($currentRole, $requestedModule)) {
                return $this->deny($request);
            }

            return $next($request);
        }

        $allowedRoles = array_map(
            static fn (string $role): string => strtolower(trim($role)),
            $roles
        );

        if ($allowedRoles !== [] && !in_array($currentRole, $allowedRoles, true)) {
            return $this->deny($request);
        }

        return $next($request);
    }

    /**
     * Matches a route name to the module permission it requires.
     */
    private function resolveModule(?string $routeName): ?string
    {
        if (!$routeName) {
            return null;
        }

        foreach ($this->moduleMap as $routePrefix => $moduleName) {
            if (str_starts_with($routeName, $routePrefix)) {
                return $moduleName;
            }
        }

        return null;
    }

    /**
     * Sends unauthorized users back with a readable access message.
     */
    private function deny(Request $request): Response
    {
        $message = 'Unauthorized access. You do not have permission to view this module.';
        $currentUrl = $request->fullUrl();
        $previousUrl = url()->previous();

        if ($previousUrl !== $currentUrl) {
            return redirect($previousUrl)->withErrors([
                'access' => $message,
            ]);
        }

        return redirect()->route('admin.dashboard')->withErrors([
            'access' => $message,
        ]);
    }
}
