# AB Portal Hub

Frontend portal IT RS Awal Bros Botania. Root project adalah folder ini. Struktur mengikuti `architecture.md`: PHP native, controller, view, router, konfigurasi menu, dan koneksi PDO yang dibuat hanya ketika diperlukan.

Panduan untuk rekan yang melakukan clone dan menambah halaman: [Design System](docs/design-system.md). Gunakan dokumen tersebut sebagai acuan ukuran, komponen, serta pola penambahan controller, route, dan view.

## Menjalankan dashboard

Jalankan dari root project:

```powershell
npm run dev
```

Buka http://127.0.0.1:8080. PHP 8.3 atau lebih baru dapat menjalankan frontend; target produksi tetap PHP 8.4 sesuai arsitektur. MySQL dan Composer belum diperlukan untuk melihat dashboard.

URL pengisian menggunakan `/forms/{nama-modul}`, sedangkan laporan menggunakan `/reports/{nama-modul}`. Route laporan hanya didaftarkan pada `/reports/...`.

Semua form dan laporan memakai breadcrumb bersama di bawah header: `Portal Monitoring Terpadu / Monitoring Harian / Nama Modul / Nama Halaman`. Hanya portal menuju `/`; kategori, nama modul, dan halaman aktif tidak ditautkan. Controller memakai helper `monitoring_breadcrumbs()` dengan nama dari `config/monitoring-navigation.php`. Kedua layout merender `app/Views/components/breadcrumbs.php`; jangan menulis ulang navigasi pada view atau navbar.

Halaman Form Monitoring Hardware Server IT tersedia di http://127.0.0.1:8080/forms/hardware-server atau melalui kartu pertamanya di dashboard. Pilihan rak/kondisi, keterangan Other, catatan cepat, validasi, dan reset dapat digunakan. Simpan Draft menyimpan data di browser perangkat yang sama dan memuatnya kembali saat halaman dibuka; pengiriman ke server masih menunggu integrasi backend.

Halaman rekap bulanan tersedia di http://127.0.0.1:8080/reports/hardware-server, juga melalui tombol Lihat Riwayat & Rekap Laporan pada form. Filter bulan/rak/status, pencarian, rincian laporan, pagination, serta Cetak Rekap sudah berfungsi di frontend. Tujuh baris awal berasal dari HTML referensi dan ditandai sebagai data referensi di halaman. Jumlah hasil dihitung dari data yang tersedia. Ekspor Excel/PDF dan unduh lembar menjelaskan bahwa integrasi laporan server belum tersedia; tidak ada unduhan atau persetujuan operasional yang diklaim berasal dari backend.

Perintah `npm run dev` menjalankan server PHP lokal. Hentikan dengan Ctrl+C. Untuk menjalankan tanpa npm, gunakan `php -S 127.0.0.1:8080 -t public public/index.php`.

CSS Tailwind dan font Inter sudah tersedia di `public/assets/`, sehingga portal tidak bergantung pada CDN maupun Node.js saat dijalankan. Dashboard, navbar, form, rekap, akun, dan login memakai Inter dari sumber lokal yang sama.

## Mengembangkan tampilan

Form Monitoring Antrian Online BPJS tersedia di http://127.0.0.1:8080/forms/antrian-online, melalui kartu Antrian Online di dashboard. View berada di `app/Views/antrian-online/form.php`, controller di `app/Controllers/AntrianOnlineController.php`, dan konfigurasi di `config/antrian-online.php`. Pilihan cron, shift, tiket, catatan, file picker/drag-and-drop, reset, draft, dan ringkasan sudah berjalan di frontend. Bukti dibatasi satu PNG/JPG/PDF/TXT/LOG maksimal 5 MB. Draft menyimpan isian serta nama berkas sebagai pengingat; isi berkas dan konfirmasi tidak disimpan. Pengiriman/unggah ke server dan konektivitas BPJS menunggu integrasi.

Rekap BPJS tersedia di http://127.0.0.1:8080/reports/antrian-online, melalui kedua tautan rekap pada form. View berada di `app/Views/antrian-online/rekap.php`, data referensi di `config/antrian-online-reports.php`, dan script di `public/assets/js/antrian-online-history.js`. Ringkasan, lima filter, pencarian frasa, rincian audit, pagination, serta cetak hasil filter sudah berfungsi. Tujuh baris referensi menghasilkan 7 sesi, cron antrian 85,7%, dan cron bed 100%. Lampiran, SOP, serta ekspor Excel/PDF menjelaskan status belum tersedia; tidak ada berkas atau hasil koneksi server yang diklaim tersedia tanpa integrasi.

