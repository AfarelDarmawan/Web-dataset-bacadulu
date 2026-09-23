<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::query()
            ->with('user')
            ->when($request->filled('action'), fn ($query) => $query->where('action', 'like', '%'.$request->string('action')->trim().'%'))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.audits.index', compact('logs'));
    }
}
