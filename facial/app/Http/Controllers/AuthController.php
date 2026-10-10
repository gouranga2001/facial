<?php

namespace App\Http\Controllers;

use App\Models\Organisation;
use App\Models\Roles;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function showRegister()
    {
        // return view('auth.register');
    }

    // Public: creates an organisation and its first admin
    public function register(Request $request)
    {
        $validated = $request->validate([
            'organisation_name' => ['required', 'string', 'max:255'],
            'first_name'        => ['required', 'string', 'max:100'],
            'last_name'         => ['nullable', 'string', 'max:100'],
            'email'             => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'          => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $org = Organisation::create([
                'name' => $validated['organisation_name'],
                ]);
            
            $adminRoleId = Roles::where('slug', 'organisation_admin')->firstOrFail()->id;

            return User::create([
                'organisation_id' => $org->id,
                'role_id'         => $adminRoleId,
                'first_name'      => $validated['first_name'],
                'last_name'       => $validated['last_name'] ?? null,
                'email'           => $validated['email'],
                'password'        => $validated['password'], // hashed by the cast
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('login');
    }

    public function showLogin()
    {
        return view('login');
    }

    public function showSetup()
    {
        return view('setup');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials + ['status' => 'active'])) {
            return back()
                ->withErrors(['email' => 'Invalid credentials or inactive account.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = $request->user();

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        if ($user->hasRole('super_admin')) {
            return redirect()->intended('/admin/dashboard');
        }

        return redirect()->intended('/dashboard');
    }
    
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // return redirect()->route('login');
    }
}