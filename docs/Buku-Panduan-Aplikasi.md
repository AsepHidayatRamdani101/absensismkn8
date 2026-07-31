# Buku Panduan Aplikasi Absensi SMKN 8

## 1. Tujuan Aplikasi
Aplikasi ini digunakan untuk mengelola proses absensi dan pengajuan izin secara terintegrasi untuk beberapa peran:
- Siswa
- Guru
- Wali Kelas
- Kurikulum

Panduan ini fokus pada penggunaan fitur oleh pengguna akhir, bukan instalasi teknis server.

---

## 2. Istilah Penting
- Hadir: Status hadir normal.
- Sakit: Status sakit melalui mekanisme pengajuan izin/sakit.
- Izin: Status izin melalui mekanisme pengajuan izin/sakit.
- Alpa/Alpha: Tidak hadir tanpa keterangan.
- Terlambat: Hadir namun terlambat.
- Pengurus kelas: Siswa dengan jabatan KM, Sekretaris, atau Bendahara.

---

## 3. Alur Umum Login
1. Buka halaman login aplikasi.
2. Masukkan username dan password sesuai akun.
3. Sistem otomatis mengarahkan ke dashboard sesuai role.

Jika gagal login:
- Pastikan username dan password benar.
- Hubungi admin jika akun belum dibuat atau password lupa.

---

## 4. Panduan Siswa

### 4.1 Dashboard Siswa
Menu utama siswa menampilkan ringkasan data absensi pribadi dan akses cepat ke fitur lain.

### 4.2 Identitas Siswa
1. Buka menu Identitas Siswa.
2. Lengkapi data penting, terutama No HP Orang Tua.
3. Simpan perubahan.

Catatan:
- Beberapa menu absensi membutuhkan data identitas minimum agar bisa diakses.

### 4.3 Riwayat Absen Siswa
1. Buka menu Riwayat Absen.
2. Gunakan filter tanggal, status, atau guru-mapel.
3. Lihat hasil di tabel.
4. Gunakan tombol PDF/Excel jika diperlukan.

### 4.4 Pengajuan Izin/Sakit Siswa
1. Buka menu Pengajuan Izin/Sakit.
2. Isi jenis pengajuan (Izin/Sakit), tanggal mulai-selesai, alasan.
3. Upload foto surat.
4. Kirim pengajuan.
5. Pantau status: Menunggu, Disetujui, Ditolak.

Catatan:
- Verifikasi dilakukan oleh Wali Kelas.

### 4.5 Absensi Guru oleh Siswa Pengurus
Fitur ini hanya untuk pengurus kelas.

1. Buka menu Absensi.
2. Pilih jadwal guru.
3. Ambil foto guru melalui kamera.
4. Pilih aksi absensi sesuai kondisi.
5. Simpan.

Catatan:
- Aksi hanya tersedia sesuai ketentuan sistem dan status pengajuan izin guru.

### 4.6 Absensi Siswa Kelas oleh Siswa Pengurus
Fitur ini hanya aktif jika seluruh syarat berikut terpenuhi:
1. Guru pada jadwal tersebut sedang izin dan sudah Disetujui kurikulum.
2. Pengurus kelas sudah mengajukan izin absen kelas ke kurikulum.
3. Pengajuan pengurus sudah Disetujui kurikulum.

Alur:
1. Buka menu Absensi Siswa Kelas.
2. Pilih jadwal guru yang izinnya disetujui.
3. Jika izin pengurus belum ada, isi form pengajuan izin petugas lalu kirim ke kurikulum.
4. Setelah disetujui kurikulum, lakukan absensi per siswa atau massal.

---

## 5. Panduan Guru

### 5.1 Dashboard Guru
Menampilkan ringkasan kehadiran, jadwal, dan akses cepat ke menu operasional.

### 5.2 Absensi Siswa oleh Guru
1. Buka menu Absensi Siswa oleh Guru.
2. Pilih kelas sesuai jadwal hari ini.
3. Isi status siswa per siswa atau massal.

Ketentuan penting:
- Tombol Sakit dan Izin ditampilkan tetapi nonaktif.
- Pengisian status Sakit/Izin dilakukan melalui mekanisme pengajuan izin/sakit siswa yang disetujui Wali Kelas.
- Aksi yang aktif untuk guru: Hadir, Alpa, Terlambat.

