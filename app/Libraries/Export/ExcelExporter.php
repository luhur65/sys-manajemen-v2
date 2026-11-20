<?php

namespace App\Libraries\Export;

use CodeIgniter\HTTP\ResponseInterface;
use Config\Export as ExportConfig;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Pembuat file Excel generik untuk laporan grid (diadaptasi dari ExcelMaker
 * di belajarci4-api).
 *
 * Data diterima sebagai iterable CHUNK (array baris asosiatif), ditulis ke
 * sheet satu chunk demi satu chunk, dan setiap chunk melapor ke
 * ExportNotifier. Dengan begitu sumber data bisa diganti generator yang
 * membaca DB bertahap, dan notifier bisa diganti SocketExportNotifier saat
 * export dipindah ke background — tanpa mengubah kelas ini.
 *
 * Definisi kolom:
 *   ['key' => 'FNominal', 'label' => 'Nominal Invoice', 'type' => 'number', 'width' => 18]
 *   type: text (default) | number | integer | date (string tanggal -> tanggal Excel)
 *   format (opsional): kode format Excel untuk number/integer, default '#,##0'
 *   group (opsional): judul header grup (seperti setGroupHeaders jqGrid). Kolom
 *     berurutan dengan group sama digabung di baris atas; kolom tanpa group
 *     digabung vertikal dua baris.
 */
class ExcelExporter
{
    private const HEADER_FILL = '4472C4';

    private ExportNotifier $notifier;
    private string $title = '';
    /** @var array<string,string> label => nilai, ditulis di atas tabel */
    private array $meta = [];
    /** Baris paling atas header (beda dengan baris header kolom bila ada group). */
    private int $headerTop = 0;

    /**
     * @param array<int,array{key:string,label:string,type?:string,width?:int}> $columns
     */
    public function __construct(
        private array $columns,
        ?ExportNotifier $notifier = null,
        private bool $withRowNumber = true
    ) {
        $this->notifier = $notifier ?? new NullExportNotifier();
        if ($withRowNumber) {
            array_unshift($this->columns, ['key' => '__no', 'label' => 'No', 'type' => 'integer', 'width' => 6]);
        }
    }

    /** Judul laporan + keterangan filter di atas tabel. */
    public function setTitle(string $title, array $meta = []): static
    {
        $this->title = $title;
        $this->meta  = $meta;
        return $this;
    }

    /** Pecah array menjadi chunk sesuai Config\Export::$chunkSize. */
    public static function chunk(array $rows, ?int $size = null): \Generator
    {
        $size = max(1, $size ?? config(ExportConfig::class)->chunkSize);
        foreach (array_chunk($rows, $size) as $chunk) {
            yield $chunk;
        }
    }

