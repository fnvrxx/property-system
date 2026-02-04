<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leases', function (Blueprint $table) {
            $table->id();
            // Relasi
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // Data Kontrak (Sesuai Kolom Excel)
            $table->date('start_date'); // Tanggal Masuk
            $table->enum('payment_frequency', ['harian', 'mingguan', 'bulanan', 'tahunan']); // Tipe Bayar
            $table->decimal('price', 15, 2); // Tarif (Harga deal)

            // Status Pembayaran (Snapshot kondisi saat ini)
            $table->date('last_payment_date')->nullable(); // Tanggal Bayar Terakhir
            $table->date('next_due_date')->nullable(); // Jatuh Tempo (Dihitung otomatis nanti)

            // Logika Kurang Bayar
            // Di Excel: Kurang Bayar = Tarif - Jumlah Dibayar
            $table->decimal('amount_paid', 15, 2)->default(0); // Jumlah Dibayar periode ini
            // Kurang bayar tidak perlu disimpan di DB, bisa dihitung on-the-fly (price - amount_paid)
            // Tapi status bisa kita simpan agar query lebih cepat

            $table->boolean('is_active')->default(true); // Agar bisa menonaktifkan penyewa lama
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leases');
    }
};
