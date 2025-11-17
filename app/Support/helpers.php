<?php

if (!function_exists('terbilang_id')) {
    /**
     * Ubah angka ke teks bahasa Indonesia (tanpa kata "rupiah").
     * Contoh: 12500 -> "dua belas ribu lima ratus".
     */
    function terbilang_id($number): string
    {
        $number = (int) round($number);

        if ($number === 0) {
            return 'nol';
        }

        // handle minus
        if ($number < 0) {
            return 'minus ' . terbilang_id(abs($number));
        }

        $words = [
            '', 'satu', 'dua', 'tiga', 'empat',
            'lima', 'enam', 'tujuh', 'delapan', 'sembilan',
            'sepuluh', 'sebelas'
        ];

        $spell = function ($n) use (&$spell, $words): string {
            $n = (int) $n;

            if ($n < 12) {
                return $words[$n];
            } elseif ($n < 20) {
                return $words[$n - 10] . ' belas';
            } elseif ($n < 100) {
                $puluh = intdiv($n, 10);
                $sisa  = $n % 10;
                return trim($words[$puluh] . ' puluh ' . $spell($sisa));
            } elseif ($n < 200) {
                return trim('seratus ' . $spell($n - 100));
            } elseif ($n < 1000) {
                $ratus = intdiv($n, 100);
                $sisa  = $n % 100;
                return trim($words[$ratus] . ' ratus ' . $spell($sisa));
            } elseif ($n < 2000) {
                return trim('seribu ' . $spell($n - 1000));
            } elseif ($n < 1000000) {
                $ribuan = intdiv($n, 1000);
                $sisa   = $n % 1000;
                return trim($spell($ribuan) . ' ribu ' . $spell($sisa));
            } elseif ($n < 1000000000) { // < 1 miliar
                $juta = intdiv($n, 1000000);
                $sisa = $n % 1000000;
                return trim($spell($juta) . ' juta ' . $spell($sisa));
            } elseif ($n < 1000000000000) { // < 1 triliun
                $miliar = intdiv($n, 1000000000);
                $sisa   = $n % 1000000000;
                return trim($spell($miliar) . ' miliar ' . $spell($sisa));
            } else { // sampai triliun
                $triliun = intdiv($n, 1000000000000);
                $sisa    = $n % 1000000000000;
                return trim($spell($triliun) . ' triliun ' . $spell($sisa));
            }
        };

        $result = $spell($number);

        // rapikan spasi ganda
        $result = preg_replace('/\s+/', ' ', trim($result));

        return $result;
    }
}
