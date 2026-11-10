<?php

namespace App\Libraries\Export;

/**
 * Penerima kabar progres export. ExcelExporter memanggilnya di awal,
 * setiap selesai menulis satu chunk, dan di akhir.
 *
 * - NullExportNotifier   : mode sinkron sekarang (tidak melakukan apa-apa).
 * - SocketExportNotifier : mode background nanti (kirim ke socket server).
 */
interface ExportNotifier
{
    public function started(int $totalRows): void;

    public function progress(int $writtenRows, int $totalRows): void;

    /** @param string|null $downloadUrl link file bila export berjalan di background */
    public function finished(int $totalRows, ?string $downloadUrl = null): void;

    public function failed(string $message): void;
}
