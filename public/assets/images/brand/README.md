# Logo RS Awal Bros

Letakkan file SVG resmi di folder ini dengan nama `rs-awal-bros.svg`.

Komponen `app/Views/components/brand.php` otomatis menampilkan file tersebut. Selama file belum ada, teks `RS AWAL BROS` ditampilkan dalam satu baris tanpa pemisah baris.

Lokasi file dapat diubah melalui kunci `logo` pada `config/app.php`. Gunakan path relatif terhadap `public/assets/`. Contoh: `images/brand/rs-awal-bros.svg`.

SVG sebaiknya memiliki `viewBox` sesuai proporsi asli logo. Komponen memakai tinggi 32 px dengan lebar mengikuti proporsi SVG. Tidak ada file logo buatan yang digunakan sebagai pengganti logo resmi.
