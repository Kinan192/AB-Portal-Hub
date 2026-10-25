# Design System AB Portal Hub

Panduan frontend Portal Monitoring IT RS Awal Bros Botania untuk rekan pengembang yang melakukan clone repository dan membuat halaman baru.

Dokumen ini mencatat implementasi terakhir pada 25 Oktober 2026. Ukuran di bawah mengikuti source project yang aktif, termasuk penyesuaian pengguna setelah referensi `code.html`. Jangan mengambil ukuran dari versi screenshot lama.

## 1. Sumber acuan dan batas pengembangan

| Sumber | Digunakan untuk |
| --- | --- |
| `design-system.md` | Patokan komponen, ukuran, interaksi, dan pola halaman baru |
| `resources/css/app.css` | Implementasi font, token, class komponen, dan aturan responsif |
| `app/Views/layouts/` | Kerangka HTML, header, varian header, dan footer |
| `app/Views/forms/` | Semua halaman pengisian, satu file per halaman |
| `app/Views/reports/` | Semua halaman laporan, satu file per halaman |
| `app/Views/components/` | Satu folder untuk elemen kecil yang digunakan ulang |
| `config/portal.php` | Definisi dan urutan kartu portal |
| `config/dashboard.php` | Identitas tampilan dan nilai progres awal frontend |
| Dokumen arsitektur awal dari pemilik project | Rancangan backend, database, autentikasi, dan middleware |

Dokumen arsitektur awal diberikan sebagai berkas terpisah bernama `architecture Portal Hub.md`. Saat panduan ini dibuat, tidak ada `architecture.md` di root repository. Panduan frontend ini dapat digunakan langsung; tim perlu menyertakan dokumen arsitektur tersebut dalam repository sebelum memulai integrasi backend.

Project ini adalah frontend aplikasi yang sedang dikembangkan langsung. Gunakan bahasa operasional seperti “Riwayat pemeriksaan”, “Simpan draft”, dan “Informasi portal”. Fitur yang belum tersedia harus dijelaskan sesuai kondisinya, tanpa mengklaim sudah tersambung ke backend.

Controller, View, router GET statis, konfigurasi, dan helper sudah digunakan. Form hardware server memiliki draft lokal di browser. Model operasional, autentikasi, middleware, serta penyimpanan form ke server belum diimplementasikan. Angka progres 5/6 (83%) saat ini berasal dari konfigurasi frontend dan akan diganti sumbernya saat integrasi backend.

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
| Judul kartu | 11.7 px, bold 700, uppercase | `.portal-card-title` |
| Deskripsi kartu | 10.8 px, medium 500 | `.portal-card-description` |
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

### 8.1.1 Breadcrumb bersama

Source: `app/Views/components/breadcrumbs.php`. Semua form dan laporan mengirim array `breadcrumbs` dari controller. Layout `main.php` merendernya satu kali sesudah header dan sebelum konten, dalam `.breadcrumb-shell.page-shell` dengan padding atas 16 px dan bawah 12 px. Jangan membuat breadcrumb lagi di navbar atau view halaman. View yang memakai container `.page-shell` menggunakan padding bawah tanpa padding atas agar hero dimulai setelah jarak bersama tersebut.

Komponen menambahkan Portal Monitoring Terpadu dengan tautan `/`. Controller memberikan trail sesudah home. Monitoring Harian berupa label kategori tanpa `href`, sehingga tidak mengulang tujuan home. Elemen terakhir selalu menjadi teks aktif dengan `aria-current=page` dan latar emerald-50; elemen tengah hanya menjadi tautan jika memiliki `href` route internal yang valid. Pada laporan, nama modul menaut ke form induk. Home dan halaman error tidak mengirim breadcrumb dan tidak menampilkannya.

Contoh data halaman laporan:

```php
'breadcrumbs' => [
    ['label' => 'Monitoring Harian'],
    ['label' => 'Kesesuaian User', 'href' => '/forms/kesesuaian-user'],
    ['label' => 'Rekap Kesesuaian User (HIS)'],
],
```

