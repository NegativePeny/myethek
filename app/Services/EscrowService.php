<?php

namespace App\Services;

use App\Models\EscrowTransaction;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMutation;
use Illuminate\Support\Facades\DB;

class EscrowService
{
    /**
     * Selesaikan pesanan dan lepaskan dana escrow (Double-entry ledger).
     *
     * @param Order $order
     * @param string|null $inputOtp Kode OTP yang dimasukkan kurir/pedagang untuk validasi
     * @return array [success => bool, message => string]
     */
    public function releaseEscrowForOrder(Order $order, ?string $inputOtp = null): array
    {
        // 1. Validasi OTP jika pesanan memiliki delivery dengan OTP
        if ($order->delivery && $order->delivery->completion_otp) {
            if ($inputOtp !== null && trim($inputOtp) !== $order->delivery->completion_otp) {
                return [
                    'success' => false,
                    'message' => 'Kode OTP tidak cocok. Mohon tanyakan 4-digit kode ke pelanggan.',
                ];
            }
        }

        $escrow = $order->escrow;
        if (!$escrow || $escrow->status !== 'held') {
            return [
                'success' => false,
                'message' => 'Dana escrow tidak ditemukan atau sudah pernah dicairkan.',
            ];
        }

        return DB::transaction(function () use ($order, $escrow) {
            // A. Update Status Order & Delivery
            $order->update([
                'status' => 'delivered',
                'payment_status' => 'paid',
            ]);

            if ($order->delivery) {
                $order->delivery->update([
                    'status' => 'delivered',
                    'delivered_at' => now(),
                ]);
            }

            // B. Kreditkan Dana ke Dompet Penjual (Merchant Owner)
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

            // C. Kreditkan Ongkir ke Dompet Driver (jika ada driver ditugaskan)
            if ($order->delivery && $order->delivery->driver_id) {
                $driverUser = User::find($order->delivery->driver_id);
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

            // D. Kreditkan Fee Platform ke Holding Koperasi Jawa Timur
            $adminUser = User::where('role', 'admin')->first();
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

            // E. Tandai Escrow Released
            $escrow->update([
                'status' => 'released',
                'released_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => "Pesanan {$order->order_number} berhasil diselesaikan. Dana Rp " . number_format($merchantPortion, 0, ',', '.') . " telah masuk ke dompet Anda.",
            ];
        });
    }
}
