@extends('layouts.storefront')

@section('title', 'Riwayat Pesanan Saya - Mak Ethek')

@section('header_extra')
    <div class="px-1 pt-2 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('storefront.index') }}" class="btn btn-sm btn-light border rounded-pill px-2 py-1 text-decoration-none">
                <i class="icofont-rounded-left"></i> Belanja
            </a>
            <span class="fw-bold small text-dark">
                Pesanan Saya
            </span>
        </div>
        <span class="badge bg-success-subtle text-success border border-success" style="font-size: 0.7rem;">
            {{ $counts['all'] }} Pesanan
        </span>
    </div>
@endsection

@section('content')
<div class="osahan-order">
    <!-- Tab Status Pesanan -->
    <div class="bg-white border-bottom sticky-top" style="top: 57px; z-index: 1010;">
        <div class="d-flex text-center overflow-auto px-2 py-2 gap-1" style="scrollbar-width: none;">
            <a href="{{ route('user.orders.index', ['status' => 'all']) }}" 
               class="btn btn-sm rounded-pill px-3 py-1 text-nowrap {{ $activeStatus === 'all' ? 'btn-success fw-bold' : 'btn-light text-muted' }}" 
               style="font-size: 0.75rem;">
                Semua ({{ $counts['all'] }})
            </a>
            <a href="{{ route('user.orders.index', ['status' => 'processing']) }}" 
               class="btn btn-sm rounded-pill px-3 py-1 text-nowrap {{ $activeStatus === 'processing' ? 'btn-success fw-bold' : 'btn-light text-muted' }}" 
               style="font-size: 0.75rem;">
                Sedang Diproses ({{ $counts['processing'] }})
            </a>
            <a href="{{ route('user.orders.index', ['status' => 'completed']) }}" 
               class="btn btn-sm rounded-pill px-3 py-1 text-nowrap {{ $activeStatus === 'completed' ? 'btn-success fw-bold' : 'btn-light text-muted' }}" 
               style="font-size: 0.75rem;">
                Selesai ({{ $counts['completed'] }})
            </a>
            <a href="{{ route('user.orders.index', ['status' => 'cancelled']) }}" 
               class="btn btn-sm rounded-pill px-3 py-1 text-nowrap {{ $activeStatus === 'cancelled' ? 'btn-success fw-bold' : 'btn-light text-muted' }}" 
               style="font-size: 0.75rem;">
                Dibatalkan ({{ $counts['cancelled'] }})
            </a>
        </div>
    </div>

    <div class="p-3">
        @if($orders->isEmpty())
            <div class="text-center py-5 bg-white border rounded-3 p-4 shadow-sm">
                <div class="bg-light rounded-circle p-4 d-inline-flex mb-3">
                    <i class="icofont-box text-muted" style="font-size: 3rem;"></i>
                </div>
                <h6 class="fw-bold text-dark">Belum Ada Pesanan</h6>
                <p class="text-muted small mb-3">
                    @if($activeStatus === 'all')
                        Anda belum pernah berbelanja di Mak Ethek. Yuk temukan sembako murah dari warung tetangga!
                    @else
                        Tidak ada pesanan dengan status "{{ ucfirst($activeStatus) }}".
                    @endif
                </p>
                <a href="{{ route('storefront.index') }}" class="btn btn-success fw-bold px-4 py-2 rounded-3 shadow-sm" style="font-size: 0.85rem;">
                    <i class="icofont-grocery me-1"></i> Mulai Belanja
                </a>
            </div>
        @else
            <div class="d-flex flex-column gap-3">
                @foreach($orders as $order)
                    @php
                        $statusBadges = [
                            'pending' => ['bg' => 'bg-warning-subtle text-warning-emphasis border-warning', 'label' => 'Menunggu Konfirmasi'],
                            'confirmed' => ['bg' => 'bg-info-subtle text-info-emphasis border-info', 'label' => 'Dikonfirmasi Toko'],
                            'processing' => ['bg' => 'bg-primary-subtle text-primary border-primary', 'label' => 'Sedang Disiapkan'],
                            'ready_for_pickup' => ['bg' => 'bg-indigo-subtle text-indigo border-indigo', 'label' => 'Siap Diantar'],
                            'shipping' => ['bg' => 'bg-primary text-white border-primary', 'label' => 'Kurir Menuju Lokasi'],
                            'delivered' => ['bg' => 'bg-success-subtle text-success border-success', 'label' => 'Pesanan Selesai'],
                            'cancelled' => ['bg' => 'bg-danger-subtle text-danger border-danger', 'label' => 'Dibatalkan'],
                        ];
                        $badge = $statusBadges[$order->status] ?? ['bg' => 'bg-secondary text-white', 'label' => ucfirst($order->status)];
                    @endphp

                    <div class="card border rounded-3 shadow-sm bg-white overflow-hidden">
                        <!-- Card Header -->
                        <div class="card-header bg-light border-bottom p-3 d-flex align-items-center justify-content-between">
                            <div>
                                <span class="fw-bold small text-dark d-block">#{{ $order->order_number }}</span>
                                <small class="text-muted" style="font-size: 0.7rem;">
                                    <i class="icofont-clock-time"></i> {{ $order->created_at->translatedFormat('d M Y, H:i') }}
                                </small>
                            </div>
                            <span class="badge {{ $badge['bg'] }} border px-2 py-1" style="font-size: 0.68rem;">
                                {{ $badge['label'] }}
                            </span>
                        </div>

                        <!-- Card Body: Ringkasan Toko & Item -->
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-2 mb-2 pb-2 border-bottom">
                                <i class="icofont-shop text-success fs-5"></i>
                                <div>
                                    <span class="fw-bold small text-dark d-block">{{ $order->merchant?->store_name ?? 'Mitra Koperasi' }}</span>
                                    <small class="text-muted" style="font-size: 0.7rem;">
                                        Metode: <strong>{{ strtoupper($order->payment_method) }}</strong> ({{ $order->payment_status === 'paid' ? 'Lunas' : 'Belum Bayar' }})
                                    </small>
                                </div>
                            </div>

                            <!-- List Item Ringkas -->
                            <div class="mb-3">
                                @foreach($order->items->take(2) as $item)
                                    <div class="d-flex align-items-center justify-content-between py-1 small">
                                        <div class="text-truncate me-2" style="max-width: 260px;">
                                            <span class="fw-semibold text-dark">{{ $item->product_name }}</span>
                                            <small class="text-muted">x{{ $item->quantity }} {{ $item->unit_name }}</small>
                                        </div>
                                        <span class="text-secondary fw-semibold">
                                            Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                        </span>
                                    </div>
                                @endforeach

                                @if($order->items->count() > 2)
                                    <small class="text-muted fst-italic" style="font-size: 0.72rem;">
                                        +{{ $order->items->count() - 2 }} barang lainnya...
                                    </small>
                                @endif
                            </div>

                            <!-- Total & Action Buttons -->
                            <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                                <div>
                                    <small class="text-muted d-block" style="font-size: 0.7rem;">Total Belanja</small>
                                    <span class="fw-bold text-success fs-6">
                                        Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                    </span>
                                </div>

                                <div class="d-flex gap-2">
                                    <form action="{{ route('user.orders.reorder', $order->order_number) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 fw-semibold" style="font-size: 0.75rem;" title="Beli Lagi barang pesanan ini">
                                            <i class="icofont-refresh"></i> Beli Lagi
                                        </button>
                                    </form>

                                    <a href="{{ route('user.orders.show', $order->order_number) }}" class="btn btn-sm btn-success rounded-pill px-3 py-1 fw-semibold" style="font-size: 0.75rem;">
                                        Lacak / Detail
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
