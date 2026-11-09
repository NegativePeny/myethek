<?php

namespace App\Http\Controllers;

use App\Models\MerchantInventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\Territory;
use App\Models\UserAddress;
use App\Services\CartService;
use App\Services\DeliveryService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserPortalController extends Controller
{
    public function orders(Request $request)
    {
        $status = $request->query('status', 'all');

        $query = Order::with(['items.product', 'merchant', 'delivery.driver', 'payment'])
            ->where('customer_id', Auth::id())
            ->where('order_type', 'retail');

        if ($status === 'processing') {
            $query->whereIn('status', ['pending', 'pending_payment', 'paid', 'confirmed', 'accepted', 'processing', 'picking', 'packed', 'ready_to_ship', 'ready_for_pickup', 'assigned', 'picked_up', 'shipping', 'on_delivery']);
        } elseif ($status === 'completed') {
            $query->whereIn('status', ['delivered', 'completed']);
        } elseif ($status === 'cancelled') {
            $query->whereIn('status', ['cancelled', 'refunded']);
        }

        $orders = $query->latest()->get();

        $counts = [
            'all' => Order::where('customer_id', Auth::id())->where('order_type', 'retail')->count(),
            'processing' => Order::where('customer_id', Auth::id())->where('order_type', 'retail')
                ->whereIn('status', ['pending', 'pending_payment', 'paid', 'confirmed', 'accepted', 'processing', 'picking', 'packed', 'ready_to_ship', 'ready_for_pickup', 'assigned', 'picked_up', 'shipping', 'on_delivery'])->count(),
            'completed' => Order::where('customer_id', Auth::id())->where('order_type', 'retail')
                ->whereIn('status', ['delivered', 'completed'])->count(),
            'cancelled' => Order::where('customer_id', Auth::id())->where('order_type', 'retail')
                ->whereIn('status', ['cancelled', 'refunded'])->count(),
        ];

        return view('user.orders.index', [
            'orders' => $orders,
            'activeStatus' => $status,
            'counts' => $counts,
        ]);
    }

    public function orderDetail(string $orderNumber)
    {
        $order = Order::with(['items.product', 'merchant.territory', 'delivery.driver', 'escrow', 'payment'])
            ->where('order_number', $orderNumber)
            ->where('customer_id', Auth::id())
            ->firstOrFail();

        return view('user.orders.show', [
            'order' => $order,
        ]);
    }

    /**
     * Batalkan Pesanan oleh Pembeli (Hanya jika status masih 'pending' / 'pending_payment')
     */
    public function cancelOrder(Request $request, string $orderNumber, InventoryService $inventoryService)
    {
        $order = Order::with(['items', 'delivery', 'escrow', 'payment'])
            ->where('order_number', $orderNumber)
            ->where('customer_id', Auth::id())
            ->firstOrFail();

        if (!in_array($order->status, ['pending', 'pending_payment'])) {
            return redirect()->back()->with(
                'error',
                'Pesanan sudah dikonfirmasi atau sedang diproses oleh gudang koperasi dan tidak dapat dibatalkan sepihak.'
            );
        }

        $reason = $request->input('cancel_reason', 'Dibatalkan oleh pembeli');
        $isPilot = (bool) config('myethek.pilot_mode', false);

        DB::transaction(function () use ($order, $reason, $isPilot, $inventoryService) {
            $order->update([
                'status' => 'cancelled',
                'notes' => $order->notes ? ($order->notes . ' | Alasan batal: ' . $reason) : ('Alasan batal: ' . $reason),
            ]);

            if ($isPilot) {
                $inventoryService->releaseReservedStockForOrder($order, 'Dibatalkan pembeli: ' . $reason, Auth::user());
            } else {
                foreach ($order->items as $item) {
                    $inventory = MerchantInventory::where('merchant_id', $order->merchant_id)
                        ->where('product_id', $item->product_id)
                        ->first();

                    if ($inventory) {
                        $inventory->increment('stock', $item->quantity);
                    }
                }
            }

            if ($order->delivery) {
                $order->delivery->update(['status' => 'cancelled']);
            }

            if ($order->payment && $order->payment->status === 'pending') {
                $order->payment->update(['status' => 'expired']);
            }

            if ($order->escrow) {
                $order->escrow->update(['status' => 'refunded']);
            }
        });

        return redirect()->route('user.orders.show', $order->order_number)->with(
            'success',
            "Pesanan #{$order->order_number} berhasil dibatalkan dan stok produk telah dikembalikan."
        );
    }

    public function reorder(string $orderNumber, CartService $cartService)
    {
        $order = Order::with(['items.product', 'merchant'])
            ->where('order_number', $orderNumber)
            ->where('customer_id', Auth::id())
            ->firstOrFail();

        $merchant = $order->merchant;
        $itemsAdded = 0;

        foreach ($order->items as $item) {
            $product = $item->product ?? Product::find($item->product_id);
            if ($product && $product->is_active) {
                $cartService->add($product, $item->quantity, $merchant);
                $itemsAdded++;
            }
        }

        if ($itemsAdded === 0) {
            return redirect()->back()->with('error', 'Produk pada pesanan ini sudah tidak tersedia di katalog.');
        }

        return redirect()->route('storefront.cart')->with(
            'success',
            "{$itemsAdded} produk dari pesanan #{$order->order_number} berhasil dimasukkan ke keranjang belanja!"
        );
    }

    public function profile()
    {
        $user = Auth::user()->load(['territory.parent', 'addresses.territory']);

        $villages = Territory::where('level', 'village')
            ->with('parent')
            ->orderBy('name')
            ->get();

        $stats = [
            'total_orders' => Order::where('customer_id', $user->id)->count(),
            'completed_orders' => Order::where('customer_id', $user->id)->whereIn('status', ['delivered', 'completed'])->count(),
            'processing_orders' => Order::where('customer_id', $user->id)
                ->whereIn('status', ['pending', 'pending_payment', 'paid', 'confirmed', 'accepted', 'processing', 'picking', 'packed', 'ready_for_pickup', 'shipping', 'on_delivery'])
                ->count(),
        ];

        return view('user.profile', [
            'user' => $user,
            'villages' => $villages,
            'stats' => $stats,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20|unique:users,phone,' . $user->id,
            'email' => 'nullable|email|max:150|unique:users,email,' . $user->id,
            'territory_id' => 'required|exists:territories,id',
            'address' => 'required|string|max:255',
            'current_password' => 'nullable|required_with:new_password',
            'new_password' => 'nullable|string|min:6|confirmed',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'phone.required' => 'Nomor WhatsApp / HP wajib diisi.',
            'phone.unique' => 'Nomor WhatsApp / HP ini sudah digunakan akun lain.',
            'email.unique' => 'Alamat email ini sudah digunakan akun lain.',
            'territory_id.required' => 'Pilih kelurahan / desa domisili Anda.',
            'address.required' => 'Alamat pengiriman wajib diisi.',
            'current_password.required_with' => 'Masukkan kata sandi lama untuk mengubah kata sandi.',
            'new_password.min' => 'Kata sandi baru minimal 6 karakter.',
            'new_password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        if (!empty($validated['new_password'])) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'Kata sandi saat ini tidak cocok.']);
            }
            $user->password = Hash::make($validated['new_password']);
        }

        $user->name = $validated['name'];
        $user->phone = $validated['phone'];
        $user->email = $validated['email'] ?? $user->email;
        $user->territory_id = $validated['territory_id'];
        $user->address = $validated['address'];
        $user->save();

        session(['active_village_id' => $user->territory_id]);

        return back()->with('success', 'Data profil dan alamat domisili belanja berhasil diperbarui.');
    }

    /**
     * Tambah Alamat Pengiriman Tersimpan / Alamat Keluarga di Blitar (CUS-003, CUS-004, CUS-005)
     */
    public function storeAddress(Request $request, DeliveryService $deliveryService)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'label' => 'required|string|max:50',
            'recipient_name' => 'required|string|max:100',
            'recipient_phone' => 'required|string|max:25',
            'territory_id' => 'required|exists:territories,id',
            'full_address' => 'required|string|max:500',
            'notes' => 'nullable|string|max:255',
            'is_default' => 'nullable|boolean',
        ]);

        $territory = Territory::find($validated['territory_id']);
        if (!$deliveryService->isWithinBlitarServiceArea($territory)) {
            return back()->with('error', 'Alamat harus berada di dalam area layanan Kota Blitar.');
        }

        if ($request->boolean('is_default')) {
            UserAddress::where('user_id', $user->id)->update(['is_default' => false]);
        }

        UserAddress::create([
            'user_id' => $user->id,
            'label' => $validated['label'],
            'recipient_name' => $validated['recipient_name'],
            'recipient_phone' => $validated['recipient_phone'],
            'territory_id' => $territory->id,
            'full_address' => $validated['full_address'],
            'latitude' => $territory->latitude,
            'longitude' => $territory->longitude,
            'notes' => $validated['notes'] ?? null,
            'is_default' => $request->boolean('is_default') || $user->addresses()->count() === 0,
        ]);

        return back()->with('success', "Alamat '{$validated['label']}' ({$validated['recipient_name']}) berhasil disimpan.");
    }

    public function deleteAddress(int $id)
    {
        $addr = UserAddress::where('user_id', Auth::id())->findOrFail($id);
        $addr->delete();

        return back()->with('success', 'Alamat tersimpan berhasil dihapus.');
    }
}
