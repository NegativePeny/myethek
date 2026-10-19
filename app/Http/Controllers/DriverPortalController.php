<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Services\EscrowService;
use Illuminate\Http\Request;

class DriverPortalController extends Controller
{
    /**
     * Dapatkan akun driver aktif (default ke driver demo Gedog).
     */
    protected function getActiveDriver(): User
    {
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
     * Halaman Utama Portal Kurir Motor Lokal
     */
    public function index()
    {
        $driver = $this->getActiveDriver();

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $driver->id],
            ['balance_available' => 0, 'balance_pending' => 0]
        );

        // 1. Pengantaran Siap Diambil di Sekitar Wilayah (Pending/Belum Ada Driver)
        $availableDeliveries = Delivery::with(['order.merchant.territory', 'order.customer'])
            ->where('delivery_type', 'ojol')
            ->whereNull('driver_id')
            ->whereIn('status', ['pending', 'dispatched'])
            ->latest()
            ->get();

        // 2. Pengantaran Aktif yang Sedang Dibawa Driver Ini
        $activeDeliveries = Delivery::with(['order.merchant.territory', 'order.customer'])
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['assigned', 'picked_up'])
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
     * Kurir Mengambil / Mengklaim Pesanan
     */
    public function takeJob(int $deliveryId)
    {
        $driver = $this->getActiveDriver();
        $delivery = Delivery::with('order')->findOrFail($deliveryId);

        if ($delivery->driver_id !== null && $delivery->driver_id !== $driver->id) {
            return redirect()->back()->with('error', 'Pesanan ini sudah diambil oleh kurir lain.');
        }

        $delivery->update([
            'driver_id' => $driver->id,
            'status' => 'picked_up',
            'assigned_at' => now(),
            'picked_up_at' => now(),
        ]);

        $delivery->order->update(['status' => 'on_delivery']);

        return redirect()->route('driver.index')->with('success', "Pesanan {$delivery->order->order_number} berhasil diambil. Silakan antar ke pelanggan!");
    }

    /**
     * Kurir Menyelesaikan Serah-Terima di Lokasi Pelanggan via OTP
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
            return redirect()->route('driver.index')->with('success', "Pesanan {$delivery->order->order_number} selesai diantar! Ongkir Rp " . number_format($delivery->fee, 0, ',', '.') . " telah masuk ke dompet Anda.");
        }

        return redirect()->back()->with('error', $result['message']);
    }
}
