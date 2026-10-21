@extends('layouts.storefront')

@section('title', 'Daftar Akun Warga - Mak Ethek')

@section('header_extra')
    <div class="px-1 pt-2 d-flex align-items-center gap-2">
        <a href="{{ route('login') }}" class="btn btn-sm btn-light border rounded-pill px-2 py-1 text-decoration-none">
            <i class="icofont-rounded-left"></i> Masuk
        </a>
        <span class="fw-bold small text-dark">
            Pendaftaran Warga Baru
        </span>
    </div>
@endsection

@section('content')
<div class="osahan-signup p-3">
    <div class="text-center my-3">
        <img src="/grofar/img/logo.png" style="height: 72px; width: 72px; object-fit: contain;" class="mb-2" alt="Logo Mak Ethek">
        <h4 class="fw-bold text-success mb-1">Daftar Akun Belanja</h4>
        <p class="text-muted small mb-0">Nikmati belanja kebutuhan pokok murah dari koperasi desa</p>
    </div>

    <div class="card border rounded-3 shadow-sm bg-white mb-4">
        <div class="card-body p-3 p-md-4">
            @if($errors->any())
                <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3" role="alert">
                    <i class="icofont-warning-alt me-1"></i> Periksa kembali isian formulir Anda di bawah.
                </div>
            @endif

            <form action="{{ route('register') }}" method="POST">
                @csrf

                <!-- Nama Lengkap -->
                <div class="mb-3">
                    <label for="nameInput" class="form-label small fw-semibold text-secondary">
                        Nama Lengkap <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0">
                            <i class="icofont-user"></i>
                        </span>
                        <input type="text" 
                               name="name" 
                               id="nameInput" 
                               class="form-control border-start-0 @error('name') is-invalid @enderror" 
                               placeholder="Contoh: Siti Khadijah" 
                               value="{{ old('name') }}" 
                               required>
                    </div>
                    @error('name')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Nomor WhatsApp / HP -->
                <div class="mb-3">
                    <label for="phoneInput" class="form-label small fw-semibold text-secondary">
                        Nomor WhatsApp / HP Aktif <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0">
                            <i class="icofont-brand-whatsapp text-success"></i>
                        </span>
                        <input type="tel" 
                               name="phone" 
                               id="phoneInput" 
                               class="form-control border-start-0 @error('phone') is-invalid @enderror" 
                               placeholder="Contoh: 08123456789" 
                               value="{{ old('phone') }}" 
                               required>
                    </div>
                    <small class="text-muted" style="font-size: 0.72rem;">Digunakan untuk konfirmasi pesanan & koordinasi kurir.</small>
                    @error('phone')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Email (Opsional) -->
                <div class="mb-3">
                    <label for="emailInput" class="form-label small fw-semibold text-secondary">
                        Alamat Email <span class="text-muted small fw-normal">(Opsional)</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0">
                            <i class="icofont-envelope"></i>
                        </span>
                        <input type="email" 
                               name="email" 
                               id="emailInput" 
                               class="form-control border-start-0 @error('email') is-invalid @enderror" 
                               placeholder="email@anda.com" 
                               value="{{ old('email') }}">
                    </div>
                    @error('email')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Desa / Kelurahan Domisili -->
                <div class="mb-3">
                    <label for="territoryInput" class="form-label small fw-semibold text-secondary">
                        Kelurahan / Desa Domisili <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0">
                            <i class="icofont-location-pin text-danger"></i>
                        </span>
                        <select name="territory_id" 
                                id="territoryInput" 
                                class="form-select border-start-0 @error('territory_id') is-invalid @enderror" 
                                required>
                            <option value="">-- Pilih Desa / Kelurahan --</option>
                            @foreach($villages as $vil)
                                <option value="{{ $vil->id }}" {{ old('territory_id', session('active_village_id')) == $vil->id ? 'selected' : '' }}>
                                    {{ $vil->name }} (Kec. {{ $vil->parent?->name ?? 'Lokal' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <small class="text-muted" style="font-size: 0.72rem;">Menentukan warung koperasi terdekat yang melayani pengantaran.</small>
                    @error('territory_id')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Alamat Lengkap / Patokan -->
                <div class="mb-3">
                    <label for="addressInput" class="form-label small fw-semibold text-secondary">
                        Alamat Pengiriman & Patokan <span class="text-danger">*</span>
                    </label>
                    <textarea name="address" 
                              id="addressInput" 
                              rows="2" 
                              class="form-control @error('address') is-invalid @enderror" 
                              placeholder="Nama jalan, RT/RW, nomor rumah, atau patokan samping toko/masjid" 
                              required>{{ old('address') }}</textarea>
                    @error('address')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Kata Sandi -->
                <div class="mb-3">
                    <label for="passwordInput" class="form-label small fw-semibold text-secondary">
                        Kata Sandi <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0">
                            <i class="icofont-ui-password"></i>
                        </span>
                        <input type="password" 
                               name="password" 
                               id="passwordInput" 
                               class="form-control border-start-0 @error('password') is-invalid @enderror" 
                               placeholder="Minimal 6 karakter" 
                               required>
                    </div>
                    @error('password')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Konfirmasi Kata Sandi -->
                <div class="mb-4">
                    <label for="passwordConfirmInput" class="form-label small fw-semibold text-secondary">
                        Ulangi Kata Sandi <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0">
                            <i class="icofont-check-circled"></i>
                        </span>
                        <input type="password" 
                               name="password_confirmation" 
                               id="passwordConfirmInput" 
                               class="form-control border-start-0" 
                               placeholder="Masukkan kembali kata sandi" 
                               required>
                    </div>
                </div>

                <button type="submit" class="btn btn-success btn-lg w-100 rounded-3 fw-bold py-2 shadow-sm" style="font-size: 0.95rem;">
                    <i class="icofont-check-circled me-1"></i> Daftar Sekarang
                </button>
            </form>

            <div class="text-center mt-3 pt-3 border-top">
                <span class="small text-muted">Sudah memiliki akun?</span>
                <a href="{{ route('login') }}" class="small fw-bold text-success text-decoration-none d-inline-block ms-1">
                    Masuk di Sini
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
