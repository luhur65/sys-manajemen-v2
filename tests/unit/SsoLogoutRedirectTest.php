<?php

namespace Tests\Unit;

use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Sso as SsoConfig;

/**
 * Tujuan redirect setelah logout (`sso.logoutToSso`).
 *
 * Dua mekanisme sengaja hidup berdampingan, dan test ini menjaga keduanya:
 *
 *   - Saklar MATI: perilaku lama. Hanya sesi yang lahir dari SSO yang
 *     dikembalikan ke dashboard SSO; sesi login lokal pulang ke /login.
 *   - Saklar MENYALA: semua sesi diantar ke dashboard SSO.
 *
 * Yang paling mudah hilang saat seseorang merapikan kode ini nanti adalah
 * cabang lamanya — dihapus karena "sekarang kan selalu ke SSO". Karena itu
 * cabang mati-nya diuji sama seriusnya dengan cabang menyalanya.
 *
 * Sisanya tidak boleh ikut berubah: sesi tetap dihancurkan lebih dulu, dan
 * logout lokal tetap TIDAK mencabut sesi SSO-nya.
 */
final class SsoLogoutRedirectTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    /** Alamat dashboard SSO yang dipakai selama pengujian. */
    private const DASHBOARD_URL = 'https://testsso.transporindo.com/dashboard';

    protected function setUp(): void
    {
        parent::setUp();

        // Login::initController membuat model, dan konstruktor Model CI4 langsung
        // membuka koneksi. Grup database test memakai SQLite3, jadi tanpa
        // ekstensi itu controllernya bahkan tidak bisa diinstansiasi.
        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped(
                'Butuh ekstensi sqlite3. Jalankan: php -d extension=sqlite3 vendor/bin/phpunit'
            );
        }

        $filters                    = config('Filters');
        $filters->globals['before'] = [];
        $filters->globals['after']  = [];

        // Session bawaan mengosongkan $_SESSION saat start() di lingkungan test,
        // sehingga nilai dari withSession() ikut hilang sebelum controller sempat
        // membacanya. MockSession tidak melakukannya.
        $this->mockSession();
    }

    // ── Saklar menyala: semua sesi diantar ke SSO ───────────────────────────

    public function testSesiLoginLokalDiarahkanKeSsoSaatSaklarMenyala(): void
    {
        $this->bootSso(true);

        // Inti fiturnya: sesi ini masuk lewat userid/password, tapi keluarnya
        // tetap ke halaman SSO.
        $this->assertSame(self::DASHBOARD_URL, $this->urlSetelahLogout(false));
    }

    public function testSesiSsoTetapDiarahkanKeSsoSaatSaklarMenyala(): void
    {
        $this->bootSso(true);

        $this->assertSame(self::DASHBOARD_URL, $this->urlSetelahLogout(true));
    }

    // ── Saklar mati: mekanisme lama harus utuh ──────────────────────────────

    public function testSesiLoginLokalPulangKeHalamanLoginSaatSaklarMati(): void
    {
        $this->bootSso(false);

        $url = $this->urlSetelahLogout(false);

        $this->assertStringContainsString('login', $url, 'Sesi login lokal tidak lagi pulang ke halaman login sys.');
        $this->assertStringNotContainsString('testsso.transporindo.com', $url);
    }

    public function testSesiSsoTetapKeDashboardSaatSaklarMati(): void
    {
        // Perilaku yang sudah ada sebelum saklar ini dibuat, dan tidak boleh
        // ikut terbawa mati bersamanya: pengguna SSO tidak pernah memakai
        // halaman login lokal.
        $this->bootSso(false);

        $this->assertSame(self::DASHBOARD_URL, $this->urlSetelahLogout(true));
    }

    // ── Jaring pengaman saat SSO belum dikonfigurasi ────────────────────────

    public function testPulangKeHalamanLoginSaatSsoDimatikan(): void
    {
        $this->bootSso(true, false);

        // Logout selalu berhasil mengakhiri sesi, jadi salah konfigurasi di sini
        // baru terasa setelah pengguna benar-benar keluar. Jangan tinggalkan dia
        // di halaman error tanpa jalan kembali.
        $url = $this->urlSetelahLogout(false);

        $this->assertStringContainsString('login', $url);
        $this->assertStringNotContainsString('testsso.transporindo.com', $url);
    }

    public function testPulangKeHalamanLoginSaatDashboardUrlKosong(): void
    {
        $this->bootSso(true, true, '');

        $url = $this->urlSetelahLogout(true);

        $this->assertStringContainsString('login', $url, 'Redirect ke alamat kosong: dashboardUrl belum diisi tapi tetap dipakai.');
    }

    // ── Default dan hal-hal yang tidak boleh ikut berubah ───────────────────

    public function testDefaultnyaMekanismeLamaYangDipakai(): void
    {
        // Instalasi yang tidak menyetel apa pun harus berperilaku persis seperti
        // sebelum fitur ini ada.
        //
        // Dibaca dari deklarasi propertinya, BUKAN dari config(): BaseConfig
        // menimpa nilainya dengan isi .env, jadi instance biasa hanya bercerita
        // tentang environment yang sedang dipakai — bukan tentang instalasi yang
        // tidak menyetel apa-apa.
        $default = (new \ReflectionClass(SsoConfig::class))->getDefaultProperties()['logoutToSso'] ?? null;

        $this->assertFalse(
            $default,
            'Default sso.logoutToSso berubah — instalasi lama ikut terlempar ke SSO tanpa diminta.'
        );
    }

    public function testSesiDihancurkanSebelumTujuanDitentukan(): void
    {
        $source = $this->sumberLogout();

        $posisiDestroy  = strpos($source, 'session()->destroy()');
        $posisiRedirect = strpos($source, 'return redirect()->to(');

        $this->assertIsInt($posisiDestroy, 'Login::logout() tidak lagi menghancurkan sesi.');
        $this->assertIsInt($posisiRedirect);
        $this->assertLessThan(
            $posisiRedirect,
            $posisiDestroy,
            'Ada jalan keluar dari logout() sebelum session()->destroy() — pengguna dikirim ke SSO tapi sesi sys-nya masih hidup.'
        );
    }

    public function testLogoutLokalTidakMencabutSesiSso(): void
    {
        // Mengantar pengguna ke dashboard SSO bukan alasan untuk sekalian
        // mencabut sesi SSO-nya: itu akan melogout dia dari HR dan CRM juga,
        // dan wewenangnya ada di dashboard SSO, bukan di aplikasi anggota.
        // Komentarnya sendiri menyebut /auth/session/revoke sebagai hal yang
        // sengaja TIDAK dipanggil, jadi yang diperiksa hanya barisan kodenya.
        $kode = (string) preg_replace('#^\s*(//|\*|/\*).*$#m', '', $this->sumberLogout());

        $this->assertStringNotContainsString(
            'revoke',
            $kode,
            'Login::logout() mencabut sesi SSO — pengguna ikut terlempar dari HR dan CRM.'
        );
    }

    /**
     * Menjalankan logout sungguhan lewat HTTP dan mengembalikan alamat tujuannya.
     *
     * `sso_sid` sengaja tidak diisi: dengan begitu SsoSlo tidak dipanggil sama
     * sekali dan test ini tidak pernah menyentuh jaringan.
     */
    private function urlSetelahLogout(bool $fromSso): string
    {
        $result = $this->withSession([
            SESSION_NAME . 'logged_in' => 1,
            SESSION_NAME . 'userid'    => 'budi',
            SESSION_NAME . 'sso_login' => $fromSso ? 1 : null,
        ])->get('logout');

        $result->assertRedirect();

        return (string) $result->getRedirectUrl();
    }

    /** Menyetel konfigurasi SSO yang dipakai request berikutnya. */
    private function bootSso(bool $logoutToSso, bool $enabled = true, string $dashboardUrl = self::DASHBOARD_URL): void
    {
        // Dipatok, tidak diambil dari .env: yang diuji di sini justru tujuan
        // pengalihannya, jadi ia tidak boleh ikut berubah kalau seseorang
        // mengganti alamat dashboard di .env.
        $sso               = config(SsoConfig::class);
        $sso->enabled      = $enabled;
        $sso->dashboardUrl = $dashboardUrl;
        $sso->logoutToSso  = $logoutToSso;
        Factories::injectMock('config', 'Sso', $sso);
    }

    /** Potongan sumber Login::logout() beserta helper tujuannya. */
    private function sumberLogout(): string
    {
        $source = (string) file_get_contents(APPPATH . 'Controllers/Login.php');
        $start  = strpos($source, 'public function logout()');
        $end    = strpos($source, 'public function unlock()');

        $this->assertIsInt($start, 'Login::logout() tidak ditemukan — test ini sudah usang.');
        $this->assertIsInt($end);

        return substr($source, $start, $end - $start);
    }
}
