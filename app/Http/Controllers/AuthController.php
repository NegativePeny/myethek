<?php

namespace App\Http\Controllers;

use App\Models\Territory;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ], [
            'login.required' => 'Nomor HP atau Email wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $loginInput = trim($validated['login']);
        $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);

        $field = $isEmail ? 'email' : 'phone';

        $credentials = [
            $field => $loginInput,
            'password' => $validated['password'],
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withInput()->withErrors([
                    'login' => 'Akun Anda sedang dinonaktifkan. Silakan hubungi pengurus koperasi.',
                ]);
            }

            if ($user->territory_id) {
                session(['active_village_id' => $user->territory_id]);
            }

            return $this->redirectBasedOnRole($user);
        }

        return back()->withInput($request->only('login', 'remember'))->withErrors([
            'login' => 'Nomor HP/Email atau kata sandi yang Anda masukkan tidak sesuai.',
        ]);
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        $villages = Territory::where('level', 'village')
            ->with('parent')
            ->orderBy('name')
            ->get();

        return view('auth.register', [
            'villages' => $villages,
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20|unique:users,phone',
            'email' => 'nullable|email|max:150|unique:users,email',
            'territory_id' => 'required|exists:territories,id',
            'address' => 'required|string|max:255',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'phone.required' => 'Nomor WhatsApp / HP wajib diisi.',
            'phone.unique' => 'Nomor WhatsApp / HP ini sudah terdaftar.',
            'email.unique' => 'Alamat email ini sudah terdaftar.',
            'territory_id.required' => 'Pilih kelurahan / desa domisili Anda.',
            'address.required' => 'Alamat lengkap / patokan rumah wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $email = $validated['email'] ?? ('warga_' . preg_replace('/\D/', '', $validated['phone']) . '@myethek.id');
        $territory = Territory::find($validated['territory_id']);

        $user = User::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $email,
            'password' => Hash::make($validated['password']),
            'role' => 'customer',
            'customer_type' => 'b2c',
            'territory_id' => $validated['territory_id'],
            'address' => $validated['address'],
            'latitude' => $territory?->latitude,
            'longitude' => $territory?->longitude,
            'is_active' => true,
        ]);

        UserAddress::create([
            'user_id' => $user->id,
            'label' => 'Rumah Utama',
            'recipient_name' => $user->name,
            'recipient_phone' => $user->phone,
            'territory_id' => $user->territory_id,
            'full_address' => $user->address,
            'latitude' => $territory?->latitude,
            'longitude' => $territory?->longitude,
            'is_default' => true,
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        session(['active_village_id' => $user->territory_id]);

        return redirect()->route('storefront.index')->with(
            'success',
            "Selamat bergabung di Mak Ethek, {$user->name}! Silakan mulai belanja kebutuhan harian Anda."
        );
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('storefront.index')->with('success', 'Anda telah berhasil keluar.');
    }

    /**
     * Quick Role Switcher untuk Demo UAT (Hanya aktif di environment local/testing)
     */
    public function quickDemoSwitch(Request $request)
    {
        if (!app()->environment(['local', 'testing'])) {
            abort(403);
        }

        $email = $request->input('email');
        if (!$email && $request->filled('role')) {
            $roleMap = [
                'superadmin' => 'superadmin@myethek.id',
                'finance' => 'finance@myethek.id',
                'warehouse' => 'gudang@myethek.id',
                'admin' => 'admin@myethek.id',
                'driver' => 'driver.gedog@myethek.id',
                'b2b' => 'warung.b2b@myethek.id',
                'customer' => 'customer.gedog@myethek.id',
            ];
            $email = $roleMap[$request->input('role')] ?? 'customer.gedog@myethek.id';
        }

        $user = User::where('email', $email)->first() ?? User::firstOrFail();

        Auth::login($user);
        $request->session()->regenerate();

        if ($user->territory_id) {
            session(['active_village_id' => $user->territory_id]);
        }

        if ($request->filled('redirect')) {
            return redirect($request->input('redirect'));
        }

        return $this->redirectBasedOnRole($user);
    }

    protected function redirectBasedOnRole(User $user)
    {
        if (in_array($user->role, ['superadmin', 'finance'])) {
            return redirect()->intended(route('finance.index'))->with(
                'success',
                "Selamat datang di Portal Super Admin & Keuangan, {$user->name}!"
            );
        }

        if ($user->role === 'warehouse') {
            return redirect()->intended(route('warehouse.index'))->with(
                'success',
                "Selamat bertugas di Portal Admin Gudang, {$user->name}!"
            );
        }

        if ($user->role === 'admin') {
            return redirect()->intended(route('admin.index'))->with(
                'success',
                "Selamat datang di Portal Admin Koperasi, {$user->name}!"
            );
        }

        if (in_array($user->role, ['sub_agent', 'agent', 'distributor', 'center'])) {
            if ($user->merchant) {
                session(['merchant_portal_id' => $user->merchant->id]);
            }
            return redirect()->intended(route('merchant.index'))->with(
                'success',
                "Selamat datang di Portal Mitra, {$user->name}!"
            );
        }

        if ($user->isDriver()) {
            session(['driver_portal_id' => $user->id]);
            return redirect()->intended(route('driver.index'))->with(
                'success',
                "Selamat bertugas di Aplikasi Driver KurirQ Mak Ethek, {$user->name}!"
            );
        }

        return redirect()->intended(route('storefront.index'))->with(
            'success',
            "Selamat datang kembali, {$user->name}!"
        );
    }
}
