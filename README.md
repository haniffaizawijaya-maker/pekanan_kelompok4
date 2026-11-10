# Sudoy

Mini e-commerce belajar: katalog, keranjang berbasis Livewire, checkout, pembayaran QRIS dinamis Tripay, dan manajemen pesanan. Alur lengkap dari auth sampai transaksi, dibangun bertahap per fase.

Nama toko di UI: Sudoy. Tema dummy lokal: toko langganan premium digital (nama merek asli hanya contoh, bukan akun sungguhan dan tidak dijual). Semua teks di UI memakai Bahasa Indonesia.

## Status: Fase 0, 1, 2, 3, 4, dan 5 selesai

Yang sudah dibangun:

- Setup Laravel 12, Livewire 4, Laravel Breeze (Blade), dan Tailwind CSS.
- Auth dengan kolom `role` di tabel users (`admin` dan `customer`). Pendaftar baru selalu menjadi `customer`.
- Katalog produk dengan Livewire: pencarian, filter kategori, sort, pagination, dan sinkronisasi query string di URL.
- Halaman detail produk dengan breadcrumb, foto, harga, badge stok, deskripsi, dan produk terkait.
- Keranjang Livewire (Fase 2): tambah dari katalog dan halaman detail, jumlah bisa diubah dengan stepper dan input angka, hapus item, subtotal realtime, tersimpan di database per akun yang login, jumlah selalu dibatasi stok terbaru, dan counter keranjang di header yang ikut berubah. Tamu diarahkan ke halaman masuk saat mencoba menambah ke keranjang. Tombol "Lanjut ke checkout" di panel ringkasan mengarah ke checkout Fase 3.
- Checkout dan pembuatan order (Fase 3): halaman checkout Livewire di `/checkout` untuk produk digital, tanpa alamat pengiriman dan tanpa ongkir (ongkos kirim Rp0, pengiriman ke email). Form hanya nama lengkap, email pengiriman, dan catatan opsional. Seluruh logika pembuatan order ada di `app/Services/OrderService.php`: validasi ulang di dalam transaksi dengan kunci baris produk, jumlah dipotong ke stok terbaru, produk nonaktif atau stok habis dihapus dari keranjang, snapshot nama dan harga terbaru saat checkout, stok dikurangi, keranjang dikosongkan, dan record Payment placeholder dibuat tanpa pemanggilan HTTP ke Tripay. Halaman detail order di `/pesanan/{order}` hanya bisa dibuka pemiliknya (pengguna lain mendapat 404). Status order dan pembayaran memakai nilai default dari migrasi (`pending`/`unpaid`, gateway `tripay`).
- Migrasi dan model untuk semua tabel: users, categories, products, carts, cart_items, addresses, orders, order_items, payments.
- Seeder: 3 kategori, 12 produk, 2 akun dev.
- Pembayaran QRIS dinamis Tripay (Fase 4): halaman pembayaran Livewire di `/pesanan/{order}/bayar` (pemilik atau admin, pengguna lain mendapat 404) yang membuat transaksi closed payment channel QRIS2 secara malas pada pemuatan pertama, memakai ulang referensi yang masih berlaku, dan membuat QR baru dengan merchant ref suffix `-R2`, `-R3`, dan seterusnya setelah kedaluwarsa. Seluruh pemanggilan HTTP ke Tripay hanya ada di `app/Services/TripayService.php` (Http facade, tanpa package SDK). Callback `POST /tripay/callback` (nama rute `tripay.callback`, tanpa auth, dikecualikan dari CSRF) memverifikasi signature HMAC-SHA256 dari raw JSON body dengan `hash_equals`, mencocokkan nominal dengan total order, dan menerapkan transisi secara idempoten di dalam transaksi: PAID menjadi lunas, EXPIRED/FAILED mengembalikan stok sekali saja dan membatalkan order, status lain hanya menyimpan payload. Tombol "Cek status pembayaran" memakai detail transaksi sebagai cadangan bila callback belum sampai. Bila kredensial kosong, halaman menampilkan pesan jujur yang menyebut ketiga variabel `.env` tanpa QR palsu. Detail order menampilkan tautan "Lihat pembayaran" selama belum lunas.

