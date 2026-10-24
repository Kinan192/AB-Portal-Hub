# Design System AB Portal Hub

Panduan frontend Portal Monitoring IT RS Awal Bros Botania untuk rekan pengembang yang melakukan clone repository dan membuat halaman baru.

Dokumen ini mencatat implementasi terakhir pada 24 Oktober 2026. Ukuran di bawah mengikuti source project yang aktif, termasuk penyesuaian pengguna setelah referensi `code.html`. Jangan mengambil ukuran dari versi screenshot lama.

## 1. Sumber acuan dan batas pengembangan

| Sumber | Digunakan untuk |
| --- | --- |
| `design-system.md` | Patokan komponen, ukuran, interaksi, dan pola halaman baru |
| `resources/css/app.css` | Implementasi font, token, class komponen, dan aturan responsif |
| `app/Views/layouts/main.php` | Kerangka HTML bersama, aset, navbar, footer, dan dialog |
| `app/Views/components/` | Komponen yang harus digunakan ulang |
| `config/portal.php` | Definisi dan urutan kartu portal |
| `config/dashboard.php` | Identitas tampilan dan nilai progres awal frontend |
| Dokumen arsitektur awal dari pemilik project | Rancangan backend, database, autentikasi, dan middleware |

Dokumen arsitektur awal diberikan sebagai berkas terpisah bernama `architecture Portal Hub.md`. Saat panduan ini dibuat, tidak ada `architecture.md` di root repository. Panduan frontend ini dapat digunakan langsung; tim perlu menyertakan dokumen arsitektur tersebut dalam repository sebelum memulai integrasi backend.

Project ini adalah frontend aplikasi yang sedang dikembangkan langsung. Gunakan bahasa operasional seperti “Riwayat pemeriksaan”, “Simpan draft”, dan “Informasi portal”. Fitur yang belum tersedia harus dijelaskan sesuai kondisinya, tanpa mengklaim sudah tersambung ke backend.

Controller, View, router GET statis, konfigurasi, dan helper sudah digunakan. Model operasional, autentikasi, middleware, serta penyimpanan form belum diimplementasikan. Angka progres 5/6 (83%) saat ini berasal dari konfigurasi frontend dan akan diganti sumbernya saat integrasi backend.

## 2. Menjalankan hasil clone

Prasyarat pengembangan: PHP 8.3 atau lebih baru, Node.js, dan npm. Target produksi mengikuti rancangan arsitektur awal, yaitu PHP 8.4. Frontend dapat dijalankan tanpa MySQL dan tanpa instalasi Composer terlebih dahulu.

```powershell
git clone <URL_REPOSITORY> AB-Portal-hub
cd AB-Portal-hub
npm ci
npm run dev
```

Ganti `<URL_REPOSITORY>` dengan alamat repository tim. Buka `http://127.0.0.1:8080`.

Perintah pengembangan:

| Perintah | Fungsi |
| --- | --- |
| `npm run dev` | Menjalankan server PHP lokal pada port 8080 |
| `npm run build` | Menghasilkan CSS Tailwind yang diminifikasi |
| `npm run watch` | Membangun ulang CSS saat template atau source berubah |

Jalankan `npm run watch` di terminal kedua ketika mengubah tampilan. `npm run dev` tidak menjalankan watcher CSS atau memuat ulang browser secara otomatis. Hentikan proses dengan Ctrl+C.

CSS dan font disajikan dari `public/assets/`. Node.js dipakai untuk build, bukan sebagai runtime backend. Pertahankan `package-lock.json` agar hasil instalasi rekan satu tim konsisten.

## 3. Karakter visual

- Tema terang untuk pekerjaan operasional rumah sakit.
- Merah untuk identitas RS, hijau tua untuk area sambutan, dan teal/emerald untuk aksen serta progres.
- Inter untuk teks aplikasi. Playfair Display hanya untuk sambutan pada dashboard.
- Kartu menu teratur, posisi ikon sejajar, border tipis, dan bayangan terbatas.
- Tinggi halaman mengikuti isi. Bagian Sistem berada setelah Monitoring & Laporan dan dapat diakses melalui scroll.
- Jangan memaksa semua konten masuk 100vh, memperkecil halaman dengan CSS zoom, atau memotong isi dengan overflow pada body.

## 4. Container, spacing, dan breakpoint

