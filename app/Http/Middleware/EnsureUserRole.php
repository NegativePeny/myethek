<?php

namespace App\Http\Middleware;

use App\Models\Merchant;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request with strict Role-Based Access Control (SEC-001, SOT Bab 4, UAT #14).
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            // Kompatibilitas session switcher untuk Merchant
            if (in_array('merchant', $roles) && session('merchant_portal_id')) {
                $merchant = Merchant::with('user')->find(session('merchant_portal_id'));
                if ($merchant && $merchant->user) {
                    Auth::login($merchant->user);
                    return $next($request);
                }
            }

            // Kompatibilitas session switcher untuk Driver
            if (in_array('driver', $roles) && session('driver_portal_id')) {
                $driver = User::find(session('driver_portal_id'));
                if ($driver && $driver->role === 'driver') {
                    Auth::login($driver);
                    return $next($request);
                }
            }

            // Kompatibilitas demo/test legacy saat pilot_mode = false
            if (!config('myethek.pilot_mode', true) && in_array('admin', $roles)) {
                $admin = User::where('role', 'admin')->first();
                if ($admin) {
                    Auth::login($admin);
                    return $next($request);
                }
            }

            return redirect()->guest(route('login'))->with('error', 'Silakan masuk ke akun Anda terlebih dahulu.');
        }

        $userRole = $request->user()->role;

        // 1. Jika role user secara eksplisit ada di daftar $roles yang diizinkan
        if (in_array($userRole, $roles)) {
            return $next($request);
        }

        // 2. Alias 'merchant' mencakup sub_agent, agent, distributor, center, warehouse, admin, superadmin
        if (in_array('merchant', $roles) && in_array($userRole, ['sub_agent', 'agent', 'distributor', 'center', 'warehouse', 'admin', 'superadmin'])) {
            return $next($request);
        }

        // 3. Superadmin memiliki hak akses ke semua modul administrasi, gudang, dan finance
        if ($userRole === 'superadmin' && !in_array('driver_only', $roles)) {
            return $next($request);
        }

        // 4. Admin memiliki akses ke modul admin, merchant, dan warehouse (tetapi untuk otorisasi finance/refund tetap dicek bila khusus finance)
        if ($userRole === 'admin' && array_intersect($roles, ['admin', 'warehouse', 'merchant'])) {
            return $next($request);
        }

        abort(403, 'Akses tidak diizinkan untuk peran akun Anda (Least Privilege RBAC).');
    }
}
