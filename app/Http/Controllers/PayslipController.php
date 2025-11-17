<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PayslipController extends Controller
{
    public function index()
    {
        $payslips = Payslip::latest()->paginate(20);

        return view('payslips.index', compact('payslips'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama'            => ['required', 'string', 'max:255'],
            'posisi_karyawan' => ['required', 'string', 'max:255'],
            'npwp'            => ['nullable', 'string', 'max:255'],
            'gaji'            => ['required', 'integer', 'min:0'],
        ]);

        $calc = $this->buildPayslipCalculation((int) $data['gaji']);

        $payslip = Payslip::create(array_merge(
            $data,
            $calc,
            ['printed_at' => now()]
        ));

        return redirect()->route('payslips.show', $payslip);
    }

    public function show(Payslip $payslip)
    {
        return view('payslips.show', compact('payslip'));
    }

    protected function buildPayslipCalculation(int $gaji): array
    {
        $JP_CAP  = 10547400;
        $PPH_MIN = 5400000;

        $jht_perusahaan = (int) round($gaji * 0.037);
        $jkk            = (int) round($gaji * 0.0024);
        $jkm            = (int) round($gaji * 0.003);

        $jp_base        = min($gaji, $JP_CAP);
        $jp_perusahaan  = (int) round($jp_base * 0.02);

        $bpjs_perusahaan = $jht_perusahaan + $jkk + $jkm + $jp_perusahaan;

        $jht_karyawan    = (int) round($gaji * 0.02);
        $jp_karyawan     = (int) round($jp_base * 0.01);

        $subtotal_potongan = $jht_karyawan + $jp_karyawan;

        $pph21 = 0;
        if ($gaji > $PPH_MIN) {
            $pph_base = $gaji + $jkk + $jkm;
            $pph21    = (int) round($pph_base * 0.035);
        }

        $total_potongan = $subtotal_potongan + $pph21;
        $gaji_bersih    = $gaji - $subtotal_potongan - $pph21;

        return compact(
            'jht_perusahaan',
            'jkk',
            'jkm',
            'jp_perusahaan',
            'bpjs_perusahaan',
            'jht_karyawan',
            'jp_karyawan',
            'subtotal_potongan',
            'pph21',
            'total_potongan',
            'gaji_bersih'
        );
    }

    public function exportPdf(Payslip $payslip)
    {
        $fmtIDR = fn($n) => 'IDR ' . number_format((float) $n, 0, '.', ',');

        // same background image concept as PO
        $bgData = null;
        $bgPath = public_path('pdf/pdf-export.png'); // same path you use for PO
        if (file_exists($bgPath)) {
            $bgData = base64_encode(file_get_contents($bgPath));
        }

        $data = [
            'payslip' => $payslip,
            'fmtIDR'  => $fmtIDR,
            'bgData'  => $bgData,
        ];

        $pdf = Pdf::loadView('payslips.pdf', $data)
            ->setPaper('A4', 'portrait');

        $fileName = 'payslip-' . preg_replace('/\s+/', '-', strtolower($payslip->nama))
            . '-' . ($payslip->printed_at?->format('Ymd') ?? now()->format('Ymd')) . '.pdf';

        return $pdf->download($fileName);   // or ->stream($fileName) if you want inline preview
    }
}