Gunakan `.page-shell` pada area utama halaman. Class ini juga dipakai navbar dan footer, sehingga tepi konten tetap sejajar.

```html
<main id="main-content" class="page-shell flex-1 space-y-5 py-4">
    <!-- Isi halaman -->
</main>
```

Implementasi saat ini:

```css
.page-shell {
  @apply mx-auto w-full max-w-[1440px] px-5 sm:px-8 lg:w-[90%] lg:px-0;
}
```

| Lebar viewport | Container dan grid kartu |
| --- | --- |
| Di bawah 640 px | Container 100%, padding horizontal 20 px; grid dua kolom |
| Mulai 640 px (`sm`) | Padding horizontal 32 px; grid tiga kolom |
| Mulai 768 px (`md`) | Grid empat kolom |
| Mulai 1024 px (`lg`) | Container 90%, maksimal 1440 px, tanpa padding horizontal tambahan; grid harian enam kolom, kategori lain tujuh kolom |

Contoh lebar desktop: pada viewport 1440 px, container 1296 px dengan margin sekitar 72 px per sisi; pada viewport 1920 px, container mencapai batas 1440 px dengan margin 240 px per sisi.

Ukuran spacing yang digunakan:

| Kebutuhan | Nilai | Tailwind |
| --- | --- | --- |
| Jarak kecil antarteks | 4 px | `gap-1`, `mt-1` |
| Jarak ikon dan label tombol | 4 px | `gap-1` |
| Jarak dalam kategori | 12 px | `space-y-3` |
| Jarak antar kartu | 14 px | `gap-3.5` |
| Padding kartu | 16 px | `p-4` |
| Jarak antar blok utama | 20 px | `space-y-5` |
| Padding panel umum | 24 px | `p-6` |

Jangan menambah container lain dengan `max-width` berbeda di dalam `.page-shell`. Untuk halaman form, panel input boleh dibatasi lebarnya, tetapi judul halaman dan navigasinya tetap mengikuti tepi container.

## 5. Tipografi

Font disimpan lokal di `public/assets/fonts/`. Inter tersedia dalam bobot 400, 500, 600, 700, 800, dan 900. Playfair Display tersedia pada bobot 700.

Ukuran desimal merupakan hasil kalibrasi terakhir pengguna. Pertahankan angka ini saat menyalin komponen; perubahan skala bersama dilakukan melalui komponen/source CSS, bukan zoom browser atau transform.

| Elemen yang sudah ada | Ukuran | Class atau aturan |
| --- | --- | --- |
| Logo teks sementara | 24 px, bobot 900 | `text-2xl font-black whitespace-nowrap` |
| Judul kartu | 11.7 px, bold | `text-[11.7px] font-bold leading-snug` |
| Deskripsi kartu | 10.8 px, medium | `text-[10.8px] font-medium leading-normal` |
| Judul kategori, banner, teks utama navbar | 11.7 px | `text-[11.7px]` |
| Subjudul navbar | 10.8 px | `text-[10.8px]` |
| Keterangan profil | 9.9 px | `text-[9.9px]` |
| Badge kartu | 9 px, bold | `.card-badge` |
| Judul sambutan di bawah 640 px | 30 px | `text-3xl` |
| Judul sambutan 640–1023 px | 36 px | `sm:text-4xl` |
| Judul sambutan mulai 1024 px | 30.6 px, line-height 1.15 | Override `.portal-page #welcome-title` |
| Persentase di cincin | 18 px | `text-lg font-black` |
| Jumlah form selesai | 23.4 px | `text-[23.4px] font-black` |
| Penyebut jumlah form | 13.5 px | `text-[13.5px]` |
| Judul dialog | 18 px | `text-lg font-bold` |
| Isi dialog | 14 px | `text-sm leading-relaxed` |

Untuk halaman baru, gunakan judul halaman sans-serif `text-lg font-bold text-slate-800`. Teks isi form dan tabel boleh lebih besar daripada metadata kartu: gunakan 12–14 px sesuai jenis input dan kepadatan isi. Ini pola halaman baru, bukan komponen form yang sudah tersedia.

Jangan memakai Playfair Display untuk label input, tabel, pesan validasi, atau tombol. Uppercase dipakai untuk label kategori dan sebagian judul kartu; kalimat penjelasan tetap memakai kapitalisasi biasa.

