# AB Portal Hub

Frontend portal IT RS Awal Bros Botania. Root project adalah folder ini. Struktur mengikuti `architecture.md`: PHP native, controller, view, router, konfigurasi menu, dan koneksi PDO yang dibuat hanya ketika diperlukan.

Panduan untuk rekan yang melakukan clone dan menambah halaman: [Design System](docs/design-system.md). Gunakan dokumen tersebut sebagai acuan ukuran, komponen, serta pola penambahan controller, route, dan view.

## Menjalankan dashboard

Jalankan dari root project:

```powershell
npm run dev
```

Buka http://127.0.0.1:8080. PHP 8.3 atau lebih baru dapat menjalankan frontend; target produksi tetap PHP 8.4 sesuai arsitektur. MySQL dan Composer belum diperlukan untuk melihat dashboard.

## Form dan riwayat

Semua form monitoring dan riwayatnya digambar dari daftar pertanyaan, bukan dari view HTML per modul:

- **Form bawaan** (enam modul): `/form/suhu`, `/form/cctv`, `/form/cleardesk`, `/forms/hardware-server`, `/forms/kesesuaian-user`, dan `/forms/antrian-online`. Pertanyaannya ada di `database/dummy/builtin-forms.php` (kunci = id kartu dashboard) dan dapat diubah lewat menu ⋯ › **Edit Isi Form** pada kartu dashboard (`/form-editor?id=…`). Tipe jawaban khusus form bawaan (suhu dengan status otomatis, tanggal & waktu, tanggal + jam, kisi Patuh/Temuan, pernyataan) tidak dapat diganti tipenya. Tombol **Kembalikan ke form asli** di editor memuat ulang pertanyaan asli.
- **Riwayat** (enam modul): `/rekap/suhu`, `/rekap/cctv`, `/rekap/cleardesk`, `/reports/hardware-server`, `/reports/kesesuaian-user`, dan `/reports/antrian-online`, juga lewat tombol **Lihat Rekap** pada kartu dan form. Satu kolom per pertanyaan form saat ini, ringkasan (laporan ditampilkan, bulan ini, perlu perhatian, terakhir dikirim), pencarian, filter periode, 25 baris per halaman, daftar kartu di bawah 768 px, dan dialog detail berisi semua pertanyaan dengan jawabannya. Laporan contoh ada di `database/dummy/builtin-responses.php`.
- **Form dinamis** buatan sendiri (tombol **+ Tambah** di dashboard): `/form-dinamis?id=…` dan riwayatnya `/form-dinamis/respons?id=…`, memakai halaman yang sama.

Semua masih frontend saja. Pertanyaan hasil edit dan laporan yang dikirim disimpan di `sessionStorage` tab browser (`public/assets/js/dashboard-store.js`) dan hilang saat tab ditutup; tidak ada yang dikirim ke server. Tombol **Atur ulang** di dashboard mengembalikan data contoh. Ekspor Excel/PDF dan cetak riwayat belum dibangun.

Tombol Kirim memvalidasi jawaban di browser (wajib isi, angka, suhu 0–60 °C, format MAC dan IPv4, ukuran dan jumlah file, pertanyaan bersyarat), menyimpan laporan, menandai kartu dashboard selesai hari ini, lalu menampilkan dialog dengan tautan ke riwayat.

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

Halaman design system mengikuti desain statis di `docs/DESIGN/rs-awal-bros-portal/` (file desain tidak diubah). Semua data masih dummy.

| Route | Controller | View |
| --- | --- | --- |
| `/login` (publik), `POST /logout` | `AuthController` | `auth/login.php` (layout `auth`) |
| `/` | `HomeController::index` | `home/index.php` (dashboard portal, layout `main`) |
| `/form-editor?id=…` | `FormEditorController::edit` | `form-editor/index.php` (Edit Isi Form: tab Pertanyaan dan Jadwal, layout `main`) |
| `/form/suhu`, `/form/cctv`, `/form/cleardesk`, `/forms/hardware-server`, `/forms/kesesuaian-user`, `/forms/antrian-online` (`/form/temperature` dialihkan ke `/form/suhu`) | `DynamicFormController::builtin()` | `form-dinamis/fill.php` (layout `main`) |
| `/rekap/suhu`, `/rekap/cctv`, `/rekap/cleardesk`, `/reports/hardware-server`, `/reports/kesesuaian-user`, `/reports/antrian-online` | `DynamicFormController::history()` | `form-dinamis/respons.php` (layout `main`) |
| `/form-dinamis?id=…`, `/form-dinamis/respons?id=…` | `DynamicFormController::fill()`, `responses()` | `form-dinamis/fill.php`, `form-dinamis/respons.php` (layout `main`) |
| `/password`, `/users` | `AccountController` | `account/password.php`, `account/users.php` (layout `design`) |

Suhu, CCTV, dan Clear Desk memakai `/form/...` dan `/rekap/...`; tiga modul lainnya memakai `/forms/...` dan `/reports/...`. URL lama ini dipertahankan agar tautan yang sudah ada tetap berlaku. Route didaftarkan di `routes/web.php` dengan id kartu dashboard sebagai argumen.