- Manajemen pesanan dan admin (Fase 5): riwayat pesanan customer di `/pesanan` (filter nomor order dan status, pagination 10) dengan tombol batal di detail order; panel admin di `/admin` (middleware `admin`, bukan admin mendapat 403) berisi total pendapatan (order dibayar dan selesai), jumlah order per status, jumlah produk, stok rendah (stok 5 atau kurang), dan 8 order terbaru; daftar pesanan admin di `/admin/pesanan` (filter nomor/nama/email pembeli dan kedua status) dengan aksi "Tandai selesai" (hanya dari dibayar) dan "Batalkan pesanan"; CRUD produk admin di `/admin/produk` (Blade biasa, bukan Livewire) dengan slug otomatis unik, toggle aktif (produk nonaktif hilang dari katalog), dan hapus yang ditolak bila produk sudah dipakai di pesanan atau keranjang. Pembatalan memakai satu layanan bersama `OrderService::cancelOrder()` untuk customer dan admin: hanya order menunggu dengan pembayaran menunggu dan tanpa kode QR aktif (belum ada referensi Tripay atau QR sudah kedaluwarsa); stok dikembalikan tepat sekali di dalam transaksi dengan kunci baris, payment menjadi `cancelled`, order menjadi `cancelled` dengan `payment_status` `failed` (sama seperti jalur kedaluwarsa Tripay), dan callback PAID yang datang setelah pembatalan manual diabaikan karena guard status di `TripayService`. Dashboard menampilkan tautan nyata sesuai peran, dan sidebar/topbar menampilkan tautan Pesanan untuk semua pengguna yang login serta tautan admin hanya untuk admin.

Yang sengaja BELUM dibangun:

- Tidak ada email pengiriman otomatis dan tidak ada refund dari aplikasi. Uji end-to-end dengan API sandbox Tripay sungguhan tetap tugas di mesin lokal setelah mengisi `.env` sendiri (lihat bagian Konfigurasi Tripay).

## Teknologi

- PHP 8.2 atau lebih baru (di mesin build ini terverifikasi memakai PHP 8.3.6)
- Laravel 12 (terverifikasi 12.70.2 di mesin build)
- Livewire 4 (terverifikasi 4.4.8)
- Laravel Breeze 2.4.2 (Blade)
- Tailwind CSS 3
- Database: SQLite untuk dev lokal di mesin build ini. Migrasi ditulis agar tetap jalan di MySQL. Lihat bagian database di bawah.

## Cara jalan (Linux)

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
php artisan migrate:fresh --seed
php artisan serve
```

Buka http://127.0.0.1:8000

Catatan: judul dan wordmark mengikuti `APP_NAME` di `.env` (default `Sudoy` di `.env.example`). Bila memakai file `.env` lama, ubah `APP_NAME=Sudoy` supaya judulnya benar. Akun dev ada di bagian Akun dev di bawah dan wajib diganti sebelum pemakaian selain latihan lokal. Bila kredensial Tripay masih kosong, halaman bayar menampilkan pesan jujur tanpa QR; QR sungguhan butuh kredensial sandbox yang diisi sendiri di `.env` (lihat Konfigurasi Tripay), jangan pernah mengirim kunci ke chat.

## Cara jalan (Windows)

Butuh PHP 8.2 atau lebih baru, Composer, dan Node.js LTS. Cara yang paling mudah:

1. Instal PHP dari php.net (atau lewat Laragon/XAMPP, pastikan PHP 8.2+), Composer dari getcomposer.org, dan Node.js LTS dari nodejs.org.
2. Buka PowerShell atau Terminal di folder proyek, lalu jalankan perintah yang sama seperti di Linux:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
npm install
npm run build
php artisan migrate:fresh --seed
php artisan serve
```

3. Buka http://127.0.0.1:8000 di browser.

Catatan Windows: file komponen Livewire di proyek ini sengaja memakai class PHP biasa di `app/Livewire`, bukan file komponen tunggal Livewire 4 yang namanya mengandung karakter khusus, supaya aman dibuka dan di-commit di Windows.

## Database: SQLite atau MySQL

Default di `.env.example` adalah SQLite (`DB_CONNECTION=sqlite`), karena itu yang dipakai dan terverifikasi di mesin build ini. File database lokal `database/database.sqlite` dibuat otomatis dan tidak masuk Git.

