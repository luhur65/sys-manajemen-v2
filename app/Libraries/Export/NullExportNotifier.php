<?php

namespace App\Libraries\Export;

/** Notifier kosong untuk export sinkron (hasil langsung di-download). */
class NullExportNotifier implements ExportNotifier
{
    public function started(int $totalRows): void
    {
    }

    public function progress(int $writtenRows, int $totalRows): void
    {
    }

    public function finished(int $totalRows, ?string $downloadUrl = null): void
    {
    }

    public function failed(string $message): void
    {
    }
}
