@extends('layouts.storefront')

@section('title', 'Mak Ethek - Belanja Kebutuhan Harian Jaringan Koperasi')

@section('header_extra')
    <!-- Search Bar Form -->
    <form action="{{ route('storefront.index') }}" method="GET" class="mt-2">
        @if($selectedCategorySlug)
            <input type="hidden" name="category" value="{{ $selectedCategorySlug }}">
        @endif
        <div class="input-group rounded shadow-sm overflow-hidden bg-white border">
            <span class="input-group-text bg-white border-0 text-success pe-1">
                <i class="icofont-search"></i>
            </span>
            <input type="text" name="q" value="{{ $searchQuery ?? '' }}" 
                   class="shadow-none border-0 form-control ps-1" 
                   placeholder="Cari beras, minyak, bumbu, telur...">
            @if($searchQuery)
                <a href="{{ route('storefront.index', ['category' => $selectedCategorySlug]) }}" class="btn btn-white border-0 text-muted">
                    <i class="icofont-close-line"></i>
                </a>
            @endif
            <button class="btn btn-success border-0 px-3 fw-semibold" type="submit" style="font-size: 0.8rem;">
                Cari
            </button>
        </div>
    </form>
@endsection

@section('content')
    <div class="osahan-body">
        <!-- Promo Banner Slider (Grofar Pattern) -->
        <div class="p-3 pb-0">
            <div class="promo-slider">
                <div class="osahan-slider-item mx-1">
                    <div class="rounded-3 overflow-hidden shadow-sm position-relative text-white p-3" 
                         style="background: linear-gradient(135deg, #10b981, #047857); min-height: 120px;">
                        <span class="badge bg-white text-success fw-bold px-2 py-1 mb-1" style="font-size: 0.65rem;">
                            JARINGAN KOPERASI JATIM
                        </span>
                        <h6 class="fw-bold mb-1">Belanja Dekat, Bantu Warung Tetangga</h6>
                        <p class="small mb-0 text-white-50" style="font-size: 0.72rem;">
                            Kebutuhan pokok terdistribusi langsung dari lumbung & mitra desa.
                        </p>
                    </div>
                </div>
                <div class="osahan-slider-item mx-1">
                    <div class="rounded-3 overflow-hidden shadow-sm position-relative text-white p-3" 
                         style="background: linear-gradient(135deg, #0284c7, #0369a1); min-height: 120px;">
                        <span class="badge bg-white text-primary fw-bold px-2 py-1 mb-1" style="font-size: 0.65rem;">
                            KULAKAN BERJENJANG
                        </span>
                        <h6 class="fw-bold mb-1">Harga Transparan & Terjamin</h6>
                        <p class="small mb-0 text-white-50" style="font-size: 0.72rem;">
                            Didukung sistem cascading stok dari holding koperasi sampai ke warung Anda.
                        </p>
                    </div>
                </div>
                <div class="osahan-slider-item mx-1">
                    <div class="rounded-3 overflow-hidden shadow-sm position-relative text-white p-3" 
                         style="background: linear-gradient(135deg, #d97706, #b45309); min-height: 120px;">
                        <span class="badge bg-white text-warning fw-bold px-2 py-1 mb-1" style="font-size: 0.65rem;">
                            LOGISTIK LOKAL
                        </span>
                        <h6 class="fw-bold mb-1">Antar Cepat Driver Ojol Desa</h6>
                        <p class="small mb-0 text-white-50" style="font-size: 0.72rem;">
                            Pemberdayaan warga sekitar dengan sistem konfirmasi OTP aman.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Banner Fallback Toko (Bila Wilayah Dialihkan) -->
        @if(isset($resolution['fallback_applied']) && $resolution['fallback_applied'])
            <div class="fallback-banner d-flex align-items-start gap-2 mx-3 mt-3">
                <i class="icofont-info-circle fs-5 mt-1 text-primary"></i>
                <div>
                    <div class="fw-bold">Pengalihan Wilayah Aktif (Cascading Fallback)</div>
                    <div style="font-size: 0.78rem;" class="mt-1">
                        {{ $resolution['reason'] }}
                    </div>
                </div>
            </div>
        @endif

        <!-- Informasi Mitra Warung Pelayan Pesanan -->
        @if($merchant)
            <div class="mx-3 mt-3 p-3 rounded-3 border bg-white shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-success text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <i class="icofont-shop fs-5"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold text-dark text-truncate" style="max-width: 210px;">
                                {{ $merchant->store_name }}
                            </h6>
                            <small class="text-muted d-block" style="font-size: 0.72rem;">
                                <i class="icofont-location-pin text-danger"></i> {{ $merchant->address }}
                            </small>
                        </div>
                    </div>

                    @if($merchant->is_open)
                        <span class="badge bg-success-subtle text-success border border-success px-2 py-1 small">
                            <i class="icofont-check-circled me-1"></i> Buka
                        </span>
                    @else
                        <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1 small">
                            <i class="icofont-close-circled me-1"></i> Tutup
                        </span>
                    @endif
                </div>

                <div class="d-flex justify-content-between pt-2 border-top text-secondary" style="font-size: 0.74rem;">
                    <div>
                        <span class="text-muted">Min. Order:</span> 
                        <strong>Rp {{ number_format($merchant->min_order_amount, 0, ',', '.') }}</strong>
                    </div>
                    <div>
                        <span class="text-muted">Radius:</span> 
                        <strong>{{ $merchant->delivery_radius_km }} km</strong>
                    </div>
                    <div>
                        <span class="text-muted">Tipe:</span> 
                        @if(isset($resolution['served_by_tier']))
                            <span class="badge {{ $resolution['served_by_tier'] === 'sub_agent' ? 'badge-sub-agent' : 'badge-agent' }} px-1">
                                {{ strtoupper(str_replace('_', ' ', $resolution['served_by_tier'])) }}
                            </span>
                        @else
                            <strong>Mitra Desa</strong>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-warning mx-3 mt-3 small">
                <i class="icofont-warning-alt me-1"></i> Belum ada toko mitra yang melayani desa ini. Silakan ganti wilayah pada header atas.
            </div>
        @endif

        <!-- Kategori Produk (Grofar Category Grid) -->
        <div class="p-3 osahan-categories" id="categories">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="m-0 fw-bold">Kategori Sembako</h6>
                @if($selectedCategorySlug)
                    <a href="{{ route('storefront.index') }}" class="small text-success text-decoration-none fw-semibold">
                        Reset Filter <i class="icofont-close-circled"></i>
                    </a>
                @endif
            </div>

            <div class="row g-2">
                @php
                    $categoryIcons = [
                        'sembako-beras' => '6.svg',
                        'sayuran-bumbu' => '1.svg',
                        'minyak-gula' => '7.svg',
                        'daging-telur' => '5.svg',
                    ];
                @endphp

                @foreach($categories as $cat)
                    @php
                        $iconFile = $categoryIcons[$cat->slug] ?? '2.svg';
                        $isActive = ($selectedCategorySlug === $cat->slug);
                    @endphp
                    <div class="col-3">
                        <a href="{{ route('storefront.index', ['category' => $cat->slug, 'q' => $searchQuery]) }}" 
                           class="text-decoration-none">
                            <div class="bg-white shadow-sm rounded text-center p-2 {{ $isActive ? 'border border-2 border-success bg-light' : 'border' }}" style="min-height: 90px;">
                                <img src="/grofar/img/categorie/{{ $iconFile }}" class="img-fluid px-1" style="height: 38px;" alt="{{ $cat->name }}">
                                <p class="m-0 pt-2 text-dark text-truncate fw-semibold" style="font-size: 0.7rem;">
                                    {{ $cat->name }}
                                </p>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Daftar Katalog Produk (Picks Today / Product Grid) -->
        <div class="px-3 pb-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="m-0 fw-bold">
                    @if($searchQuery)
                        Hasil Pencarian "{{ $searchQuery }}" ({{ $products->count() }})
                    @elseif($selectedCategorySlug)
                        Kategori: {{ $categories->firstWhere('slug', $selectedCategorySlug)?->name ?? 'Produk' }}
                    @else
                        Katalog Produk Pilihan
                    @endif
                </h6>
                <span class="small text-muted">{{ $products->count() }} barang</span>
            </div>

            @if($products->isEmpty())
                <div class="text-center py-5 bg-white rounded border my-2">
                    <i class="icofont-search-document text-muted display-4"></i>
                    <p class="text-muted mt-2 mb-2">Tidak ada produk yang cocok dengan pencarian Anda.</p>
                    <a href="{{ route('storefront.index') }}" class="btn btn-outline-success btn-sm rounded-pill px-3">
                        Lihat Semua Produk
                    </a>
                </div>
            @else
                <div class="row g-2">
                    @foreach($products as $product)
                        @php
                            $inv = $inventories[$product->id] ?? null;
                            $stock = $inv ? $inv->stock_quantity : 0;
                            $price = ($inv && $inv->retail_price > 0) ? $inv->retail_price : $product->base_price;
                            $imgIndex = ($product->id % 12) + 1;
                            $displayImg = $product->image ? asset($product->image) : "/grofar/img/listing/v{$imgIndex}.jpg";
                        @endphp

                        <div class="col-6">
                            <div class="list-card bg-white h-100 rounded overflow-hidden position-relative shadow-sm border d-flex flex-column">
                                <div class="list-card-image position-relative">
                                    <!-- Unit Badge -->
                                    <span class="badge bg-dark bg-opacity-75 text-white position-absolute top-0 start-0 m-2" style="font-size: 0.65rem;">
                                        {{ $product->unit_name }}
                                    </span>

                                    <!-- Stock Badge -->
                                    @if($stock > 0)
                                        <span class="badge bg-success-subtle text-success border border-success position-absolute top-0 end-0 m-2" style="font-size: 0.65rem;">
                                            Stok: {{ $stock }}
                                        </span>
                                    @else
                                        <span class="badge bg-danger text-white position-absolute top-0 end-0 m-2" style="font-size: 0.65rem;">
                                            Habis
                                        </span>
                                    @endif

                                    <a href="{{ route('storefront.product', $product->slug) }}">
                                        <div class="text-center p-3 bg-white" style="min-height: 125px; display: flex; align-items: center; justify-content: center;">
                                            <img src="{{ $displayImg }}" class="img-fluid rounded" style="max-height: 95px; object-fit: contain;" alt="{{ $product->name }}">
                                        </div>
                                    </a>
                                </div>

                                <div class="p-2 d-flex flex-column flex-grow-1">
                                    <a href="{{ route('storefront.product', $product->slug) }}" class="text-decoration-none text-dark">
                                        <h6 class="mb-1 text-truncate fw-semibold" style="font-size: 0.82rem;" title="{{ $product->name }}">
                                            {{ $product->name }}
                                        </h6>
                                    </a>
                                    
                                    <small class="text-muted mb-2 d-block text-truncate" style="font-size: 0.68rem;">
                                        {{ $product->category->name }}
                                    </small>

                                    <div class="mt-auto d-flex align-items-center justify-content-between pt-1">
                                        <div>
                                            <span class="fw-bold text-success" style="font-size: 0.88rem;">
                                                Rp {{ number_format($price, 0, ',', '.') }}
                                            </span>
                                        </div>

                                        @if($stock > 0)
                                            <form action="{{ route('storefront.cart.add') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                <input type="hidden" name="quantity" value="1">
                                                <button type="submit" class="btn btn-success btn-sm rounded shadow-sm px-2 py-1 d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                                    <i class="icofont-plus"></i> Tambah
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" class="btn btn-light btn-sm rounded text-muted px-2 py-1" style="font-size: 0.72rem;" disabled>
                                                Kosong
                                            </button>
                                        @endif
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
