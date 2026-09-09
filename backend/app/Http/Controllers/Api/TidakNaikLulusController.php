<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TidakNaikLulus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TidakNaikLulusController extends Controller
{
    public function index(Request $request)
    {
        $query = TidakNaikLulus::query();

        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', $request->kelas_id);
        }
        if ($request->filled('tahun_ajaran')) {
            $query->where('tahun_ajaran', $request->tahun_ajaran);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->orderByDesc('id')->get());
    }

    public function show($id)
    {
        return response()->json(TidakNaikLulus::findOrFail($id));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kelas_id'     => 'required|exists:kelas,id',
            'nama'         => 'required|string',
            'nomor_absen'  => 'required|string',
            'nis'          => 'required|string',
            'keterangan'   => 'nullable|string',
            'gender'       => 'required|in:cowok,cewek',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['kelas_id', 'nama', 'nomor_absen', 'nis', 'keterangan', 'gender']);
        $data['tahun_ajaran'] = $this->tahunAjaranSekarang();
        $data['status'] = 'belum';

        $item = TidakNaikLulus::create($data);

        return response()->json($item, 201);
    }

    public function update(Request $request, $id)
    {
        $item = TidakNaikLulus::findOrFail($id);

        if ($item->status !== 'belum') {
            return response()->json([
                'message' => 'Data berstatus "sudah" tidak bisa diedit.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'kelas_id'    => 'sometimes|exists:kelas,id',
            'nama'        => 'sometimes|string',
            'nomor_absen' => 'sometimes|string',
            'nis'         => 'sometimes|string',
            'keterangan'  => 'nullable|string',
            'gender'      => 'sometimes|in:cowok,cewek',
            // tahun_ajaran & status sengaja gak boleh diedit manual lewat endpoint ini
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $item->fill($request->only(['kelas_id', 'nama', 'nomor_absen', 'nis', 'keterangan', 'gender']));
        if ($item->getDirty() === []) {
            return response()->json(['message' => 'Tidak ada field valid yang dikirim.'], 422);
        }
        $item->save();

        return response()->json($item);
    }

    public function destroy($id)
    {
        $item = TidakNaikLulus::findOrFail($id);

        if ($item->status !== 'belum') {
            return response()->json([
                'message' => 'Data berstatus "sudah" tidak bisa dihapus.',
            ], 403);
        }

        $item->delete(); // soft delete -> masuk recycle bin

        return response()->json(['message' => 'Data dipindah ke recycle bin.']);
    }

    /* ================= RECYCLE BIN ================= */

    public function recycleBin()
    {
        return response()->json(TidakNaikLulus::onlyTrashed()->get());
    }

    public function restore($id)
    {
        $item = TidakNaikLulus::onlyTrashed()->findOrFail($id);
        // Kalau masih ada di sini berarti NKO belum sempat purge otomatis,
        // jadi aman buat direstore.
        $item->restore();

        return response()->json(['message' => 'Data dikembalikan dari recycle bin.']);
    }

    public function forceDelete($id)
    {
        $item = TidakNaikLulus::onlyTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Data dihapus permanen.']);
    }

    /* ================= HELPER ================= */

    /**
     * Tahun ajaran dari tanggal hari ini, cutoff bulan Juli.
     * Disimpan sebagai tahun mulai (mis. 2026 utk 2026/2027).
     */
    private function tahunAjaranSekarang(): int
    {
        $now = Carbon::now();
        return $now->month >= 7 ? $now->year : $now->year - 1;
    }
}
