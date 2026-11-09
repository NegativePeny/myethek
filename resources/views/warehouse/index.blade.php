@extends('layouts.backoffice')

@section('title', 'Portal Admin Gudang (WMS) - Mak Ethek Kota Blitar')
@section('page_heading', 'Warehouse Management System (WMS) — Gudang Koperasi Kota Blitar')
@section('page_subheading', 'Picking List per SKU • Verifikasi Exception • Packing • Handover Driver KurirQ • Stock Movement Ledger')

@section('content')
<!-- KPI Cards Gudang (Web Desktop) -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="kpi-card border-start border-4 border-primary">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-semibold">ANTREAN PICKING</div>
                    <h3 class="fw-bold mb-0 mt-1 text-primary">{{ $stats['waiting_picking'] }} <small class="fs-6 fw-normal text-muted">Order</small></h3>
                    <small class="text-muted" style="font-size: 0.75rem;">Order Paid siap diambil di rak</small>
                </div>
                <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                    <i class="bi bi-clipboard2-check fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="kpi-card border-start border-4 border-success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-semibold">PACKED (SIAP PICKUP)</div>
                    <h3 class="fw-bold mb-0 mt-1 text-success">{{ $stats['packed_ready'] }} <small class="fs-6 fw-normal text-muted">Paket</small></h3>
                    <small class="text-muted" style="font-size: 0.75rem;">Menunggu serah-terima KurirQ</small>
                </div>
                <div class="bg-success bg-opacity-10 text-success rounded-3 p-3">
                    <i class="bi bi-box-seam fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="kpi-card border-start border-4 border-info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-semibold">HANDED OVER KE DRIVER</div>
                    <h3 class="fw-bold mb-0 mt-1 text-info">{{ $stats['handed_over'] }} <small class="fs-6 fw-normal text-muted">Order</small></h3>
                    <small class="text-muted" style="font-size: 0.75rem;">Tercatat dengan kode handover</small>
                </div>
                <div class="bg-info bg-opacity-10 text-info rounded-3 p-3">
                    <i class="bi bi-truck fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="kpi-card border-start border-4 {{ $stats['low_stock_sku'] > 0 ? 'border-danger' : 'border-secondary' }}">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-semibold">PERINGATAN STOK MENIPIS</div>
                    <h3 class="fw-bold mb-0 mt-1 {{ $stats['low_stock_sku'] > 0 ? 'text-danger' : 'text-dark' }}">
                        {{ $stats['low_stock_sku'] }} <small class="fs-6 fw-normal text-muted">/ {{ $stats['total_sku'] }} SKU</small>
                    </h3>
                    <small class="text-muted" style="font-size: 0.75rem;">Di bawah reorder level (WH-009)</small>
                </div>
                <div class="bg-danger bg-opacity-10 text-danger rounded-3 p-3">
                    <i class="bi bi-exclamation-octagon fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Navigasi Tab Desktop Gudang -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <ul class="nav nav-pills gap-2">
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'picking' ? 'active bg-dark' : 'bg-white text-dark border' }} fw-semibold"
               href="{{ route('warehouse.index', ['tab' => 'picking']) }}">
                <i class="bi bi-list-check me-1"></i> 1. Picking List, Packing & Handover Driver ({{ $pickingOrders->count() }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'inventory' ? 'active bg-dark' : 'bg-white text-dark border' }} fw-semibold"
               href="{{ route('warehouse.index', ['tab' => 'inventory']) }}">
                <i class="bi bi-boxes me-1"></i> 2. Stok Gudang (On-Hand vs Reserved) & Stock Movements
            </a>
        </li>
    </ul>

    <div class="text-muted small">
        <i class="bi bi-geo-alt-fill text-success me-1"></i>Lokasi Gudang: <strong>{{ $merchant->store_name }}</strong> ({{ $merchant->address }})
    </div>
</div>

