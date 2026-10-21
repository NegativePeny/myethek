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
        
        <div class="d-flex justify-content-center align-items-center gap-2">
            <span class="badge bg-success-subtle text-success border border-success px-2 py-1" style="font-size: 0.68rem;">
                WARGA {{ strtoupper($user->territory?->name ?? 'JATIM') }}
            </span>
            @if($user->isMerchant())
                <span class="badge bg-warning text-dark border border-warning px-2 py-1 fw-bold" style="font-size: 0.68rem;">
                    MITRA {{ strtoupper($user->role) }}
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

            @if($user->isMerchant())
                <a href="{{ route('merchant.index') }}" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-3 bg-light">
                    <div class="d-flex align-items-center gap-2">
                        <i class="icofont-shop text-primary fs-5"></i>
                        <span class="fw-semibold text-primary">Buka Portal Toko Mitra (B2B)</span>
                    </div>
                    <i class="icofont-rounded-right text-primary"></i>
                </a>
            @endif
        </div>
    </div>

    <!-- Form Edit Profil & Alamat -->
    <div class="card border rounded-3 shadow-sm bg-white mb-4">
        <div class="card-body p-3">
            <h6 class="fw-bold text-dark mb-3">
                <i class="icofont-ui-edit text-success me-1"></i> Data Akun & Alamat Domisili
            </h6>

            @if($errors->any())
                <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3" role="alert">
                    <i class="icofont-warning-alt me-1"></i> Periksa kembali isian formulir profil Anda.
                </div>
            @endif

            <form action="{{ route('user.profile.update') }}" method="POST">
                @csrf

                <!-- Nama Lengkap -->
                <div class="mb-3">
                    <label for="nameInput" class="form-label small fw-semibold text-secondary">Nama Lengkap</label>
                    <input type="text" 
                           name="name" 
                           id="nameInput" 
                           class="form-control @error('name') is-invalid @enderror" 
                           value="{{ old('name', $user->name) }}" 
                           required>
                    @error('name')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Nomor HP / WA -->
                <div class="mb-3">
                    <label for="phoneInput" class="form-label small fw-semibold text-secondary">Nomor WhatsApp / HP</label>
                    <input type="tel" 
                           name="phone" 
                           id="phoneInput" 
                           class="form-control @error('phone') is-invalid @enderror" 
                           value="{{ old('phone', $user->phone) }}" 
                           required>
                    @error('phone')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <label for="emailInput" class="form-label small fw-semibold text-secondary">Alamat Email</label>
                    <input type="email" 
                           name="email" 
                           id="emailInput" 
                           class="form-control @error('email') is-invalid @enderror" 
                           value="{{ old('email', $user->email) }}">
                    @error('email')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Kelurahan / Desa Domisili -->
                <div class="mb-3">
                    <label for="territoryInput" class="form-label small fw-semibold text-secondary">
                        Kelurahan / Desa Domisili
                    </label>
                    <select name="territory_id" 
                            id="territoryInput" 
                            class="form-select @error('territory_id') is-invalid @enderror" 
                            required>
                        @foreach($villages as $vil)
                            <option value="{{ $vil->id }}" {{ old('territory_id', $user->territory_id) == $vil->id ? 'selected' : '' }}>
                                {{ $vil->name }} (Kec. {{ $vil->parent?->name ?? 'Lokal' }})
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted" style="font-size: 0.72rem;">Mengubah desa akan menyesuaikan toko koperasi yang melayani belanja Anda.</small>
                    @error('territory_id')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Alamat Lengkap & Patokan Rumah -->
                <div class="mb-3">
                    <label for="addressInput" class="form-label small fw-semibold text-secondary">
                        Alamat Pengiriman & Patokan
                    </label>
                    <textarea name="address" 
                              id="addressInput" 
                              rows="2" 
                              class="form-control @error('address') is-invalid @enderror" 
                              required>{{ old('address', $user->address) }}</textarea>
                    @error('address')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Ganti Kata Sandi (Opsional) -->
                <div class="mt-4 pt-3 border-top">
                    <h6 class="fw-bold small text-dark mb-2">
                        <i class="icofont-key text-muted me-1"></i> Ubah Kata Sandi <span class="fw-normal text-muted small">(Opsional)</span>
                    </h6>

                    <div class="mb-2">
                        <input type="password" 
                               name="current_password" 
                               class="form-control form-control-sm @error('current_password') is-invalid @enderror" 
                               placeholder="Kata sandi saat ini">
                        @error('current_password')
                            <small class="text-danger d-block mt-1">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="mb-2">
                        <input type="password" 
                               name="new_password" 
                               class="form-control form-control-sm @error('new_password') is-invalid @enderror" 
                               placeholder="Kata sandi baru (minimal 6 karakter)">
                        @error('new_password')
                            <small class="text-danger d-block mt-1">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <input type="password" 
                               name="new_password_confirmation" 
                               class="form-control form-control-sm" 
                               placeholder="Ulangi kata sandi baru">
                    </div>
                </div>

                <button type="submit" class="btn btn-success btn-lg w-100 rounded-3 fw-bold py-2 shadow-sm" style="font-size: 0.95rem;">
                    <i class="icofont-check-circled me-1"></i> Simpan Perubahan Profil
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
