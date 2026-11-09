<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Perluas tabel users untuk customer_type (B2C/B2B)
        Schema::table('users', function (Blueprint $table) {
            $table->string('customer_type', 20)->default('b2c')->after('role'); // b2c, b2b
            $table->timestamp('b2b_verified_at')->nullable()->after('customer_type');
            $table->string('b2b_store_name')->nullable()->after('b2b_verified_at');
        });

        // 2. Tabel user_addresses (Multi-Alamat & Beli untuk Penerima Lain - CUS-002..006)
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('label', 50)->default('Rumah'); // Rumah, Orang Tua, Warung, Kantor
            $table->string('recipient_name', 100);
            $table->string('recipient_phone', 25);
            $table->foreignId('territory_id')->nullable()->constrained('territories')->nullOnDelete();
            $table->text('full_address');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('notes')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // 3. Perluas tabel products untuk HPP (cost_price) dan Harga Grosir B2B (b2b_price)
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('cost_price', 12, 2)->default(0)->after('unit_name');
            $table->decimal('b2b_price', 12, 2)->nullable()->after('base_price');
        });

        // 4. Perluas tabel merchant_inventories untuk WMS (on_hand, reserved, reorder_level, rack_location)
        Schema::table('merchant_inventories', function (Blueprint $table) {
            $table->integer('stock_reserved')->default(0)->after('stock');
            $table->integer('reorder_level')->default(10)->after('stock_reserved');
            $table->string('rack_location', 50)->nullable()->after('reorder_level');
        });

        // 5. Tabel stock_movements (WH-008: in, reserve, commit, release, return, adjustment)
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('movement_type', 30); // in, reserve, commit, release, return, adjustment
            $table->integer('quantity');
            $table->integer('stock_before');
            $table->integer('stock_after');
            $table->string('reference_type', 50)->nullable(); // order, manual_adjustment, procurement
            $table->string('reference_id', 50)->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        // 6. Perluas tabel orders & order_items untuk B2B/B2C, Penerima, Biaya DOKU/Handling, dan Picking/Packing Gudang
        Schema::table('orders', function (Blueprint $table) {
            $table->string('customer_channel', 20)->default('b2c')->after('order_type'); // b2c, b2b
            $table->string('recipient_name', 100)->nullable()->after('customer_id');
            $table->string('recipient_phone', 25)->nullable()->after('recipient_name');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('subtotal');
            $table->decimal('doku_fee_amount', 12, 2)->default(0)->after('platform_fee');
            $table->decimal('handling_cost', 12, 2)->default(0)->after('doku_fee_amount');
            $table->string('packing_status', 30)->default('not_started')->after('status'); // not_started, picking, picked, packing, packed, handed_over
            $table->foreignId('picked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('picked_at')->nullable();
            $table->foreignId('packed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('packed_at')->nullable();
            $table->integer('package_count')->default(1);
            $table->timestamp('handed_over_at')->nullable();
            $table->string('handover_code', 30)->nullable();
            $table->string('rejection_reason')->nullable();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('cost_price', 12, 2)->default(0)->after('price');
            $table->integer('picked_quantity')->nullable()->after('quantity');
            $table->string('picking_status', 30)->default('pending')->after('picked_quantity'); // pending, picked, exception
            $table->string('exception_note')->nullable()->after('picking_status');
        });

        // 7. Tabel payments untuk DOKU Payment Adapter & Finance Reconciliation (PAY-001..006, SA-003..004)
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('provider', 30)->default('DOKU');
            $table->string('payment_reference')->unique();
            $table->string('channel', 40); // qris, shopeepay, va_bri, va_bni, va_mandiri
            $table->decimal('amount', 12, 2);
            $table->decimal('provider_fee', 12, 2)->default(0);
            $table->string('status', 30)->default('pending'); // created, pending, paid, failed, expired, refund_pending, refunded
            $table->boolean('signature_verified')->default(false);
            $table->string('webhook_idempotency_key')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->string('settlement_status', 30)->default('unsettled'); // unsettled, settled, mismatch
            $table->string('settlement_reference')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->string('refund_reason')->nullable();
            $table->foreignId('refunded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('refunded_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });

        // 8. Perluas tabel deliveries untuk KurirQ & OTP Hash + Expiry + Retry Limit (DRV-001..010, BR-010)
        Schema::table('deliveries', function (Blueprint $table) {
            $table->string('courier_provider', 40)->default('KurirQ')->after('delivery_type');
            $table->string('courier_job_id', 60)->nullable()->after('courier_provider');
            $table->string('otp_hash')->nullable()->after('completion_otp');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_hash');
            $table->integer('otp_attempts')->default(0)->after('otp_expires_at');
            $table->timestamp('accepted_at')->nullable()->after('assigned_at');
            $table->string('rejection_reason')->nullable()->after('status');
            $table->string('receiver_name', 100)->nullable()->after('pod_image_path');
        });

        // 9. Tabel audit_logs (SEC-005)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 40)->nullable();
            $table->string('action', 80);
            $table->string('entity_type', 80);
            $table->string('entity_id', 50);
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->string('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn([
                'courier_provider',
                'courier_job_id',
                'otp_hash',
                'otp_expires_at',
                'otp_attempts',
                'accepted_at',
                'rejection_reason',
                'receiver_name',
            ]);
        });
        Schema::dropIfExists('payments');
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['cost_price', 'picked_quantity', 'picking_status', 'exception_note']);
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['picked_by']);
            $table->dropForeign(['packed_by']);
            $table->dropColumn([
                'customer_channel',
                'recipient_name',
                'recipient_phone',
                'discount_amount',
                'doku_fee_amount',
                'handling_cost',
                'packing_status',
                'picked_by',
                'picked_at',
                'packed_by',
                'packed_at',
                'package_count',
                'handed_over_at',
                'handover_code',
                'rejection_reason',
            ]);
        });
        Schema::dropIfExists('stock_movements');
        Schema::table('merchant_inventories', function (Blueprint $table) {
            $table->dropColumn(['stock_reserved', 'reorder_level', 'rack_location']);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['cost_price', 'b2b_price']);
        });
        Schema::dropIfExists('user_addresses');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['customer_type', 'b2b_verified_at', 'b2b_store_name']);
        });
    }
};

