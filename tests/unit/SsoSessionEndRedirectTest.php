<?php

namespace Tests\Unit;

use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\Mock\MockCache;
use Config\Sso as SsoConfig;

/**
 * Tujuan redirect saat sesi berakhir SENDIRI — bukan lewat tombol logout.
 *
 * Ini jalur yang paling sering dialami pengguna, dan yang paling mudah
 * ketinggalan saat tujuan logout dipindahkan ke SSO: `Login::logout()` sudah
 * mengantar ke dashboard sementara AuthFilter masih mendaratkan orang di
 * halaman login sys. Dua jalurnya:
 *
 *   - Sesi sudah tidak ada saat request datang (habis sendiri, cookie hilang,
 *     atau belum pernah login).
 *   - Sesi SSO-nya dicabut di dashboard, lalu Single Logout mengakhiri sesi
 *     lokalnya.
 *
 * Keduanya punya bentuk AJAX sendiri: yang sampai ke layar saat pengguna
 * sedang menekan tombol bukan halaman baru melainkan 401 ber-JSON, dan alamat
 * tujuannya ikut di dalam badan jawaban itu (dibaca partials/header.php).
 * Kalau hanya jalur halaman yang diperbaiki, separuh kejadian tetap berakhir
 * di tempat yang lama.
 */
final class SsoSessionEndRedirectTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    /** Alamat dashboard SSO yang dipakai selama pengujian. */
    private const DASHBOARD_URL = 'https://testsso.transporindo.com/dashboard';

    /** Route mana pun yang dijaga filter auth; isinya tidak penting di sini. */
    private const ROUTE_TERJAGA = 'uji-sesi';

    private MockCache $cache;

    protected function setUp(): void
    {
        parent::setUp();

        // AuthFilter mencatat LOGOUT ke log_activity sebelum mengakhiri sesi,
        // dan konstruktor Model CI4 langsung membuka koneksi. Grup database
        // test memakai SQLite3.
        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped(
                'Butuh ekstensi sqlite3. Jalankan: php -d extension=sqlite3 vendor/bin/phpunit'
            );
        }

        // Hanya filter 'auth' yang dipasang: dialah yang diuji di sini. csrf
        // tidak berlaku untuk GET, dan acl butuh tabel yang tidak ada di
        // lingkungan test.
        $filters                    = config('Filters');
        $filters->globals['before'] = ['auth'];
        $filters->globals['after']  = [];

        // Session bawaan mengosongkan $_SESSION saat start() di lingkungan test,
        // sehingga nilai dari withSession() ikut hilang sebelum filter sempat
        // membacanya. MockSession tidak melakukannya.
        $this->mockSession();

        // SsoSlo menyimpan hasil introspeksi di cache. Dengan cache yang bisa
        // diisi dari sini, "sesi SSO sudah dicabut" bisa disimulasikan tanpa
        // menyentuh jaringan sama sekali.
        $this->cache = new MockCache();
        $this->cache->initialize();
        \Config\Services::injectMock('cache', $this->cache);

        // Route sendiri, bukan halaman aplikasi: pada kasus "sesi masih hidup"
        // filter meloloskan request, dan controller sungguhan mana pun akan
        // menyentuh database yang tidak ada di lingkungan test. Yang diuji di
        // sini filternya, bukan halaman yang dijaganya.
        $this->withRoutes([['GET', self::ROUTE_TERJAGA, static fn () => 'ok']]);
    }

    // ── Sesi sudah tidak ada saat request datang ────────────────────────────

    public function testSesiKosongDiarahkanKeSsoSaatSaklarMenyala(): void
    {
        $this->bootSso(true);

        $result = $this->get(self::ROUTE_TERJAGA);

        $result->assertRedirect();
        $this->assertSame(self::DASHBOARD_URL, (string) $result->getRedirectUrl());
    }

    public function testSesiKosongTetapKeHalamanLoginSaatSaklarMati(): void
    {
        $this->bootSso(false);

        $result = $this->get(self::ROUTE_TERJAGA);

        $result->assertRedirect();
        $this->assertLokal((string) $result->getRedirectUrl());
    }

    public function testJawabanAjaxUntukSesiKosongMembawaAlamatSso(): void
    {
        $this->bootSso(true);

        // Bentuk yang sampai ke layar saat pengguna sedang menekan tombol.
        // Alamat tujuannya ada di badan JSON, bukan di header Location.
        $body = $this->jawabanAjax();

        $this->assertTrue($body['sessionExpired'] ?? false, 'Penanda sessionExpired hilang — handler global tidak akan mengenalinya.');
        $this->assertSame(self::DASHBOARD_URL, $body['redirect'] ?? '');
    }

    public function testJawabanAjaxUntukSesiKosongTetapKeLoginSaatSaklarMati(): void
    {
        $this->bootSso(false);

        $this->assertLokal((string) ($this->jawabanAjax()['redirect'] ?? ''));
    }

    // ── Sesi SSO dicabut di dashboard (Single Logout) ───────────────────────

    public function testSesiSsoYangDicabutDiarahkanKeSsoSaatSaklarMenyala(): void
    {
        $this->bootSso(true);

        // Inti keluhan yang memunculkan test ini: sesinya berakhir karena
        // dicabut di dashboard SSO, tapi penggunanya justru mendarat di halaman
        // login sys hanya karena jalur ini membawa pesan "sesi habis".
        $result = $this->sesiSsoDicabut()->get(self::ROUTE_TERJAGA);

        $result->assertRedirect();
        $this->assertSame(self::DASHBOARD_URL, (string) $result->getRedirectUrl());
    }

    public function testSesiSsoYangDicabutTetapMembawaPesanExpiredSaatSaklarMati(): void
    {
        $this->bootSso(false);

        // Mekanisme lama, dan harus tetap begini: pengguna mendarat di halaman
        // login beserta kalimat yang menjelaskan sebabnya. Kode `expired`
        // itulah yang diterjemahkan Login::ssoMessage().
        $result = $this->sesiSsoDicabut()->get(self::ROUTE_TERJAGA);

        $result->assertRedirect();

        $url = (string) $result->getRedirectUrl();

        $this->assertLokal($url);
        $this->assertStringContainsString('sso=expired', $url, 'Kode expired hilang — halaman login tidak lagi bisa menjelaskan kenapa sesinya berakhir.');
    }

    public function testSesiSsoYangMasihHidupTidakDiganggu(): void
    {
        $this->bootSso(true);

        // Pembanding yang menjaga test di atas tetap berarti: tanpa ini,
        // filter yang melempar SEMUA orang ke SSO tanpa peduli status sesinya
        // akan lolos.
        $result = $this->sesiSso(true)->get(self::ROUTE_TERJAGA);

        $this->assertNotSame(
            self::DASHBOARD_URL,
            (string) $result->getRedirectUrl(),
            'Sesi SSO yang masih hidup ikut diakhiri.'
        );
    }

    // ── Alat bantu ──────────────────────────────────────────────────────────

    /** Menyetel konfigurasi SSO yang dipakai request berikutnya. */
    private function bootSso(bool $logoutToSso): void
    {
        // Dipatok, tidak diambil dari .env: yang diuji justru tujuan
        // pengalihannya, jadi ia tidak boleh ikut berubah kalau seseorang
        // mengganti alamat dashboard di .env.
        $sso               = config(SsoConfig::class);
        $sso->enabled      = true;
        $sso->dashboardUrl = self::DASHBOARD_URL;
        $sso->logoutToSso  = $logoutToSso;
        // Harus terisi keduanya, kalau tidak SsoSlo menganggap SLO belum
        // dikonfigurasi dan membiarkan setiap sesi hidup (fail open).
        $sso->apiBaseUrl = 'https://testssoapi.transporindo.com';
        $sso->sloSecret  = 'rahasia-test';
        Factories::injectMock('config', 'Sso', $sso);
    }

    /** Sesi SSO yang jawaban introspeksinya sudah `active:false`. */
    private function sesiSsoDicabut(): self
    {
        return $this->sesiSso(false);
    }

    /**
     * Sesi yang lahir dari SSO, dengan status introspeksi yang sudah ditanam di
     * cache — persis tempat SsoSlo membacanya, jadi tidak ada permintaan
     * jaringan yang terjadi selama test.
     */
    private function sesiSso(bool $masihHidup): self
    {
        $sid = 'sid-uji-coba';

        $this->cache->save('sso_slo_' . hash('sha256', $sid), $masihHidup ? 1 : 0, 60);

        return $this->withSession([
            SESSION_NAME . 'logged_in' => 1,
            SESSION_NAME . 'userid'    => 'budi',
            SESSION_NAME . 'sso_login' => 1,
            SESSION_NAME . 'sso_sid'   => $sid,
        ]);
    }

    /**
     * Jawaban AJAX untuk request tanpa sesi.
     *
     * @return array<string, mixed>
     */
    private function jawabanAjax(): array
    {
        $result = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(self::ROUTE_TERJAGA);

        $this->assertSame(401, $result->response()->getStatusCode());

        // FeatureTestTrait membungkus body dengan kerangka HTML, jadi JSON-nya
        // diambil kembali dari antara kurung kurawal terluar.
        $body  = (string) $result->getBody();
        $start = strpos($body, '{');
        $end   = strrpos($body, '}');

        $json = $start !== false && $end !== false ? substr($body, $start, $end - $start + 1) : $body;

        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded, 'Jawaban 401 bukan JSON: ' . $body);

        return $decoded;
    }

    /** Memastikan sebuah alamat menuju halaman login sys, bukan dashboard SSO. */
    private function assertLokal(string $url): void
    {
        $this->assertStringContainsString('login', $url, 'Tujuannya bukan halaman login sys: ' . $url);
        $this->assertStringNotContainsString('testsso.transporindo.com', $url);
    }
}
