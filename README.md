# AB Portal Hub

Frontend portal IT RS Awal Bros Botania. Root project adalah folder ini. Struktur mengikuti `architecture.md`: PHP native, controller, view, router, konfigurasi menu, dan koneksi PDO yang dibuat hanya ketika diperlukan.

Panduan untuk rekan yang melakukan clone dan menambah halaman: [Design System](docs/design-system.md). Gunakan dokumen tersebut sebagai acuan ukuran, komponen, serta pola penambahan controller, route, dan view.

## Menjalankan dashboard

Jalankan dari root project:

```powershell
npm run dev
```

Buka http://127.0.0.1:8080. PHP 8.3 atau lebih baru dapat menjalankan frontend; target produksi tetap PHP 8.4 sesuai arsitektur. MySQL dan Composer belum diperlukan untuk melihat dashboard.

Halaman Form Monitoring Hardware Server IT tersedia di http://127.0.0.1:8080/forms/hardware-server atau melalui kartu pertamanya di dashboard. Pilihan rak/kondisi, keterangan Other, catatan cepat, validasi, dan reset dapat digunakan. Simpan Draft menyimpan data di browser perangkat yang sama dan memuatnya kembali saat halaman dibuka; pengiriman ke server masih menunggu integrasi backend.

Halaman rekap bulanan tersedia di http://127.0.0.1:8080/forms/hardware-server/history, juga melalui tombol Lihat Riwayat & Rekap Laporan pada form. Filter bulan/rak/status, pencarian, rincian laporan, pagination, serta Cetak Rekap sudah berfungsi di frontend. Tujuh baris awal berasal dari HTML referensi dan ditandai sebagai data referensi di halaman. Jumlah hasil dihitung dari data yang tersedia. Ekspor Excel/PDF dan unduh lembar menjelaskan bahwa integrasi laporan server belum tersedia; tidak ada unduhan atau persetujuan operasional yang diklaim berasal dari backend.

Perintah `npm run dev` menjalankan server PHP lokal. Hentikan dengan Ctrl+C. Untuk menjalankan tanpa npm, gunakan `php -S 127.0.0.1:8080 -t public public/index.php`.

CSS Tailwind dan font sudah tersedia di `public/assets/`, sehingga dashboard tidak bergantung pada CDN maupun Node.js saat dijalankan. Inter dan Playfair Display disajikan secara lokal beserta lisensinya.

## Mengembangkan tampilan

Form Kesesuaian User HIS tersedia di http://127.0.0.1:8080/forms/kesesuaian-user dan melalui kartu Kesesuaian User di dashboard. View berada di `app/Views/forms/kesesuaian-user.php`, controller di `app/Controllers/Forms/KesesuaianUserController.php`, serta konfigurasi alasan dan sampel di `config/kesesuaian-user.php`. Pilihan tidak sesuai menampilkan catatan alasan; tanggal/jam memakai WIB; draft disimpan di browser. Konfirmasi pemeriksaan tidak dipulihkan dari draft dan harus dicentang kembali. Pengiriman serta halaman laporan masih menunggu integrasi.

```powershell
npm ci
npm run build
```

Gunakan `npm run watch` selama mengubah view. Node.js hanya dipakai untuk membangun CSS. Sumber CSS berada di `resources/css/app.css`, hasil build di `public/assets/css/app.css`. `package-lock.json` mengunci dependensi frontend. Panduan CLI: https://tailwindcss.com/docs/installation/tailwind-cli.

## Lokasi kode

| Lokasi | Fungsi |
| --- | --- |
| `public/index.php` | Front controller dan router untuk server lokal |
| `bootstrap/app.php` | Autoload dan konfigurasi zona waktu |
| `routes/web.php` | Route aplikasi |
| `app/Controllers/HomeController.php` | Data dashboard dan pengelompokan menu |
| `app/Views/home/index.php` | Dashboard portal |
| `app/Controllers/Forms/HardwareServerController.php` | Data halaman pemeriksaan hardware server |
| `app/Views/forms/hardware-server.php` | Halaman pengisian pemeriksaan hardware |
| `app/Views/forms/kesesuaian-user.php` | Halaman pengisian verifikasi user HIS |
| `config/hardware-server.php` | Lokasi, periode, pilihan rak, dan teks pemeriksaan |
| `public/assets/js/hardware-server.js` | Interaksi form, validasi, dan draft lokal |
| `app/Views/reports/hardware-server.php` | Rekap bulanan dan tabel riwayat pemeriksaan |
| `app/Views/layouts/` | Kerangka HTML, header, varian header, dan footer |
| `config/hardware-server-reports.php` | Data referensi dan periode halaman rekap |
| `public/assets/js/hardware-server-history.js` | Filter, pencarian, pagination, rincian, dan cetak |
| `app/Views/components/` | Komponen UI bersama: brand, kartu, progres, dialog, pilihan kondisi, dan hero hardware |
| `config/portal.php` | Daftar 15 kartu, kategori, target, serta urutannya |
| `config/dashboard.php` | Identitas tampilan dan nilai progres awal sebelum integrasi backend |
| `config/database.php` | Parameter koneksi MySQL dari environment |
| `app/Core/Database.php` | Koneksi PDO dengan prepared statements native |
| `docs/design.md` | Alasan desain dan batas implementasi |

