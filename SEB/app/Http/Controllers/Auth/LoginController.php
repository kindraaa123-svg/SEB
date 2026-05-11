<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('login', [
            'title' => 'Login',
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $supervisor = DB::table('supervisor')
            ->where('username', $credentials['username'])
            ->first();

        if (! $supervisor || ! Hash::check($credentials['password'], $supervisor->password ?? '')) {
            return back()
                ->withErrors(['username' => 'Invalid username or password.'])
                ->withInput($request->except('password'));
        }

        $request->session()->regenerate();
        $request->session()->put('supervisor', [
            'id' => $supervisor->supervisorid ?? $supervisor->id ?? $supervisor->supervisor_id ?? null,
            'username' => $supervisor->username,
        ]);

        return redirect()->intended(route('home'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('supervisor');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
