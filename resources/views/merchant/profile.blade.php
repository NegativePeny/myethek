@extends('layouts.merchant')

@section('title', 'Profil Toko - ' . $merchant->store_name)

@section('header_extra')
    <!-- Switcher Akun Mitra Demo -->
    <div class="mt-2 pt-2 border-top border-white-50">
        <form action="{{ route('merchant.switch') }}" method="POST" class="d-flex align-items-center gap-2">
            @csrf
            <span style="font-size: 0.72rem; white-space: nowrap;">Peran Toko:</span>
            <select name="merchant_id" class="form-select form-select-sm bg-white text-dark py-0" style="font-size: 0.75rem;" onchange="this.form.submit()">
                @foreach($allMerchants as $m)
                    <option value="{{ $m->id }}" {{ $merchant->id == $m->id ? 'selected' : '' }}>
                        [{{ strtoupper($m->tier) }}] {{ $m->store_name }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>
@endsection

@section('content')
<div class="p-3">
    <!-- Header Modul -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h5 class="fw-bold mb-0 text-dark">Profil & Operasional Toko</h5>
            <small class="text-muted" style="font-size: 0.75rem;">Konfigurasi jam buka, radius antar, dan data toko mitra</small>
        </div>
        <span class="badge bg-warning text-dark border border-warning fw-bold" style="font-size: 0.7rem;">
            MITRA {{ strtoupper($merchant->tier) }}
        </span>
    </div>

    <!-- Status Buka / Tutup Toko Card -->
    <div class="card border mb-3 shadow-sm bg-white">
        <div class="card-body p-3 d-flex align-items-center justify-content-between">
            <div>
                <span class="fw-bold text-dark small d-block">Status Operasional Toko</span>
                <small class="text-muted" style="font-size: 0.72rem;">
                    Saat tutup, warga tidak dapat melakukan checkout pesanan dari toko Anda.
                </small>
            </div>

            <form action="{{ route('merchant.toggleStatus') }}" method="POST">
                @csrf
                @if($merchant->is_open)
                    <button type="submit" class="btn btn-sm btn-success fw-bold px-3 py-1" style="font-size: 0.75rem;">
                        <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> BUKA
                    </button>
                @else
                    <button type="submit" class="btn btn-sm btn-danger fw-bold px-3 py-1" style="font-size: 0.75rem;">
                        <i class="bi bi-dash-circle me-1"></i> TUTUP
                    </button>
                @endif
            </form>
        </div>
    </div>

    <!-- Form Profil & Pengaturan Operasional -->
    <div class="card border mb-3 shadow-sm bg-white">
        <div class="card-body p-3">
            <h6 class="fw-bold small text-dark mb-3">
                <i class="bi bi-pencil-square text-success me-1"></i> Informasi Toko
            </h6>

            @if($errors->any())
                <div class="alert alert-danger py-2 px-3 small rounded mb-3">
                    <i class="bi bi-exclamation-triangle me-1"></i> Periksa kembali formulir profil toko di bawah.
                </div>
            @endif

            <form action="{{ route('merchant.profile.update') }}" method="POST">
                @csrf

                <!-- Nama Toko -->
                <div class="mb-3">
                    <label for="storeName" class="form-label small fw-semibold text-secondary">Nama Toko / Warung</label>
                    <input type="text" name="store_name" id="storeName" class="form-control form-control-sm @error('store_name') is-invalid @enderror" value="{{ old('store_name', $merchant->store_name) }}" required>
                    @error('store_name')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Nomor HP / WhatsApp Toko -->
                <div class="mb-3">
                    <label for="storePhone" class="form-label small fw-semibold text-secondary">Nomor WhatsApp Toko</label>
                    <input type="tel" name="phone" id="storePhone" class="form-control form-control-sm @error('phone') is-invalid @enderror" value="{{ old('phone', $merchant->phone) }}" required>
                    @error('phone')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Alamat Fisik Toko -->
                <div class="mb-3">
                    <label for="storeAddress" class="form-label small fw-semibold text-secondary">Alamat Fisik Toko</label>
                    <textarea name="address" id="storeAddress" rows="2" class="form-control form-control-sm @error('address') is-invalid @enderror" required>{{ old('address', $merchant->address) }}</textarea>
                    <small class="text-muted" style="font-size: 0.7rem;">Wilayah: {{ $merchant->territory?->name ?? 'Lokal' }}</small>
                    @error('address')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                <div class="row g-2 mb-3">
                    <!-- Jam Buka -->
                    <div class="col-6">
                        <label for="openTime" class="form-label small fw-semibold text-secondary">Jam Buka</label>
                        <input type="time" name="opening_time" id="openTime" class="form-control form-control-sm" value="{{ old('opening_time', $merchant->opening_time ? substr($merchant->opening_time, 0, 5) : '06:00') }}" required>
                    </div>

                    <!-- Jam Tutup -->
                    <div class="col-6">
                        <label for="closeTime" class="form-label small fw-semibold text-secondary">Jam Tutup</label>
                        <input type="time" name="closing_time" id="closeTime" class="form-control form-control-sm" value="{{ old('closing_time', $merchant->closing_time ? substr($merchant->closing_time, 0, 5) : '21:30') }}" required>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <!-- Min Order -->
                    <div class="col-6">
                        <label for="minOrder" class="form-label small fw-semibold text-secondary">Min. Belanja (Rp)</label>
                        <input type="number" name="min_order_amount" id="minOrder" class="form-control form-control-sm @error('min_order_amount') is-invalid @enderror" value="{{ old('min_order_amount', (int)$merchant->min_order_amount) }}" min="0" step="5000" required>
                    </div>

                    <!-- Radius Antar -->
                    <div class="col-6">
                        <label for="radiusKm" class="form-label small fw-semibold text-secondary">Radius Antar (Km)</label>
                        <input type="number" name="delivery_radius_km" id="radiusKm" class="form-control form-control-sm @error('delivery_radius_km') is-invalid @enderror" value="{{ old('delivery_radius_km', $merchant->delivery_radius_km) }}" min="0.5" max="50" step="0.5" required>
                    </div>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="self_delivery_active" id="selfDelivery" {{ old('self_delivery_active', $merchant->self_delivery_active) ? 'checked' : '' }}>
                    <label class="form-check-label small text-secondary" for="selfDelivery">
                        Aktifkan layanan antar mandiri oleh pihak toko (selain ojol)
                    </label>
                </div>

                <button type="submit" class="btn btn-success btn-sm w-100 fw-bold py-2 shadow-sm">
                    <i class="bi bi-save me-1"></i> Simpan Pengaturan Toko
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
