<?php

namespace App\Http\Controllers;

use App\Models\Territory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Tampilkan formulir login (Mobile-first Grofar)
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.login');
    }

    /**
     * Proses autentikasi user (mendukung Nomor HP atau Email)
     */
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

            // Sinkronkan desa aktif dengan desa domisili user jika ada
            if ($user->territory_id) {
                session(['active_village_id' => $user->territory_id]);
            }

            return $this->redirectBasedOnRole($user);
        }

        return back()->withInput($request->only('login', 'remember'))->withErrors([
            'login' => 'Nomor HP/Email atau kata sandi yang Anda masukkan tidak sesuai.',
        ]);
    }

    /**
     * Tampilkan formulir pendaftaran customer/warga
     */
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

    /**
     * Proses registrasi akun customer/warga
     */
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

        $user = User::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $email,
            'password' => Hash::make($validated['password']),
            'role' => 'customer',
            'territory_id' => $validated['territory_id'],
            'address' => $validated['address'],
            'is_active' => true,
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        session(['active_village_id' => $user->territory_id]);

        return redirect()->route('storefront.index')->with(
            'success',
            "Selamat bergabung di Mak Ethek, {$user->name}! Silakan mulai belanja kebutuhan harian Anda."
        );
    }

    /**
     * Proses logout user
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('storefront.index')->with('success', 'Anda telah berhasil keluar.');
    }

    /**
     * Redirect cerdas berdasarkan peran akun
     */
    protected function redirectBasedOnRole(User $user)
    {
        if ($user->isMerchant()) {
            if ($user->merchant) {
                session(['merchant_portal_id' => $user->merchant->id]);
            }
            return redirect()->intended(route('merchant.index'))->with(
                'success',
                "Selamat datang di Portal Mitra, {$user->name}!"
            );
        }

        if ($user->isDriver()) {
            return redirect()->intended(route('driver.index'))->with(
                'success',
                "Selamat bertugas di Portal Driver, {$user->name}!"
            );
        }

        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.index'))->with(
                'success',
                "Selamat datang di Audit Holding Koperasi, {$user->name}!"
            );
        }

        return redirect()->intended(route('storefront.index'))->with(
            'success',
            "Selamat datang kembali, {$user->name}!"
        );
    }
}
