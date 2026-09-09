<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Login cuma pakai kode_akses (bukan Auth::attempt() standar,
     * karena gak ada identifier terpisah - kode itu sendiri identitasnya).
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode_akses' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Kode akses wajib diisi.',
            ], 422);
        }

        $kodeInput = $request->input('kode_akses');

        // Karena kode di-hash, gak bisa langsung WHERE kode_akses = $kodeInput.
        // Cuma ada 2 user (Bu Riau & Pak Adi), jadi loop check aman secara performa.
        $user = User::all()->first(function ($u) use ($kodeInput) {
            return Hash::check($kodeInput, $u->kode_akses);
        });

        if (! $user) {
            return response()->json([
                'message' => 'Kode akses salah.',
            ], 401);
        }

        // Hapus token lama biar gak numpuk (opsional, tergantung kebutuhan multi-device)
        // $user->tokens()->delete();

        $token = $user->createToken('bk-token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Logout - cabut token yang lagi dipakai request ini aja
     * (bukan semua token, biar device lain tetap login kalau ada).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    /**
     * Identitas BK yang lagi login - dipakai frontend buat
     * nampilin nama & mastiin jurnal yang keliatan cuma punya dia.
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
        ]);
    }
}
