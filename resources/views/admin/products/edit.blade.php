@extends('layouts.merchant')

@section('title', 'Edit Master Produk - ' . $product->name)

@section('content')
<div class="p-3">
    <!-- Breadcrumb & Header -->
    <div class="mb-3">
        <a href="{{ route('admin.products.index') }}" class="small text-decoration-none text-muted">
            <i class="bi bi-arrow-left"></i> Kembali ke Katalog Master
        </a>
        <h5 class="fw-bold mb-0 text-dark mt-1">Edit Master Produk: {{ $product->name }}</h5>
        <small class="text-muted" style="font-size: 0.72rem;">Perubahan data acuan harga akan langsung berlaku pada katalog storefront & kulakan</small>
    </div>

    @if($errors->any())
        <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> Mohon periksa isian formulir di bawah ini:
            <ul class="mb-0 ps-3 mt-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <!-- Bagian 1: Identitas Produk Pokok -->
        <div class="card border shadow-sm mb-3 bg-white">
            <div class="card-header bg-white py-2">
                <h6 class="fw-bold mb-0 small text-dark">
                    <i class="bi bi-info-circle text-primary me-1"></i> 1. Informasi Dasar Produk
                </h6>
            </div>
            <div class="card-body p-3">
                <!-- Nama Produk -->
                <div class="mb-3">
                    <label for="name" class="form-label small fw-semibold text-secondary">
                        Nama Komoditas / Produk <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="name" id="name" class="form-control form-control-sm @error('name') is-invalid @enderror" 
                           value="{{ old('name', $product->name) }}" required>
                </div>

                <div class="row g-2 mb-3">
                    <!-- Kategori -->
                    <div class="col-6">
                        <label for="category_id" class="form-label small fw-semibold text-secondary">
                            Kategori <span class="text-danger">*</span>
                        </label>
                        <select name="category_id" id="category_id" class="form-select form-select-sm @error('category_id') is-invalid @enderror" required>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- SKU -->
                    <div class="col-6">
                        <label for="sku" class="form-label small fw-semibold text-secondary">
                            Kode SKU (Barcode) <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="sku" id="sku" class="form-control form-control-sm font-monospace @error('sku') is-invalid @enderror" 
                               value="{{ old('sku', $product->sku) }}" required>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <!-- Satuan Belanja Lokal -->
                    <div class="col-6">
                        <label for="unit_name" class="form-label small fw-semibold text-secondary">
                            Satuan Eceran <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="unit_name" id="unit_name" class="form-control form-control-sm @error('unit_name') is-invalid @enderror" 
                               value="{{ old('unit_name', $product->unit_name) }}" required>
                    </div>

                    <!-- Harga Acuan Eceran (HET Warga) -->
                    <div class="col-6">
                        <label for="base_price" class="form-label small fw-semibold text-secondary">
                            HET Acuan Warga (Rp) <span class="text-danger">*</span>
                        </label>
                        <input type="number" name="base_price" id="base_price" class="form-control form-control-sm @error('base_price') is-invalid @enderror" 
                               value="{{ old('base_price', (int)$product->base_price) }}" step="100" min="100" required>
                    </div>
                </div>

                <!-- Foto Produk -->
                <div class="mb-3">
                    <label for="image" class="form-label small fw-semibold text-secondary">
                        Ganti Foto Produk
                    </label>
                    @if($product->image)
                        <div class="d-flex align-items-center gap-2 mb-2 p-2 bg-light rounded border">
                            <img src="{{ $product->image }}" alt="{{ $product->name }}" class="rounded" style="width: 40px; height: 40px; object-fit: cover;">
                            <small class="text-muted text-truncate" style="font-size: 0.72rem;">{{ $product->image }}</small>
                        </div>
                    @endif
                    <input type="file" name="image" id="image" class="form-control form-control-sm @error('image') is-invalid @enderror" accept="image/*">
                </div>

                <!-- Deskripsi Produk -->
                <div class="mb-3">
                    <label for="description" class="form-label small fw-semibold text-secondary">
                        Deskripsi & Keterangan Kualitas
                    </label>
                    <textarea name="description" id="description" rows="2" class="form-control form-control-sm">{{ old('description', $product->description) }}</textarea>
                </div>

                <!-- Status Aktif -->
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label small fw-semibold text-dark" for="isActive">
                        Aktifkan Produk di Katalog Storefront & Kulakan
                    </label>
                </div>
            </div>
        </div>

        <!-- Bagian 2: Skema Harga Grosir Berjenjang (Tiered Pricing) -->
        <div class="card border shadow-sm mb-4 bg-white">
            <div class="card-header bg-white py-2">
                <h6 class="fw-bold mb-0 small text-dark">
                    <i class="bi bi-diagram-2 text-success me-1"></i> 2. Skema Harga Grosir Kulakan Berjenjang
                </h6>
            </div>
            <div class="card-body p-3">
                @php
                    $subAgentTier = $tierMap->get('sub_agent');
                    $agentTier = $tierMap->get('agent');
                    $distributorTier = $tierMap->get('distributor');
                @endphp

                <!-- Tier 1: Sub-Agen Desa -->
                <div class="p-2 mb-2 rounded border bg-light">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge bg-warning text-dark border border-warning fw-bold" style="font-size: 0.68rem;">
                            MITRA SUB-AGEN (WARUNG DESA)
                        </span>
                        <small class="text-muted" style="font-size: 0.68rem;">Kulakan ke Agen Kecamatan</small>
                    </div>
                    <div class="row g-2">
                        <div class="col-7">
                            <label class="form-label small text-muted mb-1" style="font-size: 0.68rem;">Harga Kulakan Sub-Agen (Rp):</label>
                            <input type="number" name="price_sub_agent" class="form-control form-control-sm" 
                                   value="{{ old('price_sub_agent', (int)($subAgentTier?->wholesale_price ?? 0)) }}" step="100" min="0" required>
                        </div>
                        <div class="col-5">
                            <label class="form-label small text-muted mb-1" style="font-size: 0.68rem;">Min. Order (MOQ):</label>
                            <input type="number" name="moq_sub_agent" class="form-control form-control-sm" 
                                   value="{{ old('moq_sub_agent', $subAgentTier?->moq ?? 5) }}" min="1" required>
                        </div>
                    </div>
                </div>

                <!-- Tier 2: Agen Kecamatan -->
                <div class="p-2 mb-2 rounded border bg-light">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge bg-info text-dark border border-info fw-bold" style="font-size: 0.68rem;">
                            MITRA AGEN (HUB KECAMATAN)
                        </span>
                        <small class="text-muted" style="font-size: 0.68rem;">Kulakan ke Distributor Kabupaten</small>
                    </div>
                    <div class="row g-2">
                        <div class="col-7">
                            <label class="form-label small text-muted mb-1" style="font-size: 0.68rem;">Harga Kulakan Agen (Rp):</label>
                            <input type="number" name="price_agent" class="form-control form-control-sm" 
                                   value="{{ old('price_agent', (int)($agentTier?->wholesale_price ?? 0)) }}" step="100" min="0" required>
                        </div>
                        <div class="col-5">
                            <label class="form-label small text-muted mb-1" style="font-size: 0.68rem;">Min. Order (MOQ):</label>
                            <input type="number" name="moq_agent" class="form-control form-control-sm" 
                                   value="{{ old('moq_agent', $agentTier?->moq ?? 20) }}" min="1" required>
                        </div>
                    </div>
                </div>

                <!-- Tier 3: Distributor Kabupaten -->
                <div class="p-2 rounded border bg-light">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge bg-secondary text-white fw-bold" style="font-size: 0.68rem;">
                            MITRA DISTRIBUTOR (GUDANG KAB/KOTA)
                        </span>
                        <small class="text-muted" style="font-size: 0.68rem;">Kulakan ke Pusat / Lumbung Koperasi</small>
                    </div>
                    <div class="row g-2">
                        <div class="col-7">
                            <label class="form-label small text-muted mb-1" style="font-size: 0.68rem;">Harga Kulakan Distributor (Rp):</label>
                            <input type="number" name="price_distributor" class="form-control form-control-sm" 
                                   value="{{ old('price_distributor', (int)($distributorTier?->wholesale_price ?? 0)) }}" step="100" min="0" required>
                        </div>
                        <div class="col-5">
                            <label class="form-label small text-muted mb-1" style="font-size: 0.68rem;">Min. Order (MOQ):</label>
                            <input type="number" name="moq_distributor" class="form-control form-control-sm" 
                                   value="{{ old('moq_distributor', $distributorTier?->moq ?? 100) }}" min="1" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tombol Aksi -->
        <div class="d-flex gap-2 mb-4">
            <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-light border py-2 px-3 fw-semibold">
                Batal
            </a>
            <button type="submit" class="btn btn-sm btn-primary flex-grow-1 py-2 fw-bold shadow-sm">
                <i class="bi bi-save me-1"></i> Perbarui Master Produk
            </button>
        </div>
    </form>
</div>
@endsection
