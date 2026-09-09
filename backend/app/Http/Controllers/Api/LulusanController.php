<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lulusan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LulusanController extends Controller
{
    /**
     * List rekap lulusan per tahun. Data barisnya sendiri
     * dibuat otomatis oleh scheduled job (freeze sebelum NKO),
     * bukan lewat endpoint ini - makanya gak ada store().
     */
    public function index(Request $request)
    {
        $query = Lulusan::query();

        if ($request->filled('tahun')) {
            $query->where('tahun', $request->tahun);
        }

        return response()->json($query->orderByDesc('tahun')->get());
    }

    /**
     * BK cuma boleh isi/ubah total_kerja & total_kuliah.
     * Kolom lain (tahun, total_cowo, total_cewe) hasil freeze,
     * gak boleh diutak-atik dari sini.
     */
    public function update(Request $request, $id)
    {
        $lulusan = Lulusan::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'total_kerja'  => 'sometimes|integer|min:0',
            'total_kuliah' => 'sometimes|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $lulusan->fill($request->only(['total_kerja', 'total_kuliah']));
        if ($lulusan->getDirty() === []) {
            return response()->json(['message' => 'Tidak ada field valid yang dikirim.'], 422);
        }
        $lulusan->save();

        return response()->json($lulusan);
    }
}
