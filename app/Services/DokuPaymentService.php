<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DokuPaymentService
{
    protected InventoryService $inventoryService;
    protected AuditService $auditService;

    public function __construct(?InventoryService $inventoryService = null, ?AuditService $auditService = null)
    {
        $this->inventoryService = $inventoryService ?? new InventoryService();
        $this->auditService = $auditService ?? new AuditService();
    }

    /**
     * Hitung estimasi biaya gateway DOKU sesuai metode pembayaran (BR-014, SOT 8.1).
     */
    public function calculateProviderFee(string $channel, float $amount): float
    {
        $fees = config('myethek.doku.fees', []);

        return match ($channel) {
            'qris' => round($amount * (($fees['qris_percent'] ?? 0.7) / 100), 2),
            'shopeepay' => (float) ($fees['shopeepay_flat'] ?? 1500),
            'va_bri', 'va_bni', 'va_mandiri', 'va' => (float) ($fees['va_flat'] ?? 4500),
            default => 0.0,
        };
    }

    /**
     * Buat transaksi pembayaran DOKU dan kaitkan dengan Order (PAY-001, PAY-002).
     */
    public function createPaymentForOrder(Order $order, string $channel): Payment
    {
        $reference = 'DOKU-BLT-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        $fee = $this->calculateProviderFee($channel, (float) $order->total_amount);
        $expiryMinutes = (int) config('myethek.doku.expiry_minutes', 60);

        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'DOKU',
            'payment_reference' => $reference,
            'channel' => $channel,
            'amount' => $order->total_amount,
            'provider_fee' => $fee,
            'status' => 'pending',
            'signature_verified' => false,
            'expired_at' => now()->addMinutes($expiryMinutes),
            'settlement_status' => 'unsettled',
            'raw_payload' => [
                'client_id' => config('myethek.doku.client_id'),
                'invoice_number' => $order->order_number,
                'channel' => $channel,
                'virtual_account_or_qr' => $this->generateChannelInstruction($channel, $reference),
            ],
        ]);

        $order->update([
            'doku_fee_amount' => $fee,
        ]);

        return $payment;
    }

    /**
     * Buat instruksi pembayaran simulasi DOKU (Nomor VA / String QRIS).
     */
    protected function generateChannelInstruction(string $channel, string $reference): string
    {
        return match ($channel) {
            'qris' => '00020101021226660014ID.CO.DOKU.WWW011893600899' . substr(preg_replace('/[^0-9]/', '', crc32($reference)), 0, 10) . '5204541153033605802ID5916KOPERASI MAKETHEK6006BLITAR',
            'shopeepay' => 'https://pay.doku.com/shopeepay-jump/' . strtolower($reference),
            'va_bri' => '88880' . str_pad((string) abs(crc32($reference)), 10, '0', STR_PAD_LEFT),
            'va_bni' => '98812' . str_pad((string) abs(crc32($reference)), 10, '0', STR_PAD_LEFT),
            'va_mandiri' => '89022' . str_pad((string) abs(crc32($reference)), 10, '0', STR_PAD_LEFT),
            default => 'DOKU-' . $reference,
        };
    }

    /**
     * Buat HMAC-SHA256 Signature sesuai protokol webhook DOKU (PAY-004, SOT 8.1).
     */
    public function generateSignature(array $payload, string $timestamp = '2026-11-09T12:00:00Z', string $requestId = 'REQ-001'): string
    {
        $clientId = config('myethek.doku.client_id', 'BRN-PILOT-BLITAR-001');
        $secretKey = config('myethek.doku.secret_key', 'SK-MAK-ETHEK-PILOT-BLITAR-2026');
        $jsonBody = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $digest = base64_encode(hash('sha256', $jsonBody, true));

        $componentSignature = "Client-Id:{$clientId}\n"
            . "Request-Id:{$requestId}\n"
            . "Request-Timestamp:{$timestamp}\n"
            . "Request-Target:/payments/doku/webhook\n"
            . "Digest:{$digest}";

        return 'HMACSHA256=' . base64_encode(hash_hmac('sha256', $componentSignature, $secretKey, true));
    }

    /**
     * Verifikasi Signature Webhook DOKU.
     */
    public function verifySignature(array $payload, string $signatureHeader, string $timestamp = '2026-11-09T12:00:00Z', string $requestId = 'REQ-001'): bool
    {
        $expected = $this->generateSignature($payload, $timestamp, $requestId);
        return hash_equals($expected, $signatureHeader);
    }

    /**
     * Proses Callback / Webhook DOKU secara Idempotent & Terverifikasi (PAY-003..005, BR-006, UAT #4, #5, #6).
     */
    public function handleWebhook(
        array $payload,
        string $signatureHeader,
        string $timestamp = '2026-11-09T12:00:00Z',
        string $requestId = 'REQ-001'
    ): array {
        // 1. Verifikasi Signature
        if (!$this->verifySignature($payload, $signatureHeader, $timestamp, $requestId)) {
            return [
                'success' => false,
                'http_code' => 401,
                'message' => 'Signature webhook DOKU tidak valid.',
            ];
        }

        $paymentReference = $payload['payment_reference'] ?? null;
        $status = strtoupper((string) ($payload['transaction_status'] ?? ''));
        $amount = (float) ($payload['amount'] ?? 0);

        $payment = Payment::with('order.items')->where('payment_reference', $paymentReference)->first();
        if (!$payment) {
            return [
                'success' => false,
                'http_code' => 404,
                'message' => 'Payment reference tidak ditemukan.',
            ];
        }

        // 2. Cek kecocokan nominal
        if (abs((float) $payment->amount - $amount) > 1) {
            $payment->update(['settlement_status' => 'mismatch']);
            return [
                'success' => false,
                'http_code' => 422,
                'message' => 'Nominal pembayaran tidak cocok dengan tagihan order.',
            ];
        }

        // 3. Idempotency Check: Jika sudah berstatus final yang sama (misal sudah 'paid'), abaikan tanpa menggandakan efek (PAY-004, UAT #5)
        if ($payment->status === 'paid' && $status === 'SUCCESS') {
            return [
                'success' => true,
                'idempotent_skip' => true,
                'http_code' => 200,
                'message' => 'Webhook sudah pernah diproses sebelumnya (Idempotent OK).',
            ];
        }

        if (in_array($payment->status, ['expired', 'failed']) && in_array($status, ['EXPIRED', 'FAILED'])) {
            return [
                'success' => true,
                'idempotent_skip' => true,
                'http_code' => 200,
                'message' => 'Status gagal/kedaluwarsa sudah tercatat (Idempotent OK).',
            ];
        }

        return DB::transaction(function () use ($payment, $status, $payload, $requestId) {
            $order = $payment->order;

            if ($status === 'SUCCESS') {
                $payment->update([
                    'status' => 'paid',
                    'signature_verified' => true,
                    'webhook_idempotency_key' => $requestId,
                    'paid_at' => now(),
                    'raw_payload' => array_merge($payment->raw_payload ?? [], ['webhook' => $payload]),
                ]);

                $order->update([
                    'status' => 'paid',
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                ]);

                // Untuk kompatibilitas escrow table yang ada
                if ($order->escrow) {
                    $order->escrow->update(['status' => 'held']);
                }

                $this->auditService->log(
                    action: 'payment_webhook_paid',
                    entity: $payment,
                    entityId: $payment->id,
                    beforeState: ['status' => 'pending'],
                    afterState: ['status' => 'paid', 'payment_reference' => $payment->payment_reference],
                    reason: 'Verifikasi webhook DOKU berhasil'
                );

                return [
                    'success' => true,
                    'idempotent_skip' => false,
                    'http_code' => 200,
                    'message' => "Pembayaran {$payment->payment_reference} terverifikasi LUNAS.",
                ];
            }

            if (in_array($status, ['FAILED', 'EXPIRED'])) {
                $mappedStatus = $status === 'EXPIRED' ? 'expired' : 'failed';

                $payment->update([
                    'status' => $mappedStatus,
                    'signature_verified' => true,
                    'webhook_idempotency_key' => $requestId,
                    'raw_payload' => array_merge($payment->raw_payload ?? [], ['webhook' => $payload]),
                ]);

                $order->update([
                    'status' => 'cancelled',
                    'payment_status' => $mappedStatus,
                    'notes' => trim(($order->notes ?? '') . " | Pembayaran DOKU {$mappedStatus}"),
                ]);

                if ($order->delivery) {
                    $order->delivery->update(['status' => 'cancelled']);
                }

                // Lepaskan reservasi stok kembali ke gudang (UAT #6, WH-007)
                $this->inventoryService->releaseReservedStockForOrder(
                    $order,
                    "Pelepasan reservasi otomatis karena pembayaran DOKU {$mappedStatus}"
                );

                $this->auditService->log(
                    action: "payment_webhook_{$mappedStatus}",
                    entity: $payment,
                    entityId: $payment->id,
                    beforeState: ['status' => 'pending'],
                    afterState: ['status' => $mappedStatus],
                    reason: "Webhook DOKU menyatakan pembayaran {$mappedStatus}"
                );

                return [
                    'success' => true,
                    'idempotent_skip' => false,
                    'http_code' => 200,
                    'message' => "Pembayaran {$mappedStatus}. Reservasi stok telah dilepas kembali ke gudang.",
                ];
            }

            return [
                'success' => false,
                'http_code' => 400,
                'message' => 'Status transaksi tidak dikenali.',
            ];
        });
    }
}