Untuk memakai MySQL, ubah `.env` menjadi:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=minicommerce
DB_USERNAME=root
DB_PASSWORD=isi-password-mysql-Anda
```

Buat dulu database kosong bernama `minicommerce` di MySQL, lalu jalankan `php artisan migrate:fresh --seed`. Semua nominal uang disimpan sebagai integer rupiah, tidak memakai float.

Urutan migrasi sudah diatur supaya tabel induk dibuat sebelum tabel yang memakai foreign key-nya (misalnya `carts` sebelum `cart_items`), jadi `migrate:fresh` aman di MySQL maupun SQLite.

## Akun dev (hanya untuk development lokal)

| Peran | Email | Kata sandi |
|---|---|---|
| Admin | admin@minicom.test | password |
| Pelanggan | customer@minicom.test | password |

Peringatan: akun ini hanya untuk belajar di mesin lokal. Seeder menolak dijalankan di environment production (gate di UserSeeder dan DatabaseSeeder), tetapi jangan pernah menjalankan seeder di server sungguhan dan jangan pernah memakai kata sandi `password` di server. Hapus atau ganti akun ini sebelum deployment apa pun.

## Data dummy

Seeder membuat 4 kategori (Streaming, Desain, Musik, Produktivitas) dan 12 produk dengan harga Rp25.000 sampai Rp240.000. Tepat 1 produk stoknya 0 untuk menguji tampilan stok habis: Zoom Pro 1 Bulan.

**Dummy lokal saja, bukan untuk dijual sungguhan.** Nama merek yang tampil (Netflix, Canva, dan sejenisnya) hanya contoh untuk latihan. Tidak ada akun sungguhan, tidak ada kredensial, tidak ada cara berbagi akun, tidak ada logo merek, dan tidak ada klaim afiliasi. Merek dagang adalah milik pemiliknya masing-masing. Proyek ini dilarang dipakai untuk menjual akun sungguhan. Setiap deskripsi produk diakhiri kalimat yang sama: "Data contoh untuk latihan. Bukan akun aktif dan tidak dijual." Stok di seeder hanya angka contoh untuk menguji batas stok, bukan slot akun.

Nama dan deskripsi produk ditulis generik (durasi langganan saja, tanpa janji fitur spesifik yang menyesatkan). Foto produk disimpan LOKAL di `public/images/products/` (10 foto generik dari Unsplash yang diunduh dan diperiksa satu per satu, tanpa logo merek yang terbaca, ditambah 2 artwork SVG buatan sendiri untuk produk yang foto aslinya memuat logo/teks merek yang terbaca), tanpa logo atau artwork merek. Foto-foto ini tetap placeholder dan harus diganti dengan aset asli sebelum dipakai jualan. Kalau suatu saat sebuah foto gagal dimuat, tampilannya jatuh ke blok latar parchment berisi mark play Sudoy, bukan bingkai kosong.

Keputusan checkout digital (Fase 3, sudah dibangun): produk digital tidak memakai ongkir dan tidak memakai alamat pengiriman; pengiriman lewat email setelah pembayaran terverifikasi, dan tidak ada email yang dikirim otomatis. Form checkout hanya meminta nama lengkap, email pengiriman, dan catatan opsional. Nomor order dibuat dengan format `MC-YYYYMMDD-XXXXXX` (6 karakter acak, dicek keunikannya sebelum dipakai). Harga order memakai harga produk terbaru saat checkout, bukan harga yang tersimpan di keranjang; stok dikunci dengan `lockForUpdate` dan dikurangi di dalam transaksi yang sama dengan pembuatan order. Model Address dan tabelnya tetap ada untuk kebutuhan di masa depan, tetapi tidak dipakai di checkout. Rencana pembayaran QRIS dinamis Tripay sandbox tidak berubah.

## Desain UI (DNA restyle, menunggu persetujuan user sebelum Fase 3)

Sistem desain diambil dari gambar referensi retro editorial yang dikirim user, diterapkan total ke semua permukaan: shell, katalog, detail, keranjang, dashboard, profil, dan semua halaman auth. Bukan salinan layar referensi; tidak ada konten referensi yang disalin (tanpa judul buku, penulis, atau deskripsi dari referensi), dan setiap elemen yang dipakai punya fungsi nyata di Sudoy (tanpa bel notifikasi, rating bintang, atau tombol contoh).

Token, satu definisi untuk semua halaman (di `tailwind.config.js`):

- Palet: latar luar beige `#E9DFC9`, latar aplikasi krem `#FBF3DC`, sidebar krem lebih gelap `#F3EAD3`, bar atas kuning mustard `#F2CE1B`, aksen oranye `#E8622C` (hover `#C94E1F`), tinta `#191919` untuk teks dan border, teks redup `#6B6257`, kartu `#FFFDF5`. Satu aksen saja, tanpa hue lain kecuali teks error yang memakai turunan gelap aksen (`#A63A1A`).
- Border: `1.5px` solid tinta di frame aplikasi, kartu, input, pil, dan tombol; radius `6px` konsisten. Isian datar, tanpa gradient, glassmorphism, blur, blob, atau ilustrasi dekoratif. Tombol utama oranye memakai bayangan keras `2px 2px 0 #191919`; sisanya datar. Satu pengecualian radius: lingkaran avatar inisial di chip pengguna.
- Tipografi: seluruh UI memakai satu keluarga font monospace gaya mesin tik (Courier Prime, di-host sendiri lewat @fontsource; fallback `ui-monospace`, "Courier New", monospace), sesuai gambar referensi. Label dan heading memakai bobot tebal; baris kategori/meta memakai huruf kapital kecil ber-spasi. Teks rata kiri. Tanpa emoji, simbol dekoratif, rating bintang palsu, jumlah terjual palsu, atau diskon palsu.

