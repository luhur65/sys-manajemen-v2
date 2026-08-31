<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\ContentSecurityPolicy;

/**
 * Regresi untuk M-01 — kelengkapan header keamanan.
 *
 * Header keamanan punya sifat yang menyulitkan: kalau salah satu hilang, tidak
 * ada yang rusak dan tidak ada yang mengeluh. Halaman tetap tampil normal,
 * hanya proteksinya yang lenyap diam-diam. Test ini menahan tiga kelas
 * kemunduran yang semuanya tidak terlihat dari layar:
 *
 *   1. Header yang hilang sama sekali.
 *   2. Nilai yang berubah menjadi lebih longgar.
 *   3. Pengetatan yang justru mematikan fitur — dua di antaranya sudah
 *      teridentifikasi dan dikunci di bawah (WebAuthn dan COEP).
 */
final class SecurityHeadersTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped(
                'Butuh ekstensi sqlite3. Jalankan: php -d extension=sqlite3 vendor/bin/phpunit'
            );
        }
    }

    /**
     * Sengaja `login`, bukan `home`.
     *
     * CodeIgniter menghentikan pipeline begitu sebuah filter `before`
     * mengembalikan Response (CodeIgniter.php:492) — filter `after`, termasuk
     * `secureheaders`, tidak pernah jalan. Jadi setiap redirect yang dilempar
     * AuthFilter keluar TANPA header keamanan. Dampaknya kecil (bodinya kosong,
     * tidak ada dokumen yang dirender), tapi artinya `home` bukan halaman yang
     * sah untuk menguji header. `login` dikecualikan dari AuthFilter sehingga
     * melewati pipeline penuh sampai `after`.
     */
    private function headers(): array
    {
        return $this->get('login')->response()->headers();
    }

    private function header(string $nama): string
    {
        $headers = $this->headers();

        $this->assertArrayHasKey($nama, $headers, 'Header ' . $nama . ' tidak dikirim sama sekali.');

        return (string) $headers[$nama]->getValueLine();
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function headerWajib(): array
    {
        return [
            'HSTS'                  => ['Strict-Transport-Security', 'max-age=31536000; includeSubDomains'],
            'X-Frame-Options'       => ['X-Frame-Options', 'SAMEORIGIN'],
            'X-Content-Type-Options' => ['X-Content-Type-Options', 'nosniff'],
            'Referrer-Policy'       => ['Referrer-Policy', 'strict-origin-when-cross-origin'],
            // M-01: tiga di bawah ini sebelumnya tidak ada sama sekali.
            'COOP'                  => ['Cross-Origin-Opener-Policy', 'same-origin-allow-popups'],
            'CORP'                  => ['Cross-Origin-Resource-Policy', 'same-origin'],
        ];
    }

    /**
     * @dataProvider headerWajib
     */
    public function testHeaderKeamananDikirimDenganNilaiYangDiharapkan(string $nama, string $nilai): void
    {
        $this->assertSame($nilai, $this->header($nama));
    }

    /**
     * `previewPDFs()` di mains.js membuka `window.open('', '_blank')` lalu
     * menulis isinya lewat `winTab.document.write()`. COOP `same-origin` akan
     * membuang referensi ke popup itu; `same-origin-allow-popups`
     * mempertahankannya. Kekeliruan di sini tidak akan terlihat sampai ada yang
     * mencoba mencetak laporan.
     */
    public function testCoopTidakMemutusPopupYangDibukaSendiri(): void
    {
        $this->assertSame(
            'same-origin-allow-popups',
            $this->header('Cross-Origin-Opener-Policy'),
            'COOP same-origin akan mematikan pratinjau PDF (previewPDFs di mains.js).'
        );
    }

    /**
     * COEP `require-corp` menuntut setiap sub-resource lintas-origin membawa
     * header CORP sendiri. Aplikasi ini memuat skrip, font, dan gambar dari
     * cdnjs, highcharts, Google Fonts, dan S3 — tidak satu pun dijamin
     * mengirimkannya. Memasangnya berarti halaman kehilangan aset-aset itu
     * sekaligus, gejala yang persis sama dengan "semua gambar rusak".
     */
    public function testCoepTidakDipasangSelamaAsetMasihDariCdn(): void
    {
        $this->assertArrayNotHasKey(
            'Cross-Origin-Embedder-Policy',
            $this->headers(),
            'COEP baru boleh dipasang setelah seluruh aset CDN dihosting lokal.'
        );
    }

    /**
     * Menambahkan `publickey-credentials-get=()` ke Permissions-Policy akan
     * mematikan login biometrik. Bentuknya persis seperti pengetatan biasa,
     * jadi mudah masuk tanpa disadari — dan gagalnya baru terlihat saat ada
     * yang menempelkan jarinya ke sensor.
     */
    public function testPermissionsPolicyTidakMematikanWebauthn(): void
    {
        $this->assertStringNotContainsString(
            'publickey-credentials-get',
            $this->header('Permissions-Policy'),
            'Direktif ini mematikan WebAuthn. Defaultnya sudah `self`; biarkan tidak disebut.'
        );
    }

    public function testPermissionsPolicyMenutupFiturYangTidakDipakai(): void
    {
        $nilai = $this->header('Permissions-Policy');

        foreach (['camera', 'microphone', 'geolocation', 'payment', 'usb'] as $fitur) {
            $this->assertStringContainsString($fitur . '=()', $nilai, 'Fitur ' . $fitur . ' tidak ditutup.');
        }
    }

    // ------------------------------------------------------------------- CSP

    /**
     * Ketiganya sebelumnya null/'self' sehingga direktifnya tidak pernah
     * dikirim atau menyisakan permukaan yang tidak dipakai.
     */
    public function testDirektifCspYangDitambahkanM01(): void
    {
        $csp = new ContentSecurityPolicy();

        $this->assertSame('self', $csp->baseURI, 'base-uri kosong berarti tag <base> suntikan bisa membelokkan semua URL relatif.');
        $this->assertSame('self', $csp->frameAncestors);
        $this->assertSame('none', $csp->objectSrc);
    }

    /**
     * `unsafe-inline`/`unsafe-eval` MASIH ADA dan itu disengaja — melepasnya
     * menuntut pemindahan 47 blok skrip inline di 44 view lebih dulu (lihat
     * bagian CSP Readiness pada laporan audit). Test ini bukan menyetujui
     * keadaan itu, melainkan menjaga agar penghapusannya nanti dilakukan sadar:
     * yang menghapusnya harus ikut memperbarui test ini, bukan menemukannya
     * lewat halaman yang tiba-tiba kosong di production.
     */
    public function testCspMasihMengizinkanInlineSampaiViewDibersihkan(): void
    {
        $csp = new ContentSecurityPolicy();

        $this->assertContains('unsafe-inline', $csp->scriptSrc);
        $this->assertContains('unsafe-eval', $csp->scriptSrc);
    }

    /**
     * Di development CSP berjalan report-only supaya nonce Debug Toolbar tidak
     * ikut memblokir seluruh inline script. Yang TIDAK boleh terjadi adalah
     * kelonggaran itu terbawa ke production.
     */
    public function testCspEnforcingSaatDebugMati(): void
    {
        $debugAsli = defined('CI_DEBUG') ? CI_DEBUG : null;

        if ($debugAsli === null) {
            $this->markTestSkipped('CI_DEBUG tidak terdefinisi pada konteks test ini.');
        }

        $csp = new ContentSecurityPolicy();

        $this->assertSame(
            (bool) $debugAsli,
            $csp->reportOnly,
            'reportOnly harus mengikuti CI_DEBUG: longgar saat debug, menegakkan saat tidak.'
        );
    }
}
