# Database Schema — Sistem Pendataan BK (Project PKL)

## `kelas`
Sumber data utama total siswa. Cuma 12 baris (3 tingkat x 4 TKJ), diupdate terus lewat NKO & pindah.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| nama_kelas | varchar | isi: TKJ 1 - TKJ 4 |
| tingkat | tinyint | 10 / 11 / 12 |
| jumlah_laki_laki | int | diupdate NKO & pindah |
| jumlah_perempuan | int | diupdate NKO & pindah |
| timestamps | | |

**Constraint:** unique (`nama_kelas`, `tingkat`)

---

## `snapshot_siswa_harian`
Foto total siswa per kelas, di-generate otomatis tiap hari (12 baris/hari). Jadi rujukan utama absensi & jurnal supaya gak salah ambil data historis kalau ada siswa pindah.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| kelas_id | FK → kelas | |
| tanggal | date | terpisah dari timestamp, buat query & index yang bersih |
| total_siswa_harian | int | hasil "foto" hari itu |
| timestamps | | |

**Constraint:** unique (`kelas_id`, `tanggal`)

---

## `jurnal_bk`
Diisi Bu Riau/Pak Adi tiap masuk jam mapel BK.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| snapshot_siswa_harian_id | FK → snapshot_siswa_harian | mewakili kelas + tanggal sekaligus (dipilih via kelas & tanggal di UI, di-resolve ke snapshot di backend) |
| user_id | FK → user | buat pisah hak akses jurnal per akun BK |
| jam_ke | tinyint | |
| tema | text | |
| kasus | text | |
| deleted_at | timestamp, nullable | soft delete |
| timestamps | | |

**Constraint:** unique (`snapshot_siswa_harian_id`, `jam_ke`)
**Turunan on-the-fly:** total siswa & siswa masuk (dari snapshot dikurangi absensi tanggal itu — sakit/izin/alpa dikurangi, dispen/terlambat tidak), nomor urut (diurutkan di frontend)
**Edit/delete:** bebas kapan saja, tanpa batasan.

---

## `absensi_detail`
Diisi sekretaris kelas.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| snapshot_siswa_harian_id | FK → snapshot_siswa_harian | |
| nama | varchar | bebas (tidak ada master data siswa) |
| nomor_absen | varchar | |
| tipe | enum | sakit / alpa / izin / terlambat / dispen |
| keterangan | text, nullable | opsional untuk semua tipe |
| tanggal_jam_mulai | datetime | default hari ini 07:00 |
| tanggal_jam_selesai | datetime | default hari ini 15:30 (Jumat 10:45) |
| deleted_at | timestamp, nullable | |
| timestamps | | |

**Edit/delete (sekre):** cuma untuk data hari ini, dan cuma kalau device/sesi terdeteksi sudah login.
**Edit/delete (BK):** bebas kapan saja.

---

## `tidak_naik_lulus`
Gabungan siswa tidak naik kelas & tidak lulus.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| kelas_id | FK → kelas | |
| nama | varchar | bebas |
| nomor_absen | varchar | |
| nis | varchar | |
| keterangan | varchar, nullable | |
| gender | enum | cowok / cewek |
| tahun_ajaran | smallint | **auto-computed** saat data dibuat (bukan manual) |
| status | enum | belum / sudah — default belum |
| deleted_at | timestamp, nullable | |
| timestamps | | |

**Edit:** hanya status "belum".
**Delete:** hanya status "belum" → masuk recycle bin. Di recycle bin, terhapus permanen otomatis kalau NKO sempat jalan selagi data ada di situ (cek via `nko_log`). Status "sudah" tidak bisa diapa-apain.
**NKO:** flip status belum→sudah + kurangi angka kelas 10 lama seperti biasa; angka kelas 10 baru TIDAK otomatis nambah dari data ini — Bu Riau input manual saat input siswa tahun ajaran baru.

---

## `lulusan`
Freeze data siswa kelas 12 sebelum NKO (level perangkatan, bukan perkelas).

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| tahun | YEAR | |
| total_cowo | int | difoto Mei/sebelum NKO |
| total_cewe | int | difoto Mei/sebelum NKO |
| total_kerja | int, nullable | diisi BK belakangan |
| total_kuliah | int, nullable | diisi BK belakangan |
| timestamps | | |

**Constraint:** unique (`tahun`)
**Edit:** hanya `total_kerja` & `total_kuliah`.
**Delete:** tidak bisa sama sekali.

---

## `pindah`
Siswa pindah masuk (in) / keluar (out).

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| nama / nomor_absen / nis / nisn | varchar | bebas |
| kelas_id | FK → kelas, nullable | terisi kalau sisi ini internal |
| sekolah_luar | varchar, nullable | terisi kalau sisi ini eksternal — "dari"/"ke" diatur di frontend berdasarkan tipe |
| tipe | enum | in / out |
| status | enum | belum / sudah — default belum (terpisah dari `tipe`) |
| gender | enum | cowok / cewek — dipakai untuk update angka di `kelas` |
| tanggal_efektif | date | default hari ini — **tidak bisa tanggal masa lalu** |
| deleted_at | timestamp, nullable | |
| timestamps | | |

**Eksekusi:** dijalankan sesuai `tanggal_efektif` (tidak terikat NKO, terikat kelas tujuan). Kalau tipe=in → +1 ke kelas tujuan; tipe=out → -1 dari kelas asal (sesuai gender). Kalau `tanggal_efektif` persis sama dengan tanggal NKO, NKO dieksekusi duluan.
**Edit/delete:** sama seperti `tidak_naik_lulus` (status belum → bisa; status sudah → tidak bisa).

---

## `nko_log`
Jejak eksekusi NKO — dipakai buat cek "NKO terakhir kapan" (referensi aturan recycle bin di `tidak_naik_lulus` & `pindah`).

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| tanggal_eksekusi | date/datetime | |
| timestamps | | |

---

## `user`
Migrasi default Laravel + kolom tambahan.

| Kolom | Tipe | Keterangan |
|---|---|---|
| ...kolom default Laravel... | | termasuk nama, dst |
| kode_akses | varchar, di-hash | dipakai sebagai kode login unik (bukan pakai kolom `password` bawaan) |

**Login:** BK input kode_akses saja (bukan Auth::attempt() standar) → sistem cari user via kode_akses, login manual.

---

## Catatan arsitektur
- Backend: Laravel (REST API) — Frontend: Vue.js — DB: MySQL, folder backend/frontend terpisah
- 2 domain frontend terpisah: BK & sekre (pertahanan pertama, bukan utama)
- Role: cuma 1 role (BK); sekre tanpa akun sama sekali
- Soft delete + recycle bin dipakai di modul yang punya rule delete bersyarat (`jurnal_bk`, `absensi_detail`, `tidak_naik_lulus`, `pindah`)
