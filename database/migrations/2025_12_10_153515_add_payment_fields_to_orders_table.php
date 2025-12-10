<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Status pembayaran: pending (belum bayar), dp_paid (sudah DP), lunas
            $table->string('payment_status')->default('pending')->after('status'); 
            // Jumlah nominal DP yang harus dibayar
            $table->decimal('dp_amount', 12, 2)->default(0)->after('total_price');
            // File bukti transfer
            $table->string('payment_proof')->nullable()->after('dp_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'dp_amount', 'payment_proof']);
        });
    }
};
