<?php

namespace Tests\Unit;

use App\Libraries\SsoSlo;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Filters as FiltersConfig;
use Config\Sso as SsoConfig;

/**
 * Sambungan-sambungan yang membuat SSO benar-benar bisa dipakai.
 *
 * Tiga hal di bawah ini gampang lepas tanpa ada yang sadar, dan gejalanya
 * membingungkan: callback SSO yang tidak dikecualikan dari AuthFilter akan
 * memantulkan pengguna ke halaman login persis saat ia hendak login, dan
 * kode aplikasi yang tidak cocok membuat semua tiket ditolak dengan alasan
 * "aud" yang sulit ditebak dari layar.
 */
final class SsoWiringTest extends CIUnitTestCase
{
    private const CALLBACK_ROUTE = 'auth/sso-callback';
    private const START_ROUTE    = 'sso/login';

    /**
     * Dibaca dari sumber Routes.php, bukan dari RouteCollection: proyek ini
     * mematikan auto-routing, dan koleksi route tidak terisi di konteks CLI
     * tempat test berjalan.
     */
    public function testRouteCallbackDanStartTerdaftar(): void
    {
        $source = (string) file_get_contents(APPPATH . 'Config/Routes.php');

        $this->assertMatchesRegularExpression(
            "/\\\$routes->get\(\s*'" . preg_quote(self::CALLBACK_ROUTE, '/') . "'\s*,\s*'SsoAuth::callback'/",
            $source,
            'Route ' . self::CALLBACK_ROUTE . ' -> SsoAuth::callback hilang dari Routes.php.'
        );

        $this->assertMatchesRegularExpression(
            "/\\\$routes->get\(\s*'" . preg_quote(self::START_ROUTE, '/') . "'\s*,\s*'SsoAuth::start'/",
            $source,
            'Route ' . self::START_ROUTE . ' -> SsoAuth::start hilang dari Routes.php.'
        );
    }

    /**
     * Callback dipanggil justru saat pengguna BELUM punya sesi sys-modern.
     * Kalau 'auth' atau 'acl' ikut berjalan di situ, pengguna dilempar kembali
     * ke halaman login dan alur SSO tidak akan pernah selesai.
     */
    public function testRouteSsoDikecualikanDariFilterAuthDanAcl(): void
    {
        $globals = (new FiltersConfig())->globals['before'];

        foreach (['auth', 'acl'] as $filter) {
            $this->assertArrayHasKey($filter, $globals, "Filter {$filter} hilang dari globals.");

            $except = $globals[$filter]['except'] ?? [];
            $except = is_array($except) ? $except : [$except];

            $this->assertContains(self::CALLBACK_ROUTE, $except, self::CALLBACK_ROUTE . " harus dikecualikan dari filter {$filter}.");
            $this->assertContains(self::START_ROUTE, $except, self::START_ROUTE . " harus dikecualikan dari filter {$filter}.");
        }
    }

    /**
     * Kode aplikasi dipakai sebagai klaim `aud` tiket. Nilainya harus sama
     * persis dengan key di TICKET_RELAY_APPS (auth-sso-api) dan `code` kartu SYS
     * di constants/apps.ts (auth-sso) — kalau berbeda, setiap tiket ditolak.
     */
    public function testAppCodeDanIssuerTerisi(): void
    {
        $config = new SsoConfig();

        $this->assertNotSame('', trim($config->appCode), 'sso.appCode kosong.');
        $this->assertSame('auth-sso', $config->issuer, 'sso.issuer harus auth-sso, sesuai SSO_TICKET_ISSUER di auth-sso-api.');
    }

    /**
     * Nonce harus bertahan lebih lama dari tiketnya sendiri. Kalau tidak,
     * ada jendela waktu ketika catatan "sudah dipakai" sudah dibuang tapi
     * tiketnya masih berlaku — dan replay jadi mungkin lagi.
     */
    public function testUmurNonceMelebihiUmurTiketDitambahToleransiJam(): void
    {
        $config = new SsoConfig();

        // TICKET_TTL_SECONDS di auth-sso-api saat ini 60 detik.
        $ticketTtl = 60;

        $this->assertGreaterThan(
            $ticketTtl + $config->leeway,
            $config->nonceTtl,
            'sso.nonceTtl harus lebih besar dari umur tiket + sso.leeway.'
        );
    }

    /**
     * Tanpa alamat API atau secret, introspeksi mustahil dilakukan. Yang tidak
     * boleh terjadi adalah semua orang ikut terlempar keluar karena itu — SLO
     * yang belum dikonfigurasi harus diam, bukan melogout.
     */
    public function testSloTanpaKonfigurasiTidakMelogoutSiapaPun(): void
    {
        $config = new SsoConfig();

        $config->apiBaseUrl = '';
        $config->sloSecret  = '';

        $slo = new SsoSlo($config);

        $this->assertFalse($slo->isConfigured());
        $this->assertTrue($slo->isSessionActive('sid-apa-saja'));
    }

    public function testSloMengabaikanSidKosong(): void
    {
        $this->assertTrue((new SsoSlo(new SsoConfig()))->isSessionActive(''));
    }

    /**
     * Tombol SSO harus punya warna teks sendiri untuk KEDUA tema.
     *
     * Tanpa itu ia mewarisi .verdant-btn, yang mewarnai teks untuk latar terisi:
     * krem di light mode, gelap di dark mode. Pada tombol berlatar transparan
     * kedua warna itu sewarna dengan kartunya dan tulisannya hilang sama sekali
     * — persis bug yang pernah terjadi. Kelihatan sepele, tapi akibatnya tombol
     * satu-satunya untuk masuk jadi tak terbaca.
     */
    public function testTombolSsoPunyaWarnaSendiriDiLightDanDarkMode(): void
    {
        $view = (string) file_get_contents(APPPATH . 'Views/login.php');

        $this->assertStringContainsString('id="btnSsoLogin"', $view, 'Tombol SSO kehilangan id yang dipakai aturan warnanya.');
        $this->assertMatchesRegularExpression('/#btnSsoLogin\s*\{[^}]*color\s*:/', $view, 'Tidak ada warna teks light mode untuk tombol SSO.');
        $this->assertMatchesRegularExpression('/body\.dark-mode\s+#btnSsoLogin\s*\{[^}]*color\s*:/', $view, 'Tidak ada warna teks dark mode untuk tombol SSO.');
    }
}