### 5.3 Agenda Guru
1. Buka menu Agenda Guru.
2. Isi materi pembelajaran, catatan, dan informasi yang diminta.
3. Simpan agenda.

Catatan:
- Agenda mendukung proses rekap dan pelaporan administrasi.

### 5.4 Pengajuan Izin Guru
1. Buka menu Pengajuan Izin Guru.
2. Isi jenis izin, rentang tanggal, deskripsi tugas, dan lampiran jika diperlukan.
3. Kirim pengajuan.
4. Pantau status approval dari kurikulum.

### 5.5 Rekap Guru
Guru dapat melihat rekap absensi mapel yang diampu melalui menu rekap.

---

## 6. Panduan Wali Kelas

### 6.1 Akses Menu Wali Kelas
Wali Kelas memiliki menu khusus untuk pengajuan siswa dan rekap kelas.

### 6.2 Verifikasi Pengajuan Izin/Sakit Siswa
1. Buka menu Pengajuan Izin/Sakit Siswa.
2. Tinjau data pengajuan dan lampiran.
3. Pilih Setujui atau Tolak.
4. Isi catatan wali jika diperlukan.

Catatan:
- Saat disetujui, sistem akan menyesuaikan status absensi siswa sesuai periode pengajuan.

### 6.3 Input Hardfile Surat
Jika siswa terkendala upload:
1. Gunakan fitur input hardfile di menu wali kelas.
2. Input data pengajuan manual.
3. Simpan sebagai pengajuan yang diverifikasi.

### 6.4 Rekap Siswa Wali Kelas
Wali Kelas dapat melihat dan mengekspor rekap absensi siswa di kelas binaan.

---

## 7. Panduan Kurikulum

### 7.1 Dashboard Kurikulum
Menampilkan ringkasan:
- Pengajuan izin guru menunggu
- Izin absen kelas menunggu
- Ringkasan absensi lainnya

### 7.2 Approval Pengajuan Izin Guru
1. Buka menu approval izin guru.
2. Tinjau pengajuan.
3. Setujui atau tolak.
4. Berikan catatan bila perlu.

### 7.3 Approval Izin Absen Kelas (Pengurus)
1. Buka menu Approve Izin Absen Kelas.
2. Tinjau pengurus pemohon, jadwal, alasan, dan konteks guru.
3. Setujui atau tolak.
4. Isi catatan kurikulum bila perlu.

Dampak approval:
- Jika disetujui, pengurus kelas dapat melakukan absensi siswa kelas pada jadwal tersebut.
- Jika belum disetujui, aksi absensi siswa kelas tetap terkunci.

### 7.4 Laporan dan Rekap
Kurikulum dapat mengakses berbagai laporan:
- Laporan absensi guru
- Laporan absensi siswa
- Laporan agenda guru
- Rekap absensi guru (kombinasi)

---

## 8. Aturan Bisnis Penting
1. Absensi siswa oleh pengurus tidak bisa dilakukan sembarang waktu.
2. Absensi siswa oleh pengurus mensyaratkan izin guru sudah approved kurikulum.
3. Selain izin guru, pengurus juga wajib mengajukan izin absen kelas ke kurikulum.
4. Status Sakit/Izin pada absensi siswa oleh guru tidak diinput manual dari halaman absen guru.
5. Status Sakit/Izin siswa mengikuti alur pengajuan izin/sakit dan verifikasi wali kelas.

---

## 9. Troubleshooting

### 9.1 Menu Tidak Bisa Dibuka
Penyebab umum:
- Role akun tidak sesuai.
- Data identitas siswa belum lengkap.
- Syarat approval belum terpenuhi.

Solusi:
1. Periksa role dan hak akses.
2. Lengkapi identitas wajib.
3. Pastikan pengajuan terkait sudah Disetujui.

### 9.2 Tombol Aksi Disabled
Penyebab umum:
- Hari libur otomatis (Sabtu/Minggu).
- Jadwal belum dipilih.
- Approval belum selesai.

Solusi:
1. Pilih jadwal yang valid.
2. Pastikan status approval sesuai alur.
3. Ulangi pada hari sekolah aktif.

### 9.3 Export PDF Gagal karena Data Terlalu Besar
Solusi:
1. Gunakan filter jurusan/kelas.
2. Export ulang per bagian data.

---

## 10. Penutup
Buku panduan ini disusun sebagai acuan operasional harian. Jika ada perubahan fitur, lakukan pembaruan panduan agar alur tiap role tetap konsisten.
