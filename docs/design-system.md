# Design System AB Portal Hub

Panduan frontend Portal Monitoring IT RS Awal Bros Botania untuk rekan pengembang yang melakukan clone repository dan membuat halaman baru.

Dokumen ini mencatat implementasi terakhir pada 27 Oktober 2026. Ukuran di bawah mengikuti source project yang aktif, termasuk penyesuaian pengguna setelah referensi `code.html`. Jangan mengambil ukuran dari versi screenshot lama.

## 1. Sumber acuan dan batas pengembangan

| Sumber | Digunakan untuk |
| --- | --- |
| `design-system.md` | Patokan komponen, ukuran, interaksi, dan pola halaman baru |
| `resources/css/app.css` | Implementasi font, token, class komponen, dan aturan responsif |
| `app/Views/layouts/` | Kerangka HTML, satu header bersama, dan footer |
| `database/dummy/builtin-forms.php` | Daftar pertanyaan keenam form monitoring bawaan (bagian 9.1) |
| `app/Views/form-dinamis/fill.php`, `respons.php` | Satu halaman form dan satu halaman riwayat untuk semua form (bagian 9.1 dan 9.2) |
| `app/Views/components/` | Satu folder untuk elemen kecil yang digunakan ulang |
| `database/dummy/dashboard.php` | Bagian, kartu, dan jadwal dashboard |
| `config/dashboard.php` | Identitas tampilan dan nilai progres awal frontend |
| Dokumen arsitektur awal dari pemilik project | Rancangan backend, database, autentikasi, dan middleware |

Dokumen arsitektur awal diberikan sebagai berkas terpisah bernama `architecture Portal Hub.md`. Saat panduan ini dibuat, tidak ada `architecture.md` di root repository. Panduan frontend ini dapat digunakan langsung; tim perlu menyertakan dokumen arsitektur tersebut dalam repository sebelum memulai integrasi backend.

Project ini adalah frontend aplikasi yang sedang dikembangkan langsung. Gunakan bahasa operasional seperti “Riwayat pemeriksaan”, “Kirim laporan”, dan “Informasi portal”. Fitur yang belum tersedia harus dijelaskan sesuai kondisinya, tanpa mengklaim sudah tersambung ke backend.

Controller, View, router GET statis, konfigurasi, dan helper sudah digunakan. Model operasional, autentikasi, middleware, serta penyimpanan form ke server belum diimplementasikan. Angka progres harian saat ini dihitung dari kartu form dummy yang terjadwal hari ini dan akan diganti sumbernya saat integrasi backend.

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
- Semua halaman memakai Inter, termasuk dashboard, navbar, form, rekap, akun, dan login. Ukuran mengikuti peran teks agar hierarki judul, isi, label, dan keterangan tetap jelas.
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

Font aktif adalah Inter, disimpan lokal di `public/assets/fonts/` dalam bobot 400, 500, 600, 700, 800, dan 900. Deklarasi bersama berada di `resources/css/design-chrome.css`. Semua layout memuat font yang sama; utility `font-sans`, `font-serif`, dan `font-mono` juga diarahkan ke Inter agar halaman baru tidak membawa jenis font berbeda.

Skala konten form/rekap mengikuti halaman Clear Desk, Suhu, dan CCTV. Aturan bersama berada di `resources/css/module-ui.css`, yang diimpor oleh `app.css` dan `design.css`. Judul hero responsif 22–28 px; judul panel utama 20 px; judul kelompok pemeriksaan 17 px; input dan tombol standar 14 px; tabel dan aksi kecil 13 px; keterangan/metadata 12 px; header tabel 11.5 px. Angka ringkasan dapat lebih besar sesuai fungsinya. Metadata lama 9–11 px pada Hardware, Kesesuaian User, dan Antrian Online sudah dinaikkan menjadi 12 px. Cetak laporan tetap memakai skala cetak tersendiri.

Ukuran desimal merupakan hasil kalibrasi terakhir pengguna. Pertahankan angka ini saat menyalin komponen; perubahan skala bersama dilakukan melalui komponen/source CSS, bukan zoom browser atau transform.

