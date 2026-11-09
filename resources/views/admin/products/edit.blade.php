@extends('layouts.backoffice')

@section('title', 'Edit Master Produk — ' . $product->name)
@section('page_title', 'Edit Master Produk: ' . $product->name)
@section('page_subtitle', 'Perubahan harga B2C/B2B dan HPP akan tercatat pada Audit Log sistem')

@section('header_actions')
    <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Katalog
    </a>
@endsection

@section('content')
@php
    $subAgentTier = $tierMap->get('sub_agent');
    $agentTier = $tierMap->get('agent');
    $distributorTier = $tierMap->get('distributor');
@endphp

<form action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="stat-card">
                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">
                    <i class="bi bi-info-circle text-primary me-1"></i> 1. Identitas & Harga Utama Produk
                </h6>

                <div class="mb-3">
                    <label for="name" class="form-label small fw-semibold text-secondary">Nama Komoditas / Produk <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="category_id" class="form-label small fw-semibold text-secondary">Kategori <span class="text-danger">*</span></label>
                        <select name="category_id" id="category_id" class="form-select" required>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="sku" class="form-label small fw-semibold text-secondary">Kode SKU <span class="text-danger">*</span></label>
                        <input type="text" name="sku" id="sku" class="form-control font-monospace" value="{{ old('sku', $product->sku) }}" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="unit_name" class="form-label small fw-semibold text-secondary">Satuan Jual <span class="text-danger">*</span></label>
                        <input type="text" name="unit_name" id="unit_name" class="form-control" value="{{ old('unit_name', $product->unit_name) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label for="cost_price" class="form-label small fw-semibold text-secondary">HPP / Harga Modal (Rp)</label>
                        <input type="number" name="cost_price" id="cost_price" class="form-control" value="{{ old('cost_price', (int)($product->cost_price ?? 0)) }}" step="100" min="0">
                    </div>
                    <div class="col-md-4">
                        <label for="base_price" class="form-label small fw-semibold text-secondary">Harga Eceran B2C (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="base_price" id="base_price" class="form-control" value="{{ old('base_price', (int)$product->base_price) }}" step="100" min="100" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="b2b_price" class="form-label small fw-semibold text-primary">Harga Grosir B2B Pilot Blitar (Rp)</label>
                        <input type="number" name="b2b_price" id="b2b_price" class="form-control border-primary" value="{{ old('b2b_price', (int)($product->b2b_price ?? $subAgentTier?->wholesale_price ?? 0)) }}" step="100" min="0">
                    </div>
                    <div class="col-md-6">
                        <label for="image" class="form-label small fw-semibold text-secondary">Ganti Foto Produk</label>
                        <input type="file" name="image" id="image" class="form-control" accept="image/*">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label small fw-semibold text-secondary">Deskripsi Produk</label>
                    <textarea name="description" id="description" rows="2" class="form-control">{{ old('description', $product->description) }}</textarea>
                </div>

                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label small fw-semibold text-dark" for="isActive">Aktifkan Produk di Katalog Storefront</label>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="stat-card mb-3">
                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">
                    <i class="bi bi-diagram-2 text-success me-1"></i> 2. Skema Harga Tier Koperasi
                </h6>

                <div class="p-3 mb-3 rounded border bg-light">
                    <span class="badge bg-warning text-dark mb-2">TIER 1: MITRA SUB-AGEN / GROSIR B2B</span>
                    <div class="row g-2">
                        <div class="col-7">
                            <label class="form-label small text-muted mb-1">Harga Grosir Sub-Agen (Rp)</label>
                            <input type="number" name="price_sub_agent" class="form-control form-control-sm" value="{{ old('price_sub_agent', (int)($subAgentTier?->wholesale_price ?? 0)) }}" step="100" min="0" required>
                        </div>
                        <div class="col-5">
                            <label class="form-label small text-muted mb-1">MOQ</label>
                            <input type="number" name="moq_sub_agent" class="form-control form-control-sm" value="{{ old('moq_sub_agent', $subAgentTier?->moq ?? 5) }}" min="1" required>
                        </div>
                    </div>
                </div>

                <div class="p-3 mb-3 rounded border bg-light">
                    <span class="badge bg-info text-dark mb-2">TIER 2: MITRA AGEN KECAMATAN</span>
                    <div class="row g-2">
                        <div class="col-7">
                            <label class="form-label small text-muted mb-1">Harga Kulakan Agen (Rp)</label>
                            <input type="number" name="price_agent" class="form-control form-control-sm" value="{{ old('price_agent', (int)($agentTier?->wholesale_price ?? 0)) }}" step="100" min="0" required>
                        </div>
                        <div class="col-5">
                            <label class="form-label small text-muted mb-1">MOQ</label>
                            <input type="number" name="moq_agent" class="form-control form-control-sm" value="{{ old('moq_agent', $agentTier?->moq ?? 20) }}" min="1" required>
                        </div>
                    </div>
                </div>

                <div class="p-3 rounded border bg-light">
                    <span class="badge bg-secondary text-white mb-2">TIER 3: DISTRIBUTOR KOTA/KAB</span>
                    <div class="row g-2">
                        <div class="col-7">
                            <label class="form-label small text-muted mb-1">Harga Distributor (Rp)</label>
                            <input type="number" name="price_distributor" class="form-control form-control-sm" value="{{ old('price_distributor', (int)($distributorTier?->wholesale_price ?? 0)) }}" step="100" min="0" required>
                        </div>
                        <div class="col-5">
                            <label class="form-label small text-muted mb-1">MOQ</label>
                            <input type="number" name="moq_distributor" class="form-control form-control-sm" value="{{ old('moq_distributor', $distributorTier?->moq ?? 100) }}" min="1" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('admin.products.index') }}" class="btn btn-light border px-4 fw-semibold">Batal</a>
                <button type="submit" class="btn btn-primary flex-grow-1 fw-bold py-2">
                    <i class="bi bi-save me-1"></i> Perbarui Master Produk
                </button>
            </div>
        </div>
    </div>
</form>
@endsection