## 6. Warna

| Peran | Acuan source |
| --- | --- |
| Latar halaman | `#f8fafc` |
| Permukaan kartu dan navbar | `bg-white` |
| Teks utama | `text-slate-800` |
| Teks umum | `text-slate-700` |
| Teks pendukung | `text-slate-500` atau `text-slate-600` |
| Border kartu | `border-slate-200/90` |
| Border kontrol | `border-slate-300` |
| Token merah brand | `--color-brand-red: #e52629` |
| Wordmark teks saat ini | `text-red-600` |
| Hero | `--color-brand-dark: #043b33` |
| Panel progres | `bg-[#022c22]/80` |
| Teks aksen hero | `text-teal-200`, `text-teal-300` |
| Progres cincin | `#10b981` |
| Progres bar | `from-teal-400 to-emerald-400` |
| Banner informasi | Latar `#e6f7f5`, border `#a2e6df`, teks `#0d6e66` |
| Focus ring | `#0f766e`, tebal 3 px, offset 4 px |

Gunakan nama utility Tailwind untuk warna slate/teal, jangan menyalin perkiraan hex dari screenshot. Warna SVG resmi mengikuti file logo aslinya.

Varian ikon yang sudah tersedia: `.tone-emerald`, `.tone-teal`, `.tone-amber`, `.tone-sky`, `.tone-slate`, `.tone-cyan`, `.tone-rose`, `.tone-purple`, dan `.tone-orange`. Setiap varian memakai latar warna tingkat 50 dan teks tingkat 700, kecuali slate yang memakai teks tingkat 600. Warna membantu pengenalan modul; nama modul tetap menjadi pembeda utama.

## 7. Radius, border, dan bayangan

| Elemen | Acuan |
| --- | --- |
| Kartu dan kotak ikon | `rounded-xl`, 12 px |
| Hero dan dialog | `rounded-2xl`, 16 px |
| Banner | `rounded-lg`, 8 px |
| Tombol header dan badge | `rounded`, 4 px |
| Border | Umumnya 1 px |
| Kartu normal | Tanpa shadow |
| Kartu saat hover | `hover:shadow-md`, border menjadi `slate-300` |
| Hero | `shadow-lg` |
| Dialog | `shadow-xl` |

Hover kartu hanya mengubah border dan bayangan selama 200 ms. Jangan memakai translate, scale, atau animasi pada ikon kartu karena membuat ikon tampak tidak sejajar.

## 8. Komponen yang digunakan ulang

### 8.1 Layout utama

Gunakan `View::render()` agar halaman memperoleh navbar, footer, CSS, JavaScript, skip link, dan dialog bersama. View halaman cukup berisi konten `<main>`; jangan membuat ulang `<html>`, `<head>`, atau `<body>`.

Layout menambahkan class `portal-page` ketika data `$groups` tersedia pada dashboard. Beberapa aturan hero dan banner desktop bergantung pada class tersebut. Jangan mengirim `$groups` palsu hanya untuk mengaktifkan CSS. Halaman baru yang membutuhkan varian serupa harus memakai class komponen yang jelas; perubahan flag layout dapat dilakukan sebagai perubahan tersendiri.

ID `main-content` harus ada satu kali pada setiap halaman agar skip link berfungsi. Judul dokumen berasal dari `$pageTitle`.

### 8.2 Navbar

Source: `app/Views/components/navbar.php`.

- Sticky di atas, `z-40`, latar putih, border bawah.
- Padding vertikal 12 px di semua ukuran layar.
- Konten mengikuti `.page-shell` yang sama dengan halaman.
- Jam dan tanggal memakai Asia/Jakarta.
- Profil menggunakan `$profile` dengan key `name`, `initials`, dan `subtitle`.
- Subtitle departemen tidak dianggap sebagai role autentikasi.
- Tombol akun menggunakan `.header-action`; jangan menduplikasi navbar di view halaman.

### 8.3 Brand dan logo SVG

Source: `app/Views/components/brand.php` dan `config/app.php`.

Letakkan SVG resmi di `public/assets/images/brand/rs-awal-bros.svg`. Komponen otomatis menampilkan file tersebut jika tersedia. Sebelum file tersedia, gunakan wordmark teks `RS AWAL BROS` dalam satu baris.

