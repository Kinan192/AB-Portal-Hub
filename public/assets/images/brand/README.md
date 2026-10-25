# Logo RS Awal Bros

Logo aktif adalah `rs-awal-bros.svg`, disalin dari file pengguna `logo-rs-awalbros (2).svg` tanpa mengubah isi SVG.

Komponen `app/Views/components/brand.php` otomatis menampilkan file tersebut. Selama file belum ada, teks `RS AWAL BROS` ditampilkan dalam satu baris tanpa pemisah baris.

Lokasi file dapat diubah melalui kunci `logo` pada `config/app.php`. Gunakan path relatif terhadap `public/assets/`. Contoh: `images/brand/rs-awal-bros.svg`.

SVG memiliki `viewBox="0 0 348 53"`. Komponen memakai tinggi 32 px dengan lebar mengikuti proporsi aslinya, maksimum 220 px. Atribut ukuran intrinsik mengikuti 348 × 53 dan warna asli SVG dipertahankan. URL memakai versi waktu perubahan file agar pembaruan logo terbaca browser.