## Perilaku tahap pertama

- Header, banner kerahasiaan, panel progres, 15 kartu dalam tiga kategori, serta footer telah dibuat mengikuti gambar dan HTML referensi.
- Jam dan tanggal mengikuti Asia/Jakarta dan diperbarui melalui JavaScript.
- Identitas IT Botania dan keterangan IT Division berasal dari `config/dashboard.php`. Keterangan departemen ini tidak menetapkan role atau hak akses pengguna.
- Panel progres menggunakan nilai awal frontend 5/6 form (83%) dari `config/dashboard.php`. Cincin, bar capaian, dan jumlah form tersisa dihitung dari nilai tersebut. Saat integrasi backend, ganti sumber array `$progress` di `HomeController` dengan hasil perhitungan pengisian form dari MySQL.
- Logo sementara berupa teks `RS AWAL BROS` dalam satu baris. Letakkan SVG resmi di `public/assets/images/brand/rs-awal-bros.svg` agar otomatis menggantikan teks. Path dapat diubah di `config/app.php`.
- Proporsi kartu berangkat dari `code.html`, lalu disesuaikan berdasarkan arahan pengguna: judul 11.7 px, deskripsi 10.8 px, badge 9 px, padding 16 px, dan ikon 48 px. Grid enam/tujuh kolom aktif mulai lebar 1024 px.
- Halaman memakai alur vertikal alami, tanpa tinggi tetap 100vh atau pemadatan kartu berdasarkan tinggi layar. Semua kartu kembali vertikal dengan ikon 48 px. Container desktop memakai lebar 90%, maksimal 1440 px, agar margin kiri dan kanan lebih lapang. Navbar, banner, dan hero tetap ringkas; bagian Sistem berada setelah Monitoring & Laporan dan dapat dijangkau dengan scroll.
- Semua kartu memakai `justify-start`, padding atas yang sama, dan kotak ikon 48 px yang tidak menyusut. Hover tidak menggeser kartu atau memperbesar ikon, sehingga posisi ikon tetap sejajar meskipun panjang judul berbeda.
- Kartu hardware server berlabel `Form` dan membuka halaman pengisian. Kartu internal lainnya masih berlabel `Segera` dan menampilkan keterangan modul. Properti `available=true` digunakan hanya setelah route dan halamannya tersedia.
- Kartu eksternal berlabel `Atur URL` sampai target dikonfigurasi di `config/portal.php`. URL HTTPS yang valid dibuka di tab baru dengan `noopener noreferrer`.
- Password, Users, dan Logout menampilkan penjelasan bahwa autentikasi belum tersedia. Tidak ada password atau data akun yang dikumpulkan.
- Badge `Portal aktif` merujuk pada halaman portal, bukan uptime sistem eksternal.
- Dialog bisa ditutup menggunakan Escape, tombol tutup, tombol kembali, atau klik di luar dialog. Fokus dikelola oleh elemen `<dialog>` browser.
- Daftar kategori tanpa menu memiliki empty state; route yang tidak ada mengembalikan 404, kegagalan render mengembalikan 500.
- Dashboard dirender langsung oleh PHP dan tidak mengambil data secara asynchronous, sehingga tidak membutuhkan indikator loading buatan.

## Batas pengembangan backend

Autentikasi Delight PHP-Auth, middleware, pengelolaan pengguna, database operasional, migrasi, formulir resmi, dan perhitungan progres adalah tahap berikutnya. Tidak ada SQL migrasi asumsi atau autentikasi tiruan yang dipasang. `composer.json` menyiapkan autoload PSR-4; tambahkan dependensi auth dan kunci `composer.lock` ketika implementasi backend dimulai.

Dashboard, form hardware server, dan halaman rekap saat ini dijalankan pada `APP_ENV=local`, yang menjadi default pengembangan. Pada environment selain local, halaman tersebut mengembalikan HTTP 503 sampai autentikasi diimplementasikan. Penggantian istilah di antarmuka tidak mengubah pembatasan akses ini.

Pada Apache, arahkan document root ke `public/` dan aktifkan `mod_rewrite`. `APP_URL` dapat diatur menjadi origin portal tanpa trailing slash. `.env` tidak dibaca otomatis. Struktur saat ini menggunakan path absolut dari origin dan ditujukan untuk root domain atau virtual host, bukan subfolder URL.

## Verifikasi

Lihat `docs/verification.md` untuk hasil pemeriksaan dan batas pemeriksaan visual pada sesi implementasi.
