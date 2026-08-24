<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Regresi sisi klien untuk C-03.
 *
 * Menyalakan filter csrf tidak ada gunanya kalau ada satu saja tempat yang lupa
 * mengirim token — akibatnya bukan celah keamanan, tapi fitur yang mati diam-diam
 * dengan 403. Test ini memindai SENDIRI seluruh view dan JS milik aplikasi, lalu
 * memastikan setiap pengirim POST terjangkau oleh salah satu mekanisme:
 *
 *   1. AJAX jQuery  -> ikut $.ajaxSetup global, asalkan halamannya memuat token.
 *   2. fetch()/XHR  -> harus memasang header X-CSRF-TOKEN sendiri.
 *
 * Karena pemindaiannya dinamis, view atau berkas JS baru otomatis ikut diperiksa.
 */
final class CsrfClientCoverageTest extends CIUnitTestCase
{
    private const JS_DIR = FCPATH . 'libraries/tas-lib/js/';

    /** Bundel pihak ketiga yang kebetulan tinggal di folder yang sama. */
    private const VENDOR_JS = ['app.js'];

    /**
     * Halaman HTML lengkap yang berdiri sendiri (tidak memakai partials/header.php)
     * dan melakukan AJAX POST wajib memasang token sendiri.
     */
    public function testStandalonePagesThatPostAlsoShipTheToken(): void
    {
        $checked = [];

        foreach ($this->viewFiles() as $file) {
            $source = file_get_contents($file);

            if (! str_contains($source, '<html')) {
                continue; // fragmen view; token diwarisi dari partials/header.php
            }
            if (! $this->hasJqueryPost($source)) {
                continue;
            }

            $name = $this->relative($file);
            $checked[] = $name;

            // Harus benar-benar elemen <meta>, bukan sekadar penyebutan nama itu
            // di dalam querySelector pada blok skrip di bawahnya.
            $this->assertMatchesRegularExpression(
                '/<meta\s+name="csrf-token"\s+content=/',
                $source,
                $name . ' melakukan AJAX POST tapi tidak memasang <meta name="csrf-token">.'
            );
            $this->assertMatchesRegularExpression(
                '/\$\.ajaxSetup\(\s*\{\s*headers:\s*\{\s*[\'"]X-CSRF-TOKEN[\'"]/',
                $source,
                $name . ' memasang meta token tapi tidak memasangnya ke $.ajaxSetup.'
            );
        }

        // Sanity check pola deteksi: login.php memang ber-AJAX POST (forgot-password,
        // webauthn) dan berdiri sendiri, jadi ia WAJIB muncul di sini.
        // partials/header.php sendiri tidak ber-POST — ia diperiksa terpisah di
        // testGlobalAjaxSetupRunsAfterJqueryIsLoaded().
        $this->assertContains(
            'app/Views/login.php',
            $checked,
            'Pola deteksi AJAX POST tidak lagi mengenali login.php — test ini jadi tidak bermakna.'
        );
    }

    /**
     * fetch() dan XMLHttpRequest tidak lewat $.ajaxSetup, jadi harus memasang
     * header X-CSRF-TOKEN secara eksplisit.
     */
    public function testNonJqueryPostersSetTheHeaderThemselves(): void
    {
        $offenders = [];

        foreach ($this->appJsFiles() as $file) {
            $source = file_get_contents($file);

            if (! preg_match('/method:\s*[\'"]POST[\'"]/i', $source)
                && ! preg_match('/\.open\(\s*[\'"]POST[\'"]/i', $source)) {
                continue;
            }

            if (! str_contains($source, 'X-CSRF-TOKEN')) {
                $offenders[] = $this->relative($file);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Pengirim POST non-jQuery tanpa header X-CSRF-TOKEN: ' . implode(', ', $offenders)
        );
    }

    /**
     * Jaring pengaman terakhir: seluruh POST jQuery memang bergantung pada
     * $.ajaxSetup, jadi pemasangannya di partials/header.php tidak boleh hilang
     * dan harus berada SETELAH jQuery dimuat.
     */
    public function testGlobalAjaxSetupRunsAfterJqueryIsLoaded(): void
    {
        foreach (['partials/header.php', 'login.php'] as $view) {
            $source = file_get_contents(APPPATH . 'Views/' . $view);

            $jquery = strpos($source, 'plugins/jquery/jquery.min.js');
            $setup  = strpos($source, '$.ajaxSetup(');

            $this->assertNotFalse($jquery, $view . ': tag jQuery tidak ditemukan.');
            $this->assertNotFalse($setup, $view . ': $.ajaxSetup tidak ditemukan.');
            $this->assertLessThan(
                $setup,
                $jquery,
                $view . ': $.ajaxSetup berjalan sebelum jQuery dimuat — token tidak akan terpasang.'
            );
        }
    }

    // ------------------------------------------------------------------ utils

    private function hasJqueryPost(string $source): bool
    {
        return (bool) preg_match('/\$\.post\(/', $source)
            || (bool) preg_match('/(type|method):\s*[\'"]POST[\'"]/i', $source);
    }

    /** @return list<string> */
    private function viewFiles(): array
    {
        $files = [];

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(APPPATH . 'Views'));

        foreach ($it as $entry) {
            if ($entry->isFile() && $entry->getExtension() === 'php') {
                $files[] = $entry->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    /** @return list<string> */
    private function appJsFiles(): array
    {
        $files = [];

        foreach (glob(self::JS_DIR . '*.js') ?: [] as $file) {
            if (! in_array(basename($file), self::VENDOR_JS, true)) {
                $files[] = $file;
            }
        }

        return $files;
    }

    private function relative(string $path): string
    {
        return str_replace([APPPATH, FCPATH, '\\'], ['app/', 'public/', '/'], $path);
    }
}
