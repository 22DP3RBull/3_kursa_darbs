<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AdminRegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return Inertia::render('Register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'admin_code' => ['required', 'string'],
        ]);

        // Replace 'YOUR_SECRET_ADMIN_CODE' with your actual secret code
        if ($validated['admin_code'] !== env('ADMIN_REGISTER_CODE', 'YOUR_SECRET_ADMIN_CODE')) {
            return back()->withErrors(['admin_code' => 'Invalid admin registration code.'])->withInput();
        }

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('login')->with('success', 'Admin registered successfully. Please log in.');
    }
}