Untuk konten baru, gunakan class peran di `module-ui.css`: `ui-panel-title` (20 px), `ui-section-title` (17 px), `ui-field-label`/`ui-body` (14 px), dan `ui-caption` (12 px). Tombol standar 14 px dan aksi kecil 13 px. Form Suhu memakai peran eksplisit tersebut agar label dan keterangan tidak mewarisi ukuran bawaan yang berbeda. Angka ringkasan dan ukuran cetak tetap mengikuti fungsinya.

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

Gunakan Inter untuk label input, tabel, pesan validasi, tombol, dan judul. Uppercase dipakai untuk label kategori dan sebagian judul kartu; kalimat penjelasan tetap memakai kapitalisasi biasa.

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

### 8.1.1 Tombol kembali dan breadcrumb bersama

Source: `config/monitoring-navigation.php`, helper `monitoring_breadcrumbs()`, serta `app/Views/components/breadcrumbs.php` dan `breadcrumb.php`. Halaman form, editor form, dan semua halaman riwayat mengirim `'backToDashboard' => true` dan menampilkan tombol outline **Kembali ke Dashboard** (ikon panah kiri, tinggi minimal 44 px, `aria-label`, focus ring) menuju `/`; view boleh mengisi `$breadcrumbAside` untuk konten di kanan baris yang sama. Riwayat keenam modul bawaan juga mengirim array `breadcrumbs` dari `DynamicFormController::history()`; breadcrumb tampil di kanan baris tombol dan turun ke baris berikutnya bila ruang tidak cukup. Layout `main.php` dan `design.php` merendernya satu kali sesudah header dan sebelum konten, dalam `.breadcrumb-shell.page-shell` dengan padding vertikal 20 px. Jangan membuat breadcrumb lagi di navbar atau view halaman.

Urutan selalu `Portal Monitoring Terpadu / Monitoring Harian / Nama Modul / Nama Halaman`. Hanya Portal Monitoring Terpadu memiliki tautan `/`. Kategori, nama modul, dan halaman aktif berupa teks tanpa tautan maupun cursor pointer, pada halaman riwayat. Halaman aktif memiliki `aria-current=page`. Home dan halaman error tidak menampilkan breadcrumb monitoring.

Contoh data halaman laporan:

```php
'breadcrumbs' => monitoring_breadcrumbs('suhu'),
```

Helper menghasilkan `Monitoring Harian / Suhu Server / Rekap Suhu Server Bulanan` sesudah portal; nama halaman rekap tetap menyebut modulnya. Keenam modul memakai pola yang sama. Breadcrumb memakai font 13 px, separator `/` yang disembunyikan dari pembaca layar, dukungan wrap, dan focus ring global. Komponen berada di luar `<main>` dan disembunyikan saat cetak.

### 8.2 Navbar

Source: `app/Views/layouts/header.php`. Layout `main.php` dan `design.php` memanggil file ini langsung. Navbar home menjadi satu-satunya navbar untuk dashboard, form, rekap, dan akun; jangan membuat varian navbar form. Halaman login memakai layout `auth.php` tanpa navbar. Style navbar dan font Inter bersama berada di `resources/css/design-chrome.css`, yang dibangun ke kedua bundle CSS. Jam WIB diperbarui oleh `public/assets/js/app.js`. Footer tetap mengikuti layout masing-masing.

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

Kartu dashboard berasal dari `database/dummy/dashboard.php` (bagian `sections` dan `cards`), bukan HTML dashboard yang disalin. Admin juga dapat menambah form dan tautan lewat tombol **+ Tambah**; perubahan itu disimpan di tab browser. Jenis kartu: `builtin` (form monitoring bawaan, dengan `url` halaman form dan `rekap` halaman riwayat), `form` (form dinamis), dan `link` (website eksternal). Kartu form memiliki `schedule` untuk badge jadwal dan filter Jadwal Hari Ini.

