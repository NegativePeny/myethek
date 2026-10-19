@extends('layouts.storefront')

@section('title', 'Keranjang Belanja - Mak Ethek')

@section('header_extra')
    <div class="px-1 pt-2 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('storefront.index') }}" class="btn btn-sm btn-light border rounded-pill px-2 py-1 text-decoration-none">
                <i class="icofont-rounded-left"></i> Belanja
            </a>
            <span class="fw-bold small text-dark">
                Keranjang ({{ $itemCount }} item)
            </span>
        </div>

        @if(!empty($cartItems))
            <form action="{{ route('storefront.cart.clear') }}" method="POST" onsubmit="return confirm('Kosongkan semua barang di keranjang?');">
                @csrf
                <button type="submit" class="btn btn-sm text-danger p-0 small text-decoration-none border-0 bg-transparent">
                    <i class="icofont-trash"></i> Kosongkan
                </button>
            </form>
        @endif
    </div>
@endsection

@section('content')
    <div class="osahan-cart">
        @if(empty($cartItems))
            <div class="text-center py-5 px-3">
                <div class="bg-white border rounded-circle p-4 d-inline-flex mb-3 shadow-sm">
                    <i class="icofont-cart text-muted" style="font-size: 3.5rem;"></i>
                </div>
                <h6 class="fw-bold text-dark">Keranjang Belanja Kosong</h6>
                <p class="text-muted small mb-4">Yuk isi keranjangmu dengan kebutuhan pokok dan sembako dari warung koperasi terdekat.</p>
                <a href="{{ route('storefront.index') }}" class="btn btn-success fw-bold px-4 py-2 rounded shadow-sm">
                    <i class="icofont-shopping-cart me-1"></i> Mulai Belanja Sekarang
                </a>
            </div>
        @else
            <!-- Toko Pemenuh Pesanan (Single-Merchant Policy) -->
            @if($merchant)
                <div class="p-3 bg-white border-bottom">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-success text-white rounded-circle p-1 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="icofont-shop"></i>
                            </div>
                            <div>
                                <span class="fw-bold small text-dark d-block">{{ $merchant->store_name }}</span>
                                <small class="text-muted" style="font-size: 0.7rem;">
                                    <i class="icofont-location-pin text-danger"></i> {{ $merchant->territory?->name }} ({{ strtoupper($merchant->tier) }})
                                </small>
                            </div>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success" style="font-size: 0.65rem;">
                            Mitra Aktif
                        </span>
                    </div>
                </div>
            @endif

            <!-- Daftar Item Belanja (Grofar Cart Items) -->
            <div class="osahan-body">
                @foreach($cartItems as $item)
                    @php
                        $imgIndex = ($item['product_id'] % 12) + 1;
                        $displayImg = "/grofar/img/listing/v{$imgIndex}.jpg";
                    @endphp
                    <div class="cart-items bg-white position-relative border-bottom p-3">
                        <div class="d-flex align-items-center">
                            <img src="{{ $displayImg }}" class="img-fluid rounded border" style="width: 60px; height: 60px; object-fit: contain;" alt="{{ $item['name'] }}">
                            
                            <div class="ms-3 text-dark text-decoration-none w-100">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <h6 class="mb-0 fw-semibold text-truncate" style="max-width: 180px; font-size: 0.85rem;">
                                        {{ $item['name'] }}
                                    </h6>
                                    
                                    <!-- Hapus Item -->
                                    <form action="{{ route('storefront.cart.remove') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                        <button type="submit" class="btn btn-sm text-muted p-0 border-0 bg-transparent" title="Hapus">
                                            <i class="icofont-close-circled text-danger fs-5"></i>
                                        </button>
                                    </form>
                                </div>

                                <p class="text-muted mb-2 small" style="font-size: 0.72rem;">
                                    Rp {{ number_format($item['price'], 0, ',', '.') }} / {{ $item['unit_name'] }}
                                </p>

                                <div class="d-flex align-items-center justify-content-between">
                                    <p class="total_price fw-bold text-success m-0" style="font-size: 0.9rem;">
                                        Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                                    </p>

                                    <!-- Kuantitas Stepper -->
                                    <form action="{{ route('storefront.cart.update') }}" method="POST" class="d-flex align-items-center">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                        <div class="input-group input-spinner cart-items-number" style="width: 100px;">
                                            <button type="submit" name="quantity" value="{{ $item['quantity'] - 1 }}" 
                                                    class="btn btn-success btn-sm py-0 px-2 fw-bold" style="height: 28px;">−</button>
                                            
                                            <input type="text" class="form-control form-control-sm text-center p-0 fw-bold border-0 bg-white" 
                                                   value="{{ $item['quantity'] }}" readonly style="height: 28px;">
                                            
                                            <button type="submit" name="quantity" value="{{ $item['quantity'] + 1 }}" 
                                                    class="btn btn-success btn-sm py-0 px-2 fw-bold" style="height: 28px;">+</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <!-- Minimum Order Warning -->
                @if($merchant && !$meetsMinimum)
                    @php
                        $shortage = $merchant->min_order_amount - $subtotal;
                    @endphp
                    <div class="alert alert-warning py-2 px-3 mx-3 mt-3 small d-flex align-items-center gap-2">
                        <i class="icofont-warning-alt text-warning fs-5"></i>
                        <div>
                            <div class="fw-bold">Minimal Belanja Belum Tercapai</div>
                            <div style="font-size: 0.75rem;">
                                Toko mitra ini menetapkan minimal order 
                                <strong>Rp {{ number_format($merchant->min_order_amount, 0, ',', '.') }}</strong>. 
                                Tambah <strong>Rp {{ number_format($shortage, 0, ',', '.') }}</strong> lagi untuk dapat checkout.
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Rincian Biaya Keranjang -->
                <div class="p-3 bg-white border-top mt-3">
                    <h6 class="fw-bold mb-2">Ringkasan Pesanan</h6>
                    <div class="d-flex justify-content-between mb-1 small text-muted">
                        <span>Total Barang:</span>
                        <span class="text-dark fw-semibold">{{ $itemCount }} item</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-bold text-dark">Subtotal:</span>
                        <span class="fw-bold text-success fs-6">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                    </div>
                    <p class="text-muted m-0" style="font-size: 0.7rem;">
                        <i class="icofont-info-circle"></i> Ongkos kirim kurir lokal ojol dihitung pada langkah checkout berikutnya.
                    </p>
                </div>

                <!-- Tombol Lanjut ke Checkout -->
                <div class="p-3 mb-4">
                    @guest
                        <div class="alert alert-light border py-2 px-3 small text-secondary rounded-3 mb-2 d-flex align-items-center gap-2">
                            <i class="icofont-info-circle text-primary fs-5"></i>
                            <span>Silakan masuk ke akun warga agar data alamat pengantaran terbaca otomatis saat checkout.</span>
                        </div>
                    @endguest

                    @if($meetsMinimum)
                        <a href="{{ route('storefront.checkout') }}" class="text-decoration-none">
                            <div class="rounded-3 shadow bg-success d-flex align-items-center p-3 text-white">
                                <div class="more">
                                    <h6 class="m-0 fw-bold">Rp {{ number_format($subtotal, 0, ',', '.') }}</h6>
                                    <p class="small m-0 text-white-50">
                                        @auth
                                            Lanjut ke Pengiriman & Checkout
                                        @else
                                            Masuk Akun & Lanjut Checkout
                                        @endauth
                                    </p>
                                </div>
                                <div class="ms-auto fs-5"><i class="icofont-simple-right"></i></div>
                            </div>
                        </a>
                    @else
                        <button type="button" class="btn btn-secondary w-100 py-3 fw-bold rounded-3" disabled>
                            Minimal Belanja Belum Terpenuhi
                        </button>
                    @endif

                    <div class="text-center mt-3">
                        <a href="{{ route('storefront.index') }}" class="small text-muted text-decoration-none">
                            <i class="icofont-plus"></i> Tambah Belanjaan Lain
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
