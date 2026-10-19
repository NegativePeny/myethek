<div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold" id="locationModalLabel">
                    <i class="icofont-location-pin text-success me-1"></i> Tentukan Wilayah Belanja Anda
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-3">
                <p class="text-muted small mb-3">
                    Pilih desa/kelurahan tempat tinggal Anda. Pesanan Anda akan diprioritaskan dilayani oleh 
                    <strong>Sub-Agen (Warung Desa)</strong> terdekat. Jika belum ada toko aktif, sistem otomatis menghubungkan ke 
                    <strong>Agen Kecamatan</strong>.
                </p>

                <form action="{{ route('storefront.setLocation') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="villageSelect" class="form-label small fw-semibold text-secondary">
                            Pilih Kelurahan / Desa di Wilayah Pilot Blitar:
                        </label>
                        @php
                            $villagesList = $availableVillages ?? \App\Models\Territory::where('level', 'village')->with('parent')->orderBy('name')->get();
                        @endphp
                        <select name="village_id" id="villageSelect" class="form-select" required>
                            @foreach($villagesList as $village)
                                <option value="{{ $village->id }}" {{ ($currentTerritory?->id == $village->id) ? 'selected' : '' }}>
                                    {{ $village->name }} — Kec. {{ $village->parent?->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="p-2 mb-3 bg-light rounded border small">
                        <div class="fw-semibold text-dark mb-1"><i class="icofont-info-circle text-primary me-1"></i> Uji Coba Aturan Wilayah:</div>
                        <ul class="mb-0 ps-3 text-secondary" style="font-size: 0.78rem;">
                            <li><strong>Kelurahan Gedog:</strong> Dilayani langsung oleh <em>Warung Berkah Gedog (Sub-Agen Desa)</em>.</li>
                            <li><strong>Kelurahan Bendogerit:</strong> Belum ada warung desa &rarr; Otomatis dialihkan ke <em>Koperasi Sananwetan (Agen Kecamatan)</em>.</li>
                        </ul>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success fw-semibold py-2">
                            <i class="icofont-check-circled me-1"></i> Terapkan Lokasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