Form Kesesuaian User HIS tersedia di http://127.0.0.1:8080/forms/kesesuaian-user dan melalui kartu Kesesuaian User di dashboard. View berada di `app/Views/kesesuaian-user/form.php`, controller di `app/Controllers/KesesuaianUserController.php`, serta konfigurasi alasan dan sampel di `config/kesesuaian-user.php`. Pilihan tidak sesuai menampilkan catatan alasan; tanggal/jam memakai WIB; draft disimpan di browser. Konfirmasi pemeriksaan tidak dipulihkan dari draft dan harus dicentang kembali. Pengiriman ke server masih menunggu integrasi.

Laporan Kesesuaian User tersedia di http://127.0.0.1:8080/reports/kesesuaian-user melalui kedua tombol rekap pada form. View berada di `app/Views/kesesuaian-user/rekap.php`, data referensi di `config/kesesuaian-user-reports.php`, dan interaksi di `public/assets/js/kesesuaian-user-history.js`. Filter periode/status/unit dan pencarian memperbarui tabel serta ringkasannya. Klik username untuk rincian; Cetak Rekap mencetak semua hasil filter lintas halaman. Ekspor Excel/PDF menunggu backend. Tujuh baris referensi menghasilkan ringkasan awal 7 user, 5 sesuai, 2 tidak sesuai, dan kepatuhan 71,4%, tanpa jumlah 128 yang tidak didukung data tabel.

```powershell
npm ci
npm run build
```

Gunakan `npm run watch` (dashboard dan halaman `main.php`) atau `npm run watch:design` (halaman design system) selama mengubah view. Node.js hanya dipakai untuk membangun CSS. Ada dua bundel: `resources/css/app.css` → `public/assets/css/app.css` untuk dashboard `/` serta form dan rekap hardware server, kesesuaian user, dan antrian online, dan `resources/css/design.css` → `public/assets/css/design.css` untuk halaman design system (login, akun, suhu, CCTV, Clear Desk) agar token desain tidak mengubah tampilan dashboard. `npm run build` membangun keduanya. `package-lock.json` mengunci dependensi frontend. Panduan CLI: https://tailwindcss.com/docs/installation/tailwind-cli.

## Halaman

Halaman design system mengikuti desain statis di `docs/DESIGN/rs-awal-bros-portal/` (file desain tidak diubah). Semua data masih dummy.

| Route | Controller | View |
| --- | --- | --- |
| `/login` (publik), `POST /logout` | `AuthController` | `auth/login.php` (layout `auth`) |
| `/` | `HomeController::index` | `home/index.php` (dashboard portal, layout `main`) |
| `/forms/hardware-server`, `/reports/hardware-server` | `HardwareServerController` | `hardware-server/form.php`, `hardware-server/rekap.php` (layout `main`) |
| `/forms/kesesuaian-user`, `/reports/kesesuaian-user` | `KesesuaianUserController` | `kesesuaian-user/form.php`, `kesesuaian-user/rekap.php` (layout `main`) |
| `/forms/antrian-online`, `/reports/antrian-online` | `AntrianOnlineController` | `antrian-online/form.php`, `antrian-online/rekap.php` (layout `main`) |
| `/password`, `/users` | `AccountController` | `account/password.php`, `account/users.php` (layout `design`) |
| `/form/suhu`, `/rekap/suhu` (`/form/temperature` dialihkan ke `/form/suhu`) | `SuhuController` | `suhu/form.php`, `suhu/rekap.php` (layout `design`) |
| `/form/cctv`, `/rekap/cctv` | `CctvController` | `cctv/form.php`, `cctv/rekap.php` (layout `design`) |
| `/form/cleardesk`, `/rekap/cleardesk` | `ClearDeskController` | `cleardesk/form.php`, `cleardesk/rekap.php` (layout `design`) |

Halaman design system (suhu, CCTV, Clear Desk) masih memakai `/form/...` dan `/rekap/...`, berbeda dari pola `/forms/...` dan `/reports/...` di atas.

Semua route kecuali `/login` memerlukan sesi login (middleware `app/Core/RequireLogin.php`); pengunjung yang belum login diarahkan ke `/login` dengan HTTP 302, lalu kembali ke halaman yang diminta setelah login. File di `public/assets/` tidak diblokir. Kartu dashboard dengan `'available' => true` di `config/portal.php` menuju form modulnya; kartu lain menampilkan dialog informasi.

