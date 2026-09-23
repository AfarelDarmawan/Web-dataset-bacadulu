@extends('layouts.admin')
@section('title', 'Audit Log')
@section('page_title', 'Audit log')
@section('content')
<div class="page-head"><div><span class="eyebrow">Traceability</span><h1>Audit log</h1><p>Jejak aktivitas penting untuk perubahan data, keputusan akses, dan ekspor.</p></div></div>
<form class="toolbar" method="GET"><div class="field field--search"><label for="action">Cari aksi</label><input id="action" name="action" value="{{ request('action') }}" placeholder="dataset.published"></div><button class="button button--line" type="submit">Cari</button>@if(request('action'))<a href="{{ route('admin.audits.index') }}">Reset</a>@endif</form>
<section class="panel panel--flush">
    @if($logs->isEmpty())
        <div class="panel-empty"><span>Belum ada aktivitas</span><p>Log akan terbentuk otomatis ketika pengguna dan administrator menjalankan aksi penting.</p></div>
    @else
        <div class="table-scroll"><table class="data-table admin-table audit-table"><thead><tr><th>Waktu</th><th>Aksi</th><th>Aktor</th><th>Objek</th><th>IP</th><th>Metadata</th></tr></thead><tbody>@foreach($logs as $log)<tr><td>{{ $log->created_at->format('d M Y') }}<small>{{ $log->created_at->format('H:i:s') }}</small></td><td><code>{{ $log->action }}</code></td><td>{{ $log->user?->name ?: 'System' }}<small>{{ $log->user?->email }}</small></td><td>{{ $log->auditable_type ? class_basename($log->auditable_type).' #'.$log->auditable_id : '—' }}</td><td><code>{{ $log->ip_address ?: '—' }}</code></td><td><small class="audit-json">{{ $log->metadata ? json_encode($log->metadata, JSON_UNESCAPED_UNICODE) : '—' }}</small></td></tr>@endforeach</tbody></table></div><div class="pagination-wrap pagination-wrap--panel">{{ $logs->links() }}</div>
    @endif
</section>
@endsection
