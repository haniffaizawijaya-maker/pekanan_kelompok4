<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fase 3 membutuhkan nama, email pengiriman, dan catatan pembeli
     * di setiap order (produk digital dikirim ke email, tanpa alamat).
     * Kolom-kolom ini belum ada di migrasi orders awal, jadi ditambah
     * lewat migrasi baru; migrasi lama tidak diubah.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('buyer_name')->nullable()->after('user_id');
            $table->string('buyer_email')->nullable()->after('buyer_name');
            $table->text('notes')->nullable()->after('buyer_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['buyer_name', 'buyer_email', 'notes']);
        });
    }
};
