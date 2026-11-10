import './bootstrap';

// JANGAN mengimpor Alpine.js di sini. Livewire 4 sudah membundel Alpine
// sendiri dan memanggil Alpine.start() miliknya di dalam Livewire.start()
// (lihat vendor/livewire/livewire/dist/livewire.js: window.Alpine diisi
// salinan bawaan Livewire, lalu start() memanggil module_default.start()).
// Mengimpor alpinejs dari npm dan memanggil Alpine.start() kedua kalinya
// membuat dua runtime Alpine berjalan di DOM yang sama: peringatan
// "Detected multiple instances of Alpine running", morph Livewire
// menduplikasi elemen, dan error "Snapshot missing". Semua direktif
// x-data di aplikasi ini (dropdown, modal) diinisialisasi oleh Alpine
// bawaan Livewire, karena setiap halaman layout aplikasi merender
// minimal satu komponen Livewire sehingga livewire.js ter-inject otomatis.
