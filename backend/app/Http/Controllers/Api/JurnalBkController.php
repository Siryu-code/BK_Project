<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JurnalBk;
use App\Models\Kelas;
use App\Models\SnapshotSiswaHarian;
use App\Models\AbsensiDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JurnalBkController extends Controller
{
    /**
     * List jurnal - cuma punya user yang login (hak akses jurnal
     * dipisah per akun BK).
     */
    public function index(Request $request)
    {
        $query = JurnalBk::with('snapshotSiswaHarian.kelas')
            ->where('user_id', $request->user()->id);

        // filter opsional
        if ($request->filled('kelas_id')) {
            $query->whereHas('snapshotSiswaHarian', function ($q) use ($request) {
                $q->where('kelas_id', $request->kelas_id);
            });
        }

        $jurnal = $query->orderByDesc('id')->get();

        return response()->json(
            $jurnal->map(fn($j) => $this->formatJurnal($j))
        );
    }

    public function show(Request $request, $id)
    {
        $jurnal = JurnalBk::with('snapshotSiswaHarian.kelas')
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json($this->formatJurnal($jurnal));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kelas_id' => 'required|exists:kelas,id',
            'tanggal'  => 'required|date',
            'jam_ke'   => 'required|integer|min:1',
            'tema'     => 'nullable|string',
            'kasus'    => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $snapshot = SnapshotSiswaHarian::where('kelas_id', $request->kelas_id)
            ->where('tanggal', $request->tanggal)
            ->first();

        if (! $snapshot) {
            // Sengaja gak auto-generate di sini - snapshot itu representasi
            // kondisi siswa di tanggal tsb, kalau job harian belum jalan
            // buat tanggal itu, lebih aman kasih tau daripada nebak angkanya.
            return response()->json([
                'message' => 'Snapshot siswa untuk kelas & tanggal ini belum tersedia.',
            ], 422);
        }

        // cegah duplikat jam_ke di snapshot yang sama (selain lewat DB constraint)
        $sudahAda = JurnalBk::where('snapshot_siswa_harian_id', $snapshot->id)
            ->where('jam_ke', $request->jam_ke)
            ->exists();

        if ($sudahAda) {
            return response()->json([
                'message' => 'Sudah ada jurnal di jam ke tersebut untuk kelas & tanggal ini.',
            ], 422);
        }

        $jurnal = JurnalBk::create([
            'snapshot_siswa_harian_id' => $snapshot->id,
            'user_id' => $request->user()->id,
            'jam_ke'  => $request->jam_ke,
            'tema'    => $request->tema,
            'kasus'   => $request->kasus,
        ]);

        return response()->json($this->formatJurnal($jurnal->load('snapshotSiswaHarian.kelas')), 201);
    }

    public function update(Request $request, $id)
    {
        $jurnal = JurnalBk::where('user_id', $request->user()->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'kelas_id' => 'sometimes|exists:kelas,id',
            'tanggal'  => 'sometimes|date',
            'jam_ke'   => 'sometimes|integer|min:1',
            'tema'     => 'nullable|string',
            'kasus'    => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // kalau kelas/tanggal diubah, cari ulang snapshot yang cocok
        if ($request->filled('kelas_id') || $request->filled('tanggal')) {
            $kelasId = $request->input('kelas_id', $jurnal->snapshotSiswaHarian->kelas_id);
            $tanggal = $request->input('tanggal', $jurnal->snapshotSiswaHarian->tanggal);

            $snapshot = SnapshotSiswaHarian::where('kelas_id', $kelasId)
                ->where('tanggal', $tanggal)
                ->first();

            if (! $snapshot) {
                return response()->json([
                    'message' => 'Snapshot siswa untuk kelas & tanggal ini belum tersedia.',
                ], 422);
            }

            $jurnal->snapshot_siswa_harian_id = $snapshot->id;
        }

        $jurnal->fill($request->only(['jam_ke', 'tema', 'kasus']));

        if ($jurnal->getDirty() === []) {
            return response()->json(['message' => 'Tidak ada field valid yang dikirim.'], 422);
        }

        $jurnal->save();

        return response()->json($this->formatJurnal($jurnal->load('snapshotSiswaHarian.kelas')));
    }

    public function destroy(Request $request, $id)
    {
        $jurnal = JurnalBk::where('user_id', $request->user()->id)->findOrFail($id);
        $jurnal->delete(); // soft delete

        return response()->json(['message' => 'Jurnal dihapus.']);
    }

    /**
     * Format 1 jurnal + hitung total siswa / siswa masuk / siswa tidak
     * masuk on the fly dari snapshot & absensi_detail.
     */
    private function formatJurnal(JurnalBk $jurnal): array
    {
        $snapshot = $jurnal->snapshotSiswaHarian;

        // sakit, izin, alpa = tidak masuk. dispen & terlambat tetap dihitung berangkat.
        $tidakMasuk = AbsensiDetail::where('snapshot_siswa_harian_id', $snapshot->id)
            ->whereIn('tipe', ['sakit', 'izin', 'alpa'])
            ->get(['nama', 'nomor_absen', 'tipe', 'keterangan']);

        $totalSiswa = $snapshot->total_siswa_harian;
        $siswaMasuk = $totalSiswa - $tidakMasuk->count();

        return [
            'id'          => $jurnal->id,
            'kelas'       => $snapshot->kelas->nama_kelas,
            'tingkat'     => $snapshot->kelas->tingkat,
            'tanggal'     => $snapshot->tanggal->toDateString(),
            'jam_ke'      => $jurnal->jam_ke,
            'tema'        => $jurnal->tema,
            'kasus'       => $jurnal->kasus,
            'total_siswa' => $totalSiswa,
            'siswa_masuk' => $siswaMasuk,
            'siswa_tidak_masuk' => $tidakMasuk,
        ];
    }
}
