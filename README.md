# AB Portal Hub

Frontend portal IT RS Awal Bros Botania. Root project adalah folder ini. Struktur mengikuti `architecture.md`: PHP native, controller, view, router, konfigurasi menu, dan koneksi PDO yang dibuat hanya ketika diperlukan.

Panduan untuk rekan yang melakukan clone dan menambah halaman: [Design System](docs/design-system.md). Gunakan dokumen tersebut sebagai acuan ukuran, komponen, serta pola penambahan controller, route, dan view.

## Menjalankan dashboard

Jalankan dari root project:

```powershell
npm run dev
```

Buka http://127.0.0.1:8080. Dashboard membaca data dari MySQL/MariaDB, jadi database harus sudah dimigrasi dan diisi seed lebih dulu (lihat [Database](#database)); tanpa itu halaman menampilkan "Database belum siap" (HTTP 503). Target PHP 8.2 atau lebih baru.

`npm run dev` hanya pintasan untuk server bawaan PHP (`php -S 127.0.0.1:8080 -t public public/index.php`) dan memakai `php` pertama di PATH. Dengan PHP XAMPP (zip, gd, dan fileinfo aktif): `C:\xampp\php\php.exe -S 127.0.0.1:8080 -t public public/index.php`. Node.js hanya diperlukan untuk `npm run build` (membangun CSS).

### Lewat XAMPP tanpa perintah (htdocs)

Salin seluruh folder project ke `C:\xampp\htdocs\<nama-folder>`, mis. `C:\xampp\htdocs\abportal`, nyalakan Apache dan MySQL di XAMPP Control Panel, lalu buka http://localhost/abportal. Portal mengenali foldernya sendiri: semua tautan, aset, form, dan ekspor memakai awalan `/abportal`, tanpa perlu mengisi `APP_URL`. File `.htaccess` di root project meneruskan setiap permintaan ke `public/`, sehingga `.env`, `app/`, `storage/` (berkas unggahan), `vendor/`, dan `.git` tidak bisa dibuka dari browser (mis. `/abportal/.env` menjawab 404). Bila `mod_rewrite` Apache mati, folder itu tidak disajikan sama sekali. Untuk server rumah sakit tetap arahkan web root ke `public/` ([docs/deploy.md](docs/deploy.md)).

Tanpa menyalin: buat junction `htdocs\abportal` yang menunjuk ke folder project (sudah dibuat di mesin pengembangan ini), sehingga perubahan kode langsung terlihat di http://localhost/abportal:

```powershell
New-Item -ItemType Junction -Path C:\xampp\htdocs\abportal -Target C:\Users\ASUS\AB-Portal-Hub
```

Hapus junction dengan `cmd /c rmdir C:\xampp\htdocs\abportal` (hanya tautannya yang hilang, isi project tetap). `npm run build` hanya perlu setelah mengubah CSS di `resources/css/`.

## Form dan rekap

Semua form adalah form dinamis: enam form monitoring (Hardware Server IT, Kesesuaian User, Suhu Server, CCTV, Clear Desk, Antrian Online) sudah tersedia dari `database/forms/monitoring.php`, form lain dibuat lewat **+ Tambah**. Semuanya diubah dengan cara yang sama di Edit Isi Form. Pertanyaan, jadwal, dan laporan tersimpan di database (`forms`, `form_versions`, `form_schedules`, `form_entries`).

- **Edit Isi Form** (`/form-editor?id=…`): tipe jawaban singkat, paragraf, pilihan ganda, kotak centang, drop-down, angka, tanggal, waktu, skala, rating, upload file, dan pernyataan; ditambah judul, bagian, dan gambar. Pengaturan per tipe: satuan (jawaban singkat, angka), maks. karakter, tinggi paragraf, min/maks dan bilangan bulat, jenis dan jumlah berkas. Per opsi: tanda **perlu perhatian** dan **pertanyaan lanjutan** (muncul hanya bila opsi itu dipilih); tiap pertanyaan bisa diatur **Tampilkan jika …** jawaban pertanyaan pilihan di atasnya. Simpan membuat versi baru bila pertanyaan berubah; versi lama tetap untuk laporan lama. **Pratinjau** membuka form yang belum disimpan di tab baru (tidak bisa dikirim). **Batal** dan hapus item meminta konfirmasi di modal.
- **Isi form** (`/form-dinamis?id=…`): tidak ada jawaban terpilih dan tidak ada isian yang disimpan di browser. Server memeriksa ulang semua jawaban (`app/Services/FormAnswers.php`). Nomor laporan `F<id>-YYYYMM-NNNN`. **Kosongkan form** dan meninggalkan halaman dengan isian meminta konfirmasi.
- **Rekap** (`/form-dinamis/respons?id=…`, dibuka di tab baru dari kartu dan dialog terkirim): tabel bergaya sheet (nomor baris, judul kolom dan no. entri tetap terlihat saat digulir), satu kolom per pertanyaan, filter di URL, detail, ubah, hapus dengan alasan, serta ekspor Excel, PDF, dan Cetak.
- **Perlu perhatian**: laporan yang memilih opsi bertanda merah.

Migrasi `2026_11_05_0010_custom_forms_only.php` menghapus enam form bawaan (Hardware Server, Kesesuaian User, Suhu, CCTV, Clear Desk, Antrian Online) beserta kartu, jadwal, dan versinya, mengosongkan semua laporan (`form_entries`, `number_sequences`), menandai berkas laporan sebagai terhapus, dan mengganti nama tabel Suhu/CCTV/Clear Desk menjadi `legacy_*` (data lama tetap bisa dibaca). Tidak bisa dibatalkan dengan `migrate.php down`; pulihkan dengan `php bin/restore.php <set>` dari backup sebelum migrasi.

Migrasi `2026_11_06_0011_monitoring_forms.php` menambahkan kembali keenam form monitoring itu sebagai form dinamis di urutan teratas bagian form (slug yang sudah dipakai dilewati). `migrate.php down` menghapusnya selama belum ada laporan.

## Progres dashboard (B7)

Semua angka di dashboard dihitung dari laporan yang tersimpan (`app/Services/Progress.php`), dengan aturan yang sama di server dan di browser (`public/assets/js/dashboard.js` menghitung ulang setelah kartu diubah).

