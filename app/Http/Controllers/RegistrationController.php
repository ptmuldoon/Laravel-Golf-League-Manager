<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegistrationController extends Controller
{
    public function showRegistrationForm()
    {
        if (SiteSetting::get('registration_enabled', '1') !== '1') {
            return redirect()->route('home')->with('error', 'Registration is currently disabled.');
        }

        if (Auth::check()) {
            if (Auth::user()->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('player.dashboard');
        }

        return view('auth.register');
    }

    public function register(Request $request)
    {
        if (SiteSetting::get('registration_enabled', '1') !== '1') {
            return redirect()->route('home')->with('error', 'Registration is currently disabled.');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone_number' => 'nullable|string|max:20',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        // Check if an existing player matches this email
        $existingPlayer = Player::whereRaw('LOWER(email) = ?', [strtolower($validated['email'])])
            ->first();

        if ($existingPlayer && $existingPlayer->user) {
            return back()->withErrors(['email' => 'An account already exists for this email address.'])->withInput();
        }

        DB::transaction(function () use ($validated, $existingPlayer, &$user) {
            if ($existingPlayer) {
                $player = $existingPlayer;
            } else {
                $player = Player::create([
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'email' => $validated['email'],
                    'phone_number' => $validated['phone_number'] ?? null,
                ]);
            }

            $user = User::create([
                'name' => $existingPlayer
                    ? $existingPlayer->name
                    : trim($validated['first_name'] . ' ' . $validated['last_name']),
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'] ?? null,
                'password' => $validated['password'],
                'player_id' => $player->id,
                'is_admin' => false,
                'is_super_admin' => false,
            ]);
        });

        Auth::login($user);

        return redirect()->route('player.dashboard')->with('success', 'Welcome! Your account has been created.');
    }
}