    /**
     * Tulis semua chunk ke file .xlsx dan kembalikan path lengkapnya.
     *
     * @param iterable<array<int,array<string,mixed>>> $chunks
     * @param array<string,mixed>|null $totals baris total, key = key kolom
     */
    public function export(iterable $chunks, int $totalRows, string $fileName, ?array $totals = null): string
    {
        try {
            $this->notifier->started($totalRows);

            $spreadsheet = new Spreadsheet();
            $sheet       = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Data');

            $headerRow = $this->writeHeading($sheet);
            $row       = $headerRow + 1;
            $written   = 0;

            foreach ($chunks as $chunk) {
                $data = [];
                foreach ($chunk as $item) {
                    $data[] = $this->toCells($item, ++$written);
                }
                if ($data) {
                    $sheet->fromArray($data, null, 'A' . $row, true);
                    $row += count($data);
                }
                $this->notifier->progress($written, $totalRows);
            }

            $lastDataRow = $row - 1;
            if ($totals !== null) {
                $sheet->fromArray([$this->toCells($totals, null)], null, 'A' . $row, true);
                $sheet->getStyle($this->range($row, $row))->getFont()->setBold(true);
                $lastDataRow = $row;
            }

            $this->formatTable($sheet, $headerRow, $lastDataRow);

            $path = $this->targetPath($fileName);
            (new Xlsx($spreadsheet))->save($path);
            $spreadsheet->disconnectWorksheets();

            $this->notifier->finished($written);

            return $path;
        } catch (\Throwable $e) {
            $this->notifier->failed('Export gagal: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Kirim file ke browser lalu hapus file sementaranya (mode sinkron).
     */
    public static function download(string $path, string $downloadName): ResponseInterface
    {
        $content = file_get_contents($path);
        @unlink($path);

        return response()
            ->download($downloadName, $content)
            ->setContentType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /** Tulis judul, keterangan filter dan header kolom; kembalikan nomor baris header. */
    private function writeHeading(Worksheet $sheet): int
    {
        $row = 1;
        if ($this->title !== '') {
            $sheet->setCellValue('A1', $this->title);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $row = 2;
        }
        // Label keterangan di-merge beberapa kolom awal sampai cukup lebar,
        // nilai di kolom sesudahnya (teks meluber ke kanan karena sel kosong).
        $labelSpan = $this->metaLabelSpan();
        $labelEnd  = Coordinate::stringFromColumnIndex($labelSpan);
        $valueCol  = Coordinate::stringFromColumnIndex($labelSpan + 1);
        foreach ($this->meta as $label => $value) {
            $sheet->setCellValueExplicit('A' . $row, $label, 's');
            if ($labelSpan > 1) {
                $sheet->mergeCells("A{$row}:{$labelEnd}{$row}");
            }
            $sheet->setCellValueExplicit($valueCol . $row, ': ' . $value, 's');
            $row++;
        }
        if ($row > 1) {
            $row++; // satu baris kosong sebelum tabel
        }

        $this->headerTop = $row;
        $grouped = (bool) array_filter($this->columns, static fn($c) => !empty($c['group']));
        if ($grouped) {
            $this->writeGroupHeader($sheet, $row);
            $row++;
        }

        foreach ($this->columns as $i => $col) {
            if ($grouped && empty($col['group'])) {
                continue; // sudah ditulis & di-merge vertikal di baris group
            }
            // explicit string: label seperti "20" / "40" tidak berubah jadi angka
            $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($i + 1) . $row, (string) $col['label'], 's');
        }
        $sheet->getStyle($this->range($this->headerTop, $row))->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::HEADER_FILL]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
        ]);

        return $row;
    }

    /** Jumlah kolom awal yang digabung untuk label keterangan agar teksnya tidak terpotong. */
    private function metaLabelSpan(): int
    {
        $need = 0;
        foreach (array_keys($this->meta) as $label) {
            $need = max($need, mb_strlen((string) $label) * 1.1 + 1);
        }
        $span = 0;
        $sum  = 0.0;
        $max  = max(1, count($this->columns) - 1);
        while ($span < $max && $sum < $need) {
            $sum += $this->columnWidth($this->columns[$span]);
            $span++;
        }
        return max(1, $span);
    }

    /**
     * Lebar kolom: minimal cukup untuk label header (bold) + tombol autofilter,
     * supaya judul kolom tidak tertutup / terpotong.
     */
    private function columnWidth(array $col): float
    {
        $min = mb_strlen((string) $col['label']) * 1.2 + 4.5;
        return max((float) ($col['width'] ?? 10), $min);
    }

    /** Tanggal cetak dengan nama bulan lengkap, mis. "20 November 2026 11:40:46" (WIB). */
    public static function tanggalCetak(?int $ts = null): string
    {
        static $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
            'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $d = (new \DateTimeImmutable('@' . ($ts ?? time())))->setTimezone(new \DateTimeZone('Asia/Jakarta'));
        return $d->format('d') . ' ' . $bulan[(int) $d->format('n')] . ' ' . $d->format('Y H:i:s');
    }

