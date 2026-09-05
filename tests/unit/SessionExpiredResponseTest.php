<?php

namespace Tests\Unit;

use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Apa yang terjadi ketika sesi berakhir di tengah pemakaian.
 *
 * Sesi bisa berakhir sementara halaman masih terbuka — kedaluwarsa sendiri, atau
 * dicabut lewat Single Logout dari dashboard SSO. Kalau saat itu pengguna sedang
 * menekan tombol dan bukan memuat ulang halaman, satu-satunya yang sampai ke
 * layar adalah jawaban AJAX-nya.
 *
 * Tanpa penanda pada jawaban itu, tiap grid dan grafik menerjemahkannya dengan
 * handler `error:` masing-masing menjadi "terjadi kesalahan saat mengambil
 * data" — pesan yang keliru, dan pengguna tidak pernah tahu bahwa ia sudah
 * logout. Test ini mengunci penandanya beserta penangan global yang membacanya.
 */
final class SessionExpiredResponseTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        // Filter TIDAK dimatikan di sini — AuthFilter justru yang diuji.
        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped(
                'Butuh ekstensi sqlite3. Jalankan: php -d extension=sqlite3 vendor/bin/phpunit'
            );
        }

        // Yang diuji di berkas ini BENTUK jawabannya, bukan tujuannya. Saklar
        // sso.logoutToSso dipatok mati supaya hasilnya tidak ikut berubah
        // mengikuti .env environment yang kebetulan menjalankan test; tujuan
        // versi SSO-nya punya berkas sendiri (SsoSessionEndRedirectTest).
        $sso              = config(\Config\Sso::class);
        $sso->logoutToSso = false;
        Factories::injectMock('config', 'Sso', $sso);
    }

    public function testRequestAjaxTanpaSesiDijawab401DenganPenanda(): void
    {
        $result = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->get('home');

        $this->assertSame(401, $result->response()->getStatusCode());

        $body = json_decode($this->jsonPayload($result->getBody()), true);

        $this->assertIsArray($body);

        // Penanda inilah yang membedakan "sesi habis" dari kegagalan biasa.
        $this->assertTrue($body['sessionExpired'] ?? false, 'Jawaban 401 tidak membawa penanda sessionExpired.');

        // Tujuan ikut dikirim supaya klien tidak perlu menebak.
        $this->assertStringContainsString('login', (string) ($body['redirect'] ?? ''));

        // Field lama dipertahankan: sudah ada call site yang membacanya.
        $this->assertSame('Session expired', $body['error'] ?? null);
    }

    public function testRequestBiasaTanpaSesiTetapDialihkanSepertiSemula(): void
    {
        // Jalur non-AJAX tidak boleh ikut berubah jadi JSON.
        $result = $this->get('home');

        $result->assertRedirect();
        $this->assertStringContainsString('login', (string) $result->getRedirectUrl());
    }

    /**
     * Cabang Single Logout harus mengirim tujuan yang membawa `?sso=expired`,
     * bukan halaman login polos. Bedanya nyata bagi pengguna: yang satu
     * menjelaskan bahwa sesi SSO-nya dicabut, yang lain hanya meminta login
     * lagi tanpa alasan.
     */
    public function testCabangSingleLogoutMengirimAlasannya(): void
    {
        $source = (string) file_get_contents(APPPATH . 'Filters/AuthFilter.php');

        $this->assertStringContainsString(
            "SsoExit::target(false, 'expired')",
            $source,
            'Cabang SLO tidak lagi membawa kode expired, jadi pengguna tidak diberi tahu sebabnya.'
        );

        // Kedua cabang harus memakai penyusun jawaban yang sama — kalau salah
        // satunya kembali menulis JSON sendiri, penandanya hilang diam-diam.
        // Dihitung dari pemanggilannya (`$this->`), bukan dari nama methodnya,
        // supaya deklarasinya sendiri tidak ikut terhitung.
        $this->assertSame(
            2,
            substr_count($source, '$this->sessionEnded('),
            'Ada cabang sesi-berakhir yang tidak lewat sessionEnded().'
        );

        // Dan penyusun itu satu-satunya tempat 401 ber-JSON dibuat.
        $this->assertSame(
            1,
            substr_count($source, '$this->sessionEndedJson('),
            'Ada cabang 401 yang menulis jawabannya sendiri, di luar sessionEnded().'
        );
    }

    public function testHeaderMemasangPenangananGlobalUntukSesiBerakhir(): void
    {
        $header = (string) file_get_contents(APPPATH . 'Views/partials/header.php');

        $this->assertStringContainsString('ajaxError', $header);
        $this->assertStringContainsString('sessionExpired', $header, 'Penangan global tidak membaca penanda sessionExpired.');
        $this->assertStringContainsString('window.location.href', $header, 'Penangan global tidak mengalihkan halaman.');

        // Satu halaman bisa punya beberapa AJAX gagal berbarengan; tanpa penjaga
        // ini pengalihan dipanggil berkali-kali.
        $this->assertStringContainsString('sessionEnded', $header, 'Tidak ada penjaga terhadap pengalihan berulang.');
    }

    /**
     * FeatureTestTrait membungkus body respons dengan kerangka HTML, jadi JSON-nya
     * diambil kembali dari antara kurung kurawal terluar.
     */
    private function jsonPayload(?string $body): string
    {
        $body  = (string) $body;
        $start = strpos($body, '{');
        $end   = strrpos($body, '}');

        return $start !== false && $end !== false ? substr($body, $start, $end - $start + 1) : $body;
    }
}
