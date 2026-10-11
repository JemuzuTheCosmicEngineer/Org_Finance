<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController
{
    // Shows the login page
    public function showLoginForm()
    {
        return view('welcome');   // <<< change 'welcome' to the name of YOUR login file in resources/views (without .blade.php)
    }

    // Handles the login form (the login page sends it with fetch and expects JSON back)
    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],   // the person's email OR name
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['username'])->first()
             ?? User::where('name', $data['username'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return $this->fail($request, 'Invalid Username or Password!');
        }

        if (($user->status ?? 'Active') !== 'Active') {
            return $this->fail($request, 'This account is inactive.');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'redirect' => route('dashboard')]);
        }

        return redirect()->route('dashboard');
    }

    // Logs the person out (the dashboard's Log Out button sends a POST here and expects JSON)
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['ok' => true]);
    }

    private function fail(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => false, 'message' => $message], 422);
        }

        return back()->withErrors(['username' => $message])->onlyInput('username');
    }
}