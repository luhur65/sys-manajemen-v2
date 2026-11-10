<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Pengaturan export Excel (App\Libraries\Export\ExcelExporter).
 *
 * Saat ini export berjalan SINKRON: file dibuat lalu langsung di-download.
 * Data tetap ditulis per chunk supaya mekanismenya sama dengan mode
 * background nanti. Untuk mode background + notifikasi socket (konsep
 * belajarci4-api: job -> POST /emit-notification -> socket.io room user_{id}):
 *   1. isi export.socketEnabled / socketUrl / socketToken di .env,
 *   2. jalankan export di proses terpisah (spark command / queue) dengan
 *      SocketExportNotifier, lalu kirim link download lewat finished().
 */
class Export extends BaseConfig
{
    /** Jumlah baris per chunk saat menulis ke sheet (dan tiap laporan progres). */
    public int $chunkSize = 1000;

    /** Folder file hasil export sementara. */
    public string $directory = WRITEPATH . 'exports';

    /** Notifikasi progres via socket server (Node socket.io), default mati. */
    public bool $socketEnabled = false;
    public string $socketUrl   = 'http://localhost:3000/emit-notification';
    public string $socketToken = '';
}
