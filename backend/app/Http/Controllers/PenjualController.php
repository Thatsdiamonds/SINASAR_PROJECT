<?php

namespace App\Http\Controllers;

use App\Models\Penjual;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PenjualController extends Controller
{
    public function index()
    {
        $data = Penjual::with('user')->get();

        $data->map(function ($penjual) {
            $penjual->foto_profil_url = $penjual->foto_profil
                ? url('storage/' . $penjual->foto_profil)
                : null;

            $penjual->foto_kios_url = $penjual->foto_kios
                ? url('storage/' . $penjual->foto_kios)
                : null;

            return $penjual;
        });

        return response()->json($data);
    }

    public function details($lokasi)
    {
        $data = Penjual::with('user')->where('lokasi', $lokasi)->firstOrFail();

        $data->owner_name = $data->user?->name ?? $data->user?->username ?? null;

        return response()->json([
            'message' => 'Data penjual ditemukan',
            'data' => $data
        ], 200);
    }


    public function show($id)
    {
        $penjual = Penjual::with('user')->findOrFail($id);

        $user = Auth::user();
        if ($user->role === 'seller' && $penjual->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($penjual);
    }

    public function update(Request $request, $id)
    {
        $penjual = Penjual::findOrFail($id);
        $user = Auth::user();

        if ($user->role === 'seller' && $penjual->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'nama' => 'nullable|string|max:255',
            'deskripsi' => 'nullable|string',
            'produk' => 'nullable|string',
            'lokasi' => 'nullable|string',
            'patokan' => 'nullable|string',
            'kontak' => 'nullable|string',
            'foto_profil' => 'nullable|image|mimes:jpg,jpeg,png',
            'foto_kios' => 'nullable|image|mimes:jpg,jpeg,png',
            'remove_foto_profil' => 'nullable|boolean',
            'remove_foto_kios' => 'nullable|boolean',
        ]);

        if (isset($data['lokasi']) && $data['lokasi'] !== $penjual->lokasi) {
            $existingPenjual = Penjual::where('lokasi', $data['lokasi'])
                ->where('id', '!=', $id)
                ->first();

            if ($existingPenjual) {
                return response()->json([
                    'message' => 'Posisi kios ' . $data['lokasi'] . ' sudah dipakai oleh seller lain',
                    'occupied_by' => $existingPenjual->nama ?? 'Seller lain'
                ], 422);
            }
        }

        if ($request->hasFile('foto_profil')) {
            if ($penjual->foto_profil) {
                Storage::disk('public')->delete($penjual->foto_profil);
            }
            $data['foto_profil'] = $request->file('foto_profil')->store('penjual/foto_profil', 'public');
        } elseif ($request->has('remove_foto_profil') && $request->remove_foto_profil) {
            if ($penjual->foto_profil) {
                Storage::disk('public')->delete($penjual->foto_profil);
            }
            $data['foto_profil'] = null;
        }

        if ($request->hasFile('foto_kios')) {
            if ($penjual->foto_kios) {
                Storage::disk('public')->delete($penjual->foto_kios);
            }
            $data['foto_kios'] = $request->file('foto_kios')->store('penjual/foto_kios', 'public');
        } elseif ($request->has('remove_foto_kios') && $request->remove_foto_kios) {
            if ($penjual->foto_kios) {
                Storage::disk('public')->delete($penjual->foto_kios);
            }
            $data['foto_kios'] = null;
        }

        $penjual->update($data);

        return response()->json(['message' => 'Penjual diupdate', 'data' => $penjual]);
    }

    public function destroy($id)
    {
        $penjual = Penjual::findOrFail($id);

        if ($penjual->foto_profil) {
            Storage::disk('public')->delete($penjual->foto_profil);
        }
        if ($penjual->foto_kios) {
            Storage::disk('public')->delete($penjual->foto_kios);
        }

        $penjual->delete();

        return response()->json(['message' => 'Penjual dihapus']);
    }

    public function getMyKios()
    {
        $user = Auth::user();

        if (!$user || !$user->penjual) {
            return response()->json(['message' => 'Data kios tidak ditemukan'], 404);
        }

        $penjual = $user->penjual;

        $penjual->foto_profil_url = $penjual->foto_profil
        ? url('storage/' . $penjual->foto_profil)
        : null;

        $penjual->foto_kios_url = $penjual->foto_kios
        ? url('storage/' . $penjual->foto_kios)
        : null;

        return response()->json($penjual);
    }

    public function updateMyKios(Request $request)
    {
        $penjual = Penjual::where('user_id', Auth::id())->firstOrFail();
        return $this->update($request, $penjual->id);
    }

    public function updateDenahSVG(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'denah_svg' => 'required|mimes:svg|max:2048',
        ]);

        if ($request->hasFile('denah_svg')) {
            Storage::disk('public')->makeDirectory('denah');

            if (Storage::disk('public')->exists('denah/pasar-owi.svg')) {
                Storage::disk('public')->delete('denah/pasar-owi.svg');
            }

            $path = $request->file('denah_svg')->storeAs(
                'denah',
                'pasar-owi.svg',
                'public'
            );

            return response()->json([
                'success' => true,
                'message' => 'Denah SVG berhasil diupload',
                'path' => Storage::url($path)
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Tidak ada file yang diupload'
        ]);
    }

    public function getDenahSVG()
    {
            return response()->json([
            'success' => true,
            'svg' => Storage::disk('public')->get('denah/pasar-owi.svg'),
            'url' => Storage::url('denah/pasar-owi.svg'),
        ]);
    }
}
