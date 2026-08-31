<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class SecurityHeaders implements FilterInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        //
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return ResponseInterface|void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $response->setHeader(
            'Strict-Transport-Security',
            'max-age=31536000; includeSubDomains'
        );

        $response->setHeader(
            'X-Frame-Options',
            'SAMEORIGIN'
        );

        $response->setHeader(
            'X-Content-Type-Options',
            'nosniff'
        );

        $response->setHeader(
            'Referrer-Policy',
            'strict-origin-when-cross-origin'
        );

        // M-01: fitur yang aplikasi ini tidak pakai sama sekali, dimatikan agar
        // skrip pihak ketiga (atau XSS) tidak bisa memintanya atas nama halaman.
        //
        // `publickey-credentials-get` SENGAJA TIDAK DIDAFTARKAN. Default-nya
        // sudah `self`, dan mencantumkannya sebagai `()` akan mematikan login
        // biometrik WebAuthn — satu baris yang kelihatan seperti pengetatan
        // biasa, tapi mematikan fitur autentikasi.
        //
        // `interest-cohort` (FLoC) yang disebut laporan tidak ikut: fiturnya
        // sudah ditarik dari browser, jadi nilainya hanya menambah panjang
        // header tanpa menutup apa pun.
        $response->setHeader(
            'Permissions-Policy',
            'accelerometer=(), camera=(), display-capture=(), geolocation=(), '
            . 'gyroscope=(), magnetometer=(), microphone=(), midi=(), '
            . 'payment=(), serial=(), usb=()'
        );

        // M-01: memutus akses `window.opener` dari dokumen lintas-origin, supaya
        // halaman lain yang membuka aplikasi ini tidak bisa menyentuh window-nya.
        //
        // Dipilih `same-origin-allow-popups`, bukan `same-origin` seperti saran
        // laporan, karena aplikasi ini benar-benar memakai pola opener:
        // `previewPDFs()` di mains.js membuka `window.open('', '_blank')` lalu
        // menulis isinya lewat `winTab.document.write()`. Nilai ini menjaga
        // referensi ke popup yang KITA buka, sambil tetap mengisolasi kita dari
        // opener lintas-origin.
        $response->setHeader(
            'Cross-Origin-Opener-Policy',
            'same-origin-allow-popups'
        );

        // M-01: aset aplikasi ini tidak boleh dimuat sebagai sub-resource oleh
        // situs lain. Tidak memengaruhi kemampuan aplikasi memuat asetnya
        // sendiri — arah aturannya keluar, bukan masuk.
        $response->setHeader(
            'Cross-Origin-Resource-Policy',
            'same-origin'
        );

        // Cross-Origin-Embedder-Policy SENGAJA TIDAK DIPASANG.
        //
        // Laporan menyarankan `require-corp`. Nilai itu menuntut SETIAP
        // sub-resource lintas-origin membawa header CORP sendiri (atau diambil
        // lewat CORS eksplisit). Aplikasi ini memuat skrip dari
        // cdnjs.cloudflare.com, code.highcharts.com, dan
        // static.cloudflareinsights.com; style dari fonts.googleapis.com; font
        // dari fonts.gstatic.com; serta gambar dari bucket S3 — tidak satu pun
        // dijamin mengirim CORP. Memasangnya berarti aset-aset itu diblokir,
        // dan halaman kehilangan skrip, font, dan gambarnya sekaligus.
        //
        // Prasyaratnya bukan mengubah baris ini, melainkan menghosting aset CDN
        // secara lokal lebih dulu (lihat bagian CSP Readiness pada laporan
        // audit). Sesudah itu barulah `require-corp` bisa dipasang tanpa
        // mematikan halaman.
    }
}
