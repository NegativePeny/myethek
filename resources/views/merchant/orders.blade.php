@extends('layouts.merchant')

@section('title', 'Manajemen Pesanan - ' . $merchant->store_name)

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
            <h5 class="fw-bold mb-0 text-dark">Daftar Pesanan Retail</h5>
            <small class="text-muted" style="font-size: 0.75rem;">Kelola pesanan warga desa yang masuk ke toko Anda</small>
        </div>
        <span class="badge bg-success" style="font-size: 0.7rem;">
            Total: {{ $counts['all'] }}
        </span>
    </div>

    <!-- Filter Status Tabs -->
    <div class="d-flex gap-1 overflow-auto pb-2 mb-3" style="scrollbar-width: none;">
        <a href="{{ route('merchant.orders', ['status' => 'all']) }}" 
           class="btn btn-sm rounded-pill px-3 py-1 text-nowrap {{ $activeStatus === 'all' ? 'btn-success fw-bold' : 'btn-outline-secondary' }}" 
           style="font-size: 0.75rem;">
            Semua ({{ $counts['all'] }})
        </a>
        <a href="{{ route('merchant.orders', ['status' => 'pending']) }}" 
           class="btn btn-sm rounded-pill px-3 py-1 text-nowrap {{ $activeStatus === 'pending' ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary' }}" 
           style="font-size: 0.75rem;">
            Baru Masuk ({{ $counts['pending'] }})
        </a>
        <a href="{{ route('merchant.orders', ['status' => 'processing']) }}" 
           class="btn btn-sm rounded-pill px-3 py-1 text-nowrap {{ $activeStatus === 'processing' ? 'btn-primary fw-bold' : 'btn-outline-secondary' }}" 
           style="font-size: 0.75rem;">
            Disiapkan ({{ $counts['processing'] }})
        </a>
        <a href="{{ route('merchant.orders', ['status' => 'completed']) }}" 
           class="btn btn-sm rounded-pill px-3 py-1 text-nowrap {{ $activeStatus === 'completed' ? 'btn-success fw-bold' : 'btn-outline-secondary' }}" 
           style="font-size: 0.75rem;">
            Selesai ({{ $counts['completed'] }})
        </a>
        <a href="{{ route('merchant.orders', ['status' => 'cancelled']) }}" 
           class="btn btn-sm rounded-pill px-3 py-1 text-nowrap {{ $activeStatus === 'cancelled' ? 'btn-danger fw-bold' : 'btn-outline-secondary' }}" 
           style="font-size: 0.75rem;">
            Batal ({{ $counts['cancelled'] }})
        </a>
    </div>

    <!-- Form Pencarian -->
    <form action="{{ route('merchant.orders') }}" method="GET" class="mb-3">
        <input type="hidden" name="status" value="{{ $activeStatus }}">
        <div class="input-group input-group-sm">
            <input type="text" name="q" class="form-control" placeholder="Cari nomor pesanan atau nama pembeli..." value="{{ $search }}">
            <button class="btn btn-outline-secondary" type="submit">
                <i class="bi bi-search"></i>
            </button>
        </div>
    </form>

    <!-- Daftar Pesanan -->
    @if($orders->isEmpty())
        <div class="card border shadow-sm text-center p-4">
            <i class="bi bi-inbox text-muted fs-1 mb-2"></i>
            <h6 class="fw-bold text-dark mb-1">Tidak Ada Pesanan</h6>
            <p class="text-muted small mb-0">Belum ada pesanan retail warga yang sesuai filter ini.</p>
        </div>
    @else
        <div class="d-flex flex-column gap-3">
            @foreach($orders as $order)
                @php
                    $statusBadges = [
                        'pending' => ['bg' => 'bg-warning text-dark', 'label' => 'Perlu Konfirmasi'],
                        'confirmed' => ['bg' => 'bg-info text-dark', 'label' => 'Dikonfirmasi'],
                        'processing' => ['bg' => 'bg-primary text-white', 'label' => 'Sedang Dikemas'],
                        'ready_to_ship' => ['bg' => 'bg-indigo text-white', 'label' => 'Siap Diambil Kurir'],
                        'shipping' => ['bg' => 'bg-primary text-white', 'label' => 'Diantar Kurir'],
                        'delivered' => ['bg' => 'bg-success text-white', 'label' => 'Selesai'],
                        'cancelled' => ['bg' => 'bg-danger text-white', 'label' => 'Dibatalkan'],
                    ];
                    $badge = $statusBadges[$order->status] ?? ['bg' => 'bg-secondary text-white', 'label' => ucfirst($order->status)];
                @endphp

                <div class="card border shadow-sm overflow-hidden">
                    <div class="card-header bg-light d-flex align-items-center justify-content-between p-2 px-3">
                        <div>
                            <span class="fw-bold small text-dark d-block">#{{ $order->order_number }}</span>
                            <small class="text-muted" style="font-size: 0.68rem;">
                                <i class="bi bi-clock"></i> {{ $order->created_at->translatedFormat('d M Y, H:i') }}
                            </small>
                        </div>
                        <span class="badge {{ $badge['bg'] }} px-2 py-1" style="font-size: 0.68rem;">
                            {{ $badge['label'] }}
                        </span>
                    </div>

                    <div class="card-body p-3">
                        <!-- Info Pemesan -->
                        <div class="d-flex align-items-start justify-content-between pb-2 mb-2 border-bottom">
                            <div>
                                <span class="fw-bold small text-dark d-block">{{ $order->customer?->name ?? 'Warga Pembeli' }}</span>
                                <small class="text-muted d-block" style="font-size: 0.72rem;">
                                    <i class="bi bi-geo-alt"></i> {{ $order->shipping_address }}
                                </small>
                            </div>
                            @if($order->customer?->phone)
                                <a href="https://wa.me/{{ preg_replace('/\D/', '', $order->customer->phone) }}?text=Halo%20{{ urlencode($order->customer->name) }},%20pesanan%20Anda%20nomor%20{{ $order->order_number }}%20sedang%20kami%20proses." 
                                   target="_blank" 
                                   class="btn btn-sm btn-outline-success py-0 px-2" style="font-size: 0.72rem;">
                                    <i class="bi bi-whatsapp"></i> Chat WA
                                </a>
                            @endif
                        </div>

                        <!-- Daftar Barang -->
                        <div class="mb-2">
                            @foreach($order->items as $item)
                                <div class="d-flex justify-content-between small py-1">
                                    <span class="text-truncate me-2" style="max-width: 280px;">
                                        {{ $item->product_name }} <span class="text-muted">x{{ $item->quantity }} {{ $item->unit_name }}</span>
                                    </span>
                                    <span class="fw-semibold text-dark">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>

                        <!-- Total & Aksi Status -->
                        <div class="pt-2 border-top d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-muted d-block" style="font-size: 0.68rem;">Total Transaksi</small>
                                <span class="fw-bold text-success fs-6">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                                <span class="badge bg-light text-secondary border ms-1" style="font-size: 0.62rem;">
                                    {{ strtoupper($order->payment_method) }}
                                </span>
                            </div>

                            <div class="d-flex gap-1">
                                @if($order->status === 'pending')
                                    <form action="{{ route('merchant.updateOrderStatus', $order->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="action" value="confirm">
                                        <button type="submit" class="btn btn-sm btn-success fw-semibold px-2 py-1" style="font-size: 0.75rem;">
                                            <i class="bi bi-check2-circle"></i> Terima & Kemas
                                        </button>
                                    </form>
                                @elseif($order->status === 'processing')
                                    <form action="{{ route('merchant.updateOrderStatus', $order->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="action" value="ready">
                                        <button type="submit" class="btn btn-sm btn-primary fw-semibold px-2 py-1" style="font-size: 0.75rem;">
                                            <i class="bi bi-box-seam"></i> Siap Diambil
                                        </button>
                                    </form>
                                @elseif(in_array($order->status, ['ready_to_ship', 'shipping']))
                                    <button type="button" class="btn btn-sm btn-outline-success fw-semibold px-2 py-1" style="font-size: 0.75rem;" 
                                            data-bs-toggle="modal" data-bs-target="#completeModal{{ $order->id }}">
                                        <i class="bi bi-shield-lock"></i> Selesaikan (OTP)
                                    </button>

                                    <!-- Modal OTP -->
                                    <div class="modal fade" id="completeModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-sm">
                                            <div class="modal-content">
                                                <form action="{{ route('merchant.updateOrderStatus', $order->id) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="action" value="complete">
                                                    <div class="modal-header py-2">
                                                        <h6 class="modal-title fw-bold">Konfirmasi Selesai</h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-3">
                                                        <p class="small text-muted mb-2">
                                                            Masukkan 6 digit kode OTP penyelesaian dari pembeli/kurir:
                                                        </p>
                                                        <input type="text" name="otp" class="form-control text-center fs-5 fw-bold letter-spacing-2" placeholder="Contoh: 123456" maxlength="6" required>
                                                    </div>
                                                    <div class="modal-footer py-2">
                                                        <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Tutup</button>
                                                        <button type="submit" class="btn btn-sm btn-success fw-bold">Verifikasi & Cairkan Dana</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="mt-2">
                {{ $orders->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>
@endsection