- **Kapan form dihitung**: kartunya ada di bagian Monitoring Harian, tidak disembunyikan, dan jadwalnya jatuh tempo hari ini (WIB): harian; mingguan pada hari terpilih; bulanan pada tanggal itu (tanggal 29–31 jatuh ke hari terakhir bulan yang lebih pendek) atau hari terakhir bulan; tahunan pada tanggal itu (29 Februari hanya di tahun kabisat); tanggal tertentu; selalu di antara tanggal mulai dan berakhir bila diisi.
- **Kapan form selesai**: ada laporan bertanggal hari ini (WIB).
- **Panel progres** = jumlah penghitung bagian ("6 dari 7 form dijadwalkan hari ini · 5 selesai" → panel 5/6), jadi keduanya selalu sama. Status di kartu: Selesai, Batas 14:00, Belum Diisi (lewat batas dengan pengingat), Hari ini, Berikutnya: 2 Nov, Tidak terjadwal, atau Belum ada data. **Lihat Rekap** aktif setelah ada laporan.
- **Perlu perhatian** (FR-P6): lima laporan terbaru yang memilih opsi bertanda merah, terbaru dulu. Setiap baris membuka detail laporan di halaman rekapnya (`?buka=<id>`). Bagian ini tidak tampil bila kosong.
- Kembali ke dashboard dengan tombol Back browser setelah mengirim form memuat ulang halaman, sehingga progres langsung memakai laporan baru (FR-P4).
- B7 tidak memakai migrasi; tidak ada data yang berubah.

## Ekspor rekap (B8)

Setiap halaman rekap punya tombol **Ekspor Excel (.xlsx)**, **Ekspor PDF**, dan **Cetak** di kepala tabel, seperti desain. Ekspor selalu memakai filter yang sedang tampil (tanpa halaman dan jumlah per halaman), jadi isinya = semua baris yang cocok dengan filter, bukan hanya halaman yang terlihat.

- **Excel** (`app/Services/RecapExport.php`, OpenSpout): sheet **Data** dengan semua baris, baris judul dibekukan, filter otomatis, dan lebar kolom menyesuaikan isi. Angka tetap angka, waktu berupa tanggal Excel `dd/mm/yyyy hh:mm` (WIB). Sheet **Ringkasan**: rumah sakit, form, periode, filter yang dipakai (mis. "Laporan: Perlu perhatian"), waktu dibuat, jumlah baris, dan empat statistik rekap.
- **PDF** (mPDF): A4 lanskap, margin 15 mm, huruf IBM Plex Sans 9 pt yang ditanam (`resources/fonts`). Setiap halaman: logo, "RS Awal Bros Botania — Instalasi Teknologi Informasi", judul rekap, periode, dan filter; kaki "Dicetak 28 Okt 2026, 07:28 WIB · Halaman X dari Y" dan ID laporan. Isi: blok statistik, lalu tabel dengan judul kolom di setiap halaman; status sebagai teks. Di akhir: "Dibuat oleh," nama admin dan tanggal, serta "Diketahui oleh, Kepala Instalasi IT" dengan nama dari `REPORT_HEAD_OF_IT_NAME` (kosong = garis untuk tanda tangan); blok ini tidak terpotong antarhalaman.
- **Cetak**: PDF yang sama dibuka di tab baru dan langsung memanggil dialog cetak browser.
- **Nama file**: `<modul>_<YYYYMMDD>-<YYYYMMDD>_<YYYYMMDDHHmm>`, mis. `rekap-cek-server_20261001-20261031_202611011015.xlsx` (periode filter, lalu waktu dibuat dalam WIB). Tanpa filter periode dipakai tanggal laporan pertama dan terakhir.
- **Batas** (FR-E12, `config/report.php`): Excel 50.000 baris, PDF dan Cetak 2.000 baris. Di atas batas tombolnya nonaktif dengan pesan "Maksimal 2.000 baris untuk PDF. Persempit filter."; server juga menolak dengan pesan yang sama. Tombol nonaktif bila tidak ada data atau filter tidak valid.
- **Catatan aktivitas** (FR-E13): setiap ekspor ditulis ke `audit_logs` (`action = export`, `entity_type = rekap`) dengan modul, format, filter, jumlah baris, ID laporan, dan nama file. ID laporan (mis. `RPT-1EEF2C6406`) tercetak di kaki PDF, sehingga salinan kertas bisa dicocokkan dengan catatan ini.
- Ekspor Excel memerlukan ekstensi PHP `zip`; bila tidak aktif (mis. PHP 8.5 WinGet untuk `npm run dev`) halaman menampilkan "Ekspor Excel memerlukan ekstensi PHP zip…", sedangkan PDF dan Cetak tetap berjalan. File sementara dibuat di `storage/app/tmp` (tidak di-commit).
- B8 tidak memakai migrasi; tidak ada data yang berubah. Paket baru: `openspout/openspout` dan `mpdf/mpdf` (`composer install`).

## Riwayat aktivitas (B9)

Menu profil › **Riwayat aktivitas** membuka `/riwayat-aktivitas` (PRD SEC-12, keputusan Q18): semua catatan `audit_logs`, terbaru dulu, 50 per halaman. Kolom: waktu (WIB), aksi (Dibuat, Diubah, Dihapus, Diekspor, Masuk, Gagal masuk, Login dikunci, Keluar, Ganti password, dan aksi kartu dashboard), objek (modul dan no. entri, judul kartu, atau username), rincian (kolom yang berubah "lama → baru", alasan hapus, format ekspor dengan jumlah baris, ID laporan, dan filternya), serta IP dan perangkat. Filter: rentang tanggal, aksi, objek, dan pencarian (no. entri, ID laporan, judul, alasan), semuanya di URL. Halaman ini hanya membaca: tidak ada tombol atau route yang mengubah atau menghapus catatan. Di layar di bawah 768 px setiap catatan tampil sebagai kartu.

## Backup dan pemulihan (B9)

