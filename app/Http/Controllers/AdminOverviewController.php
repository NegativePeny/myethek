<?php

namespace App\Http\Controllers;

use App\Models\EscrowTransaction;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Territory;
use App\Models\User;
use App\Models\WalletMutation;
use Illuminate\Http\Request;

class AdminOverviewController extends Controller
{
    /**
     * Dashboard Audit & Rekapitulasi Holding Koperasi Jawa Timur
     */
    public function index()
    {
        // 1. Metrik Finansial
        $retailGmv = (float) Order::where('order_type', 'retail')->where('status', '!=', 'cancelled')->sum('total_amount');
        $procurementGmv = (float) Order::where('order_type', 'procurement')->where('status', '!=', 'cancelled')->sum('total_amount');

        $escrowHeld = (float) EscrowTransaction::where('status', 'held')->sum('gross_amount');
        $escrowReleased = (float) EscrowTransaction::where('status', 'released')->sum('gross_amount');
        $platformFeeTotal = (float) EscrowTransaction::where('status', 'released')->sum('platform_portion');

        // 2. Metrik Jaringan Wilayah & Mitra
        $counts = [
            'total_villages' => Territory::where('level', 'village')->count(),
            'total_districts' => Territory::where('level', 'district')->count(),
            'total_regencies' => Territory::where('level', 'regency')->count(),
            'sub_agents' => Merchant::where('tier', 'sub_agent')->count(),
            'agents' => Merchant::where('tier', 'agent')->count(),
            'distributors' => Merchant::where('tier', 'distributor')->count(),
            'drivers' => User::where('role', 'driver')->count(),
            'customers' => User::where('role', 'customer')->count(),
        ];

        // 3. Mutasi Dompet Terakhir (Audit Trail)
        $latestMutations = WalletMutation::with('wallet.user')
            ->latest()
            ->take(15)
            ->get();

        // 4. Daftar Pesanan Terakhir
        $recentOrders = Order::with(['merchant.territory', 'customer'])
            ->latest()
            ->take(10)
            ->get();

        return view('admin.index', [
            'retailGmv' => $retailGmv,
            'procurementGmv' => $procurementGmv,
            'escrowHeld' => $escrowHeld,
            'escrowReleased' => $escrowReleased,
            'platformFeeTotal' => $platformFeeTotal,
            'counts' => $counts,
            'latestMutations' => $latestMutations,
            'recentOrders' => $recentOrders,
        ]);
    }
}