Perilaku saat ini: kartu form membuka halaman form-nya, dan tombol **Lihat Rekap** membuka riwayatnya (bagian 9.2). URL eksternal `http`/`https` yang valid menjadi tautan tab baru dengan `noopener noreferrer`; kartu tautan tanpa URL menampilkan "Alamat belum diatur". Menu ⋯ kartu form berisi Lihat Rekap, Edit Isi Form, Edit Kartu, Duplikat, Sembunyikan, dan Hapus; kartu tautan berisi Edit, Pindah Kategori, Sembunyikan, dan Hapus.

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

Form monitoring dan riwayatnya tidak lagi dibuat per modul: keduanya digambar dari daftar pertanyaan (bagian 9.1 dan 9.2). Resep umum di bawah berlaku untuk halaman lain yang membutuhkan form atau tabel.

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

Halaman form dan riwayat memakai tombol `.gf-btn` (gform.css) dan `.dash-btn` (dashboard.css). Halaman lain, seperti akun dan dialog design system, memakai class bersama di `resources/css/module-ui.css`. Pasangkan varian warna berikut dengan `.module-action` untuk font tombol standar 14 px, `.module-action-small` untuk aksi kecil 13 px, atau `.module-page-button` untuk pagination 13 px. Bentuk dan padding memakai utility ukuran yang sesuai. Jangan menambah utility warna lain pada tombol atau ikonnya agar tidak menimpa varian bersama. Aturan ini juga berlaku pada dialog ringkasan, tombol ekspor, dan halaman akun.

| Fungsi | Class warna | Tampilan |
| --- | --- | --- |
| Kirim laporan/verifikasi | `.button-primary` | Teal-700, teks putih; hover teal-800 |
| Kembali ke Dashboard | `.button-secondary` | Putih, border teal-600, teks teal-700; hover teal-50 |
| Kembali ke portal/form | `.button-neutral` | Putih, border slate-300, teks slate-700; hover slate-50 |
| Riwayat/rekap, ekspor/unduh, catatan cepat, Set Jam Sekarang | `.button-soft` | Teal-50, teks teal-800; hover teal-100 |
| Reset/Clear Form dan tutup dialog | `.button-ghost` | Transparan, teks slate-500; hover slate-100 |
| Aksi pada hero hijau tua | `.button-on-dark` | Putih, teks teal-800; hover teal-50 |
| Hapus berkas | `.button-danger` | Transparan, teks red-700; hover red-50 |

Cetak memakai `.button-neutral`. Pagination aktif berwarna teal-700 dengan teks putih; halaman lain netral. Warna hijau/merah pada pilihan kondisi atau status pengiriman tetap menunjukkan status, terpisah dari warna tombol aksi. Tombol baru harus memiliki tindakan yang tersedia. Perubahan data mengikuti dukungan router, CSRF, dan backend yang tersedia pada modul masing-masing.

Tampilkan keadaan kosong jika daftar belum berisi data. Jika ada pengambilan data asynchronous, tampilkan status memuat dan error yang bisa dicoba ulang. Dashboard saat ini dirender langsung oleh PHP dan tidak memerlukan indikator loading buatan.

### 9.1 Form monitoring dari daftar pertanyaan

Source: `database/dummy/builtin-forms.php`, `app/Controllers/DynamicFormController.php`, `app/Views/form-dinamis/fill.php`, `public/assets/js/form-dinamis.js`, `resources/css/gform.css`, dan `resources/css/form-dinamis.css`.

Keenam form bawaan (Suhu, CCTV, Clear Desk, Hardware Server, Kesesuaian User, Antrian Online) dan form dinamis buatan sendiri memakai satu halaman dan satu renderer. Tidak ada lagi view PHP per modul; jangan membuat folder `app/Views/{modul}/` untuk form atau rekap. Isi form diubah lewat data, atau oleh admin lewat **Edit Isi Form** (bagian 9.3).

