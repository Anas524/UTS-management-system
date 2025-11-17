<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    protected $fillable = [
        'nama','posisi_karyawan','npwp','gaji',
        'jht_perusahaan','jkk','jkm','jp_perusahaan','bpjs_perusahaan',
        'jht_karyawan','jp_karyawan','subtotal_potongan',
        'pph21','total_potongan','gaji_bersih','printed_at',
    ];

    protected $casts = [
        'printed_at' => 'datetime',
    ];
}