Logo SVG ditampilkan pada tinggi 32 px, lebar otomatis, maksimum 220 px, `object-contain`. Pertahankan `viewBox` SVG. Jangan memaksa rasio yang mengubah bentuk logo atau memberi tint pada SVG resmi.

### 8.4 Kartu portal

Source: `app/Views/components/portal-card.php`.

- Gunakan `.portal-card`, `.card-icon`, dan `.card-badge` bersama.
- Konten kartu dimulai dari atas melalui `justify-start`.
- Padding kartu 16 px, margin atas kotak ikon 4 px, margin bawah ikon 12 px.
- Kotak ikon 48 × 48 px dengan `shrink-0`; SVG di dalamnya 24 × 24 px.
- Ikon berada sebelum judul dan deskripsi, sehingga panjang judul tidak mengubah posisi awal ikon.
- Tinggi kartu mengikuti isi; kartu dalam satu baris mengikuti tinggi baris grid.
- Jangan menambahkan tinggi minimum besar, pemusatan vertikal, atau padding berbeda pada kartu tertentu.

Helper ikon:

```php
<?= icon('server', 'size-6') ?>
```

Nama yang tersedia: `server`, `user-check`, `thermometer`, `video`, `monitor`, `ticket`, `globe`, `dashboard`, `hospital`, `file`, `mail`, `target`, `bot`, `shield`, `calendar`, `chart`, `lock`, `users`, `logout`, `clock`, `close`, dan `info`. Nama yang tidak dikenal menghasilkan ikon `info`.

Tambah kartu melalui `config/portal.php`, bukan HTML dashboard yang disalin. Ikuti bentuk data berikut; `sort_order` disesuaikan dengan urutan kategori.

```php
[
    'key' => 'nama-modul',
    'title' => 'Nama Modul',
    'description' => 'Penjelasan singkat fungsi modul',
    'icon' => 'file',
    'tone' => 'teal',
    'category' => 'daily',
    'type' => 'internal',
    'target' => '/forms/nama-modul',
    'form_slug' => 'nama-modul',
    'enabled' => true,
    'sort_order' => 65,
]
```

Kategori yang ada: `daily`, `monitoring`, dan `systems`. Setiap `key` harus unik. Kartu eksternal memakai `type=external`, `form_slug=null`, dan target URL HTTPS yang dikelola pengembang. Kartu dengan `enabled=false` tidak ditampilkan.

Perilaku saat ini: URL eksternal HTTPS yang valid menjadi tautan tab baru dengan `noopener noreferrer`. Target eksternal kosong menampilkan dialog keterangan. Seluruh kartu internal masih menampilkan dialog modul belum tersedia; menambah route saja belum otomatis mengubah perilaku kartu. Saat modul internal sudah dibuat, tambahkan pemetaan kesiapan modul dan ubah komponen secara terpusat agar kartu tersebut menuju route yang benar. Hindari mengubah semua kartu internal sekaligus menjadi tautan jika modulenya belum tersedia.

### 8.5 Banner dan panel informasi

Banner kerahasiaan pada dashboard memakai `.security-notice`, font 11.7 px, ikon 16 px, dan padding 10 × 16 px pada layout dasar. Pada desktop mulai 1024 px, padding menjadi 8 × 12 px dan ikon 14 px.

Halaman baru tidak wajib mengulang banner atau hero dashboard. Gunakan panel putih berborder untuk isi form atau tabel agar halaman tetap ringkas.

```html
<section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-6">
    <h2 class="text-sm font-bold text-slate-800">Judul bagian</h2>
    <!-- Isi bagian -->
</section>
```

### 8.6 Hero dan progres

Source: `app/Views/components/progress-summary.php` dan `app/Views/home/index.php`.

Hero adalah elemen khusus dashboard. Pada desktop, padding 20 × 24 px dan judul 30.6 px. Panel progres memiliki padding 14 px dan gap 16 px. Cincin berukuran 72 px pada desktop, 80 px pada layout dasar. Bar target berukuran tinggi 8 px dan lebar 192 px mulai breakpoint `sm`.

Controller menyiapkan `completed`, `target`, `remaining`, `percentage`, dan `ratio`. Nilai selesai dibatasi antara nol dan target; target nol tidak dibagi. Cincin dan bar memakai rasio yang sama, sedangkan persentase teks dibulatkan. Role `progressbar` harus tetap memiliki nilai dan label aksesibilitas yang sesuai.

