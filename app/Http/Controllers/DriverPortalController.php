<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\User;
use App\Models\Wallet;
use App\Services\EscrowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DriverPortalController extends Controller
{
    /**
     * Dapatkan akun driver aktif (mengutamakan Auth user, lalu session switcher, lalu default driver demo).
     */
    protected function getActiveDriver(): User
    {
        if (Auth::check() && Auth::user()->role === 'driver') {
            session(['driver_portal_id' => Auth::id()]);
            return Auth::user()->load(['wallet', 'territory']);
        }

        $driverId = session('driver_portal_id');

        if ($driverId) {
            $driver = User::with(['wallet', 'territory'])->find($driverId);
            if ($driver && $driver->role === 'driver') {
                return $driver;
            }
        }

        $defaultDriver = User::with(['wallet', 'territory'])
            ->where('role', 'driver')
            ->first() ?? User::where('email', 'driver.gedog@myethek.id')->first();

        session(['driver_portal_id' => $defaultDriver->id]);
        return $defaultDriver;
    }

    /**
     * Halaman Utama Portal Mobile Driver KurirQ Mak Ethek (DRV-001 s/d DRV-010)
     */
    public function index()
    {
        $driver = $this->getActiveDriver();

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $driver->id],
            ['balance_available' => 0, 'balance_pending' => 0]
        );

        $isPilot = (bool) config('myethek.pilot_mode', false);

        // DRV-002 & DRV-010: Pada mode Pilot, driver hanya melihat tugas yang telah ditugaskan kepadanya (assigned).
        // Pada mode legacy/multi-tier, mengizinkan klaim order ojol yang masih pending.
        if ($isPilot) {
            $availableDeliveries = Delivery::with(['order.merchant.territory', 'order.customer', 'order.items'])
                ->where('driver_id', $driver->id)
                ->whereIn('status', ['assigned', 'accepted'])
                ->latest()
                ->get();
        } else {
            $availableDeliveries = Delivery::with(['order.merchant.territory', 'order.customer'])
                ->where(function ($q) use ($driver) {
                    $q->where(function ($sub) {
                        $sub->where('delivery_type', 'ojol')
                            ->whereNull('driver_id')
                            ->whereIn('status', ['pending', 'dispatched']);
                    })->orWhere(function ($sub2) use ($driver) {
                        $sub2->where('driver_id', $driver->id)
                            ->whereIn('status', ['assigned', 'accepted']);
                    });
                })
                ->latest()
                ->get();
        }

        // 2. Pengantaran Aktif yang Sedang Dibawa Driver Ini (picked_up / on_delivery)
        $activeDeliveries = Delivery::with(['order.merchant.territory', 'order.customer', 'order.items'])
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['picked_up', 'on_delivery'])
            ->latest()
            ->get();

        // 3. Riwayat Selesai
        $completedDeliveries = Delivery::with(['order.merchant', 'order.customer'])
            ->where('driver_id', $driver->id)
            ->where('status', 'delivered')
            ->latest()
            ->take(10)
            ->get();

        return view('driver.index', [
            'driver' => $driver,
            'wallet' => $wallet,
            'availableDeliveries' => $availableDeliveries,
            'activeDeliveries' => $activeDeliveries,
            'completedDeliveries' => $completedDeliveries,
        ]);
    }

    /**
     * Driver Menerima atau Menolak Tugas dengan Alasan (DRV-003)
     */
    public function respondAssignment(Request $request, int $deliveryId)
    {
        $driver = $this->getActiveDriver();
        $delivery = Delivery::with('order')->findOrFail($deliveryId);

        if ($delivery->driver_id !== null && $delivery->driver_id !== $driver->id) {
            abort(403, 'Tugas ini bukan milik Anda (DRV-010).');
        }

        $validated = $request->validate([
            'decision' => 'required|in:accept,reject',
            'rejection_reason' => 'required_if:decision,reject|nullable|string|max:255',
        ]);

        if ($validated['decision'] === 'reject') {
            $delivery->update([
                'driver_id' => null,
                'status' => 'unassigned',
                'rejection_reason' => $validated['rejection_reason'],
            ]);

            $delivery->order->update([
                'status' => 'ready_for_pickup',
            ]);

            return redirect()->route('driver.index')->with('success', "Tugas {$delivery->order->order_number} ditolak dengan alasan tercatat. Admin akan melakukan dispatch ulang.");
        }

        $delivery->update([
            'driver_id' => $driver->id,
            'status' => 'accepted',
            'accepted_at' => now(),
            'rejection_reason' => null,
        ]);

        return redirect()->route('driver.index')->with('success', "Tugas {$delivery->order->order_number} diterima. Silakan lakukan Pickup paket di Gudang Koperasi.");
    }

    /**
     * Kurir Mengonfirmasi Pickup dari Gudang & Mulai Pengiriman (DRV-004, DRV-006)
     */
    public function takeJob(int $deliveryId)
    {
        $driver = $this->getActiveDriver();
        $delivery = Delivery::with('order')->findOrFail($deliveryId);

        if ($delivery->driver_id !== null && $delivery->driver_id !== $driver->id) {
            return redirect()->back()->with('error', 'Pesanan ini sudah ditugaskan ke kurir lain.');
        }

        $merchantLat = $delivery->order?->merchant?->latitude ?? -8.098254;
        $merchantLon = $delivery->order?->merchant?->longitude ?? 112.168153;

        $delivery->update([
            'driver_id' => $driver->id,
            'status' => 'picked_up',
            'current_latitude' => $driver->latitude ?? $merchantLat,
            'current_longitude' => $driver->longitude ?? $merchantLon,
            'last_location_at' => now(),
            'assigned_at' => $delivery->assigned_at ?? now(),
            'accepted_at' => $delivery->accepted_at ?? now(),
            'picked_up_at' => now(),
        ]);

        $delivery->order->update([
            'status' => 'on_delivery',
        ]);

        return redirect()->route('driver.index')->with('success', "Paket {$delivery->order->order_number} berhasil di-pickup dari Gudang. Silakan antar ke alamat penerima!");
    }

    /**
     * Driver Memperbarui Titik Koordinat GPS Secara Real-Time (DRV-003, DRV-006)
     */
    public function updateLocation(Request $request)
    {
        $driver = $this->getActiveDriver();

        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'delivery_id' => 'nullable|exists:deliveries,id',
        ]);

        $lat = (float) $validated['latitude'];
        $lon = (float) $validated['longitude'];

        // Update titik lokasi pada akun driver
        $driver->update([
            'latitude' => $lat,
            'longitude' => $lon,
        ]);

        // Update titik pada pengantaran aktif yang sedang dipegang driver
        $deliveryQuery = Delivery::where('driver_id', $driver->id)
            ->whereIn('status', ['assigned', 'accepted', 'picked_up', 'on_delivery']);

        if (!empty($validated['delivery_id'])) {
            $deliveryQuery->where('id', $validated['delivery_id']);
        }

        $activeDelivery = $deliveryQuery->first();
        if ($activeDelivery) {
            $activeDelivery->update([
                'current_latitude' => $lat,
                'current_longitude' => $lon,
                'last_location_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Titik lokasi driver berhasil diperbarui.',
            'data' => [
                'latitude' => $lat,
                'longitude' => $lon,
                'delivery_id' => $activeDelivery?->id,
                'last_updated' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Kurir Menyelesaikan Serah-Terima di Lokasi Pelanggan via OTP Hash + Bukti Foto POD Opsional (DRV-007, DRV-008, BR-010)
     */
    public function completeJob(Request $request, int $deliveryId, EscrowService $escrowService)
    {
        $driver = $this->getActiveDriver();
        $delivery = Delivery::with(['order.escrow', 'order.merchant.user'])->findOrFail($deliveryId);

        if ($delivery->driver_id !== $driver->id) {
            return redirect()->back()->with('error', 'Anda tidak ditugaskan pada pesanan ini.');
        }

        $otp = $request->input('otp');
        $result = $escrowService->releaseEscrowForOrder($delivery->order, $otp);

        if ($result['success']) {
            $podPath = $delivery->pod_image_path;
            if ($request->hasFile('pod_image')) {
                $file = $request->file('pod_image');
                $filename = 'pod_' . $delivery->order->order_number . '_' . time() . '_' . Str::random(4) . '.' . $file->getClientOriginalExtension();
                $file->storeAs('private/pod', $filename);
                $podPath = 'private/pod/' . $filename;
            }

            if ($podPath || $request->filled('receiver_name')) {
                $delivery->update([
                    'pod_image_path' => $podPath,
                    'receiver_name' => $request->input('receiver_name', $delivery->receiver_name),
                ]);
            }

            return redirect()->route('driver.index')->with('success', "Pesanan {$delivery->order->order_number} selesai diantar! Ongkir Rp " . number_format($delivery->fee, 0, ',', '.') . " telah tercatat.");
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Perbarui / Regenerate OTP Pengiriman jika kedaluwarsa (DRV-007, BR-010)
     */
    public function renewOtp(int $deliveryId)
    {
        $driver = $this->getActiveDriver();
        $delivery = Delivery::findOrFail($deliveryId);

        if ($delivery->driver_id !== $driver->id) {
            return redirect()->back()->with('error', 'Anda tidak ditugaskan pada pesanan ini.');
        }

        $randomOtp = str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        $otpExpiryMinutes = (int) config('myethek.otp.expiry_minutes', 180);

        $delivery->update([
            'completion_otp' => $randomOtp,
            'otp_hash' => Hash::make($randomOtp),
            'otp_expires_at' => now()->addMinutes($otpExpiryMinutes),
            'otp_attempts' => 0,
        ]);

        return redirect()->back()->with('success', "Kode OTP berhasil diperbarui: {$randomOtp} (aktif {$otpExpiryMinutes} menit ke depan).");
    }
}