Shell: frame aplikasi terpusat (maks 1280px, border tinta) berisi sidebar ikon sempit 88px di kiri (mark digital netral SVG berupa tombol play di dalam bingkai, Katalog, Keranjang dengan badge counter, Dashboard, Profil, Keluar sebagai form POST; untuk tamu: Katalog, Masuk, Daftar) dan kolom utama dengan bar atas mustard berisi form pencarian (GET ke `/`, input `q`, dihormati filter pencarian Livewire yang sudah ada) dan chip pengguna (avatar inisial, nama, label peran Admin/Pelanggan/Tamu, dropdown Profil/Dashboard/Keluar). Di mobile, sidebar disembunyikan dan diganti menu hamburger di bar atas; tidak ada kontrol mati.

Perbaikan presentasi yang ikut masuk dalam restyle ini:

- Pagination memakai terjemahan Indonesia penuh: `lang/id/pagination.php`, `lang/id.json`, dan view pagination kustom `resources/views/vendor/pagination/tailwind.blade.php` dengan gaya DNA yang sama, sehingga terbaca "Menampilkan 1 sampai 9 dari 12 hasil", "Sebelumnya", "Berikutnya". File `lang/id/auth.php`, `lang/id/passwords.php`, dan `lang/id/validation.php` (kunci umum) juga ditambahkan; pesan validasi untuk aturan pinggir yang belum diterjemahkan jatuh ke bahasa Inggris lewat fallback locale.
- Counter header punya satu definisi di mana-mana: badge menghitung total kuantitas di keranjang (angka yang sama dengan yang dilaporkan halaman keranjang sebagai item), di sidebar desktop dan di menu seluler, keduanya memperbarui dari event Livewire `cart-updated` yang sama, dengan aria-label "Keranjang, N item" yang sama.
- Foto produk diganti total bersama ganti tema (31 Oktober 2026): 12 foto generik Unsplash yang berbeda, tanpa logo atau artwork merek, masing-masing diverifikasi curl HTTP 200 image/jpeg. Foto item keranjang dibatasi `w-24` (mobile) sampai `w-32` dengan aspek 4/5, dan grid katalog menjadi 2 kolom mobile, 3 tablet, 4 desktop, 5 layar sangat lebar.

Restyle ini sudah disetujui user (31 Oktober 2026) dan Fase 3 checkout sudah dibangun di atasnya.

## Konfigurasi Tripay (Fase 4, sudah dipakai)

Di `.env.example` hanya ada placeholder kosong:

```
TRIPAY_MODE=sandbox
TRIPAY_MERCHANT_CODE=
TRIPAY_API_KEY=
TRIPAY_PRIVATE_KEY=
TRIPAY_QRIS_CHANNEL=QRIS2
TRIPAY_EXPIRED_HOURS=24
```

Channel yang dipakai adalah `QRIS2`. Channel bernama `QRIS` di Tripay adalah QRIS by ShopeePay, jadi jangan tertukar. Base URL sandbox adalah `https://tripay.co.id/api-sandbox/` dan production `https://tripay.co.id/api/`, dipilih dari `TRIPAY_MODE` di `config/tripay.php`. Mode default tetap sandbox; jangan ubah ke production untuk latihan.

Langkah untuk memakai pembayaran sungguhan di mesin lokal (dilakukan user sendiri, bukan di mesin build ini):

