<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoOrderSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::where('role', 'customer')->where('phone', '08160000001')->first()
            ?? User::where('role', 'customer')->first();
        $driver = User::where('role', 'driver')->first();
        $merchant = Merchant::first();
        $products = Product::take(2)->get();

        if (!$customer || !$driver || !$merchant || $products->isEmpty()) {
            return;
        }

        // 1. Pesanan Demo Aktif yang Sedang Diantar oleh Driver (KurirQ)
        $activeOtp = '5829';
        $orderActive = Order::create([
            'order_number' => 'ORD-BLITAR-' . strtoupper(bin2hex(random_bytes(3))),
            'customer_id' => $customer->id,
            'merchant_id' => $merchant->id,
            'recipient_name' => $customer->name,
            'recipient_phone' => $customer->phone,
            'order_type' => 'retail',
            'customer_channel' => 'b2c',
            'status' => 'on_delivery',
            'packing_status' => 'handed_over',
            'subtotal' => 89700,
            'discount_amount' => 0,
            'shipping_fee' => 8000,
            'platform_fee' => 0,
            'doku_fee_amount' => 0,
            'handling_cost' => 1000,
            'total_amount' => 98700,
            'payment_method' => 'qris',
            'payment_status' => 'paid',
            'paid_at' => now()->subMinutes(25),
            'notes' => 'Tolong titipkan di pagar depan jika tidak ada orang.',
            'shipping_address' => 'Jl. Kalimantan No. 45, Kelurahan Sananwetan, Kota Blitar',
            'shipping_latitude' => -8.109000,
            'shipping_longitude' => 112.188000,
            'package_count' => 1,
            'handed_over_at' => now()->subMinutes(15),
            'handover_code' => 'HO-8821',
        ]);

        foreach ($products as $idx => $prod) {
            OrderItem::create([
                'order_id' => $orderActive->id,
                'product_id' => $prod->id,
                'product_name' => $prod->name,
                'unit_name' => $prod->unit_name,
                'quantity' => $idx === 0 ? 1 : 1,
                'price' => $prod->base_price,
                'subtotal' => $prod->base_price,
            ]);
        }

        $orderActive->escrow()->create([
            'gross_amount' => 98700,
            'merchant_portion' => 89700,
            'driver_portion' => 8000,
            'platform_portion' => 0,
            'status' => 'held',
        ]);

        $orderActive->delivery()->create([
            'courier_provider' => 'KurirQ',
            'delivery_type' => 'instant',
            'distance_km' => 2.4,
            'fee' => 8000,
            'driver_id' => $driver->id,
            'status' => 'picked_up',
            'assigned_at' => now()->subMinutes(20),
            'accepted_at' => now()->subMinutes(18),
            'picked_up_at' => now()->subMinutes(15),
            'completion_otp' => $activeOtp,
            'otp_hash' => Hash::make($activeOtp),
            'otp_expires_at' => now()->addHours(3),
            'otp_attempts' => 0,
            'current_latitude' => -8.103500,
            'current_longitude' => 112.178000,
            'last_location_at' => now()->subSeconds(30),
        ]);

        // 2. Pesanan Siap Pickup di Gudang (Waiting for Driver Pickup)
        $orderPickup = Order::create([
            'order_number' => 'ORD-BLITAR-' . strtoupper(bin2hex(random_bytes(3))),
            'customer_id' => $customer->id,
            'merchant_id' => $merchant->id,
            'recipient_name' => 'Bpk. Hendro',
            'recipient_phone' => '081234567890',
            'order_type' => 'retail',
            'customer_channel' => 'b2c',
            'status' => 'processing',
            'packing_status' => 'packed',
            'subtotal' => 74000,
            'discount_amount' => 0,
            'shipping_fee' => 8000,
            'platform_fee' => 0,
            'total_amount' => 82000,
            'payment_method' => 'qris',
            'payment_status' => 'paid',
            'paid_at' => now()->subMinutes(10),
            'shipping_address' => 'Jl. Sudirman No. 12, Bendogerit, Blitar',
            'package_count' => 1,
        ]);

        $otpPickup = '4192';
        $orderPickup->delivery()->create([
            'courier_provider' => 'KurirQ',
            'delivery_type' => 'instant',
            'distance_km' => 1.8,
            'fee' => 8000,
            'driver_id' => $driver->id,
            'status' => 'assigned',
            'assigned_at' => now()->subMinutes(5),
            'completion_otp' => $otpPickup,
            'otp_hash' => Hash::make($otpPickup),
            'otp_expires_at' => now()->addHours(3),
            'otp_attempts' => 0,
        ]);
    }
}

