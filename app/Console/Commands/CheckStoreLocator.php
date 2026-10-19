<?php

namespace App\Console\Commands;

use App\Models\Territory;
use App\Services\StoreLocatorService;
use Illuminate\Console\Command;

class CheckStoreLocator extends Command
{
    protected $signature = 'myethek:check-store {village? : Nama atau kode kelurahan/desa}';
    protected $description = 'Simulasi resolusi toko customer dengan cascading fallback (Desa -> Kecamatan -> Kabupaten)';

    public function handle(StoreLocatorService $locatorService): int
    {
        $input = $this->argument('village') ?? 'Kelurahan Gedog';

        $territory = Territory::where('level', 'village')
            ->where(function ($q) use ($input) {
                $q->where('name', 'like', "%{$input}%")
                  ->orWhere('code', $input);
            })
            ->first();

        if (!$territory) {
            $this->error("Wilayah desa/kelurahan '{$input}' tidak ditemukan.");
            return Command::FAILURE;
        }

        $this->info("=== SIMULASI CASCADING FALLBACK TOKO MAK ETHEK ===");
        $this->line("Customer Berada di  : <comment>{$territory->name}</comment> (Kode: {$territory->code})");
        $this->line("Hirarki Wilayah     : <info>{$territory->full_name}</info>");
        $this->newLine();

        $result = $locatorService->resolveMerchantForCustomer($territory);

        if (!$result['merchant']) {
            $this->warn("Hasil: Tidak ada toko yang melayani.");
            $this->line("Alasan: " . $result['reason']);
            return Command::SUCCESS;
        }

        $merchant = $result['merchant'];
        $this->table(
            ['Parameter', 'Detail Toko Terpilih'],
            [
                ['Nama Toko', $merchant->store_name],
                ['Tingkat / Tier', strtoupper($merchant->tier) . ($result['fallback_applied'] ? ' (FALLBACK AKTIF)' : ' (PRIMARY)')],
                ['Wilayah Toko', $merchant->territory->name . ' (' . $merchant->territory->level . ')'],
                ['Status Toko', $merchant->is_open ? 'BUKA / ONLINE' : 'TUTUP'],
                ['Min. Belanja', 'Rp ' . number_format($merchant->min_order_amount, 0, ',', '.')],
                ['Radius Kirim', $merchant->delivery_radius_km . ' km'],
                ['Keterangan Engine', $result['reason']],
            ]
        );

        return Command::SUCCESS;
    }
}