Setiap form dikirim (POST) ke route rekap modulnya, lalu diarahkan kembali ke halaman rekap dengan notifikasi. Belum ada data yang disimpan. Filter dan pencarian rekap berjalan di browser (`public/assets/js/design.js`). Tombol Export dan Cetak masih berupa tampilan.

Ketiga form memakai varian warna tombol bersama: `button-primary` untuk kirim, `button-secondary` untuk draft, `button-neutral` untuk kembali, `button-soft` untuk rekap/bantuan, `button-ghost` untuk reset/tutup, `button-on-dark` untuk aksi hero, dan `button-danger` untuk hapus berkas. Pasangkan dengan class ukuran seperti `inspection-button`. Detail ada di Design System bagian 9. Breadcrumb laporan menyebut nama modul secara lengkap agar tujuan rekap jelas.

Seluruh form dan rekap memakai warna tombol tersebut melalui `resources/css/module-ui.css`, yang dibangun ke kedua bundle CSS. Jenis font adalah Inter dengan ukuran berdasarkan peran: judul panel 20 px, judul bagian 17 px, label/input/tombol 14 px, aksi kecil/tabel 13 px, keterangan 12 px. Gunakan `ui-panel-title`, `ui-section-title`, `ui-field-label`, `ui-body`, dan `ui-caption` untuk tipografi; `module-action` atau `module-action-small` untuk tombol; serta `module-page-button` untuk pagination. Navbar tetap memakai sumber bersama dari home.

## Lokasi kode

| Lokasi | Fungsi |
| --- | --- |
| `public/index.php` | Front controller dan router untuk server lokal |
| `bootstrap/app.php` | Autoload, `.env`, dan zona waktu |
| `routes/web.php` | Route aplikasi |
| `app/Controllers/HomeController.php` | Data dashboard dan pengelompokan menu |
| `app/Views/home/index.php` | Dashboard portal |
| `app/Controllers/HardwareServerController.php` | Data halaman pemeriksaan hardware server |
| `app/Views/hardware-server/form.php` | Halaman pengisian pemeriksaan hardware |
| `app/Views/kesesuaian-user/form.php` | Halaman pengisian verifikasi user HIS |
| `config/hardware-server.php` | Lokasi, periode, pilihan rak, dan teks pemeriksaan |
| `public/assets/js/hardware-server.js` | Interaksi form, validasi, dan draft lokal |
| `app/Views/hardware-server/rekap.php` | Rekap bulanan dan tabel riwayat pemeriksaan |
| `app/Views/kesesuaian-user/rekap.php` | Rekap dan riwayat verifikasi user HIS |
| `app/Views/antrian-online/rekap.php` | Rekap cron, tiket, bukti, dan tindak lanjut antrian BPJS |
| `app/Views/layouts/` | Kerangka HTML, satu header bersama, dan footer |
| `config/hardware-server-reports.php` | Data referensi dan periode halaman rekap |
| `public/assets/js/hardware-server-history.js` | Filter, pencarian, pagination, rincian, dan cetak |
| `app/Views/components/` | Komponen UI bersama: brand, kartu, progres, dialog, pilihan kondisi, dan hero hardware |
| `app/Views/components/breadcrumbs.php` | Breadcrumb bersama berdasarkan data controller |
| `config/portal.php` | Daftar 15 kartu, kategori, target, serta urutannya |
| `config/dashboard.php` | Identitas tampilan (`profile`, serta `account` untuk navbar design system) dan nilai progres awal sebelum integrasi backend |
| `config/database.php` | Parameter koneksi MySQL dari environment |
| `app/Core/Database.php` | Koneksi PDO dengan prepared statements native |
| `docs/design.md` | Alasan desain dan batas implementasi |
| `app/Controllers/` | Controller halaman design system; `Controller.php` berisi penjaga `APP_ENV` dan memakai layout `design` secara default |
| `app/Core/Auth.php`, `app/Core/RequireLogin.php` | Sesi login, token CSRF, dan middleware login |
| `app/Views/layouts/design.php`, `app/Views/layouts/auth.php` | Layout design system: `design.php` memakai navbar home dari `layouts/header.php`; `auth.php` untuk login tanpa navbar; breadcrumb, footer, dan toast mengikuti halaman |
| `app/Views/components/design-footer.php`, `page-header`, `card`, `badge`, `button`, `field`, `table`, `rows/`, `submit-modal`, `toast` | Partial design system |
| `app/Views/errors/design-404.php`, `design-unavailable.php` | Halaman 404 dan 503 versi design system (router dan `Controller.php`) |
| `app/Helpers/functions.php` | `e()`, `partial()`, `icon()` (ikon dashboard dan halaman `main.php`), `design_icon()` (ikon design system), `csrf_field()`, `url()`, `asset_v()`, `dummy()` |
| `app/Helpers/icons.php` | Ikon Lucide (ISC) untuk `design_icon()` |
| `database/dummy/` | Data dummy modul suhu, CCTV, dan Clear Desk |
| `resources/css/app.css` | Gaya dashboard serta form dan rekap hardware server, kesesuaian user, antrian online |
| `resources/css/design.css` | Token design system (`@theme`), gaya dasar, dan komponen halaman design system |
| `public/assets/js/app.js` | Jam, dialog, dan menu dashboard serta halaman `main.php` |
| `public/assets/js/design.js`, `login.js` | Perilaku halaman design system (jam, menu, toast, form, filter rekap, login) |