@if($activeTab === 'picking')
    <!-- TAB 1: PICKING LIST, PACKING & HANDOVER -->
    <div class="table-card mb-4">
        <div class="p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold mb-0"><i class="bi bi-box2-fill text-primary me-2"></i>Antrean Fulfillment Gudang (WH-001 s/d WH-006)</h6>
                <small class="text-muted">Hanya menampilkan order yang telah berstatus <strong>PAID</strong> / Terotorisasi sesuai aturan bisnis <code>BR-007</code>.</small>
            </div>
            <span class="badge bg-light text-dark border">Total Antrean: {{ $pickingOrders->count() }} Order</span>
        </div>

        @if($pickingOrders->isEmpty())
            <div class="p-5 text-center text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                Belum ada order berstatus Paid yang menunggu proses Picking/Packing saat ini.
            </div>
        @else
            <div class="p-3 d-flex flex-column gap-4">
                @foreach($pickingOrders as $order)
                    <div class="border rounded-3 p-3 bg-white shadow-sm">
                        <!-- Header Order Gudang -->
                        <div class="d-flex flex-wrap justify-content-between align-items-center pb-3 mb-3 border-bottom gap-2">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="fw-bold font-monospace fs-6 text-dark">{{ $order->order_number }}</span>
                                <span class="badge {{ $order->customer_channel === 'b2b' ? 'bg-primary' : 'bg-success' }}">
                                    {{ strtoupper($order->customer_channel ?? 'B2C') }}
                                </span>
                                <span class="badge bg-dark">Status Order: {{ strtoupper($order->status) }}</span>
                                <span class="badge bg-info text-dark">Packing: {{ strtoupper($order->packing_status) }}</span>
                                @if($order->handover_code)
                                    <span class="badge bg-warning text-dark font-monospace">Handover: {{ $order->handover_code }}</span>
                                @endif
                            </div>

                            <div class="text-end small">
                                <div><strong>Penerima:</strong> {{ $order->recipient_name ?? $order->customer->name }} ({{ $order->recipient_phone ?? $order->customer->phone }})</div>
                                <div class="text-muted">{{ $order->shipping_address }}</div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <!-- Tabel Picking Item per SKU (WH-002, WH-003) -->
                            <div class="col-lg-8">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 0.84rem;">
                                        <thead class="table-light">
                                            <tr>
                                                <th>SKU / Nama Barang</th>
                                                <th>Satuan</th>
                                                <th class="text-center">Qty Order</th>
                                                <th class="text-center">Status Picking</th>
                                                <th>Konfirmasi Qty Picked & Catatan Exception (WH-003)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($order->items as $item)
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold text-dark">{{ $item->product_name }}</div>
                                                        <small class="text-muted font-monospace">{{ $item->product->sku ?? '-' }}</small>
                                                    </td>
                                                    <td>{{ $item->unit_name }}</td>
                                                    <td class="text-center fw-bold">{{ $item->quantity }}</td>
                                                    <td class="text-center">
                                                        @if($item->picking_status === 'picked')
                                                            <span class="badge bg-success"><i class="bi bi-check-lg"></i> PICKED ({{ $item->picked_quantity }})</span>
                                                        @elseif($item->picking_status === 'exception')
                                                            <span class="badge bg-danger"><i class="bi bi-exclamation-triangle"></i> EXCEPTION</span>
                                                        @else
                                                            <span class="badge bg-secondary">PENDING</span>
                                                        @endif
                                                        @if($item->exception_note)
                                                            <div class="text-danger small mt-1" style="font-size: 0.72rem;">{{ $item->exception_note }}</div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if(!in_array($order->packing_status, ['packed', 'handed_over']))
                                                            <form action="{{ route('warehouse.pickItem', $item->id) }}" method="POST" class="d-flex gap-1 align-items-center">
                                                                @csrf
                                                                <input type="number" name="picked_quantity" class="form-control form-control-sm text-center"
                                                                       style="width: 72px;" min="0" max="{{ $item->quantity }}"
                                                                       value="{{ $item->picked_quantity ?? $item->quantity }}" required>
                                                                <input type="text" name="exception_note" class="form-control form-control-sm"
                                                                       placeholder="Catatan substitusi/selisih (jika kurang)"
                                                                       value="{{ $item->exception_note }}">
                                                                <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap">
                                                                    <i class="bi bi-check2-square"></i> Pick
                                                                </button>
                                                            </form>
                                                        @else
                                                            <span class="text-muted small">Terkunci (Sudah Dikemas: {{ $item->picked_quantity ?? $item->quantity }} {{ $item->unit_name }})</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Panel Aksi Packing & Handover Driver (WH-004, WH-005, WH-006) -->
                            <div class="col-lg-4">
                                <div class="p-3 bg-light rounded-3 border h-100 d-flex flex-column justify-content-between">
                                    <div>
                                        <h6 class="fw-bold small text-uppercase text-secondary mb-2">Tahap Packing & Handover</h6>

                                        @if($order->picked_at)
                                            <div class="small text-muted mb-1">
                                                <i class="bi bi-person-check text-primary me-1"></i>Picked: {{ $order->picker->name ?? 'Petugas' }} ({{ $order->picked_at->format('H:i') }})
                                            </div>
                                        @endif
                                        @if($order->packed_at)
                                            <div class="small text-muted mb-2">
                                                <i class="bi bi-box-seam text-success me-1"></i>Packed: {{ $order->packer->name ?? 'Petugas' }} ({{ $order->packed_at->format('H:i') }} • {{ $order->package_count }} Kolli)
                                            </div>
                                        @endif

                                        <!-- Langkah A: Selesaikan Packing -->
                                        @if(!in_array($order->packing_status, ['packed', 'handed_over']))
                                            <form action="{{ route('warehouse.packOrder', $order->id) }}" method="POST" class="mb-3">
                                                @csrf
                                                <label class="form-label small fw-semibold mb-1">Jumlah Paket / Kolli (WH-004):</label>
                                                <div class="input-group input-group-sm mb-2">
                                                    <input type="number" name="package_count" class="form-control" value="1" min="1" required>
                                                    <span class="input-group-text">Paket</span>
                                                </div>
                                                <button type="submit" class="btn btn-sm btn-success w-100 fw-semibold">
                                                    <i class="bi bi-box-seam-fill me-1"></i> Tandai Packing Selesai & Commit Stok
                                                </button>
                                            </form>
                                        @endif

                                        <!-- Langkah B: Serah-Terima ke Driver KurirQ -->
                                        @if(in_array($order->packing_status, ['packed', 'handed_over']))
                                            <form action="{{ route('warehouse.handover', $order->id) }}" method="POST" class="border-top pt-2">
                                                @csrf
                                                <label class="form-label small fw-semibold mb-1">Serah-Terima ke Driver KurirQ (WH-006):</label>
                                                <select name="driver_id" class="form-select form-select-sm mb-2" required>
                                                    <option value="">-- Pilih Driver KurirQ --</option>
                                                    @foreach($drivers as $drv)
                                                        <option value="{{ $drv->id }}" {{ $order->delivery?->driver_id === $drv->id ? 'selected' : '' }}>
                                                            {{ $drv->name }} ({{ $drv->phone }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">
                                                    <i class="bi bi-truck me-1"></i> Dispatch & Handover ke Driver
                                                </button>
                                            </form>
                                        @endif
                                    </div>

                                    @if($order->delivery && $order->delivery->driver)
                                        <div class="mt-2 pt-2 border-top small">
                                            <span class="text-muted">Driver Ditugaskan:</span>
                                            <strong class="text-dark d-block">{{ $order->delivery->driver->name }} ({{ strtoupper($order->delivery->status) }})</strong>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@else
    <!-- TAB 2: INVENTORI GUDANG & STOCK MOVEMENTS (WH-007, WH-008, WH-009, ADM-005) -->
    <div class="table-card mb-4">
        <div class="p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold mb-0"><i class="bi bi-boxes text-success me-2"></i>Saldo Stok Gudang Koperasi Kota Blitar (WH-007)</h6>
                <small class="text-muted">Rumus Konsisten: <code>Stok Available = Stok On-Hand - Stok Reserved</code>. Perubahan stok wajib menyertakan alasan untuk Audit Log.</small>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.86rem;">
                <thead class="table-light">
                    <tr>
                        <th>Lokasi Rak</th>
                        <th>SKU & Nama Produk</th>
                        <th>Satuan</th>
                        <th class="text-center">On-Hand</th>
                        <th class="text-center">Reserved</th>
                        <th class="text-center">Available (Siap Jual)</th>
                        <th class="text-center">Reorder Level</th>
                        <th>Penyesuaian Stok / Barang Masuk (Wajib Alasan Audit)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inventories as $inv)
                        @php
                            $isLow = $inv->available_stock <= $inv->reorder_level;
                        @endphp
                        <tr class="{{ $isLow ? 'table-warning' : '' }}">
                            <td><span class="badge bg-dark font-monospace">{{ $inv->rack_location ?? 'RAK-A1' }}</span></td>
                            <td>
                                <div class="fw-bold text-dark">{{ $inv->product->name }}</div>
                                <small class="text-muted font-monospace">{{ $inv->product->sku }}</small>
                            </td>
                            <td>{{ $inv->product->unit_name }}</td>
                            <td class="text-center fw-semibold">{{ $inv->stock }}</td>
                            <td class="text-center text-warning fw-semibold">{{ $inv->stock_reserved }}</td>
                            <td class="text-center">
                                <span class="badge {{ $isLow ? 'bg-danger' : 'bg-success' }} fs-6">
                                    {{ $inv->available_stock }}
                                </span>
                                @if($isLow)
                                    <div class="text-danger fw-bold" style="font-size: 0.68rem;">LOW STOCK!</div>
                                @endif
                            </td>
                            <td class="text-center">{{ $inv->reorder_level }}</td>
                            <td>
                                <form action="{{ route('warehouse.adjustInventory', $inv->id) }}" method="POST" class="d-flex gap-1 align-items-center">
                                    @csrf
                                    <select name="movement_type" class="form-select form-select-sm" style="width: 115px;">
                                        <option value="adjustment">Opname</option>
                                        <option value="in">Barang Masuk</option>
                                        <option value="return">Retur</option>
                                    </select>
                                    <input type="number" name="stock" class="form-control form-control-sm text-center"
                                           style="width: 80px;" value="{{ $inv->stock }}" min="0" required>
                                    <input type="text" name="reason" class="form-control form-control-sm"
                                           placeholder="Alasan wajib (cth: Stok opname pagi / DO masuk)" required>
                                    <button type="submit" class="btn btn-sm btn-dark text-nowrap">
                                        <i class="bi bi-save"></i> Update
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Riwayat Pergerakan Stok (Stock Movement Ledger - WH-008) -->
    <div class="table-card">
        <div class="p-3 bg-white border-bottom">
            <h6 class="fw-bold mb-0"><i class="bi bi-clock-history text-primary me-2"></i>buku Besar Pergerakan Stok (Stock Movement Ledger — WH-008)</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0" style="font-size: 0.82rem;">
                <thead class="table-light">
                    <tr>
                        <th>Waktu</th>
                        <th>Produk</th>
                        <th>Tipe Movement</th>
                        <th class="text-center">Qty</th>
                        <th class="text-center">Sebelum</th>
                        <th class="text-center">Sesudah</th>
                        <th>Referensi</th>
                        <th>Petugas / Aktor</th>
                        <th>Alasan / Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stockMovements as $mov)
                        <tr>
                            <td class="text-muted">{{ $mov->created_at?->format('d M Y H:i:s') }}</td>
                            <td class="fw-semibold">{{ $mov->product->name ?? '-' }}</td>
                            <td>
                                <span class="badge bg-secondary text-uppercase">{{ $mov->movement_type }}</span>
                            </td>
                            <td class="text-center fw-bold {{ $mov->quantity >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $mov->quantity >= 0 ? '+' . $mov->quantity : $mov->quantity }}
                            </td>
                            <td class="text-center">{{ $mov->stock_before }}</td>
                            <td class="text-center fw-bold">{{ $mov->stock_after }}</td>
                            <td class="font-monospace">{{ $mov->reference_id }}</td>
                            <td>{{ $mov->actor->name ?? 'Sistem' }}</td>
                            <td>{{ $mov->reason }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">Belum ada riwayat pergerakan stok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection

