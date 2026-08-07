<?php

namespace App\Http\Controllers\Internal\Settings;

use App\Http\Controllers\Controller;
use App\Models\DashboardAuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * General-purpose audit log viewer. Reads the existing DashboardAuditLog
 * table (App\Models\DashboardAuditLog::record(), currently only called from
 * Internal\ExecutiveDashboard\AdminController) rather than introducing a
 * parallel logging table -- the model's shape (user/action/target/old-new
 * values) is already generic. As more modules start calling
 * DashboardAuditLog::record(), their entries will appear here too without
 * any change to this controller.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('settings.view'), 403);

        return view('internal.settings.audit-logs', [
            'logs' => DashboardAuditLog::with('user')->latest()->paginate(30),
        ]);
    }
}