### 8.7 Dialog

Source: `app/Views/components/portal-dialog.php` dan `public/assets/js/app.js`.

Gunakan `<dialog>` native dengan `showModal()` dan `close()`. Dialog bersama dapat ditutup melalui Escape, tombol tutup, tombol kembali, dan klik di luar batas dialog. Pertahankan `aria-labelledby` serta `aria-describedby`.

Pemicu yang sudah ditangani JavaScript adalah `[data-module]` dan `[data-account]`. Jika halaman baru memiliki dialog khusus, gunakan ID dan selector tersendiri; jangan menggandakan `portal-dialog`, `dialog-title`, atau `dialog-description` di halaman.

## 9. Pola form dan tabel untuk halaman baru

Komponen form dan tabel bersama belum dibuat. Resep di bagian ini adalah pola awal agar halaman baru selaras, bukan API komponen yang sudah tersedia.

- Susun input dalam satu kolom pada mobile, maksimal dua kolom pada desktop untuk kelompok input yang berkaitan.
- Gunakan label yang terhubung ke `id` input. Placeholder tidak menggantikan label.
- Font input 13–14 px, tinggi minimal 40 px, border slate-300, radius 8 px.
- Pesan bantuan berada di bawah input; pesan error menyebutkan masalah yang bisa diperbaiki.
- Tabel memakai judul kolom yang jelas, header latar slate-50, pemisah baris slate-200, dan angka rata kanan bila dibandingkan antarbaris.
- Bungkus tabel yang lebar dengan `overflow-x-auto` di panelnya; jangan membuat seluruh body ikut melebar.

Contoh field:

```html
<div class="space-y-1.5">
    <label for="server_name" class="block text-xs font-semibold text-slate-700">
        Nama server
    </label>
    <input id="server_name" name="server_name" type="text" required
           class="min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-[13px] text-slate-800">
</div>
```

Gunakan tombol utama teal untuk tindakan seperti “Simpan draft” atau “Kirim pemeriksaan”, tombol sekunder putih berborder untuk “Kembali”, dan merah hanya untuk tindakan yang memang membatalkan atau menghapus. Tombol baru harus memiliki tindakan yang tersedia. Perubahan data melalui POST dan token CSRF baru diaktifkan setelah dukungan router dan backend dibuat.

Tampilkan keadaan kosong jika daftar belum berisi data. Jika ada pengambilan data asynchronous, tampilkan status memuat dan error yang bisa dicoba ulang. Dashboard saat ini dirender langsung oleh PHP dan tidak memerlukan indikator loading buatan.

## 10. Contoh menambah halaman dengan MVC yang tersedia

Contoh berikut membuat halaman awal riwayat hardware server. Ini contoh penambahan file, belum merupakan route yang terpasang. Jangan menyalin navbar atau HTML dokumen ke dalamnya.

### A. Buat controller

Lokasi: `app/Controllers/Forms/HardwareServerController.php`.

```php
<?php
declare(strict_types=1);

namespace App\Controllers\Forms;

use App\Core\View;

final class HardwareServerController
{
    public function index(): void
    {
        View::render('forms/hardware-server/index', [
            'pageTitle' => 'Monitoring Hardware Server',
            'profile' => config('dashboard')['profile'],
        ]);
    }
}
```

### B. Daftarkan route

Tambahkan ke `routes/web.php` setelah deklarasi `use` yang diperlukan. Jika controller dengan nama yang sama sudah ada, gunakan file yang ada.

```php
<?php
use App\Controllers\Forms\HardwareServerController;

$router->get('/forms/hardware-server', [new HardwareServerController(), 'index']);
```

Router saat ini hanya mendukung `get(path, callable)` dengan path persis. Parameter `{slug}`, `post()`, dan pemanggilan `middleware()` belum tersedia. Jangan menyalin API Laravel atau framework lain ke router ini. Pola nama route form berikutnya mengikuti rancangan arsitektur setelah dukungan router ditambahkan.

### C. Buat view

Lokasi: `app/Views/forms/hardware-server/index.php`.

