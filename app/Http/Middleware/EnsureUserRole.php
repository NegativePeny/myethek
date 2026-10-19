<?php

namespace App\Http\Middleware;

use App\Models\Merchant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            // Kompatibilitas demo switcher: Jika session merchant_portal_id ada, sinkronkan login otomatis
            if (in_array('merchant', $roles) && session('merchant_portal_id')) {
                $merchant = Merchant::with('user')->find(session('merchant_portal_id'));
                if ($merchant && $merchant->user) {
                    Auth::login($merchant->user);
                    return $next($request);
                }
            }

            return redirect()->guest(route('login'))->with('error', 'Silakan masuk ke akun Anda terlebih dahulu.');
        }

        $userRole = $request->user()->role;

        // Izinkan jika merchant (sub_agent, agent, distributor, center)
        if (in_array('merchant', $roles) && in_array($userRole, ['sub_agent', 'agent', 'distributor', 'center'])) {
            return $next($request);
        }

        // Izinkan jika ada kecocokan role langsung
        if (in_array($userRole, $roles)) {
            return $next($request);
        }

        // Khusus admin selalu memiliki hak akses menyeluruh
        if ($userRole === 'admin') {
            return $next($request);
        }

        abort(403, 'Akses tidak diizinkan untuk peran akun Anda.');
    }
}
