<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbsensiDetail;
use App\Models\SnapshotSiswaHarian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AbsensiDetailController extends Controller
{
    /* =====================================================
     |  SISI BK - bebas lihat/create/edit/delete kapan saja
     * ===================================================== */

    public function index(Request $request)
    {
        $query = AbsensiDetail::with('snapshotSiswaHarian.kelas');

        if ($request->filled('kelas_id')) {
            $query->whereHas('snapshotSiswaHarian', fn($q) => $q->where('kelas_id', $request->kelas_id));
        }

        if ($request->filled('tanggal')) {
            $query->whereHas('snapshotSiswaHarian', fn($q) => $q->where('tanggal', $request->tanggal));
        }

        return response()->json($query->orderByDesc('id')->get());
    }

    /**
     * Ringkasan buat tampilan kalender: jumlah siswa tidak masuk
     * per tanggal, bisa difilter kelas & rentang bulan.
     */
    public function kalender(Request $request)
    {
        $query = AbsensiDetail::query()
            ->join('snapshot_siswa_harian', 'absensi_detail.snapshot_siswa_harian_id', '=', 'snapshot_siswa_harian.id')
            ->select('snapshot_siswa_harian.tanggal', DB::raw('count(*) as jumlah'));

        if ($request->filled('kelas_id')) {
            $query->where('snapshot_siswa_harian.kelas_id', $request->kelas_id);
        }

        if ($request->filled('bulan') && $request->filled('tahun')) {
            $query->whereMonth('snapshot_siswa_harian.tanggal', $request->bulan)
                ->whereYear('snapshot_siswa_harian.tanggal', $request->tahun);
        }

        $data = $query->groupBy('snapshot_siswa_harian.tanggal')->get();

        return response()->json($data);
    }

    public function store(Request $request)
    {
        return $this->simpanAbsensi($request, $request->input('tanggal'));
    }

    public function update(Request $request, $id)
    {
        $absen = AbsensiDetail::findOrFail($id);
        $this->validasiEntry($request, true);

        $absen->fill($request->only(['nama', 'nomor_absen', 'tipe', 'keterangan', 'tanggal_jam_mulai', 'tanggal_jam_selesai']));
        if ($absen->getDirty() === []) {
            return response()->json(['message' => 'Tidak ada field valid yang dikirim.'], 422);
        }
        $absen->save();

        return response()->json($absen);
    }

    public function destroy($id)
    {
        AbsensiDetail::findOrFail($id)->delete(); // soft delete
        return response()->json(['message' => 'Data absensi dihapus.']);
    }

    /* =====================================================
     |  SISI SEKRE - tanpa akun, cuma hari ini, cuma kelas
     |  yang lagi "dipilih" di sesi ini.
     * ===================================================== */

    /**
     * NOTE buat Kai: "terdeteksi sudah login" di sisi sekre ini
     * aku terjemahkan jadi "sesi udah pilih kelas ini" (session,
     * bukan akun). Begitu sekre pilih kelas di halaman select
     * class, endpoint ini dipanggil dulu buat nge-lock sesi ke
     * kelas itu. Edit/delete cuma bisa kalau session tersebut
     * masih nempel ke kelas yang sama. Confirm ke aku kalau
     * maksudmu beda dari ini.
     */
    public function masukKelas(Request $request, $kelasId)
    {
        $request->session()->put('sekre_kelas_id', $kelasId);
        return response()->json(['message' => 'Sesi kelas diatur.']);
    }

    public function todayByKelas(Request $request, $kelasId)
    {
        $this->pastikanSesiKelas($request, $kelasId);

        $snapshot = SnapshotSiswaHarian::where('kelas_id', $kelasId)
            ->where('tanggal', Carbon::today())
            ->first();

        if (! $snapshot) {
            return response()->json([]); // belum ada snapshot hari ini (job belum jalan)
        }

        $data = AbsensiDetail::where('snapshot_siswa_harian_id', $snapshot->id)->get();

        return response()->json($data);
    }

    public function storeSekre(Request $request)
    {
        $this->pastikanSesiKelas($request, $request->input('kelas_id'));

        return $this->simpanAbsensi($request, Carbon::today()->toDateString());
    }

    public function updateToday(Request $request, $id)
    {
        $absen = AbsensiDetail::with('snapshotSiswaHarian')->findOrFail($id);
        $this->pastikanSesiKelas($request, $absen->snapshotSiswaHarian->kelas_id);
        $this->pastikanDataHariIni($absen);

        $this->validasiEntry($request, true);
        $absen->fill($request->only(['nama', 'nomor_absen', 'tipe', 'keterangan', 'tanggal_jam_mulai', 'tanggal_jam_selesai']));
        if ($absen->getDirty() === []) {
            return response()->json(['message' => 'Tidak ada field valid yang dikirim.'], 422);
        }
        $absen->save();

        return response()->json($absen);
    }

    public function destroyToday(Request $request, $id)
    {
        $absen = AbsensiDetail::with('snapshotSiswaHarian')->findOrFail($id);
        $this->pastikanSesiKelas($request, $absen->snapshotSiswaHarian->kelas_id);
        $this->pastikanDataHariIni($absen);

        $absen->delete();
        return response()->json(['message' => 'Data absensi dihapus.']);
    }

    /* =====================================================
     |  HELPER
     * ===================================================== */

    /**
     * Dipakai bareng oleh store() BK maupun storeSekre() -
     * menerima banyak nama sekaligus dalam 1 request.
     */
    private function simpanAbsensi(Request $request, ?string $tanggal)
    {
        $validator = Validator::make($request->all(), [
            'kelas_id'  => 'required|exists:kelas,id',
            'entries'   => 'required|array|min:1',
            'entries.*.nama'         => 'required|string',
            'entries.*.nomor_absen'  => 'required|string',
            'entries.*.tipe'         => 'required|in:sakit,alpa,izin,terlambat,dispen',
            'entries.*.keterangan'   => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $snapshot = SnapshotSiswaHarian::where('kelas_id', $request->kelas_id)
            ->where('tanggal', $tanggal)
            ->first();

        if (! $snapshot) {
            return response()->json([
                'message' => 'Snapshot siswa untuk kelas & tanggal ini belum tersedia.',
            ], 422);
        }

        $isJumat = Carbon::parse($tanggal)->isFriday();
        $jamSelesaiDefault = $isJumat ? '10:45:00' : '15:30:00';

        $hasil = DB::transaction(function () use ($request, $snapshot, $tanggal, $jamSelesaiDefault) {
            $rows = [];
            foreach ($request->entries as $entry) {
                $rows[] = AbsensiDetail::create([
                    'snapshot_siswa_harian_id' => $snapshot->id,
                    'nama'         => $entry['nama'],
                    'nomor_absen'  => $entry['nomor_absen'],
                    'tipe'         => $entry['tipe'],
                    'keterangan'   => $entry['keterangan'] ?? null,
                    'tanggal_jam_mulai'   => $tanggal . ' 07:00:00',
                    'tanggal_jam_selesai' => $tanggal . ' ' . $jamSelesaiDefault,
                ]);
            }
            return $rows;
        });

        return response()->json($hasil, 201);
    }

    private function validasiEntry(Request $request, bool $partial = false)
    {
        $rules = [
            'nama'        => 'string',
            'nomor_absen' => 'string',
            'tipe'        => 'in:sakit,alpa,izin,terlambat,dispen',
            'keterangan'  => 'nullable|string',
        ];

        if ($partial) {
            $rules = collect($rules)->map(fn($r) => 'sometimes|' . $r)->all();
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            abort(response()->json(['errors' => $validator->errors()], 422));
        }
    }

    private function pastikanSesiKelas(Request $request, $kelasId)
    {
        if ((string) $request->session()->get('sekre_kelas_id') !== (string) $kelasId) {
            abort(response()->json(['message' => 'Sesi kelas tidak valid, pilih kelas dulu.'], 403));
        }
    }

    private function pastikanDataHariIni(AbsensiDetail $absen)
    {
        if (! $absen->snapshotSiswaHarian->tanggal->isToday()) {
            abort(response()->json(['message' => 'Data ini bukan untuk hari ini, tidak bisa diubah dari sisi sekre.'], 403));
        }
    }
}
