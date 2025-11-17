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
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('posisi_karyawan');
            $table->string('npwp')->nullable();
            $table->unsignedBigInteger('gaji');

            // company parts
            $table->unsignedBigInteger('jht_perusahaan');
            $table->unsignedBigInteger('jkk');
            $table->unsignedBigInteger('jkm');
            $table->unsignedBigInteger('jp_perusahaan');
            $table->unsignedBigInteger('bpjs_perusahaan');

            // employee deductions
            $table->unsignedBigInteger('jht_karyawan');
            $table->unsignedBigInteger('jp_karyawan');
            $table->unsignedBigInteger('subtotal_potongan');
            $table->unsignedBigInteger('pph21')->default(0);
            $table->unsignedBigInteger('total_potongan');
            $table->unsignedBigInteger('gaji_bersih');

            $table->timestamp('printed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
