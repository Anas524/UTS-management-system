<?php

namespace App\Services\InvoiceParser;

use Illuminate\Support\Facades\Log;
use Spatie\PdfToText\Pdf;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Symfony\Component\Process\Process;
use Illuminate\Support\Str;

class InvoiceParser
{
    // --- additions at top of class ---
    private array $indoMonths = [
        'januari' => 1,
        'februari' => 2,
        'maret' => 3,
        'april' => 4,
        'mei' => 5,
        'juni' => 6,
        'juli' => 7,
        'agustus' => 8,
        'september' => 9,
        'oktober' => 10,
        'november' => 11,
        'desember' => 12
    ];

    public function parse(string $uploadedPath): array
    {
        // --- copy to short OS temp path (avoids long-path/perm issues) ---
        $ext = strtolower(pathinfo($uploadedPath, PATHINFO_EXTENSION) ?: 'pdf');
        $tmp = rtrim(sys_get_temp_dir(), "\\/") . DIRECTORY_SEPARATOR . 'po_' . uniqid() . '.' . $ext;
        @copy($uploadedPath, $tmp);
        $path = file_exists($tmp) ? $tmp : $uploadedPath;

        $text = '';
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'bmp', 'heic'])) {
            $text = $this->ocrImage($path);
        } elseif ($ext === 'pdf') {
            $text = $this->pdfToText($path);
            if ($this->isEmptyText($text)) {
                $png = $this->pdfFirstPageToPng($path);
                if ($png && file_exists($png)) {
                    $text = $this->ocrImage($png);
                    @unlink($png);
                }
            }
        }

        // normalize whitespace
        $norm = preg_replace("/[ \t]+/u", ' ', (string)$text);
        $norm = preg_replace("/\r\n|\r/u", "\n", $norm);

        // --- Header fields ---
        $poNumber = $this->extractPoNumber($norm);
        $poDate   = $this->extractDate($norm);
        [$vendor, $address] = $this->extractVendorAddressSmart($norm);

        // --- Rows ---
        $rows = $this->extractRows($norm);

        @unlink($tmp);

        return [
            'po_number' => $poNumber,
            'po_date'   => $poDate,
            'vendor'    => $vendor,
            'npwp'      => null,
            'address'   => $address,
            'ppn_rate'  => null,
            'rows'      => $rows,
            'raw_text'  => $norm,
        ];
    }

    // More patterns for PO number
    protected function extractPoNumber(string $t): ?string
    {
        // Sales Order No:
        if (preg_match('/Sales\s*Order\s*No\.?\s*[:\-]\s*([A-Z0-9\/\.\-]+)/i', $t, $m)) return trim($m[1]);
        // “No. …” including roman-ish month
        if (preg_match('/\bNo\.?\s*[:\-]?\s*([A-Z0-9\/\.\-]{4,})\b/i', $t, $m))   return trim($m[1]);
        // “No Invoice”, “NO INVOICE”
        if (preg_match('/No\W*Invoice\W*[:\-]?\s*([A-Z0-9\/\.\-]+)/i', $t, $m))     return trim($m[1]);
        return null;
    }

    // Indonesian and generic date support
    protected function extractDate(string $t): ?string
    {
        // “Jakarta, 26 Agustus 2025” or “26 Agustus 2025”
        if (preg_match('/(?:[A-Za-z ]*,\s*)?(\d{1,2})\s+([A-Za-z]+)\s+(20\d{2})/u', $t, $m)) {
            $d = (int)$m[1];
            $monName = mb_strtolower($m[2]);
            $y = (int)$m[3];
            if (isset($this->indoMonths[$monName])) {
                $mnum = $this->indoMonths[$monName];
                return sprintf('%04d-%02d-%02d', $y, $mnum, $d);
            }
            // English month fallback
            $ts = strtotime("$d $monName $y");
            if ($ts) return date('Y-m-d', $ts);
        }

        // ISO / dotted / slashed
        if (preg_match('/\b(20\d{2}[-\/.]\d{1,2}[-\/.]\d{1,2}|\d{1,2}[-\/.]\d{1,2}[-\/.]\d{2,4})\b/', $t, $m)) {
            $raw = str_replace(['.', '/'], '-', $m[1]);
            $ts  = strtotime($raw);
            if ($ts) return date('Y-m-d', $ts);
        }

        return null;
    }

    // prefer buyer line "Name :" for the vendor field you want to show,
    // and use the "To :" (or Bill To / Kepada) block as the address.
    protected function extractToParty(string $t): array
    {
        $vendor = null;
        $address = null;

        // Vendor: generic buyer line (many Indonesian invoices)
        $vendor = $this->grab('/\bName\s*:\s*(.+)/iu', $t);
        if ($vendor) {
            // if you don’t want the legal prefix, strip optional "PT."
            $vendor = preg_replace('/^PT\.?\s+/i', '', trim($vendor));
        }

        // Address: prefer "To :" / "Kepada :" / "Kirim ke :" block
        if (preg_match('/\b(?:To|Kepada|Kirim ke|Krn ke)\s*:\s*\n(.+?)(?:\n\s*\n|\n{2,}|\bPhone\b|\bDate\b|\bDATE\b)/imus', $t, $m)) {
            $address = trim(preg_replace('/\s*\n\s*/', ', ', $m[1]));
        }

        // Fallback to "BILL TO:" if no "To :" found
        if (!$address && preg_match('/\bBILL TO:?\s*\n(.+?)(?:\n\s*\n|\n{2,}|\bDATE\b|\bNo\W*Invoice\b)/imus', $t, $m)) {
            $address = trim(preg_replace('/\s*\n\s*/', ', ', $m[1]));
        }

        return [$vendor ?: null, $address ?: null];
    }

    // Rows dispatcher
    protected function extractRows(string $t): array
    {
        // 1) Karya Aroma PROFORMA (Jenis Barang | Jumlah (Kg) | $USD | IDR-per-unit | Amount)
        $rows = $this->parseKaryaAromaProforma($t);
        if (!empty($rows)) return $rows;

        // 2) RPI “Invoice – SO” (multi-page) table
        $rows = $this->parseRpiInvoiceSo($t);
        if (!empty($rows)) return $rows;

        // 3) Single-line fallback (your original Jasa/Service heuristic)
        if (preg_match('/\n([^\n]*?(?:Jasa|Service|Description)[^\n]*?)\s+Rp\s*([0-9.,]+)/iu', $t, $m)) {
            return [[
                'sku'         => null,
                'description' => trim($m[1]),
                'price_usd'   => null,
                'price_idr'   => $this->parseIdrToInt($m[2]),
                'qty'         => 1,
                'unit'        => 'unit',
            ]];
        }

        return [];
    }

    protected function parseRpiInvoiceSo(string $t): array
    {
        if (!preg_match('/\bInvoice\s*-\s*SO\b/i', $t)) return [];

        // Work in page blocks; there are repeating headers.
        // We’ll scan for each quantity line and look back to gather SKU + Description.
        $rows = [];

        // 1) Index all “qty line” occurrences: "5.00 Pcs 125,225.00 626,125 ..."
        $qre = '/\b(\d{1,4}(?:[.,]\d{2})?)\s+(Pcs|Kg|Unit|Box|Pack|Set)\s+([0-9.,]+)\s+([0-9.,]+)/imu';
        if (preg_match_all($qre, $t, $qhits, PREG_OFFSET_CAPTURE)) {
            foreach ($qhits[0] as $i => $full) {
                $start = max(0, $full[1] - 500);               // look back ~500 chars for the block
                $chunk = substr($t, $start, 500);

                // 2) SKU just before description: lines like "SMT-\nLPG-15879.0\n02"
                if (preg_match('/(SMT-[A-Z0-9\.\-]+(?:\s*\n[A-Z0-9\.\-]+){0,3})\s*$/imu', $chunk, $mSku)) {
                    $sku = preg_replace('/\s+/', '', $mSku[1]); // join broken lines: "SMT-LPG-15879.0\n02" → "SMT-LPG-15879.002"
                    // Fix common “..0\n02” joins → "…-15879.0\n02" → "-15879.0 02"
                    $sku = preg_replace('/(\d)\.(\d)(\d)$/', '$1.$2$3', $sku);
                } else {
                    $sku = null;
                }

                // 3) Description block sits after SKU, before qty line. Take last 6 lines, join words, drop table headers.
                $block = preg_replace('/[ ]+/', ' ', $chunk);
                $block = preg_replace('/Item\s+Description.*?Unit Price/imu', '', $block);
                // capture the last non-empty 3–6 lines as description
                $desc = null;
                if (preg_match('/(?:\R)([A-Za-z][\pL0-9 !+\-\(\)\/]{10,120})\s*$/u', trim($block), $mDesc)) {
                    $desc = trim(preg_replace('/\s+/', ' ', $mDesc[1]));
                }

                // 4) Pull qty/unit/unit-price from main match
                $qty   = (float) str_replace(',', '.', $qhits[1][$i][0]);     // keep decimals → 5.00
                $unit  = strtolower($qhits[2][$i][0]);                        // pcs / kg / …
                $idr   = $this->parseIdrToInt($qhits[3][$i][0]);              // unit price IDR
                // amount exists as $qhits[4] but we ignore; client computes amount

                $rows[] = [
                    'sku'         => $sku,
                    'description' => $desc,
                    'price_usd'   => null,     // RPI is in IDR
                    'price_idr'   => $idr,
                    'qty'         => $qty,
                    'unit'        => $unit,
                ];
            }
        }
        return $rows;
    }

    protected function parseKaryaAromaProforma(string $t): array
    {
        // Narrow to the table block
        if (!preg_match('/\bNo\s+Kode\s+Jenis\s+Barang\s+Jumlah\s*\(Kg\)\s+Harga\s+Satuan\s*\(Rp\)\s+Total/iu', $t)) {
            // table keywords not found → not this format
            return [];
        }

        $rows = [];
        // Pattern: 1 <DESC CAPS> <QTY> $<USD> \n <IDRunit> <Amount>
        $re = '/^\s*\d+\s+([A-Z0-9][A-Z0-9 \-\/]+?)\s+(\d{1,5})\s+\$([0-9.,]+)\s*\R\s*([0-9.,]+)\s+[0-9.,]+/mu';
        if (preg_match_all($re, $t, $m, PREG_SET_ORDER)) {
            foreach ($m as $hit) {
                $desc = trim(preg_replace('/\s+/', ' ', $hit[1]));            // “ROUGE CRIMSON SLCV”
                $qty  = (float) str_replace(',', '.', $hit[2]);               // 200
                $usd  = $this->usdToCents($hit[3]);                           // $20.00 → 2000
                $idr  = $this->parseIdrToInt($hit[4]);                        // 326,820.00 → 326820

                $rows[] = [
                    'sku'         => null,
                    'description' => $desc,
                    'price_usd'   => $usd,  // integer cents; front-end already formats “$ …”
                    'price_idr'   => $idr,  // per-unit IDR (from the column under USD)
                    'qty'         => $qty,
                    'unit'        => 'kg',
                ];
            }
        }
        return $rows;
    }

    private function usdToCents(string $s): int
    {
        // "20.00" / "21,00" → 2000 / 2100
        $s = preg_replace('/[^0-9.,]/', '', $s);
        if (substr_count($s, ',') === 1 && substr_count($s, '.') === 0) $s = str_replace(',', '.', $s);
        $f = (float) $s;
        return (int) round($f * 100);
    }

    // Generic grid parser for tables that look like:
    // [No] [SKU/code] [Description ...] [Qty] [Unit] [Unit Price] [Amount]
    protected function parseRpiGrid(string $t): array
    {
        // keep newlines; compress spaces
        $flat = preg_replace('/[ \t]+/u', ' ', $t);

        // - SKU/code: long token of letters/digits/dots/dashes (6+ chars)
        // - Description: lazy up to qty
        // - Qty: number (int/decimal)
        // - Unit: short word (pcs, kg, unit, ltr, box, set, pack, etc.)
        // - Unit Price: money-like number with thousands + 2 decimals
        // - Amount: money-like number (ignored; you compute on client)
        $re = '/^\s*\d+\s+' .                               // row no.
            '([A-Z0-9.\-]{6,})\s+' .                      // SKU/code
            '(.+?)\s+' .                                  // Description
            '(\d+(?:[.,]\d+)?)\s+' .                      // Qty
            '(Pcs|PCS|Kg|KG|kg|Unit|unit|Ltr|ltr|Box|box|Pack|pack|Set|set)\s+' . // Unit
            '([\d.,]+)\s+' .                              // Unit Price
            '([\d.,]+)' .                                 // Amount (ignored)
            '/imu';

        $rows = [];
        if (preg_match_all($re, $flat, $m, PREG_SET_ORDER)) {
            foreach ($m as $hit) {
                $sku   = trim($hit[1]);
                $desc  = trim($hit[2]);
                $qty   = (float) str_replace(',', '.', strtr($hit[3], ['.' => '', ',' => '.']));
                $unit  = strtolower($hit[4]);
                $price = (int) preg_replace('/[^\d]/', '', $hit[5]); // treat as IDR

                // Clean description tail if it accidentally captured unit/price
                $desc = preg_replace('/\s+(Pcs|PCS|Kg|KG|kg|Unit|unit|Ltr|ltr|Box|box|Pack|pack|Set|set)\b.*$/i', '', $desc);
                $desc = trim($desc, ' -,');

                if ($desc && $price) {
                    $rows[] = [
                        'sku'         => $sku,
                        'description' => $desc,
                        'price_idr'   => $price,
                        'qty'         => $qty ?: 1,
                        'unit'        => $unit ?: 'unit',
                    ];
                }
            }
        }

        return $rows;
    }

    // Recognize Karya Aroma PROFORMA lines
    protected function parseKaryaAroma(string $t): array
    {
        // Many PDFs render the 2 prices per row (USD and IDR) stacked; we want the IDR number.
        // We also have "Jumlah (Kg)" column → qty.
        $rows = [];

        // Try to capture "ROUGE CRIMSON SLCV ... 200 ... 326,820.00" etc.
        $re = '/\b(ROUGE|SWEET|SLCV|MEMORY|Barang)\b.*?\n.*?\b(\d{1,4})\b.*?\n?.*?([0-9]{1,3}(?:[.,][0-9]{3})+(?:[.,]\d{2})?)\b/imu';
        if (preg_match_all($re, $t, $m, PREG_SET_ORDER)) {
            foreach ($m as $hit) {
                // description line: take the whole line above qty if available
                // fallback to the word that triggered the match
                $desc = $this->grab('/\n([A-Z0-9].{5,60})\n.*?' . $hit[2] . '/imu', $t) ?? trim($hit[0]);
                $qty  = (int)$hit[2];
                $idr  = $this->parseIdrToInt($hit[3]);

                $rows[] = [
                    'sku'         => null,
                    'description' => trim($desc),
                    'price_idr'   => $idr,
                    'qty'         => $qty,
                    'unit'        => 'kg',
                ];
            }
        }
        return $rows;
    }

    // prefer SELLER block; fallback to buyer only if we couldn't find a seller
    protected function extractVendorAddressSmart(string $t): array
    {
        // 1) “Dari :” / “From :” block (Karya Aroma) → seller
        if (preg_match('/\b(?:Dari|From)\s*:?\s*\n(.+?)(?:\n\s*\n|\n{2,}|^\s*No\b|\bKirim ke\b|\bTo\b)/imus', $t, $m)) {
            $block = trim($m[1]);
            $lines = array_values(array_filter(array_map('trim', explode("\n", $block))));
            $vendor  = $lines[0] ?? null;
            // if “Head office …” or contact lines follow, take 3–5 lines as address
            $address = implode(', ', array_slice($lines, 1, 5)) ?: null;
            return [$vendor, $address];
        }

        // 2) Top-of-page company heading that starts with “PT.” (RPI and many others)
        if (preg_match('/^\s*(PT\.\s[^\n]+)\s*(?:\n[^\S\r\n]*.*){0,3}\b(?:Invoice|Faktur|PROFORMA|Sales Order)/imu', $t, $m)) {
            $vendor = trim($m[1]);
            // try to grab lines just under the heading as address (until a blank line or table header)
            if (preg_match('/' . preg_quote($vendor, '/') . '\s*\n(.+?)(?:\n\s*\n|\n{2,}|\bInvoice\b|\bPROFORMA\b|\bFaktur\b)/imu', $t, $m2)) {
                $addrLines = array_values(array_filter(array_map('trim', explode("\n", trim($m2[1])))));
                $address   = implode(', ', array_slice($addrLines, 0, 5)) ?: null;
                return [$vendor, $address];
            }
            return [$vendor, null];
        }

        // 3) Buyer block (fallback when we truly cannot find the seller)
        if (preg_match('/\b(?:To|Kepada|Kirim ke|Krn ke|BILL TO)\s*:?\s*\n(.+?)(?:\n\s*\n|\n{2,}|^\s*PHONE|\bDate\b|\bDATE\b|\bNo\W*Invoice\b)/imus', $t, $m)) {
            $block   = trim($m[1]);
            $lines   = array_values(array_filter(array_map('trim', explode("\n", $block))));
            $vendor  = $lines[0] ?? null;
            $address = implode(', ', array_slice($lines, 1, 5)) ?: null;
            return [$vendor, $address];
        }

        return [null, null];
    }

    /** treat control/whitespace-only (e.g., "\f") as empty */
    protected function isEmptyText(?string $t): bool
    {
        return trim((string)$t) === '' || trim((string)$t) === "\f";
    }

    protected function extractBillTo(string $text): array
    {
        // very light heuristic that works for your sample
        if (preg_match('/BILL TO:?\s*(.+)\n(.+)\n(.+)\n(.+)/i', $text, $m)) {
            $vendor  = trim($m[1]);
            $address = trim($m[2] . ', ' . $m[3] . ', ' . $m[4]);
            return [$vendor, $address];
        }
        return [null, null];
    }

    private function parseIdrToInt(string $s): int
    {
        return (int) preg_replace('/[^0-9]/', '', $s);
    }

    protected function pdfToText(string $pdf): string
    {
        $bin = env('PDFTOTEXT_PATH');
        if (!$bin || !file_exists($bin)) {
            Log::warning("PDFTOTEXT_PATH missing or invalid: {$bin}");
            return '';
        }

        $cmd = [$bin, '-layout', $pdf, '-'];
        $p = new Process($cmd, dirname($pdf), null, null, 30);
        $p->run();

        if (!$p->isSuccessful()) {
            Log::warning('pdftotext failed: ' . $p->getErrorOutput());
            return '';
        }
        return (string)$p->getOutput();
    }

    protected function pdfFirstPageToPng(string $pdf): ?string
    {
        $ppm = env('PDFTOPPM_PATH');
        if (!$ppm || !file_exists($ppm)) {
            Log::info('pdftoppm not configured; skipping image rasterization');
            return null;
        }
        $outBase = rtrim(sys_get_temp_dir(), "\\/") . DIRECTORY_SEPARATOR . 'poimg_' . uniqid();
        $cmd = [$ppm, '-singlefile', '-png', '-f', '1', '-l', '1', $pdf, $outBase];
        $p = new Process($cmd, dirname($pdf), null, null, 30);
        $p->run();
        if (!$p->isSuccessful()) {
            Log::warning('pdftoppm failed: ' . $p->getErrorOutput());
            return null;
        }
        $png = $outBase . '.png';
        return file_exists($png) ? $png : null;
    }

    protected function ocrImage(string $img): string
    {
        $tess = env('TESSERACT_PATH');
        if (!$tess || !file_exists($tess)) {
            Log::warning("TESSERACT_PATH missing or invalid: {$tess}");
            return '';
        }
        try {
            return (new TesseractOCR($img))
                ->executable($tess)->lang('eng')->psm(6)->run();
        } catch (\Throwable $e) {
            Log::warning('Tesseract failed: ' . $e->getMessage());
            return '';
        }
    }

    protected function grab(string $re, string $text): ?string
    {
        return (preg_match($re, $text, $m)) ? trim($m[1] ?? '') : null;
    }
}
