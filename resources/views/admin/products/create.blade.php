@extends('layouts.backoffice')

@section('title', 'Tambah Master Produk — Mak Ethek Backoffice')
@section('page_title', 'Tambah Master Produk Baru')
@section('page_subtitle', 'Standarisasi komoditas pangan, HPP, harga retail B2C, dan harga grosir B2B Koperasi')

@section('header_actions')
    <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Katalog
    </a>
@endsection

@section('content')
<form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="stat-card">
                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">
                    <i class="bi bi-info-circle text-primary me-1"></i> 1. Identitas & Harga Utama Produk
                </h6>

                <div class="mb-3">
                    <label for="name" class="form-label small fw-semibold text-secondary">Nama Komoditas / Produk <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="Contoh: Beras Lahap Wangi 5kg" value="{{ old('name') }}" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="category_id" class="form-label small fw-semibold text-secondary">Kategori <span class="text-danger">*</span></label>
                        <select name="category_id" id="category_id" class="form-select" required>
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="sku" class="form-label small fw-semibold text-secondary">Kode SKU</label>
                        <input type="text" name="sku" id="sku" class="form-control font-monospace" value="{{ old('sku', $suggestedSku) }}">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="unit_name" class="form-label small fw-semibold text-secondary">Satuan Jual <span class="text-danger">*</span></label>
                        <input type="text" name="unit_name" id="unit_name" class="form-control" placeholder="sak (5 kg), liter, kg" value="{{ old('unit_name') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label for="cost_price" class="form-label small fw-semibold text-secondary">HPP / Harga Modal (Rp)</label>
                        <input type="number" name="cost_price" id="cost_price" class="form-control" placeholder="Contoh: 60000" value="{{ old('cost_price') }}" step="100" min="0">
                    </div>
                    <div class="col-md-4">
                        <label for="base_price" class="form-label small fw-semibold text-secondary">Harga Eceran B2C (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="base_price" id="base_price" class="form-control" placeholder="Contoh: 75000" value="{{ old('base_price') }}" step="100" min="100" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="b2b_price" class="form-label small fw-semibold text-primary">Harga Grosir B2B Pilot Blitar (Rp)</label>
                        <input type="number" name="b2b_price" id="b2b_price" class="form-control border-primary" placeholder="Otomatis dari Sub-Agen bila kosong" value="{{ old('b2b_price') }}" step="100" min="0">
                        <small class="text-muted" style="font-size: 0.7rem;">Berlaku untuk akun B2B (Min. total belanja Rp1.000.000).</small>
                    </div>
                    <div class="col-md-6">
                        <label for="image" class="form-label small fw-semibold text-secondary">Foto Produk (Opsional)</label>
                        <input type="file" name="image" id="image" class="form-control" accept="image/*">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label small fw-semibold text-secondary">Deskripsi Produk</label>
                    <textarea name="description" id="description" rows="2" class="form-control">{{ old('description') }}</textarea>
                </div>

                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
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
                            <input type="number" name="price_sub_agent" class="form-control form-control-sm" value="{{ old('price_sub_agent') }}" step="100" min="0" required>
                        </div>
                        <div class="col-5">
                            <label class="form-label small text-muted mb-1">MOQ</label>
                            <input type="number" name="moq_sub_agent" class="form-control form-control-sm" value="{{ old('moq_sub_agent', 5) }}" min="1" required>
                        </div>
                    </div>
                </div>

                <div class="p-3 mb-3 rounded border bg-light">
                    <span class="badge bg-info text-dark mb-2">TIER 2: MITRA AGEN KECAMATAN</span>
                    <div class="row g-2">
                        <div class="col-7">
                            <label class="form-label small text-muted mb-1">Harga Kulakan Agen (Rp)</label>
                            <input type="number" name="price_agent" class="form-control form-control-sm" value="{{ old('price_agent') }}" step="100" min="0" required>
                        </div>
                        <div class="col-5">
                            <label class="form-label small text-muted mb-1">MOQ</label>
                            <input type="number" name="moq_agent" class="form-control form-control-sm" value="{{ old('moq_agent', 20) }}" min="1" required>
                        </div>
                    </div>
                </div>

                <div class="p-3 rounded border bg-light">
                    <span class="badge bg-secondary text-white mb-2">TIER 3: DISTRIBUTOR KOTA/KAB</span>
                    <div class="row g-2">
                        <div class="col-7">
                            <label class="form-label small text-muted mb-1">Harga Distributor (Rp)</label>
                            <input type="number" name="price_distributor" class="form-control form-control-sm" value="{{ old('price_distributor') }}" step="100" min="0" required>
                        </div>
                        <div class="col-5">
                            <label class="form-label small text-muted mb-1">MOQ</label>
                            <input type="number" name="moq_distributor" class="form-control form-control-sm" value="{{ old('moq_distributor', 100) }}" min="1" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('admin.products.index') }}" class="btn btn-light border px-4 fw-semibold">Batal</a>
                <button type="submit" class="btn btn-success flex-grow-1 fw-bold py-2">
                    <i class="bi bi-cloud-arrow-up-fill me-1"></i> Simpan Master Produk
                </button>
            </div>
        </div>
    </div>
</form>
@endsection
