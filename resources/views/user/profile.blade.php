@extends('layouts.storefront')

@section('title', 'Profil Saya - Mak Ethek')

@section('header_extra')
    <div class="px-1 pt-2 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('storefront.index') }}" class="btn btn-sm btn-light border rounded-pill px-2 py-1 text-decoration-none">
                <i class="icofont-rounded-left"></i> Belanja
            </a>
            <span class="fw-bold small text-dark">
                Akun Saya
            </span>
        </div>
        <form action="{{ route('logout') }}" method="POST" class="m-0">
            @csrf
            <button type="submit" class="btn btn-sm text-danger p-0 border-0 bg-transparent fw-semibold" style="font-size: 0.75rem;">
                <i class="icofont-logout"></i> Keluar
            </button>
        </form>
    </div>
@endsection

@section('content')
<div class="osahan-profile p-3">
    <!-- Profil Header Card -->
    <div class="card border rounded-3 shadow-sm bg-white mb-3 text-center p-3">
        <div class="position-relative d-inline-block mx-auto mb-2">
            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 64px; height: 64px; font-size: 1.8rem;">
                <i class="icofont-user"></i>
            </div>
            <span class="position-absolute bottom-0 end-0 badge rounded-pill bg-primary border border-white" style="font-size: 0.6rem;">
                <i class="icofont-check"></i>
            </span>
        </div>

        <h5 class="fw-bold text-dark mb-0">{{ $user->name }}</h5>
        <span class="text-muted small d-block mb-1">{{ $user->phone }}</span>
        
        <div class="d-flex justify-content-center align-items-center gap-2 flex-wrap">
            <span class="badge bg-success-subtle text-success border border-success px-2 py-1" style="font-size: 0.68rem;">
                WARGA {{ strtoupper($user->territory?->name ?? 'BLITAR') }}
            </span>
            @if($user->isB2bCustomer())
                <span class="badge bg-primary text-white px-2 py-1 fw-bold" style="font-size: 0.68rem;">
                    AKUN GROSIR B2B (MIN. RP 1 JT)
                </span>
            @else
                <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 0.68rem;">
                    PELANGGAN ECERAN B2C
                </span>
            @endif
        </div>
    </div>

    <!-- Statistik Belanja -->
    <div class="card border rounded-3 shadow-sm bg-white mb-3">
        <div class="card-body p-3">
            <div class="row text-center g-2">
                <div class="col-4 border-end">
                    <span class="d-block fw-bold fs-5 text-dark">{{ $stats['total_orders'] }}</span>
                    <small class="text-muted" style="font-size: 0.68rem;">Total Belanja</small>
                </div>
                <div class="col-4 border-end">
                    <span class="d-block fw-bold fs-5 text-primary">{{ $stats['processing_orders'] }}</span>
                    <small class="text-muted" style="font-size: 0.68rem;">Sedang Diproses</small>
                </div>
                <div class="col-4">
                    <span class="d-block fw-bold fs-5 text-success">{{ $stats['completed_orders'] }}</span>
                    <small class="text-muted" style="font-size: 0.68rem;">Pesanan Selesai</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Pintasan Menu Cepat -->
    <div class="card border rounded-3 shadow-sm bg-white mb-3 overflow-hidden">
        <div class="list-group list-group-flush small">
            <a href="{{ route('user.orders.index') }}" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="icofont-bag text-success fs-5"></i>
                    <span class="fw-semibold text-dark">Riwayat & Status Pesanan</span>
                </div>
                <i class="icofont-rounded-right text-muted"></i>
            </a>
        </div>
    </div>

    <!-- Daftar Multi-Alamat Pengiriman (Rumah Sendiri / Orang Tua / Warung - CUS-003) -->
    <div class="card border rounded-3 shadow-sm bg-white mb-3">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="icofont-location-pin text-danger me-1"></i> Buku Alamat Pengiriman ({{ $user->addresses->count() }})
                </h6>
                <button class="btn btn-sm btn-outline-success py-1 px-2" type="button" data-bs-toggle="collapse" data-bs-target="#addAddressCollapse" style="font-size: 0.72rem;">
                    + Tambah Alamat
                </button>
            </div>
            <small class="text-muted d-block mb-3" style="font-size: 0.72rem;">
                Simpan alamat rumah sendiri, rumah orang tua di Blitar, atau lokasi warung Anda.
            </small>

            <!-- Collapse Tambah Alamat Baru -->
            <div class="collapse mb-3" id="addAddressCollapse">
                <form action="{{ route('user.addresses.store') }}" method="POST" class="p-3 rounded border bg-light">
                    @csrf
                    <h6 class="fw-bold small text-dark mb-2">Tambah Alamat Penerima Baru</h6>
                    <div class="mb-2">
                        <label class="form-label small text-secondary mb-1">Label Alamat</label>
                        <input type="text" name="label" class="form-control form-control-sm" placeholder="Contoh: Rumah Orang Tua / Rumah Sendiri" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small text-secondary mb-1">Nama Penerima</label>
                        <input type="text" name="recipient_name" class="form-control form-control-sm" placeholder="Contoh: Ibu Hj. Salamah" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small text-secondary mb-1">No. WhatsApp Penerima</label>
                        <input type="text" name="recipient_phone" class="form-control form-control-sm" placeholder="Contoh: 081333999111" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small text-secondary mb-1">Kelurahan / Desa</label>
                        <select name="territory_id" class="form-select form-select-sm">
                            @foreach($villages as $vil)
                                <option value="{{ $vil->id }}" {{ $user->territory_id == $vil->id ? 'selected' : '' }}>
                                    {{ $vil->name }} (Kec. {{ $vil->parent?->name ?? 'Blitar' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small text-secondary mb-1">Alamat Lengkap</label>
                        <textarea name="full_address" rows="2" class="form-control form-control-sm" placeholder="Jalan, RT/RW, Nomor Rumah..." required></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small text-secondary mb-1">Catatan Patokan (Opsional)</label>
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Pagar warna coklat depan masjid">
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_default" value="1" id="isDefaultAddr">
                        <label class="form-check-label small" for="isDefaultAddr">Jadikan Alamat Utama</label>
                    </div>
                    <button type="submit" class="btn btn-sm btn-success w-100 fw-bold">Simpan Alamat</button>
                </form>
            </div>

            <!-- Daftar Alamat -->
            <div class="d-flex flex-column gap-2">
                @forelse($user->addresses as $addr)
                    <div class="p-2 rounded border {{ $addr->is_default ? 'border-success bg-success-subtle bg-opacity-10' : 'bg-light' }}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="badge {{ $addr->is_default ? 'bg-success' : 'bg-secondary' }} mb-1" style="font-size: 0.65rem;">
                                    {{ $addr->label }} {{ $addr->is_default ? '• UTAMA' : '' }}
                                </span>
                                <div class="fw-bold small text-dark">{{ $addr->recipient_name }} ({{ $addr->recipient_phone }})</div>
                                <div class="text-muted" style="font-size: 0.72rem;">{{ $addr->full_address }}</div>
                                @if($addr->notes)
                                    <small class="text-secondary d-block" style="font-size: 0.68rem;">Patokan: {{ $addr->notes }}</small>
                                @endif
                            </div>
                            <form action="{{ route('user.addresses.destroy', $addr->id) }}" method="POST" onsubmit="return confirm('Hapus alamat ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm text-danger p-0 border-0 bg-transparent">
                                    <i class="icofont-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-2 text-muted small">Belum ada daftar alamat tambahan.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Form Edit Profil & Alamat Utama -->
    <div class="card border rounded-3 shadow-sm bg-white mb-4">
        <div class="card-body p-3">
            <h6 class="fw-bold text-dark mb-3">
                <i class="icofont-ui-edit text-success me-1"></i> Data Akun & Domisili Utama
            </h6>

            <form action="{{ route('user.profile.update') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="nameInput" class="form-label small fw-semibold text-secondary">Nama Lengkap</label>
                    <input type="text" name="name" id="nameInput" class="form-control" value="{{ old('name', $user->name) }}" required>
                </div>

                <div class="mb-3">
                    <label for="phoneInput" class="form-label small fw-semibold text-secondary">Nomor WhatsApp / HP</label>
                    <input type="tel" name="phone" id="phoneInput" class="form-control" value="{{ old('phone', $user->phone) }}" required>
                </div>

                <div class="mb-3">
                    <label for="emailInput" class="form-label small fw-semibold text-secondary">Alamat Email</label>
                    <input type="email" name="email" id="emailInput" class="form-control" value="{{ old('email', $user->email) }}">
                </div>

                <div class="mb-3">
                    <label for="territoryInput" class="form-label small fw-semibold text-secondary">Kelurahan / Desa Domisili</label>
                    <select name="territory_id" id="territoryInput" class="form-select" required>
                        @foreach($villages as $vil)
                            <option value="{{ $vil->id }}" {{ old('territory_id', $user->territory_id) == $vil->id ? 'selected' : '' }}>
                                {{ $vil->name }} (Kec. {{ $vil->parent?->name ?? 'Lokal' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="addressInput" class="form-label small fw-semibold text-secondary">Alamat Pengiriman & Patokan</label>
                    <textarea name="address" id="addressInput" rows="2" class="form-control" required>{{ old('address', $user->address) }}</textarea>
                </div>

                <button type="submit" class="btn btn-success btn-lg w-100 rounded-3 fw-bold py-2 shadow-sm" style="font-size: 0.95rem;">
                    <i class="icofont-check-circled me-1"></i> Simpan Perubahan Profil
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
