<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMutation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EscrowService
{
    /**
     * Selesaikan pesanan dengan verifikasi OTP (Hash, Expiry, & Max Attempts - BR-010, DRV-007, SOT 10.1)
     * dan catat jurnal mutasi internal.
     */
    public function releaseEscrowForOrder(Order $order, ?string $inputOtp = null): array
    {
        $delivery = $order->delivery;

        if ($delivery && ($delivery->otp_hash || $delivery->completion_otp)) {
            $maxAttempts = (int) config('myethek.otp.max_attempts', 5);

            // 1. Cek batas percobaan OTP (BR-010)
            if ((int) $delivery->otp_attempts >= $maxAttempts) {
                return [
                    'success' => false,
                    'message' => "Batas percobaan OTP ({$maxAttempts}x) telah terlampaui. Silakan hubungi Admin Koperasi untuk resolusi.",
                ];
            }

            // 2. Cek kedaluwarsa OTP (BR-010)
            if ($delivery->otp_expires_at && now()->greaterThan($delivery->otp_expires_at)) {
                return [
                    'success' => false,
                    'message' => 'Kode OTP pengiriman sudah kedaluwarsa (expired).',
                ];
            }

            $cleanInput = trim((string) $inputOtp);
            $isValidOtp = false;

            if ($delivery->otp_hash && Hash::check($cleanInput, $delivery->otp_hash)) {
                $isValidOtp = true;
            } elseif ($delivery->completion_otp && $cleanInput !== '' && $cleanInput === $delivery->completion_otp) {
                $isValidOtp = true;
            }

            if (!$isValidOtp) {
                $delivery->increment('otp_attempts');
                $remaining = max(0, $maxAttempts - $delivery->otp_attempts);

                return [
                    'success' => false,
                    'message' => "Kode OTP tidak cocok. Sisa percobaan: {$remaining}x.",
                ];
            }
        }

        $escrow = $order->escrow;
        if ($escrow && $escrow->status === 'released') {
            return [
                'success' => false,
                'message' => 'Pesanan ini sudah pernah diselesaikan sebelumnya (OTP sekali pakai).',
            ];
        }

        return DB::transaction(function () use ($order, $escrow, $delivery) {
            $order->update([
                'status' => 'delivered',
                'payment_status' => 'paid',
            ]);

            if ($delivery) {
                $delivery->update([
                    'status' => 'delivered',
                    'delivered_at' => now(),
                    'completion_otp' => null, // Sekali pakai (BR-010)
                ]);
            }

            if ($escrow && $escrow->status === 'held') {
                $merchantUser = $order->merchant->user;
                $merchantWallet = Wallet::firstOrCreate(
                    ['user_id' => $merchantUser->id],
                    ['balance_available' => 0, 'balance_pending' => 0]
                );

                $merchantBalanceBefore = (float) $merchantWallet->balance_available;
                $merchantPortion = (float) $escrow->merchant_portion;
                $merchantBalanceAfter = $merchantBalanceBefore + $merchantPortion;

                $merchantWallet->update([
                    'balance_available' => $merchantBalanceAfter,
                ]);

                WalletMutation::create([
                    'wallet_id' => $merchantWallet->id,
                    'type' => 'credit',
                    'amount' => $merchantPortion,
                    'balance_before' => $merchantBalanceBefore,
                    'balance_after' => $merchantBalanceAfter,
                    'reference_type' => 'order',
                    'reference_id' => (string) $order->id,
                    'description' => "Penerimaan hasil penjualan pesanan {$order->order_number}",
                ]);

                if ($delivery && $delivery->driver_id) {
                    $driverUser = User::find($delivery->driver_id);
                    if ($driverUser) {
                        $driverWallet = Wallet::firstOrCreate(
                            ['user_id' => $driverUser->id],
                            ['balance_available' => 0, 'balance_pending' => 0]
                        );

                        $driverBalanceBefore = (float) $driverWallet->balance_available;
                        $driverPortion = (float) $escrow->driver_portion;
                        $driverBalanceAfter = $driverBalanceBefore + $driverPortion;

                        $driverWallet->update([
                            'balance_available' => $driverBalanceAfter,
                        ]);

                        WalletMutation::create([
                            'wallet_id' => $driverWallet->id,
                            'type' => 'credit',
                            'amount' => $driverPortion,
                            'balance_before' => $driverBalanceBefore,
                            'balance_after' => $driverBalanceAfter,
                            'reference_type' => 'delivery_fee',
                            'reference_id' => (string) $order->id,
                            'description' => "Ongkos kirim pengantaran pesanan {$order->order_number}",
                        ]);
                    }
                }

                $adminUser = User::whereIn('role', ['admin', 'superadmin'])->first();
                if ($adminUser && $escrow->platform_portion > 0) {
                    $adminWallet = Wallet::firstOrCreate(
                        ['user_id' => $adminUser->id],
                        ['balance_available' => 0, 'balance_pending' => 0]
                    );

                    $adminBefore = (float) $adminWallet->balance_available;
                    $platformPortion = (float) $escrow->platform_portion;
                    $adminAfter = $adminBefore + $platformPortion;

                    $adminWallet->update(['balance_available' => $adminAfter]);

                    WalletMutation::create([
                        'wallet_id' => $adminWallet->id,
                        'type' => 'credit',
                        'amount' => $platformPortion,
                        'balance_before' => $adminBefore,
                        'balance_after' => $adminAfter,
                        'reference_type' => 'platform_fee',
                        'reference_id' => (string) $order->id,
                        'description' => "Fee platform koperasi untuk pesanan {$order->order_number}",
                    ]);
                }

                $escrow->update([
                    'status' => 'released',
                    'released_at' => now(),
                ]);
            }

            $merchantPortion = $escrow ? (float) $escrow->merchant_portion : (float) $order->subtotal;

            return [
                'success' => true,
                'message' => "Pesanan {$order->order_number} berhasil diselesaikan. Dana Rp " . number_format($merchantPortion, 0, ',', '.') . " telah tercatat.",
            ];
        });
    }
}