## Perilaku tahap pertama

- Header, banner kerahasiaan, panel progres, 15 kartu dalam tiga kategori, serta footer telah dibuat mengikuti gambar dan HTML referensi.
- Jam dan tanggal mengikuti Asia/Jakarta dan diperbarui melalui JavaScript.
- Identitas IT Botania dan keterangan IT Division berasal dari `config/dashboard.php`. Keterangan departemen ini tidak menetapkan role atau hak akses pengguna.
- Panel progres menggunakan nilai awal frontend 5/6 form (83%) dari `config/dashboard.php`. Cincin, bar capaian, dan jumlah form tersisa dihitung dari nilai tersebut. Saat integrasi backend, ganti sumber array `$progress` di `HomeController` dengan hasil perhitungan pengisian form dari MySQL.
- Logo memakai SVG yang diberikan pengguna di `public/assets/images/brand/rs-awal-bros.svg`, melalui komponen brand bersama pada semua halaman. Rasio asli 348:53 dipertahankan pada tinggi 32 px; URL logo diberi versi `filemtime` agar perubahan tidak tertahan cache. Teks satu baris digunakan hanya jika aset tidak tersedia. Path dapat diubah di `config/app.php`.
- Proporsi kartu berangkat dari `code.html`, lalu disesuaikan berdasarkan arahan pengguna: judul 11.7 px, deskripsi 10.8 px, badge 9 px, padding 16 px, dan ikon 48 px. Grid enam/tujuh kolom aktif mulai lebar 1024 px.
- Halaman memakai alur vertikal alami, tanpa tinggi tetap 100vh atau pemadatan kartu berdasarkan tinggi layar. Semua kartu kembali vertikal dengan ikon 48 px. Container desktop memakai lebar 90%, maksimal 1440 px, agar margin kiri dan kanan lebih lapang. Navbar, banner, dan hero tetap ringkas; bagian Sistem berada setelah Monitoring & Laporan dan dapat dijangkau dengan scroll.
- Semua kartu memakai tiga baris subgrid bersama untuk ikon, judul, dan deskripsi. Judul diratakan ke atas dan tinggi barisnya mengikuti judul terpanjang pada satu baris kartu, tanpa minimum dua baris. Deskripsi memakai margin atas 2 px dan tetap sejajar antar kartu; ruang tersisa di bawah judul pendek menjaga perataan kedua teks. Padding serta kotak ikon 48 px tetap sama; hover tidak menggeser kartu atau memperbesar ikon.
- Kartu Hardware Server, Kesesuaian User, Antrian Online, Rata-rata Suhu Server, Monitoring CCTV, dan Clear Desk berlabel `Form` dan membuka halaman pengisian. Kartu internal yang belum tersedia masih berlabel `Segera` dan menampilkan keterangan modul. Properti `available=true` digunakan hanya setelah route dan halamannya tersedia.
- Kartu eksternal berlabel `Atur URL` sampai target dikonfigurasi di `config/portal.php`. URL HTTPS yang valid dibuka di tab baru dengan `noopener noreferrer`.
- Password dan Users membuka halaman akun; Logout mengirim `POST /logout` (dengan token CSRF) dan kembali ke `/login`. Header form memakai tombol Logout yang sama.
- Badge `Portal aktif` merujuk pada halaman portal, bukan uptime sistem eksternal.
- Dialog bisa ditutup menggunakan Escape, tombol tutup, tombol kembali, atau klik di luar dialog. Fokus dikelola oleh elemen `<dialog>` browser.
- Daftar kategori tanpa menu memiliki empty state; route yang tidak ada mengembalikan 404, kegagalan render mengembalikan 500.
- Dashboard dirender langsung oleh PHP dan tidak mengambil data secara asynchronous, sehingga tidak membutuhkan indikator loading buatan.