1. Daftar sebagai merchant di tripay.co.id.
2. Di dashboard Tripay, ambil kredensial sandbox (merchant code, API key, private key) dan aktifkan channel QRIS2. Kredensial sandbox berbeda dengan kredensial production. Menurut dokumentasi resmi Tripay, kredensial sandbox diambil di menu API & Integrasi, Simulator, Merchant, Detail, dan channel diaktifkan di menu API & Integrasi, Simulator, Merchant, Channel Pembayaran.
3. Masukkan kredensial tersebut hanya di file `.env` lokal. Jangan pernah menempel API key atau private key di chat, di commit, atau di file yang dibagikan.
4. Jalankan aplikasi, buat pesanan, lalu buka halaman pembayarannya. Kode QR dibuat dari API sandbox Tripay.
5. Untuk menguji callback di mesin lokal ada dua jalan yang didukung dokumentasi: pakai Callback Tester di dashboard Tripay (mode sandbox: Simulator, Console, Callback) untuk mengirim callback simulasi ke URL callback, atau buat URL callback lokal dapat diakses publik lewat tunnel (misalnya ngrok) dan atur callback URL di dashboard Tripay. Tanpa salah satu dari keduanya, callback Tripay tidak sampai ke `http://127.0.0.1:8000`, dan status tetap bisa diperbarui lewat tombol "Cek status pembayaran" di halaman pembayaran. Callback URL aplikasi ini adalah rute bernama `tripay.callback` di path `/tripay/callback`.

Pemetaan status: callback dan cek status memakai status Tripay PAID, EXPIRED, FAILED, dan REFUND sesuai dokumentasi resmi. PAID melunasi order; EXPIRED/FAILED mengembalikan stok sekali saja dan membatalkan order; REFUND menandai payment sebagai Dikembalikan tanpa mengubah status order atau stok, dan pengembalian dana harus direkonsiliasi manual di dashboard Tripay; status lain tidak mengubah status yang sudah final. Biaya Tripay (`fee_merchant`, `total_fee`, `amount_received`) tidak punya kolom sendiri dan tersimpan di payload JSON respons Tripay. Batas kedaluwarsa QRIS2 menurut dokumentasi adalah 10 menit sampai 1440 menit, jadi `TRIPAY_EXPIRED_HOURS` dijepit ke 1 sampai 24 jam.

Tidak ada kunci Tripay, Midtrans, Xendit, atau DOKU di proyek ini. Keputusan yang sudah dikunci: QRIS dinamis Tripay saja, tanpa Midtrans/Xendit/DOKU.

**Batas jujur:** end-to-end dengan API sandbox Tripay sungguhan belum diuji di mesin build ini (tidak ada kredensial di sana). Yang teruji di sini adalah 26 tes Fase 4 dengan HTTP yang dipalsukan, termasuk rumus signature yang dicocokkan dengan vektor contoh dari dokumentasi resmi Tripay. Uji sandbox sungguhan harus dilakukan di mesin lokal setelah mengisi `.env` sendiri, mengikuti langkah di atas.

## Deploy produksi

Jangan deploy dengan menyalin `.env.example`. File itu adalah konfigurasi development lokal (debug aktif, URL localhost, mailer log). Untuk production, salin `.env.production.example` menjadi `.env` di server dan isi semua nilai yang ditandai.

1. Salin `.env.production.example` ke `.env` di server. Isi `APP_KEY` dengan hasil `php artisan key:generate` di server, `APP_URL` dengan domain HTTPS sungguhan, kredensial MySQL sendiri, mailer sungguhan, dan kredensial Tripay production (berbeda dari sandbox, diambil dari dashboard Tripay production). Jangan menempel kunci apa pun di chat atau commit.
2. Jalankan `composer install --no-dev --optimize-autoloader` lalu `npm install && npm run build`.
3. Jalankan `php artisan migrate`. Jangan jalankan `migrate:fresh` dan jangan pernah menjalankan `db:seed` atau `migrate:fresh --seed` di production: seeder menolak di environment production dan fresh akan menghapus data.
4. Bila memakai `config:cache` atau `route:cache`, jalankan setelah `.env` final. Bila config di-cache, variabel environment seperti `TRUSTED_PROXIES` harus disetel di environment server, bukan hanya di `.env`.
5. HTTPS wajib. Atur security headers (HSTS, CSP, X-Frame-Options, dan sejenisnya) di reverse proxy atau hosting, karena aplikasi tidak menambahkan header tersebut sendiri. Periksa URL dan cookie di belakang reverse proxy; setel `TRUSTED_PROXIES` bila memakai proxy di depan server.
6. Atur callback URL Tripay production ke `https://domain-anda/tripay/callback` di dashboard Tripay. Uji end-to-end sandbox dulu (Callback Tester atau tunnel) sebelum beralih ke production.
7. Cadangkan database secara berkala dan pantau log (log harian). Zona waktu aplikasi adalah Asia/Jakarta, jadi waktu kedaluwarsa QR tampil dalam WIB.
8. Hapus atau ganti akun seed dan buat akun admin production lewat cara yang aman.
9. Verifikasi email: dengan mailer log di development lokal, tautan verifikasi ditulis ke `storage/logs/laravel.log`. Di production, mailer sungguhan wajib dikonfigurasi agar email verifikasi dan reset kata sandi terkirim. Checkout, riwayat pesanan, dan halaman bayar mensyaratkan email terverifikasi.
10. Refund: pembayaran yang dikembalikan di Tripay hanya menandai payment sebagai Dikembalikan. Status order tidak berubah, stok tidak berubah, dan tidak ada aksi refund di aplikasi. Rekonsiliasi dilakukan manual di dashboard Tripay.

