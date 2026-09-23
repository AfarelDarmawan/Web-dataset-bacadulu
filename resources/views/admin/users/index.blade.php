@extends('layouts.admin')
@section('title', 'Pengguna')
@section('page_title', 'Pengguna')
@section('content')
<div class="page-head"><div><span class="eyebrow">Account registry</span><h1>Pengguna peneliti</h1><p>Periksa institusi, aktivitas permintaan, dan status akun.</p></div></div>
<form class="toolbar" method="GET"><div class="field field--search"><label for="q">Cari pengguna</label><input id="q" name="q" value="{{ request('q') }}" placeholder="Nama, email, atau institusi"></div><div class="field"><label for="status">Status</label><select id="status" name="status"><option value="">Semua status</option><option value="active" @selected(request('status') === 'active')>Aktif</option><option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option></select></div><button class="button button--line" type="submit">Terapkan</button>@if(request()->hasAny(['q','status']))<a href="{{ route('admin.users.index') }}">Reset</a>@endif</form>
<section class="panel panel--flush">
    @if($users->isEmpty())
        <div class="panel-empty"><span>Belum ada pengguna</span><p>Akun peneliti yang mendaftar akan tampil di sini.</p></div>
    @else
        <div class="table-scroll"><table class="data-table admin-table"><thead><tr><th>Pengguna</th><th>Institusi</th><th>Permintaan</th><th>Login terakhir</th><th>Status</th><th></th></tr></thead><tbody>@foreach($users as $user)<tr><td><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></td><td>{{ $user->institution ?: '—' }}</td><td>{{ $user->access_requests_count }}</td><td>{{ optional($user->last_login_at)->format('d M Y H:i') ?: 'Belum pernah' }}</td><td><span class="status status--{{ $user->status }}">{{ ucfirst($user->status) }}</span></td><td><form method="POST" action="{{ route('admin.users.toggle', $user) }}" data-confirm="Ubah status akun pengguna ini?">@csrf @method('PATCH')<button class="table-action" type="submit">{{ $user->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}</button></form></td></tr>@endforeach</tbody></table></div><div class="pagination-wrap pagination-wrap--panel">{{ $users->links() }}</div>
    @endif
</section>
@endsection