## Batas pengembangan backend

Login memeriksa tabel `users`: isian Username dicocokkan dengan kolom `email` (akun `role = admin`, `is_active = 1`), lalu password diperiksa dengan `password_verify`. Pembatasan percobaan login dan audit log masih TODO di `AuthController`.

Dashboard, form hardware server, dan halaman rekap saat ini dijalankan pada `APP_ENV=local`, yang menjadi default pengembangan. Pada environment selain local, halaman tersebut mengembalikan HTTP 503 sampai autentikasi diimplementasikan. Penggantian istilah di antarmuka tidak mengubah pembatasan akses ini.

**Mode pengembangan (login bebas).** Jika `APP_ENV=local` **dan** `AUTH_DEV_BYPASS=true` di `.env`, username dan password apa pun yang tidak kosong diterima sebagai admin dummy. Halaman login menampilkan banner "Mode pengembangan: login bebas", dan setiap login bebas dicatat di `storage/logs/auth-dev-bypass.log` (waktu, username, IP, user agent; tanpa password). Jika salah satu syarat tidak terpenuhi, mode ini mati otomatis. `.env.example` memakai `AUTH_DEV_BYPASS=false`; jangan pernah mengaktifkannya di server RS.

Autentikasi Delight PHP-Auth, pengelolaan pengguna, database operasional, migrasi, formulir resmi, dan perhitungan progres adalah tahap berikutnya. Tidak ada SQL migrasi asumsi yang dipasang. `composer.json` menyiapkan autoload PSR-4; tambahkan dependensi auth dan kunci `composer.lock` ketika implementasi backend dimulai.

Dashboard saat ini dijalankan pada `APP_ENV=local`, yang menjadi default pengembangan. Pada environment selain local, semua halaman mengembalikan HTTP 503 sampai autentikasi diimplementasikan. Penggantian istilah di antarmuka tidak mengubah pembatasan akses ini.

Pada Apache, arahkan document root ke `public/` dan aktifkan `mod_rewrite`. `APP_URL` dapat diatur menjadi origin portal tanpa trailing slash. `.env` di root project dibaca otomatis oleh `bootstrap/app.php`; variabel environment server tetap diutamakan. Struktur saat ini menggunakan path absolut dari origin dan ditujukan untuk root domain atau virtual host, bukan subfolder URL.

## Database

1. Salin `.env.example` menjadi `.env`, lalu isi koneksi MySQL lokal. `.env` tidak boleh di-commit.
2. Aktifkan `extension=pdo_mysql` pada `php.ini`.
3. Buat tabel dengan `php bin/migrate.php` (atau `composer db:migrate`), atau impor `database/schema.sql` melalui phpMyAdmin. Skrip aman dijalankan berulang.
4. Periksa koneksi dan daftar tabel dengan `php bin/db-check.php` (atau `composer db:check`).
5. Buat akun admin (atau ganti passwordnya) dengan `php bin/create-admin.php` (atau `composer admin:create`). Skrip menanyakan email, nama, dan password; password diketik tersembunyi dan diulang untuk konfirmasi, minimal 12 karakter, dan tidak boleh sama dengan email. Password disimpan dengan `password_hash` (bcrypt, cost 12), akun dibuat dengan `role = admin` dan `is_active = 1`, dan setiap pembuatan atau penggantian password dicatat di `audit_logs`. Jika email sudah terdaftar, skrip menanyakan apakah password ingin diganti, sehingga aman dijalankan berulang. Portal hanya memakai satu admin: skrip menolak membuat admin kedua dengan email lain.

   Jalankan dari PowerShell atau CMD. Di Git Bash, pakai `winpty php bin/create-admin.php` agar password tidak terlihat. Saat login, isi kolom **Username** dengan **email** akun ini, lalu set `AUTH_DEV_BYPASS=false` di `.env` untuk memakai login sebenarnya.

Koneksi dibuat melalui `App\Core\Database::connection()` dengan zona waktu sesi `+07:00`.

## Verifikasi

Lihat `docs/verification.md` untuk hasil pemeriksaan dan batas pemeriksaan visual pada sesi implementasi.
