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
 */
class ExcelExporter
{
    private const HEADER_FILL = '4472C4';

    private ExportNotifier $notifier;
    private string $title = '';
    /** @var array<string,string> label => nilai, ditulis di atas tabel */
    private array $meta = [];

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
        foreach ($this->meta as $label => $value) {
            $sheet->setCellValueExplicit('A' . $row, $label, 's');
            $sheet->setCellValueExplicit('B' . $row, ': ' . $value, 's');
            $row++;
        }
        if ($row > 1) {
            $row++; // satu baris kosong sebelum tabel
        }

        $sheet->fromArray([array_column($this->columns, 'label')], null, 'A' . $row);
        $sheet->getStyle($this->range($row, $row))->applyFromArray([
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
        $ts = strtotime((string) $val);
        return $ts === false ? (string) $val : ExcelDate::PHPToExcel($ts);
    }

    private function formatTable(Worksheet $sheet, int $headerRow, int $lastRow): void
    {
        $lastRow = max($lastRow, $headerRow);

        $sheet->getStyle($this->range($headerRow, $lastRow))
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
            isset($col['width']) ? $dim->setWidth($col['width']) : $dim->setAutoSize(true);
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