```text
app/Views/
├── form-dinamis/
│   ├── fill.php       halaman form (semua form)
│   └── respons.php    halaman riwayat (semua form)
├── form-editor/
│   └── index.php      Edit Isi Form
├── home/
├── account/
├── auth/
├── layouts/
├── components/
└── errors/
```

**Definisi form** (kunci = id kartu dashboard): `title`, `description`, `meta` (daftar `label` + `value`, atau `auto: now | today | month` yang diisi dengan waktu WIB di browser), `submitLabel`, `resetLabel` (bawaan "Kosongkan form"), dan `questions`.

**Pertanyaan**: `id` tetap (dipakai jawaban dan kondisi; jangan diganti setelah ada laporan), `type`, `title`, `help`, `required`, serta opsional `badge` (pill di kanan judul), `placeholder`, `default`, `maxlength`, `rows`, `mono`, `hint`, `counter`, dan `image`.

| Kelompok | Tipe | Catatan |
| --- | --- | --- |
| Umum (tipe dapat diganti di editor) | `short`, `paragraph`, `radio`, `checkbox`, `select`, `file`, `date`, `time`, `scale`, `number`, `rating` | `number` menerima `21,5` dan `21.5` |
| Blok tanpa jawaban | `section` (nomor, `help`, `chip`), `title`, `image` | Bagian diberi nomor otomatis |
| Khusus form bawaan (tipe terkunci) | `temperature` (`unit`, `bands`), `datetime`, `datetime2` (dengan tombol Set Jam Sekarang), `grid` (`columns`, `rows`), `confirm` | `confirm` adalah pernyataan wajib centang (PRD FR-F7) |

- **Opsi** berupa teks atau objek `value`, `label`, `note`, `badge`, dan `tone: 'red'` (jawaban yang perlu perhatian). `value` tetap meskipun label diubah. `other` bernilai `true` atau `['label' => …, 'placeholder' => …]` untuk pilihan "Lainnya" dengan isian teks.
- **Rekomendasi cepat**: `chips` berupa teks atau `['label' => …, 'text' => …]`, dengan `chipsLabel` dan `chipMode` (`set` mengganti isi, `append` menambah baris tanpa duplikat).
- **Kondisi**: `showIf` (`q`, `value`) menampilkan pertanyaan hanya untuk satu jawaban; `requiredWhen` (daftar `q`, `value`) membuatnya wajib bila salah satu jawaban dipilih. Pertanyaan tersembunyi tidak divalidasi dan tidak disimpan.
- **Format**: `format: 'mac'` (disimpan `AA:BB:CC:DD:EE:FF`) dan `format: 'ipv4'` pada jawaban singkat.
- **File**: `accept`, `maxMb`, `multiple`, `maxFiles`, `capture`, `dropText`, dan `formats`. Pada data contoh file tidak diunggah; hanya nama file yang disimpan.
- **Status suhu** (PRD FR-T2) dihitung dari nilai yang dibulatkan satu desimal dengan `bands` (bawaan 18,0 / 24,0 / 27,0): Normal 18,0–24,0 °C; Peringatan di bawah 18,0 atau 24,1–26,9 °C; Kritis mulai 27,0 °C. Aturannya ada sekali di `public/assets/js/dashboard-store.js` (`tempStatus`) dan dipakai form maupun riwayat. Jangan menulis target suhu lain di teks pertanyaan.

Tampilan dan perilaku halaman form:

- Header: baris kecil RS AWAL BROS GROUP • IT BOTANIA, judul, keterangan, `meta`, Jadwal dan Batas pengisian dari kartu, keterangan wajib diisi, serta tautan **Lihat Rekap** ke riwayat. Kembali ke dashboard lewat tombol di atas halaman; aksi bawah hanya **Kosongkan form** dan tombol kirim.
- Label terhubung ke input; pilihan, skala, rating, kisi, dan tanggal + jam memakai `fieldset` dengan `legend`. Pesan error berbahasa Indonesia tampil di bawah kartu pertanyaan, dan fokus pindah ke pertanyaan pertama yang salah. Input angka memakai keypad desimal; MAC memakai huruf kapital.
- Setelah dikirim, laporan disimpan di state browser, kartu dashboard ditandai selesai hari ini, dan dialog sukses menautkan ke riwayat. Jangan mengklaim laporan tersimpan di server sebelum backend tersedia.
- Perubahan yang belum dikirim memicu peringatan saat meninggalkan halaman.
- Form bawaan yang kartunya dihapus dari dashboard tetap dapat dibuka lewat URL-nya, dari definisi aslinya.

