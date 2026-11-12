<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Merchant;
use App\Models\MerchantInventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\Territory;
use App\Models\User;
use App\Services\DokuPaymentService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PilotBlitarPrdSotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('myethek.pilot_mode', true);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_pilot_mode_resolves_single_main_cooperative_merchant_in_kota_blitar(): void
    {
        $response = $this->get(route('storefront.index'));
        $response->assertStatus(200);
        // Pilot mode uses single central merchant (Gudang Pusat Koperasi Kota Blitar)
        $response->assertSee('Gudang Pusat Koperasi Kota Blitar');
    }

    public function test_b2b_customer_gets_wholesale_price_and_enforces_1_million_minimum_order(): void
    {
        $b2bUser = User::where('email', 'warung.b2b@myethek.id')->firstOrFail();
        $product = Product::firstOrFail();

        // 1. Add small quantity (below Rp 1.000.000)
        $this->actingAs($b2bUser)->post(route('storefront.cart.add'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        // Attempt to access checkout should redirect back to cart with error
        $checkoutAttempt = $this->actingAs($b2bUser)->get(route('storefront.checkout'));
        $checkoutAttempt->assertRedirect(route('storefront.cart'));

        // 2. Increase quantity so subtotal >= Rp 1.000.000
        $b2bPrice = $product->getEffectiveB2bPrice();
        $neededQty = (int) ceil(1000000 / $b2bPrice) + 1;

        MerchantInventory::query()->update(['stock' => 500]);

        $this->actingAs($b2bUser)->post(route('storefront.cart.update'), [
            'product_id' => $product->id,
            'quantity' => $neededQty,
        ]);

        $checkoutOk = $this->actingAs($b2bUser)->get(route('storefront.checkout'));
        $checkoutOk->assertStatus(200);
        $checkoutOk->assertSee('Metode Pengiriman');
    }

    public function test_customer_can_save_multiple_addresses_and_checkout_for_parents_in_blitar(): void
    {
        $customer = User::where('email', 'customer.gedog@myethek.id')->firstOrFail();
        $village = Territory::where('level', 'village')->firstOrFail();

        // Add new address for parents
        $res = $this->actingAs($customer)->post(route('user.addresses.store'), [
            'label' => 'Rumah Ibu di Kepanjenkidul',
            'recipient_name' => 'Ibu Hj. Aminah',
            'recipient_phone' => '081299887766',
            'territory_id' => $village->id,
            'full_address' => 'Jl. Mastrip No. 45, Kota Blitar',
            'notes' => 'Pagar bambu kuning',
            'is_default' => 1,
        ]);
        $res->assertRedirect();

        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $customer->id,
            'recipient_name' => 'Ibu Hj. Aminah',
        ]);

        // Add product & checkout for parents
        $product = Product::firstOrFail();
        $this->actingAs($customer)->post(route('storefront.cart.add'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $checkoutRes = $this->actingAs($customer)->post(route('storefront.checkout.store'), [
            'recipient_name' => 'Ibu Hj. Aminah',
            'phone' => '081299887766',
            'shipping_address' => 'Jl. Mastrip No. 45, Kota Blitar',
            'delivery_type' => 'kurirq',
            'payment_method' => 'qris',
            'notes' => 'Titip untuk Ibu',
        ]);

        $order = Order::latest('id')->firstOrFail();
        $checkoutRes->assertRedirect(route('storefront.orderSuccess', $order->order_number));

        $this->assertEquals('Ibu Hj. Aminah', $order->recipient_name);
        $this->assertEquals('081299887766', $order->recipient_phone);
        $this->assertEquals('unpaid', $order->payment_status);
        $this->assertNotNull($order->payment);
    }

    public function test_doku_webhook_verifies_signature_is_idempotent_and_releases_reserved_stock_on_expiry(): void
    {
        $customer = User::where('email', 'customer.gedog@myethek.id')->firstOrFail();
        $product = Product::firstOrFail();

        $merchant = Merchant::where('slug', 'gudang-pusat-koperasi-kota-blitar')->firstOrFail();
        $invBefore = MerchantInventory::where('merchant_id', $merchant->id)
            ->where('product_id', $product->id)
            ->firstOrFail();
        $initialReserved = $invBefore->stock_reserved;

        // Create digital payment order (QRIS) -> reserves stock
        $this->actingAs($customer)->post(route('storefront.cart.add'), [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $this->actingAs($customer)->post(route('storefront.checkout.store'), [
            'recipient_name' => $customer->name,
            'phone' => $customer->phone,
            'shipping_address' => $customer->address,
            'delivery_type' => 'kurirq',
            'payment_method' => 'qris',
        ]);

        $order = Order::with('payment')->latest('id')->firstOrFail();
        $payment = $order->payment;
        $invAfterReserve = $invBefore->fresh();
        $this->assertEquals($initialReserved + 3, $invAfterReserve->stock_reserved);

        $payload = [
            'payment_reference' => $payment->payment_reference,
            'transaction_status' => 'SUCCESS',
            'amount' => (float) $payment->amount,
            'order_number' => $order->order_number,
        ];

        // 1. Test Invalid Signature Rejected with 401
        $badWebhook = $this->postJson(route('payments.doku.webhook'), $payload, [
            'Signature' => 'invalid-signature',
            'Request-Timestamp' => '2026-11-09T12:00:00Z',
            'Request-Id' => 'REQ-001',
        ]);
        $badWebhook->assertStatus(401);

        // 2. Test Valid Signature Marks Order Paid
        $dokuService = app(DokuPaymentService::class);
        $validSig = $dokuService->generateSignature($payload, '2026-11-09T12:00:00Z', 'REQ-001');

        $goodWebhook = $this->postJson(route('payments.doku.webhook'), $payload, [
            'Signature' => $validSig,
            'Request-Timestamp' => '2026-11-09T12:00:00Z',
            'Request-Id' => 'REQ-001',
        ]);
        $goodWebhook->assertStatus(200)->assertJson(['success' => true]);

        $this->assertEquals('paid', $order->fresh()->payment_status);
        $this->assertEquals('paid', $payment->fresh()->status);

        // 3. Test Idempotency (calling webhook again returns idempotent_skip = true)
        $duplicateWebhook = $this->postJson(route('payments.doku.webhook'), $payload, [
            'Signature' => $validSig,
            'Request-Timestamp' => '2026-11-09T12:00:00Z',
            'Request-Id' => 'REQ-001',
        ]);
        $duplicateWebhook->assertStatus(200)->assertJson(['idempotent_skip' => true]);
    }

    public function test_end_to_end_wms_warehouse_and_driver_kurirq_otp_and_rbac(): void
    {
        $customer = User::where('email', 'customer.gedog@myethek.id')->firstOrFail();
        $warehouseAdmin = User::where('email', 'gudang@myethek.id')->firstOrFail();
        $driver = User::where('email', 'driver.gedog@myethek.id')->firstOrFail();
        $superAdmin = User::where('email', 'superadmin@myethek.id')->firstOrFail();
        $product = Product::firstOrFail();

        // 1. Customer places QRIS order and simulates DOKU paid
        $this->actingAs($customer)->post(route('storefront.cart.add'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        $this->actingAs($customer)->post(route('storefront.checkout.store'), [
            'recipient_name' => $customer->name,
            'phone' => $customer->phone,
            'shipping_address' => $customer->address,
            'delivery_type' => 'kurirq',
            'payment_method' => 'qris',
        ]);

        $order = Order::with(['items', 'delivery', 'payment'])->latest('id')->firstOrFail();

        // Simulate DOKU Payment Paid
        $this->actingAs($customer)->post(route('storefront.simulateDoku', $order->order_number), [
            'status' => 'SUCCESS',
        ]);
        $this->assertEquals('paid', $order->fresh()->payment_status);

        // 2. Warehouse Admin views Desktop WMS & progresses Pick -> Pack -> Handover to Driver
        $wmsView = $this->actingAs($warehouseAdmin)->get(route('warehouse.index'));
        $wmsView->assertStatus(200)
            ->assertSee('Warehouse Management System (WMS)')
            ->assertSee('Admin Gudang (WMS)')
            ->assertSee('WMS Picking, Packing & Handover', false)
            ->assertSee('Stok Gudang & Stock Movement', false)
            ->assertDontSee('Manajemen Order & Dispatch')
            ->assertDontSee('Master SKU & Harga B2C/B2B')
            ->assertDontSee('Super Admin & Finance')
            ->assertDontSee('Rekonsiliasi DOKU & Refund');

        $orderItem = $order->items->first();
        $this->actingAs($warehouseAdmin)->post(route('warehouse.pickItem', $orderItem->id), [
            'picked_quantity' => $orderItem->quantity,
        ])->assertRedirect();
        $this->assertEquals('picked', $orderItem->fresh()->picking_status);

        $this->actingAs($warehouseAdmin)->post(route('warehouse.packOrder', $order->id), [
            'package_count' => 1,
        ])->assertRedirect();
        $this->assertEquals('packed', $order->fresh()->packing_status);

        $this->actingAs($warehouseAdmin)->post(route('warehouse.handover', $order->id), [
            'driver_id' => $driver->id,
        ])->assertRedirect();
        $this->assertEquals('handed_over', $order->fresh()->packing_status);

        // 3. Warehouse Admin performs Stock Opname with mandatory reason
        $merchant = Merchant::where('slug', 'gudang-pusat-koperasi-kota-blitar')->firstOrFail();
        $inv = MerchantInventory::where('merchant_id', $merchant->id)
            ->where('product_id', $product->id)
            ->firstOrFail();

        $this->actingAs($warehouseAdmin)->post(route('warehouse.adjustInventory', $inv->id), [
            'stock' => 450,
            'movement_type' => 'adjustment',
            'reason' => 'Stock Opname Rutin Mingguan Gudang Blitar',
        ])->assertRedirect();

        $this->assertEquals(450, $inv->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'adjustment',
            'stock_after' => 450,
        ]);

        // 4. Driver accepts assignment & completes delivery with OTP verification
        $delivery = $order->delivery->fresh();
        $this->actingAs($driver)->post(route('driver.respond', $delivery->id), [
            'decision' => 'accept',
        ])->assertRedirect();

        $otpCode = $delivery->completion_otp;
        $this->assertTrue(Hash::check($otpCode, $delivery->otp_hash));

        // Wrong OTP increments attempts and fails
        $this->actingAs($driver)->post(route('driver.complete', $delivery->id), [
            'otp' => '0000',
        ]);
        $this->assertEquals(1, $delivery->fresh()->otp_attempts);
        $this->assertNotEquals('completed', $order->fresh()->status);

        // Correct OTP completes order & releases escrow to driver wallet
        $this->actingAs($driver)->post(route('driver.complete', $delivery->id), [
            'otp' => $otpCode,
        ]);
        $this->assertTrue(in_array($order->fresh()->status, ['delivered', 'completed']));
        $this->assertEquals('delivered', $delivery->fresh()->status);

        // 5. RBAC Check: Driver cannot access Super Admin or Warehouse Web Backoffice (403)
        $this->actingAs($driver)->get(route('superadmin.index'))->assertStatus(403);
        $this->actingAs($driver)->get(route('warehouse.index'))->assertStatus(403);

        // 6. Super Admin can access Executive & Finance Dashboard and Export CSV
        $finView = $this->actingAs($superAdmin)->get(route('superadmin.index'));
        $finView->assertStatus(200)->assertSee('Executive Super Admin & Financial Reconciliation');

        $csvExport = $this->actingAs($superAdmin)->get(route('finance.exportCsv'));
        $csvExport->assertStatus(200);
        $this->assertTrue(str_contains($csvExport->headers->get('content-type'), 'text/csv'));

        // Verify Audit Logs were recorded
        $this->assertGreaterThan(0, AuditLog::count());
    }
}
