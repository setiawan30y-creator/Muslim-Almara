<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

// @2026-Alifiandra
class AuditController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless($request->user()->hasPermission('audit.view'), 403);
        $action = trim((string) $request->query('aksi'));
        $logs = AuditLog::query()->with('actor:id,name')
            ->when($action !== '', fn ($q) => $q->where('action', 'like', $action.'%'))
            ->latest('created_at')->paginate(40)->withQueryString();

        return view('admin.audit', ['logs' => $logs, 'action' => $action]);
    }
}