    /** Baris header grup: kolom ber-group sama digabung horizontal, tanpa group digabung vertikal. */
    private function writeGroupHeader(Worksheet $sheet, int $row): void
    {
        $count = count($this->columns);
        for ($i = 0; $i < $count; $i++) {
            $col    = $this->columns[$i];
            $letter = Coordinate::stringFromColumnIndex($i + 1);
            $group  = $col['group'] ?? '';

            if ($group === '') {
                $sheet->setCellValue($letter . $row, $col['label']);
                $sheet->mergeCells($letter . $row . ':' . $letter . ($row + 1));
                continue;
            }

            $end = $i;
            while ($end + 1 < $count && ($this->columns[$end + 1]['group'] ?? '') === $group) {
                $end++;
            }
            $sheet->setCellValue($letter . $row, $group);
            if ($end > $i) {
                $sheet->mergeCells($letter . $row . ':' . Coordinate::stringFromColumnIndex($end + 1) . $row);
            }
            $i = $end;
        }
    }

    /** Ubah satu baris asosiatif menjadi array sel sesuai urutan kolom. */
    private function toCells(array $item, ?int $no): array
    {
        $cells = [];
        foreach ($this->columns as $col) {
            if ($col['key'] === '__no') {
                $cells[] = $no ?? ($item['__no'] ?? null);
                continue;
            }
            $val = $item[$col['key']] ?? null;
            $cells[] = match ($col['type'] ?? 'text') {
                'number', 'integer' => ($val === null || $val === '') ? null : (is_numeric($val) ? $val + 0 : $val),
                'date'   => $this->toExcelDate($val),
                default  => $val === null ? '' : (string) $val,
            };
        }
        return $cells;
    }

    private function toExcelDate($val)
    {
        if ($val === null || $val === '') {
            return null;
        }
        // Timestamp Unix (UTC) membuat tanggal WIB mundur 1 hari di Excel
        // (16-Oct 00:00 WIB = 15-Oct 17:00 UTC). Pakai objek tanggal lokal.
        try {
            return ExcelDate::PHPToExcel(new \DateTimeImmutable((string) $val));
        } catch (\Throwable) {
            return (string) $val;
        }
    }

    private function formatTable(Worksheet $sheet, int $headerRow, int $lastRow): void
    {
        $lastRow = max($lastRow, $headerRow);

        $sheet->getStyle($this->range($this->headerTop ?: $headerRow, $lastRow))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->freezePane('A' . ($headerRow + 1));
        $sheet->setAutoFilter($this->range($headerRow, $headerRow));

        foreach ($this->columns as $i => $col) {
            $letter = Coordinate::stringFromColumnIndex($i + 1);
            $type   = $col['type'] ?? 'text';
            $body   = $letter . ($headerRow + 1) . ':' . $letter . $lastRow;

            if ($type === 'number' || $type === 'integer') {
                $sheet->getStyle($body)->getNumberFormat()->setFormatCode($col['format'] ?? '#,##0');
                $sheet->getStyle($body)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            } elseif ($type === 'date') {
                $sheet->getStyle($body)->getNumberFormat()->setFormatCode('dd-mmm-yyyy');
                $sheet->getStyle($body)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            $dim = $sheet->getColumnDimension($letter);
            isset($col['width']) ? $dim->setWidth($this->columnWidth($col)) : $dim->setAutoSize(true);
        }
    }

    private function range(int $fromRow, int $toRow): string
    {
        $last = Coordinate::stringFromColumnIndex(count($this->columns));
        return "A{$fromRow}:{$last}{$toRow}";
    }

    private function targetPath(string $fileName): string
    {
        $dir = rtrim(config(ExportConfig::class)->directory, '\\/');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        // nama file disterilkan + suffix acak agar tidak bentrok antar user
        $safe = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $fileName);
        return $dir . DIRECTORY_SEPARATOR . $safe . '_' . bin2hex(random_bytes(4)) . '.xlsx';
    }
}