`php bin/backup.php` membuat satu **set backup** di `BACKUP_DIR`: folder `abportal-YYYYMMDD-HHMMSS` (WIB) berisi `database.sql.gz` (mysqldump seluruh database, termasuk `audit_logs`), `files.zip` (`storage/app/private`), dan `manifest.json` (ukuran dan SHA-256 setiap file). Password database diberikan ke `mysqldump` lewat file sementara, bukan baris perintah. Bila `BACKUP_ENCRYPTION_KEY` diisi, kedua file dienkripsi AES-256-GCM (`.enc`). Set ditulis ke folder `.partial-…` dan baru diberi nama akhir setelah lengkap, jadi backup yang gagal tidak pernah menggantikan backup yang baik. Setelah itu: salinan ke `BACKUP_COPY_DIR` (bila diisi), hanya `BACKUP_KEEP` (30) set terbaru yang disimpan di setiap lokasi (folder lain tidak disentuh), log error lebih dari 90 hari dihapus, dan peringatan bila disk backup terpakai 80 % atau lebih. Kode keluar: 0 berhasil, 1 gagal, 2 backup jadi tetapi salinan gagal.

Jadwal setiap malam pukul 01:00 (Windows Task Scheduler atau cron) ada di [docs/deploy.md](docs/deploy.md#6-backup-otomatis-nfr-4-nfr-5).

Pemulihan:

```powershell
php bin/restore.php abportal-20261101-010000 --database=abportal_restore_test --files-to=D:\restore-test\berkas
```

Skrip memeriksa checksum setiap file lebih dulu (set yang rusak ditolak), membuat database tujuan bila belum ada, meminta nama database diketik ulang, lalu memulihkan tabel (tabel bernama sama **diganti**) dan berkas (berkas bernama sama ditimpa; nama berkas di arsip diperiksa agar tidak keluar dari folder tujuan). Tanpa `--database` tujuannya `DB_DATABASE`; tanpa `--files-to` tujuannya `storage/app/private`. `--only=database` atau `--only=files` memulihkan salah satunya. Sebelum memulihkan database yang sedang dipakai, jalankan backup dulu dan hentikan web server.

Log error aplikasi ditulis per hari ke `storage/logs/app-YYYY-MM-DD.log` dengan waktu dan stack trace (NFR-13).

B9 tidak memakai migrasi; tidak ada data yang berubah.

## Dashboard (database)

Dashboard (`/`) digambar PHP dari tabel `dashboard_sections`, `dashboard_cards`, `forms`, `form_versions`, `form_schedules`, dan `form_schedule_dates` (`app/Models/Dashboard.php`). Setiap aksi di dashboard dikirim ke API JSON `DashboardController` dengan `fetch()` (header `X-CSRF-Token` dari `<meta name="csrf-token">`), lalu dashboard digambar ulang dari state yang dikembalikan server. Perubahan langsung tersimpan dan terlihat setelah muat ulang maupun di browser lain.

| Aksi | Route (POST, JSON) | Aturan di server |
| --- | --- | --- |
| Tambah form (tab Form) | `/dashboard/forms` | Judul wajib, maks. 60 karakter; deskripsi maks. 60; keterangan maks. 500; icon dan warna dari daftar. Form dinamis baru tanpa pertanyaan, jadwal harian mulai hari ini (WIB), nomor entri `F<id>` |
| Tambah tautan (tab Kategori & Tautan) | `/dashboard/links` | Judul wajib, maks. 40; alamat harus `http://` atau `https://` dengan host, tanpa spasi (ditolak: `javascript:`, `data:`, `file:`, alamat relatif); gambar icon harus `https://` |
| Kategori baru / hapus kategori | `/dashboard/sections`, `/dashboard/sections/{id}/delete` | Nama wajib, maks. 32, unik (huruf besar/kecil sama). Hanya kategori tambahan yang kosong yang dapat dihapus |
| Edit Kartu / Edit Tautan | `/dashboard/cards/{id}` | Aturan sama dengan tambah. Judul atau keterangan form yang berubah disimpan sebagai versi form baru |
| Sembunyikan / Tampilkan | `/dashboard/cards/{id}/hide`, `/show` | — |
| Pindah Kategori | `/dashboard/cards/{id}/move` | Hanya tautan; kartu pindah ke akhir kategori tujuan |
| Duplikat | `/dashboard/cards/{id}/duplicate` | Hanya kartu form; salinan menjadi form dinamis dengan pertanyaan, keterangan, dan jadwal yang sama, tepat setelah aslinya |
| Hapus | `/dashboard/cards/{id}/delete` | Kartu form hanya terhapus bila `confirm_title` sama persis dengan judulnya (diperiksa di server). Tidak ada yang dihapus permanen: kartu dan form diberi `deleted_at`, laporan tetap tersimpan |

Jawaban API: 200 (`message`, `state`, `id`), 401 sesi berakhir, 404 kartu/kategori tidak ada, 419 token CSRF salah, 422 (`errors` per field, ditampilkan di bawah field dialog), 500 bila database gagal (tidak ada yang tersimpan). Setiap perubahan dicatat di `audit_logs` dalam transaksi yang sama. Filter **Jadwal Hari Ini / Semua Form** tetap pilihan per browser (`localStorage`). Upload icon (PNG/JPG/WebP) belum dibuat; icon dipilih dari daftar atau dari alamat gambar `https://`.

Di bawah header, halaman form, editor form, dan riwayat menampilkan tombol **Kembali ke Dashboard** menuju `/` (controller mengirim `'backToDashboard' => true`). Riwayat keenam modul juga menampilkan breadcrumb bersama di kanan baris yang sama: `Portal Monitoring Terpadu / Monitoring Harian / Nama Modul / Nama Halaman`. Hanya portal menuju `/`; kategori, nama modul, dan halaman aktif tidak ditautkan. `DynamicFormController::history()` memakai helper `monitoring_breadcrumbs()` dengan nama dari `config/monitoring-navigation.php`. Kedua layout merender `app/Views/components/breadcrumbs.php`; jangan menulis ulang navigasi pada view atau navbar.

Perintah `npm run dev` menjalankan server PHP lokal. Hentikan dengan Ctrl+C. Untuk menjalankan tanpa npm, gunakan `php -S 127.0.0.1:8080 -t public public/index.php`.

CSS Tailwind dan font Inter sudah tersedia di `public/assets/`, sehingga portal tidak bergantung pada CDN maupun Node.js saat dijalankan. Dashboard, navbar, form, riwayat, akun, dan login memakai font dari sumber lokal yang sama.

## Mengembangkan tampilan

```powershell
npm ci
npm run build
```

Gunakan `npm run watch` (dashboard dan halaman `main.php`) atau `npm run watch:design` (halaman design system) selama mengubah view. Node.js hanya dipakai untuk membangun CSS. Ada dua bundel: `resources/css/app.css` → `public/assets/css/app.css` untuk halaman layout `main` (dashboard `/`, form, riwayat, dan editor form), dan `resources/css/design.css` → `public/assets/css/design.css` untuk halaman design system (login, akun, dan halaman error) agar token desain tidak mengubah tampilan dashboard. `npm run build` membangun keduanya. `package-lock.json` mengunci dependensi frontend. Panduan CLI: https://tailwindcss.com/docs/installation/tailwind-cli.

## Halaman

Halaman design system mengikuti desain statis di `docs/DESIGN/rs-awal-bros-portal/` (file desain tidak diubah). Dashboard dan daftar pertanyaan berasal dari database; laporan form belum (lihat di atas).

| Route | Controller | View |
| --- | --- | --- |
| `/login`, `POST /login` (publik), `POST /logout` | `AuthController` | `auth/login.php` (layout `auth`) |
| `/` | `HomeController::index` | `home/index.php` (dashboard portal, layout `main`) |
| `POST /dashboard/...` (lihat [Dashboard (database)](#dashboard-database)) | `DashboardController` | JSON |
| `/form-editor?id=…`, `POST /form-editor`, `POST /form-editor/pratinjau` | `FormEditorController::edit()`, `save()`, `preview()` | `form-editor/index.php` (Edit Isi Form: tab Pertanyaan dan Jadwal, layout `main`); simpan dalam JSON; pratinjau di tab baru |
| `/berkas/{id}` | `FileController::show()` | Berkas unggahan (hanya saat login) |
| `/form-dinamis?id=…`, `/form-dinamis/respons?id=…`, `/form-dinamis/respons/data?id=…` | `DynamicFormController::fill()`, `responses()`, `data()` | `form-dinamis/fill.php`, `form-dinamis/rekap-form.php` (layout `main`); data dalam JSON |
| `POST /form-dinamis/entri?id=…`, `/form-dinamis/entri/{id}/ubah`, `POST /form-dinamis/entri/{id}`, `POST /form-dinamis/entri/{id}/hapus`, `/form-dinamis/entri/{id}` | `DynamicFormController::store()`, `edit()`, `update()`, `destroy()`, `detail()` | Laporan form dinamis: simpan, ubah, hapus, detail (JSON) |
| `/password`, `POST /password` | `AccountController::password()`, `updatePassword()` | `account/password.php` (layout `design`) |

Route didaftarkan di `routes/web.php`. Alamat lama form bawaan (`/form/suhu`, `/rekap/cctv`, `/forms/…`, `/reports/…`) sudah tidak ada dan menjawab 404. Route API memakai pola `{id}` (angka positif) di `app/Core/Router.php`.

Semua route kecuali `/login` memerlukan sesi login (middleware `app/Core/RequireLogin.php`); pengunjung yang belum login diarahkan ke `/login` dengan HTTP 302, lalu kembali ke halaman yang diminta setelah login. Permintaan JSON tanpa sesi mendapat HTTP 401. File di `public/assets/` tidak diblokir. Kartu Monitoring Harian membuka halaman form-nya dan tombol **Lihat Rekap** membuka riwayatnya (aktif setelah ada laporan di database).

Form dan riwayat memakai `resources/css/gform.css` (form bergaya Google Forms) dan `resources/css/form-dinamis.css` (tipe jawaban tambahan dan halaman rekap); editor memakai `resources/css/form-editor.css`. Varian tombol `button-*` dan class `module-action` di `resources/css/module-ui.css` masih dipakai halaman akun, tombol Kembali ke Dashboard, dan dialog design system.

## Lokasi kode

| Lokasi | Fungsi |
| --- | --- |
| `public/index.php` | Front controller dan router untuk server lokal |
| `bootstrap/app.php` | Autoload, `.env`, dan zona waktu |
| `routes/web.php` | Route aplikasi |
| `app/Controllers/HomeController.php` | Data dashboard, pengelompokan menu, progres, dan daftar Perlu perhatian |
| `app/Services/Progress.php`, `app/Models/AttentionList.php` | Penghitung progres (bagian dan panel) dan laporan yang perlu perhatian (FR-P6) |
| `app/Controllers/DashboardController.php` | API JSON dashboard (tambah, edit, sembunyikan, pindah, duplikat, hapus) |
| `app/Models/Dashboard.php` | Query bagian, kartu, form, versi, dan jadwal (prepared statements) |
| `app/Services/CardInput.php` | Pemeriksaan isian dialog dashboard di server, termasuk alamat aman (`http`/`https`) |
| `app/Services/FileUpload.php`, `app/Models/Attachment.php`, `app/Controllers/FileController.php` | Pemeriksaan berkas unggahan, penyimpanan di `storage/app/private`, dan unduhan `/berkas/{id}` |
| `app/Services/FormToken.php` | Token sekali pakai per halaman form (tidak ada kiriman ganda) |
| `app/Helpers/Format.php` | Format tampilan: `15 Okt 2026, 10:18 WIB` dan desimal dengan koma |
| `public/assets/js/rekap.js` | Halaman rekap yang datanya dari server (filter di URL, urutan, statistik, detail, hapus); dialog detail dan hapus ada di `app/Views/components/recap-dialogs.php` |
| `app/Models/NumberSequence.php` | Nomor entri `<MOD>-YYYYMM-NNNN` untuk semua modul |
| `app/Views/home/index.php` | Dashboard portal |
| `app/Controllers/DynamicFormController.php`, `app/Models/FormEntries.php` | Form dinamis: halaman form dan riwayat, simpan, ubah, hapus, detail laporan |
| `app/Services/FormDefinition.php`, `FormAnswers.php`, `FormRecap.php` | Pemeriksaan isi editor (versi baru), pemeriksaan jawaban di server (termasuk `showIf`/`requiredWhen`), tampilan jawaban, filter dan statistik riwayat |
| `app/Controllers/FormEditorController.php` | Halaman Edit Isi Form dan simpannya (versi baru bila pertanyaan berubah, jadwal) |
| `app/Views/form-dinamis/fill.php`, `rekap-form.php` | Kerangka halaman form dan riwayat form dinamis; isinya digambar oleh script |
| `app/Services/RecapExport.php`, `config/report.php` | Ekspor rekap: Excel (OpenSpout), PDF dan Cetak (mPDF), batas baris, nama file, ID laporan, catatan di `audit_logs` |
| `app/Views/components/recap-export.php` | Tombol Ekspor Excel, Ekspor PDF, dan Cetak di kepala tabel rekap |
| `resources/fonts/` | IBM Plex Sans (TTF, lisensi SIL OFL 1.1) yang ditanam di PDF |
| `app/Controllers/AuditController.php`, `app/Models/AuditLogs.php`, `app/Services/ActivityLog.php`, `app/Views/account/activity.php` | Halaman Riwayat aktivitas (baca saja): filter, dan setiap catatan dalam kata-kata |
| `bin/backup.php`, `bin/restore.php`, `app/Services/Backup.php`, `config/backup.php` | Backup malam (database + berkas, enkripsi opsional, simpan 30, salinan kedua) dan pemulihan dengan pemeriksaan checksum |
| `app/Core/ErrorLog.php` | Log error harian `storage/logs/app-YYYY-MM-DD.log` dan penghapusan log lama |
| `docs/deploy.md` | Panduan deploy server (Apache/Nginx, HTTPS, firewall LAN/VPN, jadwal backup, daftar periksa keamanan dan go-live) |
| `app/Views/form-editor/index.php` | Kerangka editor form (tab Pertanyaan dan Jadwal) |
| `database/seeds/2026_10_29_dashboard.php` | Dashboard awal: 3 bagian, 6 form monitoring, dan 9 kartu tautan (hanya bila tabel masih kosong) |
| `database/forms/monitoring.php` | Pertanyaan, kartu, dan jadwal awal enam form monitoring (dipakai seed dan migrasi `2026_11_06_0011`) |
| `app/Helpers/DashboardData.php`, `Schedule.php` | State dashboard untuk browser, aturan jadwal (sama dengan `dashboard-store.js`), dan konversi jadwal ↔ `form_schedules` |
| `public/assets/js/dashboard-store.js` | State dari server, `api()` untuk fetch JSON, `ask()` (modal konfirmasi), ikon, toast, dan jadwal |
| `public/assets/js/dashboard.js` | Dashboard: kartu, filter jadwal, dialog Tambah, menu kartu; setiap aksi memanggil API |
| `public/assets/js/question-ui.js` | Tampilan setiap tipe pertanyaan (kartu, bagian, judul, gambar), dipakai halaman form dan pratinjau |
| `public/assets/js/form-dinamis.js` | Halaman form dan pratinjau: validasi, pertanyaan bersyarat, dan pengiriman ke server |
| `public/assets/js/form-editor.js` | Editor form: pertanyaan, jadwal, Pratinjau di tab baru, simpan ke server |
| `app/Views/layouts/` | Kerangka HTML, satu header bersama, dan footer |
| `app/Views/components/` | Komponen UI bersama: brand, kartu, progres, dialog, breadcrumb, dan dialog dashboard |
| `app/Views/components/breadcrumbs.php` | Breadcrumb bersama berdasarkan data controller |
| `config/dashboard.php` | Subjudul navbar dan nama cadangan bila akun tidak punya nama |
| `config/database.php` | Parameter koneksi MySQL dari environment (zona waktu sesi UTC) |
| `app/Core/Database.php` | Koneksi PDO dengan prepared statements native |
| `app/Core/Migrator.php`, `bin/migrate.php`, `database/migrations/` | Migrasi berversi (up/down/status/seed) |
| `app/Core/VerifyCsrf.php`, `app/Core/SecurityHeaders.php` | Pemeriksaan CSRF untuk setiap POST dan header keamanan |
| `app/Core/Audit.php`, `app/Core/Flash.php` | Change log (`audit_logs`) dan pesan satu kali setelah redirect |
| `app/Models/User.php`, `app/Services/LoginThrottle.php`, `app/Services/PasswordPolicy.php` | Akun admin, batas percobaan login, dan aturan password |
| `bin/create-admin.php` | Membuat akun admin atau mereset passwordnya dari server |
| `tests/Unit/`, `phpunit.xml.dist` | Tes unit (PHPUnit) |
| `docs/design.md` | Alasan desain dan batas implementasi |
| `app/Controllers/` | Controller halaman; `Controller.php` memakai layout `design` secara default |
| `app/Core/Auth.php`, `app/Core/RequireLogin.php` | Sesi login (batas 30 menit / 12 jam), token CSRF, dan middleware login |
| `app/Views/layouts/design.php`, `app/Views/layouts/auth.php` | Layout design system: `design.php` memakai navbar home dari `layouts/header.php`; `auth.php` untuk login tanpa navbar; breadcrumb, footer, dan toast mengikuti halaman |
| `app/Views/components/design-footer.php`, `page-header`, `card`, `badge`, `button`, `field`, `submit-modal`, `toast` | Partial design system |
| `app/Views/errors/design-404.php`, `design-unavailable.php` | Halaman 404 dan 503 versi design system (router dan `Controller.php`) |
| `app/Helpers/functions.php` | `e()`, `partial()`, `icon()` (ikon dashboard dan halaman `main.php`), `design_icon()` (ikon design system), `csrf_field()`, `url()`, `asset_v()` |
| `app/Helpers/icons.php` | Ikon Lucide (ISC) untuk `design_icon()` |
| `resources/css/app.css` | Gaya dashboard, form, riwayat, dan editor form (mengimpor `gform.css`, `dashboard.css`, `form-dinamis.css`, `form-editor.css`) |
| `resources/css/design.css` | Token design system (`@theme`), gaya dasar, dan komponen halaman design system |
| `public/assets/js/app.js` | Jam, dialog, dan menu dashboard serta halaman `main.php` |
| `public/assets/js/design.js`, `login.js` | Perilaku halaman design system (jam, menu, toast, login) |

## Perilaku tahap pertama

- Header, banner kerahasiaan, panel progres, 15 kartu dalam tiga kategori, serta footer telah dibuat mengikuti gambar dan HTML referensi.
- Jam dan tanggal mengikuti Asia/Jakarta dan diperbarui melalui JavaScript.
- Identitas IT Botania dan keterangan IT Division berasal dari `config/dashboard.php`. Keterangan departemen ini tidak menetapkan role atau hak akses pengguna.
- Panel progres dan penghitung di atas kartu Monitoring Harian dihitung dari laporan di server dengan satu aturan (lihat [Progres dashboard (B7)](#progres-dashboard-b7)), sehingga angkanya selalu sama.
- Logo memakai SVG yang diberikan pengguna di `public/assets/images/brand/rs-awal-bros.svg`, melalui komponen brand bersama pada semua halaman. Rasio asli 348:53 dipertahankan pada tinggi 32 px; URL logo diberi versi `filemtime` agar perubahan tidak tertahan cache. Teks satu baris digunakan hanya jika aset tidak tersedia. Path dapat diubah di `config/app.php`.
- Proporsi kartu berangkat dari `code.html`, lalu disesuaikan berdasarkan arahan pengguna: judul 11.7 px, deskripsi 10.8 px, badge 9 px, padding 16 px, dan ikon 48 px. Grid enam/tujuh kolom aktif mulai lebar 1024 px.
- Halaman memakai alur vertikal alami, tanpa tinggi tetap 100vh atau pemadatan kartu berdasarkan tinggi layar. Semua kartu kembali vertikal dengan ikon 48 px. Container desktop memakai lebar 90%, maksimal 1440 px, agar margin kiri dan kanan lebih lapang. Navbar, banner, dan hero tetap ringkas; bagian Sistem berada setelah Monitoring & Laporan dan dapat dijangkau dengan scroll.
- Semua kartu memakai tiga baris subgrid bersama untuk ikon, judul, dan deskripsi. Judul diratakan ke atas dan tinggi barisnya mengikuti judul terpanjang pada satu baris kartu, tanpa minimum dua baris. Deskripsi memakai margin atas 2 px dan tetap sejajar antar kartu; ruang tersisa di bawah judul pendek menjaga perataan kedua teks. Padding serta kotak ikon 48 px tetap sama; hover tidak menggeser kartu atau memperbesar ikon.
- Kartu form membuka halaman form-nya; tombol Lihat Rekap pada kartu membuka rekapnya di tab baru. Menu ⋯ pada kartu berisi Lihat Rekap, Edit Isi Form, Edit Kartu, Duplikat, Sembunyikan, dan Hapus.
- Kartu tautan tanpa alamat menampilkan "Alamat belum diatur" sampai URL diisi lewat menu ⋯ › Edit. URL `http`/`https` yang valid dibuka di tab baru dengan `noopener noreferrer`.
- Menu profil (nama di kanan header) berisi Ubah password, Riwayat aktivitas, dan Logout (`POST /logout` dengan token CSRF, kembali ke `/login`); bisa dipakai dengan keyboard (panah, Esc). Tidak ada halaman Users. Nama di navbar berasal dari akun yang sedang masuk.
- Badge `Portal aktif` merujuk pada halaman portal, bukan uptime sistem eksternal.
- Dialog bisa ditutup menggunakan Escape, tombol tutup, tombol kembali, atau klik di luar dialog. Fokus dikelola oleh elemen `<dialog>` browser.
- Daftar kategori tanpa menu memiliki empty state; route yang tidak ada mengembalikan 404, kegagalan render mengembalikan 500.
- Tampilan awal dashboard dirender langsung oleh PHP. Aksi di dashboard dikirim ke server; selama satu aksi berjalan, tombol Simpan dinonaktifkan dan klik aksi lain diabaikan. Bila gagal, toast merah menjelaskan sebabnya (mis. "Sesi berakhir", "Permintaan ditolak") dan tidak ada yang berubah.

## Login dan keamanan

Portal memakai satu akun admin di tabel `users` (tanpa peran dan tanpa halaman Users).

- **Login**: kolom Username dicocokkan dengan `users.username`, password diperiksa dengan `password_verify`. Username tidak dikenal dan password salah memberi pesan yang sama, "Username atau password salah." Hash lama diperbarui otomatis (`password_needs_rehash`).
- **Batas percobaan**: 5 kali gagal dalam 15 menit untuk satu username dari satu IP mengunci login pasangan itu selama 15 menit, walaupun password berikutnya benar ("Terlalu banyak percobaan. Coba lagi dalam 15 menit."). Satu IP juga dikunci setelah 20 kegagalan dalam 15 menit untuk username apa pun. Percobaan dicatat di `login_attempts`.
- **Sesi**: id sesi diganti saat login; keluar otomatis setelah 30 menit tanpa aktivitas atau 12 jam sejak login. Cookie `HttpOnly`, `SameSite=Lax`, dan `Secure` bila portal memakai HTTPS.
- **Ubah password** (menu Password, `POST /password`): password saat ini, password baru, dan konfirmasi. Password baru minimal `PASSWORD_MIN_LENGTH` karakter (bawaan 12, paling kecil 8), tidak sama dengan username atau password saat ini. Setelah diubah, perangkat lain yang masih masuk dikeluarkan (`users.session_version`).
- **Reset password tanpa login**: jalankan `php bin/create-admin.php` di server dengan username yang sama; semua perangkat ikut dikeluarkan.
- **CSRF**: setiap `POST` (termasuk login) wajib membawa token sesi: field `_token` (`csrf_field()`) atau header `X-CSRF-Token` untuk `fetch()`. Token salah atau hilang ditolak (halaman: kembali dengan pesan; JSON: HTTP 419).
- **Header keamanan** (`app/Core/SecurityHeaders.php`): Content-Security-Policy tanpa script/style inline, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy`, dan HSTS bila HTTPS. Jangan menambah `<script>` atau atribut `style` inline di view; set ukuran dinamis lewat JavaScript.
- **Change log** (`audit_logs`, `App\Core\Audit`): login, login gagal, login terkunci, logout, ubah password, dan pembuatan/reset admin dicatat dengan waktu (UTC), IP, dan user agent, tanpa password. Aplikasi tidak punya jalur untuk mengubah atau menghapus log ini.

Dengan `APP_ENV` selain `local` (server) semua halaman terbuka setelah login; detail error tidak pernah ditampilkan ke pengunjung, dan tidak ada login tanpa password. Panduan memasang di server rumah sakit: [docs/deploy.md](docs/deploy.md).

Pada Apache, arahkan document root ke `public/` dan aktifkan `mod_rewrite`. `.env` di root project dibaca otomatis oleh `bootstrap/app.php`; variabel environment server tetap diutamakan. Struktur saat ini menggunakan path absolut dari origin dan ditujukan untuk root domain atau virtual host, bukan subfolder URL.

## Konfigurasi (.env)

Salin `.env.example` menjadi `.env`. `.env` tidak boleh di-commit.

| Kunci | Isi |
| --- | --- |
| `APP_ENV` | `local` di PC pengembang; nilai lain (mis. `production`) di server |
| `APP_URL` | Alamat portal tanpa garis miring di akhir, mis. `https://portal-it.rs.local`. Kosong = dikenali otomatis: root situs, atau foldernya bila project ada di dalam `htdocs` (mis. `/abportal`) |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Koneksi MySQL/MariaDB |
| `PASSWORD_MIN_LENGTH` | Panjang minimal password (bawaan 12, paling kecil 8) |
| `BACKUP_DIR`, `BACKUP_COPY_DIR`, `BACKUP_KEEP` | Folder backup (di luar folder project; kosong = `AB-Portal-Hub-backups` di sebelah project), salinan kedua di mesin lain/NAS (NFR-5), jumlah set yang disimpan (bawaan 30) |
| `BACKUP_ENCRYPTION_KEY` | Kunci AES-256 opsional untuk backup (`php bin/backup.php key`). Simpan salinannya di luar server: tanpa kunci, backup terenkripsi tidak bisa dipulihkan |
| `MYSQL_BIN_DIR` | Folder `mysqldump` dan `mysql`, mis. `C:\xampp\mysql\bin` (kosong = dari PATH) |
| `LOG_KEEP_DAYS` | Umur log error harian di `storage/logs` sebelum dihapus oleh backup malam (bawaan 90) |
| `REPORT_HEAD_OF_IT_NAME` | Nama di bawah "Diketahui oleh, Kepala Instalasi IT" pada PDF dan Cetak (keputusan Q15). Kosong = garis kosong untuk tanda tangan |

Waktu disimpan di database dalam **UTC** (sesi koneksi `+00:00`) dan ditampilkan dalam WIB (`config/app.php`). `DB_TIMEZONE` tidak dipakai lagi.

## Database

Prasyarat: PHP 8.2 atau lebih baru dengan `pdo_mysql` dan `mbstring`, serta `fileinfo` dan `zip` untuk pemeriksaan berkas unggahan (tanpa keduanya berkas tetap diperiksa dari isinya, tetapi lebih longgar); `zip` juga wajib untuk ekspor Excel dan `gd` untuk PDF (logo); Composer; MariaDB 10.4+ atau MySQL 8. Batas unggah di `php.ini`: `upload_max_filesize = 12M`, `post_max_size = 64M`, `max_file_uploads = 20`. PHP XAMPP sudah memenuhi (40M, `fileinfo` dan `zip` aktif); PHP 8.5 dari WinGet yang dipakai `npm run dev` masih 2M/8M tanpa `fileinfo`/`zip`, jadi berkas di atas 2 MB ditolak di sana.

1. Salin `.env.example` menjadi `.env`, lalu isi koneksi database.
2. `composer install` (memasang autoload, OpenSpout dan mPDF untuk ekspor, dan PHPUnit untuk tes; `vendor/` tidak di-commit).
3. Terapkan migrasi: `php bin/migrate.php` (atau `composer db:migrate`). Perintah lain: `php bin/migrate.php status`, `php bin/migrate.php down` (membatalkan batch terakhir, minta ketik `YA`).
4. Isi dashboard awal: `php bin/migrate.php seed` (3 bagian, 6 form monitoring, dan 9 kartu tautan; dilewati bila dashboard sudah berisi data, jadi aman dijalankan ulang).
5. Periksa koneksi dan daftar tabel: `php bin/db-check.php` (atau `composer db:check`).
6. Buat akun admin: `php bin/create-admin.php` (atau `composer admin:create`). Skrip menanyakan username, nama tampilan, email, dan password (tersembunyi, diulang). Jalankan ulang dengan username yang sama untuk mengganti password. Portal hanya memakai satu admin: skrip menolak membuat akun kedua. Jalankan dari PowerShell atau CMD; di Git Bash pakai `winpty php bin/create-admin.php`.

Migrasi ada di `database/migrations/` (`YYYY_MM_DD_NNNN_nama.php`, berisi fungsi `up` dan `down`) dan dicatat di `schema_migrations` dengan nomor batch. `database/schema.sql` adalah skema dasar yang dijalankan oleh migrasi `0001_baseline`; jangan mengimpornya sendiri lagi.

Migrasi `2026_10_29_0003_dashboard` (B2) membuat tabel `forms`, `form_versions`, `form_schedules`, `form_schedule_dates`, `dashboard_sections`, `dashboard_cards`, `number_sequences`, dan kolom `attachments.field`. Tabel lama tidak dihapus, hanya diganti nama: `dashboard_sections`/`dashboard_cards` milestone M1 menjadi `m1_dashboard_sections`/`m1_dashboard_cards`, dan `forms`/`form_submissions` dari `schema.sql` menjadi `legacy_forms`/`legacy_form_submissions`. `down` menghapus tabel baru (semua kartu dan form yang dibuat sejak itu hilang) lalu mengembalikan nama tabel lama.

Migrasi `2026_10_30_0004_temperature` (B3) hanya menambah tabel kosong `temperature_entries`. `down` menghapus tabel itu (semua entri suhu sejak itu hilang; berkas di `storage/app/private` dan baris `attachments` tetap).

Migrasi `2026_10_31_0005_cctv` (B4) hanya menambah dua tabel kosong: `cctv_entries` dan `cctv_broken_cameras`. `down` menghapus keduanya (semua entri CCTV sejak itu hilang; berkas di `storage/app/private` dan baris `attachments` tetap).

Migrasi `2026_11_01_0006_cleardesk` (B5) hanya menambah dua tabel kosong: `cleardesk_sessions` dan `cleardesk_rows`. `down` menghapus keduanya (semua sesi Clear Desk sejak itu hilang; foto di `storage/app/private` dan baris `attachments` tetap).

Migrasi `2026_11_02_0007_form_entries` (B6) hanya menambah tabel kosong `form_entries` (laporan form dinamis). Penyimpanan di Edit Isi Form menambah baris ke tabel `form_versions` yang sudah ada (versi lama tidak diubah). `down` menghapus `form_entries` (semua laporan form dinamis sejak itu hilang; versi form, berkas di `storage/app/private`, dan baris `attachments` tetap).

### Backup sebelum migrasi dan cara membatalkan

Sebelum migrasi yang mengubah data, buat backup (`--result-file` menjaga file tetap UTF-8 di PowerShell):

```powershell
C:\xampp\mysql\bin\mysqldump.exe -h 127.0.0.1 -P 3306 -u <DB_USERNAME> -p --single-transaction --routines --triggers --default-character-set=utf8mb4 --result-file=C:\backup\abportal_db-YYYYMMDD.sql abportal_db
```

Membatalkan migrasi terakhir: `php bin/migrate.php down` (ketik `YA`). Mengembalikan seluruh database dari backup (PowerShell tidak mengenal `<`, jadi file dibaca lewat `source`):

```powershell
C:\xampp\mysql\bin\mysql.exe -h 127.0.0.1 -P 3306 -u <DB_USERNAME> -p --default-character-set=utf8mb4 abportal_db -e "source C:/backup/abportal_db-YYYYMMDD.sql"
```

Simpan file backup di luar folder project. Berkas unggahan tidak ada di dump database: salin juga folder `storage/app/private` (mis. `robocopy C:\path\AB-Portal-Hub\storage\app\private C:\backup\berkas-YYYYMMDD /E`). Atau pakai `php bin/backup.php`, yang mencakup database dan berkas sekaligus (lihat [Backup dan pemulihan (B9)](#backup-dan-pemulihan-b9)).

## Tes otomatis

`composer test` (atau `php vendor/bin/phpunit`) menjalankan tes unit di `tests/Unit`: batas percobaan login, aturan password, batas waktu sesi, pemeriksaan CSRF, CSP, alat migrasi, pemeriksaan alamat dan isian dialog dashboard, konversi jadwal ↔ database, route `{id}`, status suhu dan kelembapan (semua nilai uji PRD FR-T2, FR-T3, FR-T5), pemeriksaan form suhu, berkas unggahan (`.exe` yang diganti nama menjadi `.pdf` dan file 12 MB ditolak), filter, statistik, dan kelengkapan rekap suhu, serta modul CCTV: tanpa shift (shift dari halaman lama diabaikan, C1), kamera berfungsi dan persen ketersediaan (FR-C1), status entri (semua nilai uji FR-C2), retensi di bawah 30 hari = NVR Kritis, pemeriksaan baris kamera (IP, zona, Lainnya, duplikat), filter, statistik, dan panel pemeriksaan hari ini (Sudah/Belum diisi, satu baris per pemeriksaan), serta modul Clear Desk: format MAC (`:` `-` `.` atau tanpa pemisah), tingkat kepatuhan (nilai uji FR-D5: 142 baris, 137 Patuh → 96,5 %), minimal 1 workstation (FR-D4), pemeriksaan baris (MAC ganda, lokasi, IP, OS Lainnya, kategori dan keterangan Temuan), 200 baris tanpa batas, filter (cari MAC `1a:2b`), dan statistik dengan warna target, serta form dinamis: pemeriksaan isi editor (tipe dan pengaturan yang dikenal saja, gambar `javascript:` ditolak, syarat tampil yang tidak valid dibuang), pemeriksaan jawaban di server untuk semua tipe jawaban termasuk `showIf`/`requiredWhen` dan berkas, serta riwayat yang menampilkan laporan versi 1 dengan pertanyaan versinya sendiri. Progres dashboard: jadwal jatuh tempo untuk setiap tipe (harian, mingguan termasuk Minggu, bulanan tanggal 31 di Februari dan hari terakhir di tahun kabisat, tahunan 29 Februari, tanggal tertentu, tanggal mulai dan berakhir), status kartu, panel = jumlah penghitung bagian, pengingat suhu (24 Okt tidak tampil, 25 Okt tampil, 4/4 tidak tampil), dan urutan Perlu perhatian. Ekspor rekap: batas 50.000 baris Excel dan 2.000 baris PDF/Cetak beserta pesannya, nama file `rekap-suhu_20261001-20261031_202611011015.xlsx` (waktu WIB), ID laporan dari filter dan waktu, baris bertipe untuk keempat rekap (angka, tanggal WIB, NIHIL), workbook dengan sheet Data (header dibekukan, filter otomatis, format `dd/mm/yyyy hh:mm` dan `0.0`) dan Ringkasan, kepala dan kaki PDF (Halaman X dari Y), tanda tangan, teks yang di-escape, serta kelompok sesi Clear Desk dengan subtotal. Riwayat aktivitas: filter tanggal WIB (batas UTC), kelompok aksi, nilai tidak dikenal diabaikan, perubahan dengan label kolom, hapus dengan alasan, ekspor, gagal masuk, nama perangkat, dan log harian yang kedaluwarsa setelah 90 hari. Backup: nama set (WIB), simpan 30 set (malam ke-31 menghapus yang tertua, folder lain tidak disentuh), file opsi password, nama database dan isi arsip yang aman (`../` ditolak), enkripsi bolak-balik untuk berbagai ukuran, serta kunci salah, satu byte berubah, dan file terpotong yang ditolak. Tanpa jawaban awal: editor tidak menyimpan `default`, juga untuk jawaban yang valid, dan daftar pertanyaan awal (`database/dummy/builtin-forms.php`) tidak punya jawaban terpilih. Tes tidak memakai database maupun `.env`; tes Excel dan arsip dilewati bila PHP tanpa `zip`.

## Verifikasi

Lihat `docs/verification.md` untuk hasil pemeriksaan dan batas pemeriksaan visual pada sesi implementasi.
