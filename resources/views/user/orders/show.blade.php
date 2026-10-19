@extends('layouts.storefront')

@section('title', 'Detail Pesanan #' . $order->order_number . ' - Mak Ethek')

@section('header_extra')
    <div class="px-1 pt-2 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('user.orders.index') }}" class="btn btn-sm btn-light border rounded-pill px-2 py-1 text-decoration-none">
                <i class="icofont-rounded-left"></i> Pesanan
            </a>
            <span class="fw-bold small text-dark">
                #{{ $order->order_number }}
            </span>
        </div>
        <span class="text-muted" style="font-size: 0.72rem;">
            {{ $order->created_at->translatedFormat('d M Y, H:i') }}
        </span>
    </div>
@endsection

@section('content')
<div class="osahan-status p-3">
    @php
        $statusMap = [
            'pending' => ['step' => 1, 'title' => 'Menunggu Konfirmasi', 'desc' => 'Pesanan baru dikirimkan ke mitra warung koperasi.'],
            'confirmed' => ['step' => 2, 'title' => 'Dikonfirmasi Toko', 'desc' => 'Toko telah menerima pesanan dan mulai menyiapkan barang.'],
            'processing' => ['step' => 2, 'title' => 'Sedang Disiapkan', 'desc' => 'Barang sembako sedang dikemas rapi oleh warung.'],
            'ready_for_pickup' => ['step' => 3, 'title' => 'Siap Diambil Kurir', 'desc' => 'Paket siap di toko, menunggu kurir ojol mengambil.'],
            'shipping' => ['step' => 3, 'title' => 'Kurir Sedang Mengantar', 'desc' => 'Kurir ojol desa sedang dalam perjalanan ke alamat Anda.'],
            'delivered' => ['step' => 4, 'title' => 'Pesanan Selesai', 'desc' => 'Barang belanjaan telah berhasil diterima oleh pemesan.'],
            'cancelled' => ['step' => 0, 'title' => 'Pesanan Dibatalkan', 'desc' => 'Pesanan ini telah dibatalkan.'],
        ];

        $currentStatus = $statusMap[$order->status] ?? ['step' => 1, 'title' => ucfirst($order->status), 'desc' => ''];
        $currentStep = $currentStatus['step'];
    @endphp

    <!-- Card Status Tracking -->
    <div class="card border rounded-3 shadow-sm bg-white mb-3">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="fw-bold text-dark m-0">{{ $currentStatus['title'] }}</h6>
                @if($order->status === 'delivered')
                    <span class="badge bg-success-subtle text-success border border-success px-2 py-1" style="font-size: 0.7rem;">
                        <i class="icofont-check-circled me-1"></i> Selesai
                    </span>
                @elseif($order->status === 'cancelled')
                    <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1" style="font-size: 0.7rem;">
                        <i class="icofont-close-circled me-1"></i> Batal
                    </span>
                @else
                    <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1" style="font-size: 0.7rem;">
                        <i class="icofont-refresh me-1"></i> Dalam Proses
                    </span>
                @endif
            </div>
            <p class="text-muted small mb-3">{{ $currentStatus['desc'] }}</p>

            @if($order->status !== 'cancelled')
                <!-- Stepper Visual -->
                <div class="position-relative my-3 px-2">
                    <div class="d-flex justify-content-between position-relative" style="z-index: 2;">
                        <!-- Step 1 -->
                        <div class="text-center" style="width: 25%;">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-1 {{ $currentStep >= 1 ? 'bg-success text-white' : 'bg-light text-muted border' }}" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                @if($currentStep > 1) <i class="icofont-check"></i> @else 1 @endif
                            </div>
                            <span class="d-block text-truncate small {{ $currentStep >= 1 ? 'fw-bold text-success' : 'text-muted' }}" style="font-size: 0.65rem;">Diterima</span>
                        </div>
                        <!-- Step 2 -->
                        <div class="text-center" style="width: 25%;">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-1 {{ $currentStep >= 2 ? 'bg-success text-white' : 'bg-light text-muted border' }}" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                @if($currentStep > 2) <i class="icofont-check"></i> @else 2 @endif
                            </div>
                            <span class="d-block text-truncate small {{ $currentStep >= 2 ? 'fw-bold text-success' : 'text-muted' }}" style="font-size: 0.65rem;">Disiapkan</span>
                        </div>
                        <!-- Step 3 -->
                        <div class="text-center" style="width: 25%;">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-1 {{ $currentStep >= 3 ? 'bg-success text-white' : 'bg-light text-muted border' }}" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                @if($currentStep > 3) <i class="icofont-check"></i> @else 3 @endif
                            </div>
                            <span class="d-block text-truncate small {{ $currentStep >= 3 ? 'fw-bold text-success' : 'text-muted' }}" style="font-size: 0.65rem;">Diantar</span>
                        </div>
                        <!-- Step 4 -->
                        <div class="text-center" style="width: 25%;">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-1 {{ $currentStep >= 4 ? 'bg-success text-white' : 'bg-light text-muted border' }}" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                @if($currentStep >= 4) <i class="icofont-check"></i> @else 4 @endif
                            </div>
                            <span class="d-block text-truncate small {{ $currentStep >= 4 ? 'fw-bold text-success' : 'text-muted' }}" style="font-size: 0.65rem;">Selesai</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Toko Penjual -->
    <div class="card border rounded-3 shadow-sm bg-white mb-3">
        <div class="card-body p-3">
            <h6 class="fw-bold small text-dark mb-2">
                <i class="icofont-shop text-success me-1"></i> Toko Penyedia Belanjaan
            </h6>
            <div class="d-flex align-items-start justify-content-between">
                <div>
                    <span class="fw-bold text-dark d-block">{{ $order->merchant?->store_name }}</span>
                    <small class="text-muted d-block" style="font-size: 0.75rem;">
                        {{ $order->merchant?->address }} ({{ $order->merchant?->territory?->name }})
                    </small>
                </div>
                @if($order->merchant?->phone)
                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $order->merchant->phone) }}?text=Halo%20{{ urlencode($order->merchant->store_name) }},%20saya%20menanyakan%20pesanan%20nomor%20{{ $order->order_number }}" 
                       target="_blank" 
                       class="btn btn-sm btn-outline-success rounded-pill px-2 py-1 text-nowrap" style="font-size: 0.72rem;">
                        <i class="icofont-brand-whatsapp"></i> Chat Toko
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Info Kurir & Pengiriman -->
    <div class="card border rounded-3 shadow-sm bg-white mb-3">
        <div class="card-body p-3">
            <h6 class="fw-bold small text-dark mb-2">
                <i class="icofont-delivery-time text-primary me-1"></i> Pengiriman & Kurir
            </h6>
            
            <div class="mb-2 pb-2 border-bottom">
                <small class="text-muted d-block" style="font-size: 0.7rem;">Alamat Pengantaran</small>
                <span class="text-dark small fw-semibold">{{ $order->shipping_address }}</span>
            </div>

            @if($order->delivery)
                <div class="d-flex align-items-center justify-content-between pt-1">
                    <div>
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Kurir Ojol Desa</small>
                        <span class="fw-bold text-dark small">
                            {{ $order->delivery->driver?->name ?? 'Mencari Kurir Ojol...' }}
                        </span>
                        <small class="text-muted d-block" style="font-size: 0.7rem;">
                            Kode Lacak: <code>{{ $order->delivery->tracking_code }}</code>
                        </small>
                    </div>

                    @if($order->delivery->driver?->phone)
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $order->delivery->driver->phone) }}?text=Halo%20Kurir,%20saya%20pemesan%20nomor%20{{ $order->order_number }}" 
                           target="_blank" 
                           class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1 text-nowrap" style="font-size: 0.72rem;">
                            <i class="icofont-brand-whatsapp"></i> Hubungi Kurir
                        </a>
                    @endif
                </div>
            @else
                <div class="text-secondary small">
                    <i class="icofont-info-circle me-1"></i> Pengantaran ditangani langsung oleh pihak warung toko.
                </div>
            @endif
        </div>
    </div>

    <!-- Rincian Produk Belanja -->
    <div class="card border rounded-3 shadow-sm bg-white mb-3">
        <div class="card-body p-3">
            <h6 class="fw-bold small text-dark mb-3">
                <i class="icofont-cart text-success me-1"></i> Rincian Barang ({{ $order->items->count() }} item)
            </h6>

            <div class="d-flex flex-column gap-2">
                @foreach($order->items as $item)
                    <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                        <div>
                            <span class="fw-semibold text-dark small d-block">{{ $item->product_name }}</span>
                            <small class="text-muted" style="font-size: 0.72rem;">
                                {{ $item->quantity }} {{ $item->unit_name }} x Rp {{ number_format($item->price, 0, ',', '.') }}
                            </small>
                        </div>
                        <span class="fw-bold text-dark small">
                            Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                        </span>
                    </div>
                @endforeach
            </div>

            @if($order->notes)
                <div class="mt-2 pt-2 bg-light rounded p-2 small text-secondary">
                    <strong>Catatan Pemesan:</strong> "{{ $order->notes }}"
                </div>
            @endif
        </div>
    </div>

    <!-- Rincian Biaya & Pembayaran -->
    <div class="card border rounded-3 shadow-sm bg-white mb-4">
        <div class="card-body p-3">
            <h6 class="fw-bold small text-dark mb-3">
                <i class="icofont-bill text-secondary me-1"></i> Rincian Pembayaran
            </h6>

            <div class="d-flex justify-content-between small text-secondary mb-1">
                <span>Subtotal Barang</span>
                <span class="fw-semibold text-dark">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
            </div>

            <div class="d-flex justify-content-between small text-secondary mb-1">
                <span>Ongkos Kirim Desa</span>
                <span class="fw-semibold text-dark">Rp {{ number_format($order->shipping_fee, 0, ',', '.') }}</span>
            </div>

            <div class="d-flex justify-content-between small text-secondary mb-2">
                <span>Biaya Platform Koperasi</span>
                <span class="fw-semibold text-dark">Rp {{ number_format($order->platform_fee, 0, ',', '.') }}</span>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                <div>
                    <span class="fw-bold text-dark">Total Pembayaran</span>
                    <small class="d-block text-muted" style="font-size: 0.72rem;">
                        Metode: <strong>{{ strtoupper($order->payment_method) }}</strong> ({{ $order->payment_status === 'paid' ? 'LUNAS' : 'Belum Lunas / Bayar di Tempat' }})
                    </small>
                </div>
                <span class="fw-bold text-success fs-5">
                    Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                </span>
            </div>
        </div>
    </div>

    <!-- Tombol Aksi Bawah -->
    <div class="d-grid gap-2 mb-4">
        @if($order->status === 'pending')
            <button type="button" class="btn btn-outline-danger btn-lg w-100 rounded-3 fw-bold py-2 shadow-sm" style="font-size: 0.95rem;" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                <i class="icofont-close-circled me-1"></i> Batalkan Pesanan Ini
            </button>

            <!-- Modal Batalkan Pesanan -->
            <div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form action="{{ route('user.orders.cancel', $order->order_number) }}" method="POST">
                            @csrf
                            <div class="modal-header py-2">
                                <h6 class="modal-title fw-bold text-dark">Batalkan Pesanan #{{ $order->order_number }}</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-3">
                                <p class="small text-secondary mb-3">
                                    Apakah Anda yakin ingin membatalkan pesanan ini? Stok barang akan segera dikembalikan ke toko mitra.
                                </p>
                                <label class="form-label small fw-semibold text-dark">Pilih Alasan Pembatalan:</label>
                                <select name="cancel_reason" class="form-select form-select-sm mb-2" required>
                                    <option value="Ingin mengubah rincian barang belanjaan">Ingin mengubah rincian barang belanjaan</option>
                                    <option value="Salah memasukkan alamat pengantaran">Salah memasukkan alamat pengantaran</option>
                                    <option value="Ingin mengganti metode pembayaran">Ingin mengganti metode pembayaran</option>
                                    <option value="Berubah pikiran / tidak jadi beli">Berubah pikiran / tidak jadi beli</option>
                                </select>
                            </div>
                            <div class="modal-footer py-2">
                                <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Tidak Jadi</button>
                                <button type="submit" class="btn btn-sm btn-danger fw-bold">Ya, Batalkan Pesanan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @elseif(in_array($order->status, ['confirmed', 'processing', 'ready_for_pickup', 'shipping']))
            <div class="alert alert-light border py-2 px-3 small text-secondary rounded-3 d-flex align-items-center justify-content-between mb-0">
                <div>
                    <i class="icofont-info-circle text-primary me-1"></i>
                    <span>Pesanan sedang diproses/diantar toko.</span>
                </div>
                @if($order->merchant?->phone)
                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $order->merchant->phone) }}?text=Halo%20{{ urlencode($order->merchant->store_name) }},%20saya%20ingin%20mengajukan%20pembatalan%20pesanan%20nomor%20{{ $order->order_number }}" 
                       target="_blank" 
                       class="btn btn-sm btn-outline-success py-1 px-2 text-nowrap" style="font-size: 0.72rem;">
                        <i class="icofont-brand-whatsapp"></i> Chat Toko
                    </a>
                @endif
            </div>
        @endif

        <form action="{{ route('user.orders.reorder', $order->order_number) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-success btn-lg w-100 rounded-3 fw-bold py-2 shadow-sm" style="font-size: 0.95rem;">
                <i class="icofont-refresh me-1"></i> Beli Lagi Produk Ini
            </button>
        </form>

        <a href="{{ route('user.orders.index') }}" class="btn btn-light border rounded-3 py-2 text-secondary fw-semibold small">
            Kembali ke Riwayat Pesanan
        </a>
    </div>
</div>
@endsection