```php
<main id="main-content" class="page-shell flex-1 space-y-5 py-4">
    <header class="space-y-2">
        <a href="/" class="text-xs font-medium text-teal-700 hover:underline">
            Kembali ke portal
        </a>
        <h1 class="text-lg font-bold text-slate-800"><?= e($pageTitle) ?></h1>
        <p class="text-xs text-slate-500">Riwayat pemeriksaan hardware server IT.</p>
    </header>

    <section aria-labelledby="history-title"
             class="rounded-xl border border-slate-200 bg-white p-4 sm:p-6">
        <h2 id="history-title" class="text-sm font-bold text-slate-800">
            Riwayat pemeriksaan
        </h2>
        <p class="mt-3 text-xs text-slate-500">Belum ada data yang ditampilkan.</p>
    </section>
</main>
```

Jika view benar-benar membutuhkan data, controller menyiapkannya. Query SQL nantinya ditempatkan pada Model, bukan pada view. Escape teks dan nilai atribut dinamis menggunakan `e()`. Gunakan `asset()` untuk URL aset dan `icon()` untuk ikon yang tersedia.

Route baru tetap perlu pengaturan hak akses ketika backend diintegrasikan. `AuthMiddleware`, `AdminMiddleware`, dan `CsrfMiddleware` masih merupakan rancangan arsitektur; contoh ini tidak memasang autentikasi atau penyimpanan form.

### D. Hubungkan dan bangun CSS

Saat modul siap, hubungkan kartu internal melalui perubahan komponen terpusat yang dijelaskan pada bagian kartu. Lalu jalankan `npm run build` atau watcher CSS, buka route baru, dan periksa tampilannya.

Tailwind memindai `app/**/*.php`, `config/**/*.php`, dan `public/assets/js/*.js`. Tulis nama class lengkap di source, misalnya `text-[11.7px]`. Jangan menyusun class baru seperti `bg-<?= $warna ?>-50` tanpa daftar class lengkap yang bisa dibaca Tailwind. Varian `.tone-*` sudah ditulis eksplisit dalam source CSS.

## 11. Batas penyesuaian halaman

- CSS baru ditulis di `resources/css/app.css`; jangan mengedit `public/assets/css/app.css` hasil build.
- Perubahan navbar, footer, logo, kartu, atau dialog bersama dilakukan di komponen aslinya.
- Pertahankan margin container yang lapang, ikon kartu sejajar, dan skala teks terakhir.
- Jangan menambahkan CDN Tailwind, font eksternal, framework frontend, atau icon library kedua untuk satu halaman.
- Jangan mengubah target URL melalui parameter browser. Daftar layanan eksternal tetap dikelola lewat konfigurasi.
- Status layanan harus sesuai sumbernya. Badge “Portal aktif” tidak menyatakan CCTV, HIS, atau layanan lain sedang online.
- Scope class khusus halaman agar tidak menimpa semua `button`, `h1`, `section`, atau `.grid` secara global.
- Berkas aset baru berada di `public/assets/`; controller, konfigurasi, dan dokumen internal tetap di luar document root.
- Layout sudah memberi versi CSS dan JavaScript melalui waktu perubahan file; jangan menghapus mekanisme tersebut.

## 12. Pemeriksaan sebelum mengajukan perubahan

1. Jalankan `npm run build` setelah menambah class atau mengubah CSS.
2. Jalankan `php -l` pada PHP yang diubah dan `node --check` bila JavaScript diubah.
3. Buka route langsung dan dari tautan/menu yang tersedia.
4. Periksa mobile, tablet, serta desktop pada zoom browser 100%; pastikan tidak ada teks atau body yang melebar keluar layar.
5. Bandingkan tepi navbar, judul halaman, panel, dan footer dengan `.page-shell`.
6. Pastikan ikon kartu tidak berubah posisi saat judul membungkus atau saat hover.
7. Coba Tab, Enter, dan Escape; pertahankan focus ring. Kontrol sentuh baru sebaiknya menyediakan area interaksi minimal 44 px.
8. Periksa keadaan kosong, validasi, error, serta loading bila halaman mengambil data.
9. Pastikan semua tombol memiliki tindakan dan tidak mengklaim penyimpanan berhasil sebelum backend tersedia.
10. Jika keputusan desain bersama berubah, perbarui dokumen ini dan komponen sumber dalam perubahan yang sama.

Catatan: nilai yang tercatat di dokumen berasal dari source. Hasil akhir tetap perlu diperiksa pada browser saat rekan menambah halaman baru.
