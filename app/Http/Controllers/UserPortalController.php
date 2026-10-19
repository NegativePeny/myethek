<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Territory;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserPortalController extends Controller
{
    /**
     * Tampilkan riwayat belanja pengguna (Customer Orders)
     */
    public function orders(Request $request)
    {
        $status = $request->query('status', 'all');

        $query = Order::with(['items.product', 'merchant', 'delivery.driver'])
            ->where('customer_id', Auth::id())
            ->where('order_type', 'retail');

        if ($status === 'processing') {
            $query->whereIn('status', ['pending', 'confirmed', 'processing', 'ready_for_pickup', 'shipping']);
        } elseif ($status === 'completed') {
            $query->where('status', 'delivered');
        } elseif ($status === 'cancelled') {
            $query->where('status', 'cancelled');
        }

        $orders = $query->latest()->get();

        $counts = [
            'all' => Order::where('customer_id', Auth::id())->where('order_type', 'retail')->count(),
            'processing' => Order::where('customer_id', Auth::id())->where('order_type', 'retail')
                ->whereIn('status', ['pending', 'confirmed', 'processing', 'ready_for_pickup', 'shipping'])->count(),
            'completed' => Order::where('customer_id', Auth::id())->where('order_type', 'retail')
                ->where('status', 'delivered')->count(),
            'cancelled' => Order::where('customer_id', Auth::id())->where('order_type', 'retail')
                ->where('status', 'cancelled')->count(),
        ];

        return view('user.orders.index', [
            'orders' => $orders,
            'activeStatus' => $status,
            'counts' => $counts,
        ]);
    }

    /**
     * Detail Pesanan Pengguna dengan Status Tracking Realtime
     */
    public function orderDetail(string $orderNumber)
    {
        $order = Order::with(['items.product', 'merchant.territory', 'delivery.driver', 'escrow'])
            ->where('order_number', $orderNumber)
            ->where('customer_id', Auth::id())
            ->firstOrFail();

        return view('user.orders.show', [
            'order' => $order,
        ]);
    }

    /**
     * Batalkan Pesanan oleh Pembeli (Hanya jika status masih 'pending')
     * Mengembalikan kuantitas stok produk ke toko mitra secara otomatis
     */
    public function cancelOrder(Request $request, string $orderNumber)
    {
        $order = Order::with(['items', 'delivery', 'escrow'])
            ->where('order_number', $orderNumber)
            ->where('customer_id', Auth::id())
            ->firstOrFail();

        if ($order->status !== 'pending') {
            return redirect()->back()->with('error', 
                'Pesanan sudah dikonfirmasi atau sedang diproses oleh toko mitra dan tidak dapat dibatalkan sepihak. Silakan hubungi toko via WhatsApp.'
            );
        }

        $reason = $request->input('cancel_reason', 'Dibatalkan oleh pembeli');

        \Illuminate\Support\Facades\DB::transaction(function () use ($order, $reason) {
            // 1. Ubah status pesanan
            $order->update([
                'status' => 'cancelled',
                'notes' => $order->notes ? ($order->notes . ' | Alasan batal: ' . $reason) : ('Alasan batal: ' . $reason),
            ]);

            // 2. Kembalikan stok inventori ke toko mitra
            foreach ($order->items as $item) {
                $inventory = \App\Models\MerchantInventory::where('merchant_id', $order->merchant_id)
                    ->where('product_id', $item->product_id)
                    ->first();

                if ($inventory) {
                    $inventory->increment('stock', $item->quantity);
                }
            }

            // 3. Batalkan pengiriman jika ada
            if ($order->delivery) {
                $order->delivery->update(['status' => 'cancelled']);
            }

            // 4. Batalkan transaksi escrow jika ada (status enum: held, released, refunded)
            if ($order->escrow) {
                $order->escrow->update(['status' => 'refunded']);
            }
        });

        return redirect()->route('user.orders.show', $order->order_number)->with(
            'success',
            "Pesanan #{$order->order_number} berhasil dibatalkan dan stok produk telah dikembalikan ke toko mitra."
        );
    }

    /**
     * Beli Lagi (Re-order): Masukkan seluruh produk pesanan kembali ke keranjang
     */
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

    /**
     * Tampilkan profil akun pengguna (Warga)
     */
    public function profile()
    {
        $user = Auth::user()->load('territory.parent');

        $villages = Territory::where('level', 'village')
            ->with('parent')
            ->orderBy('name')
            ->get();

        $stats = [
            'total_orders' => Order::where('customer_id', $user->id)->count(),
            'completed_orders' => Order::where('customer_id', $user->id)->where('status', 'delivered')->count(),
            'processing_orders' => Order::where('customer_id', $user->id)
                ->whereIn('status', ['pending', 'confirmed', 'processing', 'ready_for_pickup', 'shipping'])
                ->count(),
        ];

        return view('user.profile', [
            'user' => $user,
            'villages' => $villages,
            'stats' => $stats,
        ]);
    }

    /**
     * Perbarui data profil, alamat domisili, atau kata sandi
     */
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
}