Untuk form, cukup kategori dan nama halaman aktif. Nama aktif laporan harus menyebut modul: `Rekap Monitoring Hardware Server IT`, `Rekap Kesesuaian User (HIS)`, dan `Rekap Monitoring Antrian Online BPJS`. Hindari label generik seperti `Rekap Hasil Inputan` tanpa nama modul. Jika halaman khusus kategori dibuat nanti, kategori boleh diberi tautan ke halaman tersebut; jangan mengarahkannya kembali ke `/` atau `/#section-daily`. Breadcrumb memakai font 12 px, separator `›` yang disembunyikan dari pembaca layar, dukungan wrap, dan focus ring dari style global. Komponen berada di luar `<main>` sehingga aturan cetak laporan menyembunyikannya bersama header.

### 8.2 Navbar

Source: `app/Views/layouts/header.php` dan `header-form.php`. Layout `main.php` memanggil `header.php`; file ini memilih varian header sesuai `navbarVariant`. Footer beserta wrapper tampilannya berada di `app/Views/layouts/footer.php`.

- Sticky di atas, `z-40`, latar putih, border bawah.
- Padding vertikal 12 px di semua ukuran layar.
- Konten mengikuti `.page-shell` yang sama dengan halaman.
- Jam dan tanggal memakai Asia/Jakarta.
- Profil menggunakan `$profile` dengan key `name`, `initials`, dan `subtitle`.
- Subtitle departemen tidak dianggap sebagai role autentikasi.
- Tombol akun menggunakan `.header-action`; jangan menduplikasi navbar di view halaman.

### 8.3 Brand dan logo SVG

Source: `app/Views/components/brand.php` dan `config/app.php`.

SVG pengguna sudah terpasang di `public/assets/images/brand/rs-awal-bros.svg`, dengan isi asli dari `logo-rs-awalbros (2).svg`. Komponen brand dipakai semua header dan otomatis menampilkan SVG tersebut. Wordmark teks `RS AWAL BROS` dalam satu baris hanya menjadi fallback jika file tidak tersedia.

Logo SVG ditampilkan pada tinggi 32 px, lebar otomatis, maksimum 220 px, `object-contain`. Pertahankan `viewBox="0 0 348 53"` dan atribut intrinsik 348 × 53. URL memakai versi `filemtime`. Jangan memaksa rasio yang mengubah bentuk logo atau memberi tint pada SVG resmi.

### 8.4 Kartu portal

Source: `app/Views/components/portal-card.php`.

- Gunakan `.portal-card`, `.card-icon`, dan `.card-badge` bersama.
- Semua judul memakai `.portal-card-title` dengan ukuran, ketebalan, line-height, dan huruf kapital yang sama. Jangan memberi pengecualian pada modul/kategori tertentu. Deskripsi memakai `.portal-card-description`.
- Kartu memakai grid dengan tiga baris bersama (`grid-template-rows: subgrid`, `grid-row: span 3`): ikon, judul, dan deskripsi. Setiap kartu dalam satu baris mengikuti tinggi track yang sama, sehingga awal deskripsi sejajar walaupun judul membungkus berbeda. `row-gap: 0` menjaga jarak internal mengikuti margin komponen, sementara grid luar mempertahankan gap antarkartu.
- Padding kartu 16 px, margin atas kotak ikon 4 px, margin bawah ikon 12 px.
- Kotak ikon 48 × 48 px dengan `shrink-0`; SVG di dalamnya 24 × 24 px.
- Ikon berada sebelum judul dan deskripsi, sehingga panjang judul tidak mengubah posisi awal ikon.
- Area judul mengikuti tinggi konten, tanpa minimum dua baris. Judul diratakan ke atas dengan `self-start`, sehingga baris pertamanya sejajar dengan kartu lain di baris yang sama. Track subgrid mengikuti judul terpanjang pada baris tersebut dan menjaga awal deskripsi sejajar. Margin atas deskripsi 2 px (`mt-0.5`); ruang tersisa di bawah judul pendek memang diperlukan agar judul dan deskripsi sama-sama sejajar. Jangan memakai line-clamp atau tinggi tetap yang memotong teks.
- Tinggi kartu mengikuti isi; kartu dalam satu baris mengikuti tinggi baris grid.
- Jangan menambahkan tinggi minimum besar, pemusatan vertikal, atau padding berbeda pada kartu tertentu.

