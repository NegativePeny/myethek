@extends('layouts.storefront')

@section('title', 'Checkout Pengiriman - Mak Ethek')

@section('header_extra')
    <div class="px-1 pt-2 d-flex align-items-center gap-2">
        <a href="{{ route('storefront.cart') }}" class="btn btn-sm btn-light border rounded-pill px-2 py-1 text-decoration-none">
            <i class="icofont-rounded-left"></i> Keranjang
        </a>
        <div class="fw-bold small text-dark flex-grow-1">
            Checkout & Pengiriman {{ !empty($isB2b) ? '(Grosir B2B)' : '' }}
        </div>
    </div>
@endsection

@section('content')
<div class="osahan-checkout pb-4">
    <!-- Merchant Fulfillment Info -->
    <div class="p-3 bg-white border-bottom">
        <div class="d-flex align-items-center justify-content-between mb-1">
            <div class="d-flex align-items-center gap-2">
                <div class="bg-success text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                    <i class="icofont-shop fs-5"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold text-dark small">{{ $merchant->store_name }}</h6>
                    <small class="text-muted" style="font-size: 0.7rem;">
                        <i class="icofont-location-pin text-danger"></i> {{ $merchant->territory?->name }} • Radius {{ $merchant->delivery_radius_km }} km
                    </small>
                </div>
            </div>
            <span class="badge bg-success-subtle text-success border border-success" style="font-size: 0.65rem;">
                {{ strtoupper($merchant->tier) }}
            </span>
        </div>
        <div class="mt-2 pt-2 border-top d-flex justify-content-between text-secondary" style="font-size: 0.74rem;">
            <span><i class="icofont-map-pins text-danger me-1"></i> Estimasi Jarak Rute:</span>
            <strong>{{ $distanceKm }} km</strong>
        </div>
    </div>

    <form action="{{ route('storefront.checkout.store') }}" method="POST">
        @csrf

        <!-- Ringkasan Barang Belanjaan (Items Preview) -->
        <div class="address p-3 bg-white border-bottom mt-2 d-flex justify-content-between align-items-center">
            <h6 class="m-0 text-dark fw-bold"><i class="icofont-bag text-success me-1"></i> Barang yang Dipesan ({{ count($items) }})</h6>
            <a href="{{ route('storefront.cart') }}" class="small text-success text-decoration-none fw-semibold" style="font-size: 0.72rem;">
                Ubah di Keranjang
            </a>
        </div>
        <div class="p-3 bg-white border-bottom">
            <div class="d-flex flex-column gap-2">
                @foreach($items as $it)
                    @php
                        $itImage = $it['image'] ?? \App\Models\Product::find($it['product_id'])?->image;
                        $itImgIndex = ($it['product_id'] % 12) + 1;
                        $itDisplayImg = $itImage ? asset($itImage) : "/grofar/img/listing/v{$itImgIndex}.jpg";
                    @endphp
                    <div class="d-flex align-items-center justify-content-between p-2 rounded bg-light border">
                        <div class="d-flex align-items-center gap-2">
                            <img src="{{ $itDisplayImg }}" onerror="this.onerror=null; this.src='/grofar/img/listing/v{{ $itImgIndex }}.jpg';" class="rounded border bg-white" style="width: 44px; height: 44px; object-fit: contain;" alt="{{ $it['name'] }}">
                            <div>
                                <h6 class="mb-0 fw-semibold text-dark small text-truncate" style="max-width: 190px;">{{ $it['name'] }}</h6>
                                <small class="text-muted" style="font-size: 0.7rem;">
                                    {{ $it['quantity'] }} {{ $it['unit_name'] }} &times; Rp {{ number_format($it['price'], 0, ',', '.') }}
                                </small>
                            </div>
                        </div>
                        <strong class="text-dark small text-nowrap">Rp {{ number_format($it['subtotal'], 0, ',', '.') }}</strong>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Pilihan Metode Pengiriman -->
        <div class="address p-3 bg-white border-bottom mt-2">
            <h6 class="m-0 text-dark fw-bold"><i class="icofont-delivery-time text-success me-1"></i> Metode Pengiriman</h6>
        </div>
        
        <div class="p-3 bg-white border-bottom">
            @php
                $deliveryService = app(\App\Services\DeliveryService::class);
                $kurirqFee = $deliveryService->calculateShippingFee('kurirq', $distanceKm);
                $ojolFee = $deliveryService->calculateShippingFee('ojol', $distanceKm);
                $warungFee = $deliveryService->calculateShippingFee('merchant_courier', $distanceKm);
            @endphp

            <div class="d-flex flex-column gap-2">
                <!-- Opsi 1: KurirQ / Ojol Lokal -->
                <label class="p-2 rounded-3 border d-flex align-items-center justify-content-between cursor-pointer {{ in_array($selectedDeliveryType, ['ojol', 'kurirq']) ? 'border-success bg-light' : '' }}">
                    <div class="d-flex align-items-center gap-2">
                        <input type="radio" name="delivery_type" value="ojol" class="form-check-input" 
                               {{ in_array($selectedDeliveryType, ['ojol', 'kurirq']) ? 'checked' : '' }}
                               onchange="window.location.href = '{{ route('storefront.checkout', ['delivery' => 'ojol']) }}'">
                        <div>
                            <div class="fw-bold small text-dark"><i class="icofont-motor-bike text-success me-1"></i> KurirQ / Ojol Lokal Blitar</div>
                            <small class="text-muted d-block" style="font-size: 0.7rem;">Pengiriman cepat mitra KurirQ Koperasi</small>
                        </div>
                    </div>
                    <span class="fw-bold text-success small">Rp {{ number_format($ojolFee, 0, ',', '.') }}</span>
                </label>

                <!-- Opsi 2: Kurir Toko Warung -->
                <label class="p-2 rounded-3 border d-flex align-items-center justify-content-between cursor-pointer {{ $selectedDeliveryType === 'merchant_courier' ? 'border-success bg-light' : '' }}">
                    <div class="d-flex align-items-center gap-2">
                        <input type="radio" name="delivery_type" value="merchant_courier" class="form-check-input" 
                               {{ $selectedDeliveryType === 'merchant_courier' ? 'checked' : '' }}
                               onchange="window.location.href = '{{ route('storefront.checkout', ['delivery' => 'merchant_courier']) }}'">
                        <div>
                            <div class="fw-bold small text-dark"><i class="icofont-fast-delivery text-primary me-1"></i> Armada Gudang Koperasi</div>
                            <small class="text-muted d-block" style="font-size: 0.7rem;">Tarif pengantaran internal koperasi</small>
                        </div>
                    </div>
                    <span class="fw-bold text-success small">Rp {{ number_format($warungFee, 0, ',', '.') }}</span>
                </label>

                <!-- Opsi 3: Ambil Sendiri -->
                <label class="p-2 rounded-3 border d-flex align-items-center justify-content-between cursor-pointer {{ $selectedDeliveryType === 'self_pickup' ? 'border-success bg-light' : '' }}">
                    <div class="d-flex align-items-center gap-2">
                        <input type="radio" name="delivery_type" value="self_pickup" class="form-check-input" 
                               {{ $selectedDeliveryType === 'self_pickup' ? 'checked' : '' }}
                               onchange="window.location.href = '{{ route('storefront.checkout', ['delivery' => 'self_pickup']) }}'">
                        <div>
                            <div class="fw-bold small text-dark"><i class="icofont-shop text-warning me-1"></i> Ambil Sendiri di Toko/Gudang</div>
                            <small class="text-muted d-block" style="font-size: 0.7rem;">Ambil langsung di lokasi koperasi</small>
                        </div>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success">Gratis</span>
                </label>
            </div>
        </div>

        <!-- Alamat & Data Penerima (Multi-Alamat & Beli untuk Orang Tua/Keluarga CUS-003/CUS-005) -->
        <div class="address p-3 bg-white border-bottom mt-2 d-flex justify-content-between align-items-center">
            <h6 class="m-0 text-dark fw-bold"><i class="icofont-ui-user text-success me-1"></i> Data Penerima & Alamat</h6>
            @auth
                <a href="{{ route('user.profile') }}" class="small text-success text-decoration-none fw-semibold" style="font-size: 0.72rem;">
                    + Kelola Alamat
                </a>
            @endauth
        </div>
        
        <div class="p-3 bg-white border-bottom">
            @if(isset($savedAddresses) && $savedAddresses->isNotEmpty())
                <div class="mb-3 p-2 rounded border bg-light">
                    <label class="form-label small fw-bold text-dark mb-1" style="font-size: 0.72rem;">
                        <i class="icofont-address-book text-primary me-1"></i> Pilih dari Daftar Alamat Tersimpan (Bisa Kirim ke Orang Tua/Keluarga):
                    </label>
                    <select name="address_id" id="savedAddressSelect" class="form-select form-select-sm" onchange="applySavedAddress(this)">
                        <option value="">-- Isi Manual / Alamat Baru --</option>
                        @foreach($savedAddresses as $addr)
                            <option value="{{ $addr->id }}"
                                    data-recipient="{{ $addr->recipient_name }}"
                                    data-phone="{{ $addr->recipient_phone }}"
                                    data-address="{{ $addr->full_address }}"
                                    data-notes="{{ $addr->notes }}"
                                    {{ $addr->is_default ? 'selected' : '' }}>
                                [{{ $addr->label }}] {{ $addr->recipient_name }} ({{ $addr->recipient_phone }}) — {{ \Illuminate\Support\Str::limit($addr->full_address, 40) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            @php
                $defaultAddr = isset($savedAddresses) ? ($savedAddresses->firstWhere('is_default', true) ?? $savedAddresses->first()) : null;
            @endphp

            <div class="mb-2">
                <label class="form-label small fw-semibold text-secondary mb-1">Nama Penerima (Bisa Orang Tua / Kerabat):</label>
                <input type="text" name="recipient_name" id="checkoutRecipientName" class="form-control form-control-sm" 
                       placeholder="Contoh: Ibu Siti Khadijah" value="{{ old('recipient_name', $defaultAddr?->recipient_name ?? $customer?->name) }}" required>
            </div>
            
            <div class="mb-2">
                <label class="form-label small fw-semibold text-secondary mb-1">Nomor WhatsApp Penerima Aktif:</label>
                <input type="text" name="phone" id="checkoutRecipientPhone" class="form-control form-control-sm" 
                       placeholder="Contoh: 081234567890" value="{{ old('phone', $defaultAddr?->recipient_phone ?? $customer?->phone) }}" required>
                <small class="text-muted" style="font-size: 0.68rem;">Untuk konfirmasi pesanan dan serah-terima OTP KurirQ.</small>
            </div>

            <div class="mb-2">
                <label class="form-label small fw-semibold text-secondary mb-1">Alamat Lengkap Pengantaran:</label>
                <textarea name="shipping_address" id="checkoutShippingAddress" class="form-control form-control-sm" rows="2" 
                          placeholder="Nama jalan, RT/RW, nomor rumah, patokan..." required>{{ old('shipping_address', $defaultAddr?->full_address ?? $customer?->address) }}</textarea>
            </div>

            <div class="mb-0">
                <label class="form-label small fw-semibold text-secondary mb-1">Catatan Patokan Rumah (Opsional):</label>
                <input type="text" name="notes" id="checkoutNotes" class="form-control form-control-sm" 
                       value="{{ old('notes', $defaultAddr?->notes) }}"
                       placeholder="Contoh: Rumah pagar hijau / titip tetangga jika keluar">
            </div>
        </div>

        <!-- Metode Pembayaran Digital DOKU (CUS-006) -->
        <div class="address p-3 bg-white border-bottom mt-2">
            <h6 class="m-0 text-dark fw-bold"><i class="icofont-credit-card text-success me-1"></i> Metode Pembayaran (DOKU Payment Gateway)</h6>
        </div>

        <div class="p-3 bg-white border-bottom">
            <div class="d-flex flex-column gap-2">
                <label class="p-2 rounded-3 border d-flex align-items-center justify-content-between cursor-pointer">
                    <div class="d-flex align-items-center gap-2">
                        <input type="radio" name="payment_method" value="qris" class="form-check-input" checked>
                        <div>
                            <div class="fw-bold small text-dark">QRIS DOKU Koperasi</div>
                            <small class="text-muted d-block" style="font-size: 0.7rem;">BCA, BRI, Mandiri, BNI, GoPay, OVO, DANA</small>
                        </div>
                    </div>
                    <i class="icofont-qr-code text-dark fs-5"></i>
                </label>

                <label class="p-2 rounded-3 border d-flex align-items-center justify-content-between cursor-pointer">
                    <div class="d-flex align-items-center gap-2">
                        <input type="radio" name="payment_method" value="va_bri" class="form-check-input">
                        <div>
                            <div class="fw-bold small text-dark">Virtual Account Bank (BRI / Mandiri / BCA)</div>
                            <small class="text-muted d-block" style="font-size: 0.7rem;">Verifikasi otomatis webhook DOKU 24 jam</small>
                        </div>
                    </div>
                    <span class="badge bg-primary text-white" style="font-size: 0.65rem;">VA BANK</span>
                </label>

                <label class="p-2 rounded-3 border d-flex align-items-center justify-content-between cursor-pointer">
                    <div class="d-flex align-items-center gap-2">
                        <input type="radio" name="payment_method" value="shopeepay" class="form-check-input">
                        <div>
                            <div class="fw-bold small text-dark">E-Wallet (ShopeePay / OVO / DANA)</div>
                            <small class="text-muted d-block" style="font-size: 0.7rem;">Pembayaran instan via aplikasi dompet digital</small>
                        </div>
                    </div>
                    <span class="badge bg-warning text-dark" style="font-size: 0.65rem;">E-WALLET</span>
                </label>

                <label class="p-2 rounded-3 border d-flex align-items-center justify-content-between cursor-pointer">
                    <div class="d-flex align-items-center gap-2">
                        <input type="radio" name="payment_method" value="cod" class="form-check-input">
                        <div>
                            <div class="fw-bold small text-dark">Bayar Tunai di Tempat (COD)</div>
                            <small class="text-muted d-block" style="font-size: 0.7rem;">Bayar tunai ke kurir saat sembako tiba</small>
                        </div>
                    </div>
                    <i class="icofont-money text-success fs-5"></i>
                </label>
            </div>
        </div>

        <!-- Rincian Biaya Pembayaran -->
        <div class="address p-3 bg-white border-bottom mt-2">
            <h6 class="text-dark m-0 fw-bold"><i class="icofont-bill-alt text-success me-1"></i> Rincian Pembayaran</h6>
        </div>

        <div class="p-3 bg-white border-bottom">
            <div class="clearfix">
                <p class="mb-1 text-muted small">
                    Subtotal Produk ({{ count($items) }} barang) 
                    <span class="float-end text-dark fw-semibold">Rp {{ number_format($costBreakdown['subtotal'], 0, ',', '.') }}</span>
                </p>
                <p class="mb-1 text-muted small">
                    Ongkos Kirim ({{ strtoupper(str_replace('_', ' ', $selectedDeliveryType)) }})
                    <span class="float-end text-dark fw-semibold">Rp {{ number_format($costBreakdown['shipping_fee'], 0, ',', '.') }}</span>
                </p>
                <p class="mb-1 text-muted small">
                    Biaya Platform Koperasi
                    <span class="float-end text-dark fw-semibold">Rp {{ number_format($costBreakdown['platform_fee'], 0, ',', '.') }}</span>
                </p>
                <hr class="my-2">
                <h6 class="fw-bold mb-0 text-dark">
                    TOTAL PEMBAYARAN 
                    <span class="float-end text-success fs-5">Rp {{ number_format($costBreakdown['total_amount'], 0, ',', '.') }}</span>
                </h6>
            </div>
        </div>

        <!-- Tombol Buat Pesanan -->
        <div class="p-3 mt-2">
            <button type="submit" class="btn btn-success btn-block w-100 py-3 rounded-3 shadow fw-bold d-flex align-items-center justify-content-center gap-2">
                <i class="icofont-check-circled fs-5"></i> Buat Pesanan Sekarang
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    function applySavedAddress(selectEl) {
        const opt = selectEl.options[selectEl.selectedIndex];
        if (!opt || !opt.value) return;
        document.getElementById('checkoutRecipientName').value = opt.getAttribute('data-recipient') || '';
        document.getElementById('checkoutRecipientPhone').value = opt.getAttribute('data-phone') || '';
        document.getElementById('checkoutShippingAddress').value = opt.getAttribute('data-address') || '';
        document.getElementById('checkoutNotes').value = opt.getAttribute('data-notes') || '';
    }
</script>
@endsection