Semua route kecuali `/login` memerlukan sesi login (middleware `app/Core/RequireLogin.php`); pengunjung yang belum login diarahkan ke `/login` dengan HTTP 302, lalu kembali ke halaman yang diminta setelah login. File di `public/assets/` tidak diblokir. Kartu dashboard berasal dari `database/dummy/dashboard.php`: kartu Monitoring Harian membuka halaman form-nya dan tombol **Lihat Rekap** membuka riwayatnya.

Form dan riwayat memakai `resources/css/gform.css` (form bergaya Google Forms) dan `resources/css/form-dinamis.css` (tipe jawaban tambahan, status suhu, dan halaman riwayat); editor memakai `resources/css/form-editor.css`. Varian tombol `button-*` dan class `module-action` di `resources/css/module-ui.css` masih dipakai halaman akun, tombol Kembali ke Dashboard, dan dialog design system.

## Lokasi kode

| Lokasi | Fungsi |
| --- | --- |
| `public/index.php` | Front controller dan router untuk server lokal |
| `bootstrap/app.php` | Autoload, `.env`, dan zona waktu |
| `routes/web.php` | Route aplikasi |
| `app/Controllers/HomeController.php` | Data dashboard dan pengelompokan menu |
| `app/Views/home/index.php` | Dashboard portal |
| `app/Controllers/DynamicFormController.php` | Halaman form dan riwayat: enam form bawaan (`builtin()`, `history()`) dan form dinamis (`fill()`, `responses()`) |
| `app/Controllers/FormEditorController.php` | Halaman Edit Isi Form |
| `app/Views/form-dinamis/fill.php`, `respons.php` | Kerangka halaman form dan riwayat; isinya digambar oleh script |
| `app/Views/form-editor/index.php` | Kerangka editor form (tab Pertanyaan dan Jadwal) |
| `database/dummy/dashboard.php` | Bagian dan kartu dashboard, jadwal, serta form dinamis contoh |
| `database/dummy/builtin-forms.php` | Daftar pertanyaan keenam form bawaan, termasuk tipe jawaban khususnya |
| `database/dummy/builtin-responses.php` | Laporan contoh keenam form bawaan untuk halaman riwayat |
| `app/Helpers/DashboardData.php`, `Schedule.php` | Data awal untuk browser dan aturan jadwal (sama dengan `dashboard-store.js`) |
| `public/assets/js/dashboard-store.js` | State bersama di browser (data contoh + `sessionStorage`), ikon, toast, jadwal, dan aturan status suhu |
| `public/assets/js/dashboard.js` | Dashboard: kartu, filter jadwal, dialog Tambah, menu kartu |
| `public/assets/js/form-dinamis.js` | Halaman form: tipe jawaban, validasi, pertanyaan bersyarat, dan penyimpanan laporan |
| `public/assets/js/form-riwayat.js` | Halaman riwayat: kolom per pertanyaan, filter, pagination, kartu mobile, dan dialog detail |
| `public/assets/js/form-editor.js` | Editor form: pertanyaan, jadwal, dan Kembalikan ke form asli |
| `app/Views/layouts/` | Kerangka HTML, satu header bersama, dan footer |
| `app/Views/components/` | Komponen UI bersama: brand, kartu, progres, dialog, breadcrumb, dan dialog dashboard |
| `app/Views/components/breadcrumbs.php` | Breadcrumb bersama berdasarkan data controller |
| `config/dashboard.php` | Identitas tampilan (`profile`, serta `account` untuk navbar design system) dan nilai progres awal sebelum integrasi backend |
| `config/database.php` | Parameter koneksi MySQL dari environment |
| `app/Core/Database.php` | Koneksi PDO dengan prepared statements native |
| `docs/design.md` | Alasan desain dan batas implementasi |
| `app/Controllers/` | Controller halaman design system; `Controller.php` berisi penjaga `APP_ENV` dan memakai layout `design` secara default |
| `app/Core/Auth.php`, `app/Core/RequireLogin.php` | Sesi login, token CSRF, dan middleware login |
| `app/Views/layouts/design.php`, `app/Views/layouts/auth.php` | Layout design system: `design.php` memakai navbar home dari `layouts/header.php`; `auth.php` untuk login tanpa navbar; breadcrumb, footer, dan toast mengikuti halaman |
| `app/Views/components/design-footer.php`, `page-header`, `card`, `badge`, `button`, `field`, `table`, `rows/user.php`, `submit-modal`, `toast` | Partial design system |
| `app/Views/errors/design-404.php`, `design-unavailable.php` | Halaman 404 dan 503 versi design system (router dan `Controller.php`) |
| `app/Helpers/functions.php` | `e()`, `partial()`, `icon()` (ikon dashboard dan halaman `main.php`), `design_icon()` (ikon design system), `csrf_field()`, `url()`, `asset_v()`, `dummy()` |
| `app/Helpers/icons.php` | Ikon Lucide (ISC) untuk `design_icon()` |
| `resources/css/app.css` | Gaya dashboard, form, riwayat, dan editor form (mengimpor `gform.css`, `dashboard.css`, `form-dinamis.css`, `form-editor.css`) |
| `resources/css/design.css` | Token design system (`@theme`), gaya dasar, dan komponen halaman design system |
| `public/assets/js/app.js` | Jam, dialog, dan menu dashboard serta halaman `main.php` |
| `public/assets/js/design.js`, `login.js` | Perilaku halaman design system (jam, menu, toast, login) |

