<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\RoleService;

class CheckRole
{
    private RoleService $roleService;

    /**
     * Maps route prefixes to exact Database Module Names.
     * This allows us to secure routes without changing web.php.
     */
    private array $moduleMap = [
        'admin.dashboard'   => 'Dashboard',
        'admin.attendance'  => 'Attendance Logs',
        'admin.departments' => 'Departments',
        'admin.personnel'   => 'Library Personnel',
        'admin.members'     => 'Library Members',
        'admin.books'       => 'Book Management',
        'admin.borrow'      => 'Borrow Book',
        'admin.return'      => 'Return Book',
        'admin.book-list'   => 'Book List / Search',
        'admin.reports'     => 'Report',
        'admin.audit-trail' => 'Audit Trail',
        'admin.roles'       => 'Role Permission',
    ];

    /**
     * Injects the RoleService to query the database.
     */
    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Checks if the logged-in user has the required role to access the page.
     * Now upgraded to check the database matrix directly based on the route name.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Check if user is logged in at all
        if (!session()->has('personnel_id')) {
            return redirect()->route('login')->withErrors([
                'login' => 'Please log in first.',
            ]);
        }

        // Get the current user's role from the session
        $currentRole = session('personnel_role');
        
        // Determine which module is being accessed based on the route name
        $routeName = $request->route()->getName();
        $requestedModule = null;

        if ($routeName) {
            foreach ($this->moduleMap as $routePrefix => $moduleName) {
                // Matches exact module regardless of specific action (index, store, update, etc.)
                if (str_starts_with($routeName, $routePrefix)) {
                    $requestedModule = $moduleName;
                    break;
                }
            }
        }

        if ($requestedModule) {
            // NEW LOGIC: Check the dynamic database matrix
            if (!$this->roleService->hasPermission($currentRole, $requestedModule)) {
                // If not allowed, kick them to the 403 Access Denied page
                abort(403, 'Unauthorized access. You do not have permission to view this module.');
            }
        } else {
            // FALLBACK LOGIC: If route isn't mapped above, use the old hardcoded web.php roles
            if (!in_array($currentRole, $roles)) {
                // If not allowed, kick them to the 403 Access Denied page
                abort(403, 'Unauthorized access. You do not have permission to view this module.');
            }
        }

        // If they are allowed, let the request proceed normally
        return $next($request);
    }
}