<?php

namespace App\Http\Controllers;

use App\Models\SecurityAuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = SecurityAuditLog::query()
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($request->filled('action')) {
            $query->where('action', 'like', '%'.$request->string('action').'%');
        }

        $logs = $query->paginate(40)->withQueryString();

        return view('detections.audit-log', compact('logs'));
    }
}
