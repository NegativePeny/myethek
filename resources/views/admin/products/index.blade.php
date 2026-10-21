@extends('layouts.merchant')

@section('title', 'Master Katalog Produk - Holding Koperasi Jatim')

@section('content')
<div class="p-3">
    <!-- Breadcrumb & Header -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <a href="{{ route('admin.index') }}" class="small text-decoration-none text-muted">
                <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
            </a>
            <h5 class="fw-bold mb-0 text-dark">Master Katalog Produk</h5>
            <small class="text-muted" style="font-size: 0.72rem;">Standarisasi komoditas pangan & harga grosir berjenjang se-Jatim</small>
        </div>
        <a href="{{ route('admin.products.create') }}" class="btn btn-sm btn-success fw-bold py-1 px-3" style="font-size: 0.8rem;">
            <i class="bi bi-plus-lg me-1"></i> Tambah Produk
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 px-3 small" role="alert">
            <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($products->isEmpty())
        <div class="p-4 rounded-3 border bg-white text-center text-muted">
            <i class="bi bi-box-seam fs-2 d-block mb-1 opacity-50"></i>
            <span class="small">Belum ada master produk terdaftar.</span>
            <div class="mt-2">
                <a href="{{ route('admin.products.create') }}" class="btn btn-sm btn-success">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Produk Sekarang
                </a>
            </div>
        </div>
    @else
        <div class="d-flex flex-column gap-2 mb-3">
            @foreach($products as $p)
                @php
                    $tierMap = $p->tierPrices->keyBy('buyer_tier');
                @endphp
                <div class="card border shadow-sm bg-white">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex gap-2">
                                @if($p->image)
                                    <img src="{{ $p->image }}" alt="{{ $p->name }}" class="rounded border object-fit-cover" style="width: 48px; height: 48px;">
                                @else
                                    <div class="bg-light rounded border d-flex align-items-center justify-content-center text-muted" style="width: 48px; height: 48px;">
                                        <i class="bi bi-box fs-5"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="badge bg-light text-secondary border" style="font-size: 0.65rem;">
                                            {{ $p->category?->name ?? 'Kategori Umum' }}
                                        </span>
                                        <span class="badge bg-dark-subtle text-dark border font-monospace" style="font-size: 0.65rem;">
                                            {{ $p->sku }}
                                        </span>
                                        @if($p->is_active)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;">
                                                Aktif
                                            </span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.65rem;">
                                                Nonaktif
                                            </span>
                                        @endif
                                    </div>
                                    <h6 class="fw-bold text-dark mb-0 mt-1 small">{{ $p->name }}</h6>
                                    <small class="text-muted" style="font-size: 0.72rem;">Satuan: <strong>{{ $p->unit_name }}</strong></small>
                                </div>
                            </div>

                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border py-1 px-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size: 0.78rem;">
                                    <li>
                                        <a class="dropdown-item py-1" href="{{ route('admin.products.edit', $p->id) }}">
                                            <i class="bi bi-pencil me-2 text-primary"></i> Edit Master & Harga
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li>
                                        <form action="{{ route('admin.products.destroy', $p->id) }}" method="POST" 
                                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk {{ $p->name }} dari katalog master?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item py-1 text-danger">
                                                <i class="bi bi-trash me-2"></i> Hapus Produk
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Skema Harga Eceran & Kulakan Berjenjang -->
                        <div class="p-2 bg-light rounded border small mb-2" style="font-size: 0.72rem;">
                            <div class="d-flex justify-content-between mb-1 pb-1 border-bottom">
                                <span class="text-muted">Acuan Eceran Warga (HET):</span>
                                <strong class="text-success fs-6">Rp {{ number_format($p->base_price, 0, ',', '.') }}</strong>
                            </div>
                            <div class="row g-1 text-center mt-1">
                                <div class="col-4 border-end">
                                    <small class="text-muted d-block" style="font-size: 0.65rem;">Grosir Sub-Agen</small>
                                    <span class="fw-bold text-dark">
                                        Rp {{ isset($tierMap['sub_agent']) ? number_format($tierMap['sub_agent']->wholesale_price, 0, ',', '.') : '-' }}
                                    </span>
                                    <small class="text-muted d-block" style="font-size: 0.62rem;">Min: {{ $tierMap['sub_agent']->moq ?? 1 }}</small>
                                </div>
                                <div class="col-4 border-end">
                                    <small class="text-muted d-block" style="font-size: 0.65rem;">Grosir Agen</small>
                                    <span class="fw-bold text-dark">
                                        Rp {{ isset($tierMap['agent']) ? number_format($tierMap['agent']->wholesale_price, 0, ',', '.') : '-' }}
                                    </span>
                                    <small class="text-muted d-block" style="font-size: 0.62rem;">Min: {{ $tierMap['agent']->moq ?? 1 }}</small>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block" style="font-size: 0.65rem;">Grosir Distributor</small>
                                    <span class="fw-bold text-dark">
                                        Rp {{ isset($tierMap['distributor']) ? number_format($tierMap['distributor']->wholesale_price, 0, ',', '.') : '-' }}
                                    </span>
                                    <small class="text-muted d-block" style="font-size: 0.62rem;">Min: {{ $tierMap['distributor']->moq ?? 1 }}</small>
                                </div>
                            </div>
                        </div>

                        @if($p->description)
                            <p class="text-muted small mb-0 text-truncate" style="font-size: 0.72rem;">
                                {{ $p->description }}
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-center">
            {{ $products->links() }}
        </div>
    @endif
</div>
@endsection
