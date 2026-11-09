@extends('layouts.backoffice')

@section('title', 'Master Katalog Produk — Mak Ethek Backoffice')
@section('page_title', 'Master Katalog Produk & Harga (B2C / B2B)')
@section('page_subtitle', 'Kelola SKU, Harga Pokok (HPP), Harga Eceran Warga (B2C), dan Harga Grosir Warung (B2B)')

@section('header_actions')
    <a href="{{ route('admin.products.create') }}" class="btn btn-sm btn-success fw-bold px-3">
        <i class="bi bi-plus-lg me-1"></i> Tambah Produk Baru
    </a>
@endsection

@section('content')
@if($products->isEmpty())
    <div class="stat-card text-center py-5 text-muted">
        <i class="bi bi-box-seam fs-1 d-block mb-2 opacity-50"></i>
        <h6>Belum ada master produk terdaftar</h6>
        <a href="{{ route('admin.products.create') }}" class="btn btn-sm btn-success mt-2">
            <i class="bi bi-plus-lg me-1"></i> Tambah Produk Sekarang
        </a>
    </div>
@else
    <div class="table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Produk & SKU</th>
                        <th>Kategori</th>
                        <th>Satuan</th>
                        <th class="text-end">HPP (Modal)</th>
                        <th class="text-end">Harga B2C (Eceran)</th>
                        <th class="text-end">Harga B2B (Grosir)</th>
                        <th class="text-center">Status</th>
                        <th class="pe-3 text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $p)
                        @php
                            $tierMap = $p->tierPrices->keyBy('buyer_tier');
                            $b2bPrice = $p->b2b_price ?? ($tierMap['sub_agent']->wholesale_price ?? $p->base_price);
                        @endphp
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex align-items-center gap-3">
                                    @if($p->image)
                                        <img src="{{ $p->image }}" alt="{{ $p->name }}" class="rounded border object-fit-cover" style="width: 44px; height: 44px;">
                                    @else
                                        <div class="bg-light rounded border d-flex align-items-center justify-content-center text-muted" style="width: 44px; height: 44px;">
                                            <i class="bi bi-box"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="fw-bold text-dark">{{ $p->name }}</div>
                                        <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.68rem;">{{ $p->sku }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $p->category?->name ?? 'Umum' }}</span>
                            </td>
                            <td class="text-secondary">{{ $p->unit_name }}</td>
                            <td class="text-end text-muted">
                                Rp {{ number_format($p->cost_price ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="text-end fw-bold text-success">
                                Rp {{ number_format($p->base_price, 0, ',', '.') }}
                            </td>
                            <td class="text-end fw-bold text-primary">
                                Rp {{ number_format($b2bPrice, 0, ',', '.') }}
                                <small class="d-block text-muted fw-normal" style="font-size: 0.68rem;">
                                    Sub-Agen: Rp {{ isset($tierMap['sub_agent']) ? number_format($tierMap['sub_agent']->wholesale_price, 0, ',', '.') : '-' }}
                                </small>
                            </td>
                            <td class="text-center">
                                @if($p->is_active)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Aktif</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Nonaktif</span>
                                @endif
                            </td>
                            <td class="pe-3 text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.products.edit', $p->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <form action="{{ route('admin.products.destroy', $p->id) }}" method="POST"
                                          onsubmit="return confirm('Hapus produk {{ $p->name }} dari katalog?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3 border-top d-flex justify-content-end">
            {{ $products->links() }}
        </div>
    </div>
@endif
@endsection
