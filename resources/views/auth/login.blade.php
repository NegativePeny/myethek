@extends('layouts.storefront')

@section('title', 'Masuk Akun - Mak Ethek')

@section('header_extra')
    <div class="px-1 pt-2 d-flex align-items-center gap-2">
        <a href="{{ route('storefront.index') }}" class="btn btn-sm btn-light border rounded-pill px-2 py-1 text-decoration-none">
            <i class="icofont-rounded-left"></i> Kembali
        </a>
        <span class="fw-bold small text-dark">
            Masuk ke Akun
        </span>
    </div>
@endsection

@section('content')
<div class="osahan-signin p-3">
    <div class="text-center my-3">
        <img src="/grofar/img/logo.svg" style="height: 52px;" class="mb-2" alt="Logo Mak Ethek">
        <h4 class="fw-bold text-success mb-1">Mak Ethek</h4>
        <p class="text-muted small mb-0">Platform Belanja Sembako Koperasi Jawa Timur</p>
    </div>

    <div class="card border rounded-3 shadow-sm bg-white mb-4">
        <div class="card-body p-3 p-md-4">
            <h5 class="fw-bold text-dark mb-1">Selamat Datang</h5>
            <p class="text-muted small mb-3">Silakan masuk dengan Nomor HP (WhatsApp) atau Email Anda.</p>

            @if($errors->has('login'))
                <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3" role="alert">
                    <i class="icofont-warning-alt me-1"></i> {{ $errors->first('login') }}
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="loginInput" class="form-label small fw-semibold text-secondary">Nomor HP / Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0">
                            <i class="icofont-ui-user"></i>
                        </span>
                        <input type="text" 
                               name="login" 
                               id="loginInput" 
                               class="form-control border-start-0 @error('login') is-invalid @enderror" 
                               placeholder="Contoh: 08160000001 atau email" 
                               value="{{ old('login') }}" 
                               required 
                               autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="passwordInput" class="form-label small fw-semibold text-secondary">Kata Sandi</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0">
                            <i class="icofont-ui-password"></i>
                        </span>
                        <input type="password" 
                               name="password" 
                               id="passwordInput" 
                               class="form-control border-start-0 @error('password') is-invalid @enderror" 
                               placeholder="Masukkan kata sandi Anda" 
                               required>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="rememberMe" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label small text-muted" for="rememberMe">
                            Ingat Saya
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-success btn-lg w-100 rounded-3 fw-bold py-2 shadow-sm" style="font-size: 0.95rem;">
                    <i class="icofont-login me-1"></i> Masuk Sekarang
                </button>
            </form>

            <div class="text-center mt-3 pt-3 border-top">
                <span class="small text-muted">Belum memiliki akun warga?</span>
                <a href="{{ route('register') }}" class="small fw-bold text-success text-decoration-none d-inline-block ms-1">
                    Daftar di Sini
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Demo Accounts Helper -->
    <div class="card border-0 bg-light rounded-3 p-3 mb-4">
        <h6 class="fw-bold small text-dark mb-2">
            <i class="icofont-info-circle text-primary me-1"></i> Akun Uji Coba Demo (Password: <code>password</code>)
        </h6>
        <div class="small text-secondary">
            <div class="d-flex justify-content-between py-1 border-bottom">
                <span><strong>Warga (Customer):</strong></span>
                <span class="text-dark"><code>08160000001</code></span>
            </div>
            <div class="d-flex justify-content-between py-1 border-bottom">
                <span><strong>Mitra Warung (Sub-Agen):</strong></span>
                <span class="text-dark"><code>08140000001</code></span>
            </div>
            <div class="d-flex justify-content-between py-1 border-bottom">
                <span><strong>Kurir Ojol Desa:</strong></span>
                <span class="text-dark"><code>08150000001</code></span>
            </div>
            <div class="d-flex justify-content-between py-1">
                <span><strong>Admin Holding:</strong></span>
                <span class="text-dark"><code>08110000001</code></span>
            </div>
        </div>
    </div>
</div>
@endsection
