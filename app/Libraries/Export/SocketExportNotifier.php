<?php

namespace App\Libraries\Export;

use Config\Export as ExportConfig;

/**
 * Kirim progres export ke socket server (pola belajarci4-api):
 * POST {socketUrl} dengan header Authorization, server Node lalu
 * emit event 'notification' ke room "user_{user_id}".
 *
 * Belum dipakai: export saat ini sinkron. Dipakai nanti saat export
 * dijalankan di background, mis.:
 *
 *   $notifier = new SocketExportNotifier($userPk, 'Laporan Piutang Overdue');
 *   $exporter = new ExcelExporter($columns, $notifier);
 *
 * Kegagalan kirim hanya dicatat ke log, tidak menggagalkan export.
 */
class SocketExportNotifier implements ExportNotifier
{
    private ExportConfig $config;

    /** Progres dikirim tiap kenaikan minimal sekian persen agar socket tidak dibanjiri. */
    private int $lastPercent = -1;

    public function __construct(
        private int|string $userId,
        private string $title,
        private int $minPercentStep = 10
    ) {
        $this->config = config(ExportConfig::class);
    }

    public function started(int $totalRows): void
    {
        $this->emit('started', 'Export dimulai (' . $totalRows . ' baris).', ['percent' => 0]);
    }

    public function progress(int $writtenRows, int $totalRows): void
    {
        $percent = $totalRows > 0 ? (int) floor($writtenRows * 100 / $totalRows) : 100;
        if ($percent < 100 && $percent - $this->lastPercent < $this->minPercentStep) {
            return;
        }
        $this->lastPercent = $percent;
        $this->emit('progress', "Menulis {$writtenRows} dari {$totalRows} baris.", [
            'percent' => $percent,
            'written' => $writtenRows,
            'total'   => $totalRows,
        ]);
    }

    public function finished(int $totalRows, ?string $downloadUrl = null): void
    {
        $this->emit('finished', 'File export sudah selesai dibuat.', [
            'percent'    => 100,
            'action_url' => $downloadUrl,
        ]);
    }

    public function failed(string $message): void
    {
        $this->emit('failed', $message, [], 'error');
    }

    private function emit(string $stage, string $message, array $extra = [], string $type = 'info'): void
    {
        if (!$this->config->socketEnabled) {
            return;
        }

        try {
            service('curlrequest')->post($this->config->socketUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config->socketToken,
                    'Content-Type'  => 'application/json',
                ],
                'json' => array_merge([
                    'user_id' => $this->userId,
                    'title'   => $this->title,
                    'stage'   => $stage,
                    'type'    => $stage === 'finished' ? 'success' : $type,
                    'message' => $message,
                ], $extra),
                'timeout'     => 3,
                'http_errors' => false,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'SocketExportNotifier: ' . $e->getMessage());
        }
    }
}