Menambah form bawaan baru:

1. Tambahkan definisi di `database/dummy/builtin-forms.php` dengan id kartu sebagai kunci.
2. Tambahkan kartu `kind: 'builtin'` dengan `url` dan `rekap` di `database/dummy/dashboard.php`.
3. Daftarkan dua route di `routes/web.php`: halaman form ke `$dynamicForm->builtin('id-kartu')` dan riwayat ke `$dynamicForm->history('id-kartu', 'kunci-navigasi')`.
4. Tambahkan nama modul dan halaman riwayat di `config/monitoring-navigation.php` untuk breadcrumb.

### 9.2 Halaman riwayat

Source: `app/Views/form-dinamis/respons.php`, `public/assets/js/form-riwayat.js`, `database/dummy/builtin-responses.php`, dan `resources/css/form-dinamis.css`. Route riwayat keenam modul tetap memakai URL rekap lama (`/rekap/...`, `/reports/...`); form dinamis memakai `/form-dinamis/respons?id=…`.

- Kolom selalu mengikuti form saat ini: No, Waktu kirim (WIB), satu kolom per pertanyaan (kecuali pernyataan), dan tombol Detail. Laporan yang dikirim sebelum suatu pertanyaan ditambahkan menampilkan "—"; jawaban pertanyaan yang sudah dihapus tidak ditampilkan.
- Nilai ditampilkan sesuai tipenya: suhu "24,6 °C" dengan chip status, opsi bertanda `tone: 'red'` sebagai chip merah, kisi sebagai ringkasan "Patuh 2 · Temuan 1", tanggal "27 Okt 2026, 08.12", dan daftar file dipisah koma. Teks panjang dipotong tiga baris di tabel dan tampil lengkap di detail.
- Ringkasan: Laporan ditampilkan dan Perlu perhatian (keduanya mengikuti filter), Bulan ini (bulan WIB berjalan), dan Terakhir dikirim. Laporan perlu perhatian bila ada suhu Peringatan/Kritis, jawaban `tone: 'red'`, atau kisi selain kolom pertama.
- Pencarian mencakup semua jawaban dan berjalan 300 ms setelah berhenti mengetik; filter periode per bulan WIB; tombol Reset filter muncul saat filter aktif; 25 baris per halaman; keadaan kosong membedakan "belum ada laporan" dan "tidak ada hasil filter".
- Di bawah 768 px tabel diganti daftar kartu per laporan (PRD FR-R9). Wrapper tabel dapat digeser horizontal di desktop, tetapi body halaman tidak boleh melebar.
- Klik baris atau tombol Detail membuka `<dialog>` native berisi semua pertanyaan per bagian beserta jawabannya; fokus kembali ke pemicu saat dialog ditutup. Nilai ditulis dengan escape, bukan HTML mentah.
- Bentuk laporan: `id`, `at` (waktu kirim UTC, ISO 8601), dan `answers` per id pertanyaan, berisi label opsi, angka, nama file, atau objek baris → kolom untuk kisi. Halaman menampilkan laporan contoh ditambah laporan yang dikirim di tab browser.
- Belum tersedia: ekspor Excel/PDF, cetak, ubah/hapus laporan, dan change log. Semuanya menunggu backend.

### 9.3 Edit Isi Form

Source: `app/Controllers/FormEditorController.php`, `app/Views/form-editor/index.php`, `public/assets/js/form-editor.js`, dan `resources/css/form-editor.css`. Dibuka dari menu ⋯ › Edit Isi Form pada kartu form, termasuk keenam form bawaan.

