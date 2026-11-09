<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuditService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceController extends Controller
{
    protected AuditService $auditService;
    protected InventoryService $inventoryService;

    public function __construct(AuditService $auditService, InventoryService $inventoryService)
    {
        $this->auditService = $auditService;
        $this->inventoryService = $inventoryService;
    }

    /**
     * Dashboard Web Desktop Super Admin & Finance (PRD Bab 5.9: SA-001 s/d SA-007 & Bab 12)
     */
    public function index(Request $request)
    {
        $channelFilter = $request->query('channel', 'all'); // all, b2c, b2b
        $dateFrom = $request->query('from');
        $dateTo = $request->query('to');

        $ordersQuery = Order::with(['items.product', 'customer', 'payment', 'delivery'])
            ->where('order_type', 'retail');

        if ($channelFilter !== 'all') {
            $ordersQuery->where('customer_channel', $channelFilter);
        }
        if ($dateFrom) {
            $ordersQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $ordersQuery->whereDate('created_at', '<=', $dateTo);
        }

        $allOrders = $ordersQuery->latest()->get();

        // Pisahkan order aktif/berhasil dari yang dibatalkan/refunded (PRD Bab 12)
        $validOrders = $allOrders->whereNotIn('status', ['cancelled', 'refunded']);

        // 1. GMV & Revenue Penjualan
        $gmvTotal = (float) $validOrders->sum('total_amount');
        $revenueSales = (float) $validOrders->sum('subtotal') - (float) $validOrders->sum('discount_amount');
        $shippingCollected = (float) $validOrders->sum('shipping_fee');

        // 2. HPP (Cost of Goods Sold) & Gross Margin (SA-002, PRD Bab 12)
        $totalHpp = 0.0;
        foreach ($validOrders as $ord) {
            foreach ($ord->items as $item) {
                $unitCost = (float) ($item->cost_price > 0 ? $item->cost_price : ($item->product->cost_price ?? ($item->price * 0.84)));
                $totalHpp += $unitCost * $item->quantity;
            }
        }

        $grossMargin = $revenueSales - $totalHpp;
        $grossMarginPct = $revenueSales > 0 ? round(($grossMargin / $revenueSales) * 100, 1) : 0.0;

        // 3. Biaya Variabel (KurirQ, Gateway DOKU, Handling) & Contribution Margin (PRD Bab 12)
        $courierCostTotal = (float) $validOrders->sum(fn ($o) => $o->delivery ? (float) $o->delivery->fee : (float) $o->shipping_fee);
        $dokuFeeTotal = (float) $validOrders->sum('doku_fee_amount');
        $handlingCostTotal = (float) $validOrders->sum(fn ($o) => (float) ($o->handling_cost > 0 ? $o->handling_cost : config('myethek.finance.handling_cost_per_order', 1000)));

        $contributionMargin = $grossMargin + $shippingCollected - $courierCostTotal - $dokuFeeTotal - $handlingCostTotal;
        $fixedOpex = (float) config('myethek.finance.fixed_monthly_opex', 12000000);
        $operatingProfit = $contributionMargin - $fixedOpex;

        // 4. Komposisi B2C vs B2B (Target 90:10 berdasarkan jumlah order - BR-003, SA-001)
        $totalOrderCount = $validOrders->count();
        $b2cCount = $validOrders->where('customer_channel', 'b2c')->count();
        $b2bCount = $validOrders->where('customer_channel', 'b2b')->count();
        $b2cPct = $totalOrderCount > 0 ? round(($b2cCount / $totalOrderCount) * 100, 1) : 90.0;
        $b2bPct = $totalOrderCount > 0 ? round(($b2bCount / $totalOrderCount) * 100, 1) : 10.0;

        $b2cGmv = (float) $validOrders->where('customer_channel', 'b2c')->sum('total_amount');
        $b2bGmv = (float) $validOrders->where('customer_channel', 'b2b')->sum('total_amount');
        $aov = $totalOrderCount > 0 ? round($gmvTotal / $totalOrderCount, 0) : 0;

        // 5. Payment & Delivery Success Rate (PRD Bab 12)
        $allPayments = Payment::with('order.customer')->latest()->get();
        $paidPaymentsCount = $allPayments->where('status', 'paid')->count();
        $paymentSuccessRate = $allPayments->count() > 0 ? round(($paidPaymentsCount / $allPayments->count()) * 100, 1) : 100.0;

        $deliveredCount = $validOrders->whereIn('status', ['delivered', 'completed'])->count();
        $deliverySuccessRate = $totalOrderCount > 0 ? round(($deliveredCount / $totalOrderCount) * 100, 1) : 100.0;

        // 6. Daftar Pelanggan & Verifikasi Akun B2B (B2B-001)
        $customers = User::where('role', 'customer')->with('territory')->latest()->get();

        // 7. Audit Logs (SEC-005)
        $auditLogs = AuditLog::with('actor')->latest()->take(25)->get();

        return view('finance.index', [
            'gmvTotal' => $gmvTotal,
            'revenueSales' => $revenueSales,
            'totalHpp' => $totalHpp,
            'grossMargin' => $grossMargin,
            'grossMarginPct' => $grossMarginPct,
            'shippingCollected' => $shippingCollected,
            'courierCostTotal' => $courierCostTotal,
            'dokuFeeTotal' => $dokuFeeTotal,
            'handlingCostTotal' => $handlingCostTotal,
            'contributionMargin' => $contributionMargin,
            'fixedOpex' => $fixedOpex,
            'operatingProfit' => $operatingProfit,
            'totalOrderCount' => $totalOrderCount,
            'b2cCount' => $b2cCount,
            'b2bCount' => $b2bCount,
            'b2cPct' => $b2cPct,
            'b2bPct' => $b2bPct,
            'b2cGmv' => $b2cGmv,
            'b2bGmv' => $b2bGmv,
            'aov' => $aov,
            'paymentSuccessRate' => $paymentSuccessRate,
            'deliverySuccessRate' => $deliverySuccessRate,
            'payments' => $allPayments,
            'orders' => $allOrders->take(25),
            'customers' => $customers,
            'auditLogs' => $auditLogs,
            'channelFilter' => $channelFilter,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    /**
     * Rekonsiliasi Settlement DOKU oleh Finance/Superadmin (SA-003, SA-004, SA-005)
     */
    public function reconcileSettlement(Request $request, int $paymentId)
    {
        $payment = Payment::with('order')->findOrFail($paymentId);

        $validated = $request->validate([
            'settlement_status' => 'required|in:settled,mismatch,unsettled',
            'settlement_reference' => 'nullable|string|max:100',
            'reason' => 'nullable|string|max:255',
        ]);

        $before = [
            'settlement_status' => $payment->settlement_status,
            'settlement_reference' => $payment->settlement_reference,
        ];

        $payment->update([
            'settlement_status' => $validated['settlement_status'],
            'settlement_reference' => $validated['settlement_reference'] ?? ('STL-DOKU-' . date('Ymd') . '-' . $payment->id),
            'settled_at' => $validated['settlement_status'] === 'settled' ? now() : null,
        ]);

        $this->auditService->log(
            action: 'finance_settlement_reconcile',
            entity: $payment,
            entityId: $payment->id,
            beforeState: $before,
            afterState: [
                'settlement_status' => $payment->settlement_status,
                'settlement_reference' => $payment->settlement_reference,
            ],
            reason: $validated['reason'] ?? 'Rekonsiliasi settlement DOKU'
        );

        return redirect()->back()->with('success', "Settlement untuk referensi {$payment->payment_reference} berhasil diperbarui menjadi " . strtoupper($payment->settlement_status) . '.');
    }

    /**
     * Proses Refund Pembayaran oleh Role Berwenang (Finance / Superadmin) dengan Audit Log (PAY-006, SA-005, UAT #14)
     */
    public function processRefund(Request $request, int $paymentId)
    {
        $payment = Payment::with('order.items')->findOrFail($paymentId);

        $validated = $request->validate([
            'refund_reason' => 'required|string|min:5|max:255',
        ], [
            'refund_reason.required' => 'Alasan refund wajib diisi untuk keperluan audit keuangan.',
        ]);

        DB::transaction(function () use ($payment, $validated) {
            $beforeStatus = $payment->status;

            $payment->update([
                'status' => 'refunded',
                'refund_reason' => $validated['refund_reason'],
                'refunded_by' => Auth::id(),
                'refunded_at' => now(),
            ]);

            if ($payment->order) {
                $payment->order->update([
                    'status' => 'refunded',
                    'payment_status' => 'refunded',
                    'notes' => trim(($payment->order->notes ?? '') . ' | Refund Finance: ' . $validated['refund_reason']),
                ]);

                $this->inventoryService->releaseReservedStockForOrder(
                    $payment->order,
                    'Refund pembayaran: ' . $validated['refund_reason'],
                    Auth::user()
                );
            }

            $this->auditService->log(
                action: 'finance_payment_refund',
                entity: $payment,
                entityId: $payment->id,
                beforeState: ['status' => $beforeStatus],
                afterState: ['status' => 'refunded', 'amount' => $payment->amount],
                reason: $validated['refund_reason']
            );
        });

        return redirect()->back()->with('success', "Refund untuk transaksi {$payment->payment_reference} berhasil diproses dan tercatat di Audit Log.");
    }

    /**
     * Verifikasi / Ubah Akun Customer menjadi Tipe B2B Grosir (B2B-001)
     */
    public function toggleB2BStatus(Request $request, int $userId)
    {
        $customer = User::where('role', 'customer')->findOrFail($userId);
        $beforeType = $customer->customer_type;

        $newType = $customer->customer_type === 'b2b' ? 'b2c' : 'b2b';
        $customer->update([
            'customer_type' => $newType,
            'b2b_verified_at' => $newType === 'b2b' ? now() : null,
            'b2b_store_name' => $request->input('b2b_store_name', $customer->b2b_store_name ?? ('Warung Mitra ' . $customer->name)),
        ]);

        $this->auditService->log(
            action: 'customer_b2b_verification',
            entity: $customer,
            entityId: $customer->id,
            beforeState: ['customer_type' => $beforeType],
            afterState: ['customer_type' => $newType, 'b2b_verified_at' => $customer->b2b_verified_at],
            reason: "Perubahan status tipe pelanggan menjadi " . strtoupper($newType)
        );

        return redirect()->back()->with('success', "Status akun {$customer->name} berhasil diubah menjadi " . strtoupper($newType) . '.');
    }

    /**
     * Ekspor Laporan Keuangan & Transaksi ke CSV (SA-006)
     */
    public function exportCsv(): StreamedResponse
    {
        $orders = Order::with(['customer', 'payment', 'delivery'])
            ->where('order_type', 'retail')
            ->latest()
            ->get();

        $filename = 'laporan_keuangan_mak_ethek_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($orders) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'No Order',
                'Tanggal',
                'Pelanggan',
                'Kanal (B2C/B2B)',
                'Subtotal Barang',
                'Ongkir',
                'Fee DOKU',
                'Handling Cost',
                'Total Tagihan',
                'Metode Bayar',
                'Status Bayar',
                'Ref DOKU',
                'Status Settlement',
                'Status Order',
            ]);

            foreach ($orders as $o) {
                fputcsv($handle, [
                    $o->order_number,
                    $o->created_at?->format('Y-m-d H:i:s'),
                    $o->customer?->name,
                    strtoupper($o->customer_channel ?? 'B2C'),
                    $o->subtotal,
                    $o->shipping_fee,
                    $o->doku_fee_amount,
                    $o->handling_cost,
                    $o->total_amount,
                    strtoupper((string) $o->payment_method),
                    strtoupper((string) $o->payment_status),
                    $o->payment?->payment_reference ?? '-',
                    strtoupper($o->payment?->settlement_status ?? 'UNSETTLED'),
                    strtoupper((string) $o->status),
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}

