@if (session('success'))
    <div class="alert alert--success" data-dismissible role="status">
        <span>{{ session('success') }}</span>
        <button type="button" data-dismiss aria-label="Tutup notifikasi">&times;</button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert--danger" data-dismissible role="alert">
        <span>{{ session('error') }}</span>
        <button type="button" data-dismiss aria-label="Tutup notifikasi">&times;</button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert--danger" data-dismissible role="alert">
        <div>
            <strong>Ada data yang perlu diperiksa.</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <button type="button" data-dismiss aria-label="Tutup notifikasi">&times;</button>
    </div>
@endif
