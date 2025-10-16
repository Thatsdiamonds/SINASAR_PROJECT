<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Penjual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string|unique:users,username',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6'
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['role'] = 'seller';

        $user = User::create($data);

        Penjual::create([
            'user_id' => $user->id,
            'nama'    => $user->username
        ]);

        return response()->json(['message' => 'Register berhasil', 'user' => $user], 201);
    }

    public function registerByAdmin(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string|unique:users,username',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role'     => 'required|in:admin,seller'
        ]);

        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        if ($user->role === 'seller') {
            Penjual::create([
                'user_id' => $user->id,
                'nama'    => $user->username
            ]);
        }

        return response()->json(['message' => 'User dibuat oleh admin', 'user' => $user], 201);
    }

    // Login: terima field 'login' (username atau email) + password
    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string'
        ]);

        $login = $request->input('login');
        $user = User::where('username', $login)->orWhere('email', $login)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Username/email atau password salah.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $penjual = Penjual::where('user_id', $user->id)->first();

        return response()->json([
            'message' => 'Login berhasil',
            'token' => $token,
            'role' => $user->role,
            'lokasi' => $penjual ? $penjual->lokasi : null,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logout berhasil']);
    }

    // Get authenticated user profile
    public function profile(Request $request)
    {
        //Biar gampang pas frontend request ror
        $user = $request->user();
        $penjual = Penjual::find(1);

        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'penjualId' => $penjual->id,
            'created_at' => $user->created_at
        ]);
    }

    // Update user profile (admin only)
    public function updateUser(Request $request, $id)
    {
        // route yang memanggil method ini harus memakai middleware 'auth:sanctum' & 'role:admin'
        $user = User::findOrFail($id);

        $data = $request->validate([
            'username' => 'required|string|max:255|unique:users,username,' . $id,
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:6',
            'role' => 'nullable|in:admin,seller'
        ]);

        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json([
            'message' => 'User berhasil diupdate',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role
            ]
        ]);
    }

    // Delete user account (admin only)
    public function deleteUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Hapus foto-foto terkait jika ada
        if ($user->penjual) {
            if ($user->penjual->foto_profil) {
                Storage::disk('public')->delete($user->penjual->foto_profil);
            }
            if ($user->penjual->foto_kios) {
                Storage::disk('public')->delete($user->penjual->foto_kios);
            }
        }

        // Hapus user (cascade delete akan menghapus penjual terkait)
        $user->delete();

        return response()->json(['message' => 'User dan data terkait berhasil dihapus']);
    }
}
