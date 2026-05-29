<?php
namespace App\Http\Controllers;

use App\Events\UserStatusChanged;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) return redirect()->route('conversations.index');
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Identifiants incorrects.']);
        }

        $request->session()->regenerate();
        $user = Auth::user();
        $user->update(['status' => 'online', 'last_seen_at' => now()]);
        event(new UserStatusChanged($user->id, 'online', now()->toISOString()));

        return redirect()->intended(route('conversations.index'));
    }

    public function showRegister()
    {
        if (Auth::check()) return redirect()->route('conversations.index');
        return Inertia::render('Auth/Register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users|alpha_dash',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'username' => $request->username,
            'email'    => $request->email,
            'password' => $request->password,
            'status'   => 'online',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('conversations.index');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        $user->update(['status' => 'offline', 'last_seen_at' => now()]);
        event(new UserStatusChanged($user->id, 'offline', now()->toISOString()));

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