## Tes

```bash
php artisan test
```

Tes mencakup katalog, pencarian dan filter Livewire, state kosong dan reset filter, halaman detail, peran akun seed, auth bawaan Breeze, pagination berbahasa Indonesia, pemberitahuan dummy lokal, keranjang (tambah, batas stok, ubah jumlah, hapus, subtotal, isolasi per akun, counter header, dan tamu diarahkan ke login), checkout (tamu diarahkan ke login, keranjang kosong diarahkan ke keranjang, pembuatan order dengan snapshot harga dan pengurangan stok, validasi, pemotongan stok, penghapusan item tidak tersedia, otorisasi detail order, dan harga terbaru saat checkout), dan pembayaran Tripay (rumus signature, pembuatan transaksi QRIS2, callback terverifikasi dengan idempotensi dan pengembalian stok sekali saja, cek status manual, ketersediaan channel QRIS2, halaman pembayaran termasuk kredensial kosong dan QR baru setelah kedaluwarsa). Di mesin build, 90 tes lolos (477 assertions), termasuk 26 tes baru untuk pembayaran.

## Verifikasi yang sudah dilakukan di mesin build

- `php artisan migrate:fresh --seed` berhasil, dengan hitungan: 2 users, 4 categories, 12 products, tepat 1 produk stok 0 (Zoom Pro 1 Bulan).
- `php artisan serve` lalu curl setelah ganti tema: `/` HTTP 200 dan berisi nama produk baru, pemberitahuan dummy lokal, "Tambah ke Keranjang", kalimat pagination Indonesia, dan placeholder pencarian baru ("Cari Netflix, Canva, atau lainnya"); `/produk/netflix-premium-1-bulan` HTTP 200; `/keranjang` sebagai tamu HTTP 302 ke `/login`; `/login` HTTP 200. HTML yang dirender tidak mengandung kelas gaya lama (emerald/stone/Figtree) dan tidak mengandung em-dash.
- `npm run build` berhasil dan `php artisan test` 90 tes lolos (477 assertions), termasuk 13 tes untuk checkout dan 26 tes untuk pembayaran Tripay.
- `php artisan serve` lalu curl setelah Fase 3: `/` HTTP 200; `/checkout` sebagai tamu HTTP 302 ke `/login`; `/pesanan/1` sebagai tamu HTTP 302 ke `/login`; `/login` HTTP 200. Checkout dengan keranjang kosong, pembuatan order, dan otorisasi detail order dibuktikan lewat tes (login lewat curl tidak dilakukan). Audit grep: 0 pemanggilan HTTP ke Tripay/Midtrans/Xendit/DOKU, 0 teks placeholder Fase 2 yang tersisa di kode dan view, 0 em-dash di string baru, 0 pengiriman email otomatis.

Yang belum terverifikasi: belum ada screenshot browser, pengecekan klik di browser sungguhan, atau pengecekan manual di lebar 390px. Klaim tidak ada overflow horizontal dan kenyamanan mobile baru didukung oleh struktur layout responsif di kode (sidebar disembunyikan di bawah `md`, grid 2 kolom di mobile, flex-wrap, tanpa lebar tetap), bukan oleh tangkapan layar. Pembaruan aria-label counter di kedua instans nav juga belum diverifikasi di browser. Cek di browser sendiri sebelum menyatakan UI selesai.