Rujukan mekanisme perataan: [CSS subgrid (MDN)](https://developer.mozilla.org/en-US/docs/Web/CSS/Guides/Grid_layout/Subgrid).

Helper ikon:

```php
<?= icon('server', 'size-6') ?>
```

Nama yang tersedia: `server`, `user-check`, `thermometer`, `video`, `monitor`, `ticket`, `globe`, `dashboard`, `hospital`, `file`, `mail`, `target`, `bot`, `shield`, `calendar`, `chart`, `lock`, `users`, `logout`, `clock`, `close`, `info`, `home`, `network`, `clipboard`, `location`, `check-circle`, `cube`, `warning`, `arrow-left`, `bookmark`, dan `send`. Nama yang tidak dikenal menghasilkan ikon `info`.

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

Perilaku saat ini: URL eksternal HTTPS yang valid menjadi tautan tab baru dengan `noopener noreferrer`. Target eksternal kosong menampilkan dialog keterangan. Kartu internal dengan `available=true` menjadi tautan dalam tab yang sama dan berlabel `Form`; saat ini Hardware Server, Kesesuaian User, dan Antrian Online sudah tersedia. Kartu internal tanpa properti tersebut tetap berlabel `Segera` dan membuka dialog. Tambahkan `available=true` pada konfigurasi kartu setelah route dan view modul selesai.

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

Form hardware server tersedia sebagai acuan implementasi. Komponen tabel bersama belum dibuat. Resep umum di bawah tetap berlaku untuk halaman berikutnya.

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

Warna aksi form memakai class bersama di `resources/css/app.css`. Pasangkan varian warna berikut dengan `.inspection-button` untuk tombol standar, atau class ukuran kontrol yang sudah ada untuk tombol kecil/hero. Jangan menambah utility warna lain pada tombol atau ikonnya agar tidak menimpa varian bersama. Aturan ini juga berlaku pada dialog ringkasan.

| Fungsi | Class warna | Tampilan |
| --- | --- | --- |
| Kirim laporan/verifikasi | `.button-primary` | Teal-700, teks putih; hover teal-800 |
| Simpan Draft | `.button-secondary` | Putih, border teal-600, teks teal-700; hover teal-50 |
| Kembali ke portal/form | `.button-neutral` | Putih, border slate-300, teks slate-700; hover slate-50 |
| Riwayat/rekap, catatan cepat, Set Jam Sekarang | `.button-soft` | Teal-50, teks teal-800; hover teal-100 |
| Reset/Clear Form dan tutup dialog | `.button-ghost` | Transparan, teks slate-500; hover slate-100 |
| Aksi pada hero hijau tua | `.button-on-dark` | Putih, teks teal-800; hover teal-50 |
| Hapus berkas | `.button-danger` | Transparan, teks red-700; hover red-50 |

Warna hijau/merah pada pilihan kondisi atau status pengiriman tetap menunjukkan status, terpisah dari warna tombol aksi. Tombol baru harus memiliki tindakan yang tersedia. Perubahan data melalui POST dan token CSRF baru diaktifkan setelah dukungan router dan backend dibuat.

Tampilkan keadaan kosong jika daftar belum berisi data. Jika ada pengambilan data asynchronous, tampilkan status memuat dan error yang bisa dicoba ulang. Dashboard saat ini dirender langsung oleh PHP dan tidak memerlukan indikator loading buatan.

### 9.1 Form hardware server yang sudah tersedia

Source: `app/Views/forms/hardware-server.php`, `app/Views/components/hardware-condition.php`, `config/hardware-server.php`, dan `public/assets/js/hardware-server.js`.

Kelompokkan view berdasarkan fungsinya. Semua halaman pengisian berada di `forms/`, semua halaman laporan di `reports/`, dan hanya ada satu folder `components/`. Gunakan struktur berikut untuk halaman selanjutnya:

```text
app/Views/
├── forms/
│   ├── antrian-online.php
│   ├── hardware-server.php
│   └── kesesuaian-user.php
├── reports/
│   ├── antrian-online.php
│   ├── hardware-server.php
│   └── kesesuaian-user.php
├── layouts/
│   ├── main.php
│   ├── header.php
│   ├── header-form.php
│   └── footer.php
├── components/
│   ├── brand.php
│   ├── breadcrumbs.php
│   ├── hardware-condition.php
│   ├── hardware-server-hero.php
│   ├── portal-card.php
│   ├── portal-dialog.php
│   └── progress-summary.php
├── home/
│   └── index.php
└── errors/
    ├── 404.php
    └── unavailable.php
```

Tambahkan file halaman pengisian berikutnya ke `forms/` dan halaman riwayat/rekap ke `reports/`. Jangan membuat ulang folder `form/`, `reports/`, atau `components/` di dalam folder bernama modul. `layouts/` hanya untuk kerangka dokumen dan struktur global seperti header serta footer. Bagian konten yang dipakai ulang seperti hero hardware, kartu, dialog, logo, atau pilihan kondisi masuk `components/`, meskipun ukurannya besar. Pembagian mengikuti fungsi, bukan ukuran atau jumlah baris. URL route tetap independen dari lokasi fisik view.

Pola URL: pengisian `/forms/{nama-modul}`, laporan `/reports/{nama-modul}`. Ketiga laporan menggunakan `/reports/hardware-server`, `/reports/kesesuaian-user`, dan `/reports/antrian-online`. Daftarkan laporan hanya pada `/reports/...` dan arahkan semua tautan navigasi ke URL tersebut. Placeholder `{nama-modul}` adalah konvensi penamaan; router saat ini tetap mendaftarkan path statis secara eksplisit.

- Route: `/forms/hardware-server`; controller: `App\Controllers\Forms\HardwareServerController`.
- `navbarVariant=form` memilih varian header kompak. Breadcrumb tetap dirender oleh layout di bawah header, sama seperti form dan laporan lainnya. Komponen brand, profil, jam, ikon, dan dialog akun tetap memakai sumber bersama.
- Form, laporan, dan isi hero memakai `.page-shell` yang sama dengan home dan navbar: lebar 90% mulai desktop, maksimal 1440 px, dengan padding mobile 20/32 px. Jangan menambahkan batas 1024 px pada halaman hardware server.
- `.inspection-panel`: radius 16 px, border slate-200, padding 24 px pada mobile dan 32 px mulai `md`. Jarak antarpanel 24 px. Heading bagian 16 px; label pilihan 14 px; deskripsi 12 px; teks input 13 px.
- `.inspection-choice` memakai radio native yang tetap dapat difokuskan melalui keyboard. State checked berborder teal-600 dan berlatar `#f0fdf4`; fokus memiliki outline. Pilihan awal mengikuti HTML referensi: rak pertama, CPU BAIK, dan monitor BAIK.
- Kelompok CPU/monitor memakai partial `components/hardware-condition.php`. Tambahkan konfigurasi teks melalui controller/config, jangan menyalin struktur pilihan radio.
- Keterangan Other wajib terisi ketika dipilih. Catatan cepat menambahkan teks ke textarea tanpa menggandakan rekomendasi yang sama. Reset dan Input Form Baru mengembalikan pilihan awal serta menghapus draft setelah konfirmasi.
- Simpan Draft menggunakan `localStorage` dengan versi format dan nama profil, lalu memuat draft pada pembukaan berikutnya di browser yang sama. Ini belum merupakan penyimpanan lintas akun/perangkat; sumber identitas dan penyimpanan harus diganti saat autentikasi/backend diintegrasikan.
- Kirim Laporan membuka ringkasan setelah validasi dan menyatakan pengiriman belum tersedia. Lihat Riwayat & Rekap Laporan mengarah ke `/reports/hardware-server`. Jangan menampilkan status berhasil dikirim sebelum ada respons server yang berhasil.
- Script khusus halaman didaftarkan melalui array `pageScripts` di controller. Layout memuatnya dengan `defer` dan versi `filemtime`; jangan memasukkan script inline ke view.
- Controller mempertahankan pembatasan `APP_ENV=local` sampai autentikasi tersedia.

### 9.2 Rekap bulanan hardware server

Source: `app/Views/reports/hardware-server.php`, `config/hardware-server-reports.php`, dan `public/assets/js/hardware-server-history.js`. Route `/reports/hardware-server` memakai method `history()` pada controller hardware yang sama. Header dan `components/hardware-server-hero.php` digunakan bersama dengan form; tombol Input Form Baru mengarah kembali ke form, dan breadcrumb menunjukkan Rekap Monitoring Hardware Server IT.

- Pertahankan `.page-shell` yang sama dengan home (90% pada desktop, maksimal 1440 px), jarak antarpanel 24 px, radius 16 px. Heading rekap 18 px; isi tabel 12 px; header dan badge tabel 10–11 px, mengikuti HTML referensi.
- Panel kontrol memakai satu kolom pada mobile, dua mulai `sm`, dan empat mulai `md`. Label terhubung ke setiap input. Kelompok tombol ekspor dapat membungkus ketika ruang menyempit.
- Tabel memakai `scope=col`, caption, dan wrapper horizontal yang bisa difokuskan. Lebar minimum 1380 px menjaga delapan kolom terbaca; hanya tabel yang bergeser, bukan seluruh halaman. Catatan penuh tersedia pada dialog rincian, sehingga teks tabel boleh dipotong dengan ellipsis.
- Filter bulan/rak/status dapat digabung dengan pencarian beberapa kata. Pencarian mencakup catatan, nama/NIK petugas, identitas laporan, rak, dan kondisi. Perubahan filter mengembalikan pagination ke halaman pertama; tujuh baris per halaman berasal dari konfigurasi. Total dan tombol halaman dihitung dari hasil yang sebenarnya, tanpa jumlah 31 atau halaman tambahan buatan.
- Kondisi kosong menampilkan penjelasan dan Reset Filter. Tombol ekspor/cetak dinonaktifkan ketika tidak ada hasil.
- Data awal adalah tujuh baris dari HTML referensi, dengan pemberitahuan sumber pada halaman. Ganti sumber controller dengan Model saat data operasional tersedia; jangan menganggap data referensi sebagai laporan pemeriksaan sungguhan.
- Rincian memakai dialog native. Data JSON diserialisasi dengan opsi `JSON_HEX_*`; nilai dimasukkan ke DOM menggunakan `textContent`.
- Cetak Rekap membuka dialog cetak browser. CSS cetak menampilkan semua hasil filter lintas halaman, identitas rumah sakit, dan ringkasan filter; navigasi, hero, tombol aksi, dan pagination tidak ikut dicetak. Tata letak cetak memakai A4 landscape.
- Ekspor Excel/PDF dan unduh lembar membuka keterangan integrasi yang belum tersedia. Jangan membuat pesan unduhan berhasil atau file dengan ekstensi palsu. Ekspor operasional menunggu backend dan hak akses.
- Ikon tambahan: `chart-search`, `table`, `printer`, `search`, `chevron-down`, `eye`, dan `download`, melalui helper `icon()` yang sama.

### 9.3 Form Kesesuaian User HIS

Route `/forms/kesesuaian-user` menggunakan `KesesuaianUserController::index()` dan view `app/Views/forms/kesesuaian-user.php`. Halaman laporannya berada di `app/Views/reports/kesesuaian-user.php`, tanpa folder modul tambahan. Konten alasan dan sampel berasal dari `config/kesesuaian-user.php`, dan script khusus halaman dari `public/assets/js/kesesuaian-user.js`.

- Gunakan navbar global dan breadcrumb bersama dari layout; jangan menulis breadcrumb di view. Hero berwarna hijau dan judul sans-serif 24/30 px. Container sama dengan home: 90% pada desktop, maksimal 1440 px.
- Isi memakai satu kolom pada mobile dan grid 12 kolom mulai `lg`: form delapan kolom dan sampel terakhir empat kolom. Panel putih memakai radius 12 px, padding 24/32 px. Field 13 px dengan tinggi 48 px; label 14 px; deskripsi 12 px.
- Pilihan kesesuaian memakai radio native, dengan `.compliance-option`, `.compliance-radio`, dan fokus keyboard yang terlihat. Pilihan Tidak Sesuai membuka alasan dan catatan. Alasan wajib terisi untuk status tersebut; catatan wajib untuk alasan Lainnya. Field tersembunyi dinonaktifkan agar tidak menghalangi validasi.
- Username wajib terisi dan tidak boleh mengandung spasi. Tanggal/jam login wajib terisi; Set Jam Sekarang menggunakan `Intl.DateTimeFormat` dengan zona Asia/Jakarta, tanpa mencampur tanggal UTC dengan jam lokal.
- Draft lokal memiliki versi format dan nama profil. Draft dapat memuat isian belum lengkap; format, panjang teks, alasan, dan tanggal/jam diverifikasi saat dimuat kembali. Checkbox konfirmasi tidak dipulihkan dari draft agar pemeriksaan harus dikonfirmasi kembali.
- Kirim Verifikasi membuka ringkasan setelah validasi dan menjelaskan pengiriman belum tersedia. Kedua tombol rekap membuka `/reports/kesesuaian-user`; jangan mengarahkannya ke laporan hardware atau menampilkan penyimpanan berhasil tanpa respons server.
- Sidebar memakai tiga sampel dari HTML referensi, ditandai sebagai data referensi. Jumlah badge mengikuti array, tanpa mengklaim Hari Ini (4). Ganti sumber data melalui Model setelah backend tersedia.
- View memiliki anotasi PHPDoc untuk variabel yang dikirim controller. Ikon `user-search` dan `history` menggunakan helper yang sama. Controller tetap membatasi akses ke `APP_ENV=local` sampai autentikasi tersedia.

### 9.4 Laporan Kesesuaian User HIS

Route `/reports/kesesuaian-user` memakai `KesesuaianUserController::history()`, view `app/Views/reports/kesesuaian-user.php`, konfigurasi `config/kesesuaian-user-reports.php`, dan script `public/assets/js/kesesuaian-user-history.js`.

- Navbar global, breadcrumb, dan `.page-shell` mengikuti halaman form/home. Hero berwarna hijau dengan judul sans-serif 24/30 px. Empat kartu ringkasan memakai grid satu/dua/empat kolom sesuai breakpoint, padding 20 px, dan angka 30 px.
- Ringkasan dihitung dari data yang tersedia dan mengikuti filter tabel, termasuk jumlah sesuai/tidak sesuai, persentase, dan sampling terakhir. Data awal tujuh baris referensi menghasilkan 7/5/2 dan 71,4%; jangan memakai 128/122/6 sebagai angka operasional tanpa sumber data.
- Filter periode, status, unit, dan pencarian beberapa kata dapat digabung. Pencarian mencakup username, nama lengkap, unit, petugas, dan temuan. Perubahan filter mengembalikan halaman pertama. Kondisi kosong menampilkan nol, persentase “—”, pesan kosong, dan Reset Filter.
- Tabel memiliki enam kolom, font isi 12 px, header 10 px, dan lebar minimum 1120 px. Kolom temuan membungkus agar seluruh isinya terbaca. Wrapper tabel dapat digeser; body halaman tetap mengikuti container. Username berupa tombol yang membuka rincian pada dialog native sehingga tidak perlu kolom aksi tambahan.
- Pagination dihitung dari hasil filter dengan tujuh baris per halaman. Kartu ringkasan menghitung seluruh hasil, bukan hanya baris di halaman aktif. Tampilan cetak A4 landscape memuat seluruh hasil filter lintas halaman dan ringkasan filter.
- Data referensi diberi penanda. Ekspor Excel/PDF membuka keterangan integrasi belum tersedia; tidak membuat unduhan palsu. Penyimpanan serta ekspor operasional menunggu backend dan kontrol akses. Controller mempertahankan batas `APP_ENV=local`.
- Komponen logo mengikuti SVG bersama; jangan menyalin markup header/footer atau membuat folder components di dalam reports.

### 9.5 Form Monitoring Antrian Online BPJS

Route `/forms/antrian-online` memakai `AntrianOnlineController::index()`, view `app/Views/forms/antrian-online.php`, konfigurasi `config/antrian-online.php`, dan script `public/assets/js/antrian-online.js`. Kartu Antrian Online bertipe internal dengan `available=true`. Kedua tautan rekap mengarah ke `/reports/antrian-online`.

- Gunakan header, footer, logo, dan `.page-shell` bersama. Hero hijau memakai judul sans-serif 24/30 px dan padding 24/36 px; metadata, dua kelompok cron, endpoint/tiket, serta unggah/catatan mengikuti susunan referensi. Panel isi putih radius 12 px, padding 20/32 px. Permukaan field memakai `#eff4ff`, label utama 14 px, deskripsi 12 px, dan metadata monospace 9–11 px. Jangan membawa CSS global referensi yang menyembunyikan scrollbar atau mengunci ukuran body.
- Pasangan cron, endpoint/tiket, serta unggah/catatan memakai `.queue-paired-grid` dan `.queue-field-group`. Mulai `lg`, setiap pasangan berbagi tiga track subgrid agar judul, isi, dan keterangan sejajar meskipun teks membungkus. Bagian unggah mengikuti tinggi gabungan catatan dan konfirmasi. Pada layar kecil, masing-masing bagian kembali satu kolom dengan tinggi alami. Jangan menyamakan tinggi memakai angka tetap atau memotong deskripsi.
- Radio cron menggunakan `.queue-choice` dan `.queue-radio` native dengan focus ring. Status TERKIRIM berwarna mint, TIDAK TERKIRIM merah. Pilihan awal mengikuti referensi; konfirmasi pemeriksaan tetap wajib dicentang pengguna. Jika salah satu cron gagal, catatan tindak lanjut wajib terisi dan tidak boleh hanya spasi.
- Shift awal dihitung menurut WIB: pagi 07:00–14:00, siang 14:00–21:00, malam 21:00–07:00. Perubahan shift memperbarui label periode, dan waktu pencatatan ditampilkan dalam Asia/Jakarta.
- Empat jumlah tiket wajib berupa bilangan bulat nonnegatif. Nilai awal 284/112/68/18 merupakan input manual dari referensi, dengan penanda pada halaman; jangan mengklaimnya sebagai tiket tersinkron langsung atau menampilkan 100% Pushed tanpa sumber.
- Endpoint hanya ditampilkan dari konfigurasi. Tidak ada pemanggilan BPJS, status HTTP 200, latency 124 ms, atau status keamanan/uptime tiruan.
- Pemilihan bukti menerima satu berkas PNG/JPG/JPEG/PDF/TXT/LOG, berisi data, maksimal 5 MiB. Tipe/ekstensi dan ukuran diperiksa di browser. Pemilihan normal dan drop mengisi input file; nama berkas dimasukkan lewat `textContent`. Pemeriksaan tipe/konten dan penyimpanan server tetap diperlukan pada integrasi backend. Jangan menampilkan berkas seolah terlampir dari daemon.
- Draft lokal menyimpan isian dan nama berkas sebagai pengingat, tanpa byte berkas atau checkbox konfirmasi. Pemulihan draft meminta pemilihan berkas dan konfirmasi ulang. Draft dapat berisi jumlah tiket yang belum diisi. Reset meminta konfirmasi, menghapus draft, mengosongkan catatan/bukti/konfirmasi, mengatur tiket ke nol, serta memperbarui waktu dan shift WIB.
- Kirim Laporan membuka ringkasan setelah validasi, dengan keterangan bahwa laporan dan berkas belum dikirim. Tambahkan POST, validasi server, CSRF, identitas sesi, serta penyimpanan saat backend diintegrasikan. Controller tetap dibatasi ke `APP_ENV=local`.
- Ikon `upload` dan `reset` berasal dari helper `icon()` bersama.

### 9.6 Rekap Monitoring Antrian Online BPJS

Route `/reports/antrian-online` memakai `AntrianOnlineController::history()`, view `app/Views/reports/antrian-online.php`, konfigurasi `config/antrian-online-reports.php`, dan script `public/assets/js/antrian-online-history.js`. Semua halaman tetap memakai header, footer, logo, dan `.page-shell` bersama, tanpa folder modul atau komponen tambahan.

- Hero hijau memiliki judul sans-serif 24/30 px. Empat kartu ringkasan mengikuti grid satu/dua/empat kolom dan menampilkan jumlah sesi, persentase cron antrian, persentase cron bed, serta audit terakhir. Angka dihitung dari seluruh hasil filter, bukan hanya halaman aktif. Tujuh baris referensi menghasilkan 7 sesi, 6 cron antrian terkirim (85,7%), dan 7 cron bed terkirim (100%). Jangan memakai jumlah 62 atau 98,4% tanpa sumber data yang mendukung.
- Panel log memakai lima filter: bulan, status cron antrian, status cron bed, shift, dan pencarian. Layout kontrol satu/dua/lima kolom mengikuti breakpoint. Pencarian frasa tidak peka huruf besar/kecil dan mencakup tanggal WIB, kode HTTP, nama lampiran, petugas, serta catatan. Filter dapat digabung; perubahan mengembalikan pagination ke halaman pertama. Hasil kosong menampilkan nol dan persentase “—”, lalu menyediakan Reset Filter.
- Tabel memiliki tujuh kolom, font isi 12 px, header/badge monospace 10 px, dan lebar minimum 1280 px. Catatan lengkap membungkus, wrapper tabel dapat digeser, dan hanya ada satu footer global. Nomor audit membuka rincian melalui dialog native.
- Nama bukti log mengikuti referensi HTML. Klik lampiran menjelaskan berkas belum tersedia; tidak membuat URL unduhan palsu. Tombol SOP juga menjelaskan dokumen belum ditambahkan, tanpa mengklaim versi atau aturan operasional yang belum diverifikasi dari dokumen resmi. Ekspor Excel/PDF menunggu backend.
- Cetak Rekap menggunakan dialog cetak browser, A4 landscape, dengan identitas rumah sakit, ringkasan filter, dan semua hasil lintas halaman. Navigasi, hero, kartu, tombol, pagination, dan panel SOP tidak dicetak. Penanda data referensi tetap tercetak.
- Data JSON memakai `JSON_HEX_*` dan teks dialog dimasukkan melalui `textContent`. Kode HTTP dan latency pada catatan merupakan konten referensi historis, bukan hasil pemanggilan BPJS langsung. Controller tetap membatasi akses ke `APP_ENV=local` sampai autentikasi dan penyimpanan server tersedia.

## 10. Contoh menambah halaman dengan MVC yang tersedia

Route `/forms/hardware-server`, controller, dan view yang disebut di bawah sudah dipakai oleh form pemeriksaan. Jangan menimpa file tersebut atau menggandakan route. Contoh ringkas ini menjelaskan pola MVC; gunakan nama modul dan path baru untuk halaman selanjutnya. Jangan menyalin navbar atau HTML dokumen ke dalam view.

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
        View::render('forms/hardware-server', [
            'pageTitle' => 'Monitoring Hardware Server',
            'breadcrumbs' => [
                ['label' => 'Monitoring Harian'],
                ['label' => 'Form Monitoring Hardware Server IT'],
            ],
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

Lokasi: `app/Views/forms/hardware-server.php`.

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