## Perilaku tahap pertama

- Header, banner kerahasiaan, panel progres, 15 kartu dalam tiga kategori, serta footer telah dibuat mengikuti gambar dan HTML referensi.
- Jam dan tanggal mengikuti Asia/Jakarta dan diperbarui melalui JavaScript.
- Identitas IT Botania dan keterangan IT Division berasal dari `config/dashboard.php`. Keterangan departemen ini tidak menetapkan role atau hak akses pengguna.
- Panel progres menghitung kartu form yang terjadwal hari ini dan yang sudah selesai (data contoh di `database/dummy/dashboard.php` serta laporan yang dikirim di tab browser). Cincin, bar capaian, dan jumlah form tersisa dihitung dari nilai tersebut. Saat integrasi backend, ganti sumber `$progress` di `HomeController` dengan hasil perhitungan pengisian form dari MySQL.
- Logo memakai SVG yang diberikan pengguna di `public/assets/images/brand/rs-awal-bros.svg`, melalui komponen brand bersama pada semua halaman. Rasio asli 348:53 dipertahankan pada tinggi 32 px; URL logo diberi versi `filemtime` agar perubahan tidak tertahan cache. Teks satu baris digunakan hanya jika aset tidak tersedia. Path dapat diubah di `config/app.php`.
- Proporsi kartu berangkat dari `code.html`, lalu disesuaikan berdasarkan arahan pengguna: judul 11.7 px, deskripsi 10.8 px, badge 9 px, padding 16 px, dan ikon 48 px. Grid enam/tujuh kolom aktif mulai lebar 1024 px.
- Halaman memakai alur vertikal alami, tanpa tinggi tetap 100vh atau pemadatan kartu berdasarkan tinggi layar. Semua kartu kembali vertikal dengan ikon 48 px. Container desktop memakai lebar 90%, maksimal 1440 px, agar margin kiri dan kanan lebih lapang. Navbar, banner, dan hero tetap ringkas; bagian Sistem berada setelah Monitoring & Laporan dan dapat dijangkau dengan scroll.
- Semua kartu memakai tiga baris subgrid bersama untuk ikon, judul, dan deskripsi. Judul diratakan ke atas dan tinggi barisnya mengikuti judul terpanjang pada satu baris kartu, tanpa minimum dua baris. Deskripsi memakai margin atas 2 px dan tetap sejajar antar kartu; ruang tersisa di bawah judul pendek menjaga perataan kedua teks. Padding serta kotak ikon 48 px tetap sama; hover tidak menggeser kartu atau memperbesar ikon.
- Kartu Hardware Server, Kesesuaian User, Antrian Online, Rata-rata Suhu Server, Monitoring CCTV, dan Clear Desk membuka halaman form-nya; tombol Lihat Rekap pada kartu membuka riwayatnya. Menu ⋯ pada kartu berisi Lihat Rekap, Edit Isi Form, Edit Kartu, Duplikat, Sembunyikan, dan Hapus.
- Kartu tautan tanpa alamat menampilkan "Alamat belum diatur" sampai URL diisi lewat Edit atau di `database/dummy/dashboard.php`. URL `http`/`https` yang valid dibuka di tab baru dengan `noopener noreferrer`.
- Password dan Users membuka halaman akun; Logout mengirim `POST /logout` (dengan token CSRF) dan kembali ke `/login`. Header form memakai tombol Logout yang sama.
- Badge `Portal aktif` merujuk pada halaman portal, bukan uptime sistem eksternal.
- Dialog bisa ditutup menggunakan Escape, tombol tutup, tombol kembali, atau klik di luar dialog. Fokus dikelola oleh elemen `<dialog>` browser.
- Daftar kategori tanpa menu memiliki empty state; route yang tidak ada mengembalikan 404, kegagalan render mengembalikan 500.
- Dashboard dirender langsung oleh PHP dan tidak mengambil data secara asynchronous, sehingga tidak membutuhkan indikator loading buatan.

## Batas pengembangan backend

Login memeriksa tabel `users`: isian Username dicocokkan dengan kolom `email` (akun `role = admin`, `is_active = 1`), lalu password diperiksa dengan `password_verify`. Pembatasan percobaan login dan audit log masih TODO di `AuthController`.

Dashboard, form, dan halaman riwayat saat ini dijalankan pada `APP_ENV=local`, yang menjadi default pengembangan. Pada environment selain local, halaman tersebut mengembalikan HTTP 503 sampai autentikasi diimplementasikan. Penggantian istilah di antarmuka tidak mengubah pembatasan akses ini.

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
