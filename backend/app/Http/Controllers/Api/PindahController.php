<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pindah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PindahController extends Controller
{
    public function index(Request $request)
    {
        $query = Pindah::with('kelas');

        if ($request->filled('tipe')) {
            $query->where('tipe', $request->tipe);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->orderByDesc('id')->get());
    }

    public function show($id)
    {
        return response()->json(Pindah::with('kelas')->findOrFail($id));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama'            => 'required|string',
            'nomor_absen'     => 'nullable|string',
            'nis'             => 'nullable|string',
            'nisn'            => 'nullable|string',
            'tipe'            => 'required|in:in,out',
            'kelas_id'        => 'required|exists:kelas,id',   // sisi internal (tujuan kalau in, asal kalau out)
            'sekolah_luar'    => 'required|string',            // sisi eksternal (asal kalau in, tujuan kalau out)
            'gender'          => 'required|in:cowok,cewek',
            'tanggal_efektif' => 'required|date|after_or_equal:today', // gak boleh masa lalu
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only([
            'nama',
            'nomor_absen',
            'nis',
            'nisn',
            'tipe',
            'kelas_id',
            'sekolah_luar',
            'gender',
            'tanggal_efektif',
        ]);
        $data['status'] = 'belum';

        $item = Pindah::create($data);

        // NOTE: +1/-1 ke tabel `kelas` SENGAJA gak dieksekusi di sini.
        // tanggal_efektif bisa hari ini atau masa depan, jadi eksekusi
        // beneran dilakukan oleh scheduled command EksekusiPindahTerjadwal
        // pas tanggal itu tiba (lihat pembahasan console.php sebelumnya).

        return response()->json($item->load('kelas'), 201);
    }

    public function update(Request $request, $id)
    {
        $item = Pindah::findOrFail($id);

        if ($item->status !== 'belum') {
            return response()->json([
                'message' => 'Data berstatus "sudah" tidak bisa diedit.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nama'            => 'sometimes|string',
            'nomor_absen'     => 'nullable|string',
            'nis'             => 'nullable|string',
            'nisn'            => 'nullable|string',
            'tipe'            => 'sometimes|in:in,out',
            'kelas_id'        => 'sometimes|exists:kelas,id',
            'sekolah_luar'    => 'sometimes|string',
            'gender'          => 'sometimes|in:cowok,cewek',
            'tanggal_efektif' => 'sometimes|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $item->fill($request->only([
            'nama',
            'nomor_absen',
            'nis',
            'nisn',
            'tipe',
            'kelas_id',
            'sekolah_luar',
            'gender',
            'tanggal_efektif',
        ]));
        if ($item->getDirty() === []) {
            return response()->json(['message' => 'Tidak ada field valid yang dikirim.'], 422);
        }
        $item->save();

        return response()->json($item->load('kelas'));
    }

    public function destroy($id)
    {
        $item = Pindah::findOrFail($id);

        if ($item->status !== 'belum') {
            return response()->json([
                'message' => 'Data berstatus "sudah" tidak bisa dihapus.',
            ], 403);
        }

        $item->delete(); // soft delete -> recycle bin

        return response()->json(['message' => 'Data dipindah ke recycle bin.']);
    }

    /* ================= RECYCLE BIN ================= */

    public function recycleBin()
    {
        return response()->json(Pindah::onlyTrashed()->with('kelas')->get());
    }

    public function restore($id)
    {
        $item = Pindah::onlyTrashed()->findOrFail($id);
        $item->restore();

        return response()->json(['message' => 'Data dikembalikan dari recycle bin.']);
    }

    public function forceDelete($id)
    {
        $item = Pindah::onlyTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Data dihapus permanen.']);
    }
}