- Tab **Pertanyaan**: judul dan keterangan form, kartu pertanyaan (teks, deskripsi, tipe, opsi, "Lainnya", rekomendasi cepat, gambar lewat URL, wajib diisi, pindah atas/bawah, duplikat, hapus) dan toolbar Tambah Pertanyaan/Judul/Gambar/Bagian. Di bawah 900 px toolbar menjadi bilah di bawah layar.
- Tipe khusus form bawaan tampil sebagai label terkunci dengan penjelasan. Pertanyaan bersyarat diberi keterangan "Tampil hanya jika …". Pengaturan yang tidak ditampilkan editor (catatan dan badge opsi, nilai awal, kondisi, `meta` form) ikut tersimpan.
- Tab **Jadwal**: pola harian, mingguan, bulanan, tahunan, atau tanggal tertentu; tanggal mulai/berakhir; batas waktu dan pengingat; kalender pratinjau.
- **Simpan Form** menyimpan pertanyaan dan jadwal sekaligus. **Kembalikan ke form asli** (form bawaan) memuat pertanyaan asli dan baru berlaku setelah disimpan. **Batal** dengan perubahan yang belum disimpan menampilkan bilah "Lanjut mengedit / Buang perubahan".

## 10. Contoh menambah halaman dengan MVC yang tersedia

Contoh ini untuk halaman di luar form monitoring, misalnya halaman informasi. Untuk form monitoring baru, ikuti bagian 9.1 dan jangan membuat view form sendiri. Nama `InfoController` dan route `/info` hanya contoh; pastikan nama dan path belum dipakai. Jangan menyalin navbar atau HTML dokumen ke dalam view.

### A. Buat controller

Lokasi: `app/Controllers/InfoController.php`.

```php
<?php
declare(strict_types=1);

namespace App\Controllers;

final class InfoController extends Controller
{
    public function index(): void
    {
        $this->render('info/index', [
            'pageTitle' => 'Informasi Portal',
            'backToDashboard' => true,
        ], 'main');
    }
}
```

`Controller::render()` menutup halaman dengan HTTP 503 di luar `APP_ENV=local` sampai autentikasi tersedia.

### B. Daftarkan route

Tambahkan ke `routes/web.php` setelah deklarasi `use` yang diperlukan. Route di bawah middleware login otomatis memerlukan sesi.

```php
<?php
use App\Controllers\InfoController;

$router->get('/info', [new InfoController(), 'index']);
```

Router mendukung `get()`, `post()`, dan middleware dengan path statis. Folder view tidak menentukan URL; daftarkan route secara eksplisit.

### C. Buat view

Lokasi: `app/Views/info/index.php`.

```php
<main id="main-content" class="page-shell flex-1 space-y-5 py-4">
    <h1 class="text-lg font-bold text-slate-800"><?= e($pageTitle) ?></h1>

    <section aria-labelledby="info-title"
             class="rounded-xl border border-slate-200 bg-white p-4 sm:p-6">
        <h2 id="info-title" class="text-sm font-bold text-slate-800">Informasi portal</h2>
        <p class="mt-3 text-xs text-slate-500">Belum ada data yang ditampilkan.</p>
    </section>
</main>
```

Jika view benar-benar membutuhkan data, controller menyiapkannya. Query SQL nantinya ditempatkan pada Model, bukan pada view. Escape teks dan nilai atribut dinamis menggunakan `e()`. Gunakan `asset()` untuk URL aset dan `icon()` untuk ikon yang tersedia.

Route baru tetap perlu pengaturan hak akses ketika backend diintegrasikan. `AuthMiddleware`, `AdminMiddleware`, dan `CsrfMiddleware` masih merupakan rancangan arsitektur; contoh ini tidak memasang autentikasi atau penyimpanan form.

### D. Hubungkan dan bangun CSS

Bila halaman perlu kartu di dashboard, tambahkan kartu tautan atau form seperti dijelaskan pada bagian 8.4. Lalu jalankan `npm run build` atau watcher CSS, buka route baru, dan periksa tampilannya.

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
