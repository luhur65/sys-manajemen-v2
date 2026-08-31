<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Config\App;

/**
 * Regresi untuk M-05 (Host Header Injection pada Pembentukan `baseURL`).
 *
 * `Config\App::__construct()` menyusun baseURL dari host request. Selama daftar
 * `$allowedHostnames` kosong, isi header `Host` masuk apa adanya — dan karena
 * `Login::forgotPassword()` merakit link reset dengan `base_url()`, satu request
 * ber-`Host: evil.example` cukup untuk membuat email reset korban menunjuk ke
 * server penyerang. Token korban ikut terkirim ke sana begitu link diklik.
 *
 * Yang dijaga di sini: host request hanya boleh dipakai kalau ada di daftar
 * putih, dan host di luar daftar tidak boleh menyentuh baseURL sama sekali.
 */
final class HostHeaderInjectionTest extends CIUnitTestCase
{
    private const BASE_URL_STATIS = 'https://sys.transporindo.com/';

    /** @var array<string, mixed> */
    private array $savedServer = [];

    /** @var array<string, string|false> */
    private array $savedEnv = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->savedServer = $_SERVER;

        foreach (['app.baseURL', 'app.folder', 'app.allowedHostnames'] as $key) {
            $this->savedEnv[$key] = getenv($key);
        }

        putenv('app.baseURL=' . self::BASE_URL_STATIS);
        putenv('app.folder=');
        putenv('app.allowedHostnames');
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->savedServer;

        foreach ($this->savedEnv as $key => $value) {
            if ($value === false) {
                putenv($key);
            } else {
                putenv($key . '=' . $value);
            }
        }

        parent::tearDown();
    }

    public function testHostAsingTidakPernahMasukKeBaseUrl(): void
    {
        $this->assertSame(
            self::BASE_URL_STATIS,
            $this->baseUrlUntukHost('evil.example'),
            'Host di luar daftar putih masih dipakai membangun baseURL (M-05).'
        );
    }

    public function testHostAsingTidakBisaMenumpangHostYangSah(): void
    {
        // Bentuk-bentuk yang lolos kalau host hanya disambung apa adanya:
        // path menambah segmen, kredensial menggeser host sebenarnya ke kanan.
        foreach (['sys.transporindo.com/evil.example', 'evil.example@sys.transporindo.com', 'sys.transporindo.com evil.example'] as $host) {
            $this->assertSame(
                self::BASE_URL_STATIS,
                $this->baseUrlUntukHost($host),
                sprintf('Header Host "%s" tidak ditolak (M-05).', $host)
            );
        }
    }

    public function testHostYangDiizinkanTetapDipakai(): void
    {
        $this->assertSame(
            'https://sys.transporindo.com/',
            $this->baseUrlUntukHost('sys.transporindo.com')
        );

        putenv('app.folder=sys');

        $this->assertSame(
            'https://staging.transporindo.com/sys/',
            $this->baseUrlUntukHost('staging.transporindo.com')
        );
    }

    public function testPortPengembanganLokalTetapTerbawa(): void
    {
        putenv('app.folder=/sys-modern/');

        $this->assertSame(
            'http://localhost:8080/sys-modern/',
            $this->baseUrlUntukHost('localhost:8080', false)
        );
    }

    public function testPenulisanHostTidakPerluPersis(): void
    {
        // Host header sah ditulis dengan huruf besar atau titik penutup; yang
        // keluar tetap satu bentuk yang sama.
        $this->assertSame(
            'https://sys.transporindo.com/',
            $this->baseUrlUntukHost('SYS.Transporindo.com.')
        );
    }

    public function testDaftarPutihBisaDiaturLewatEnv(): void
    {
        putenv('app.allowedHostnames=app.contoh.test, lain.contoh.test');

        $this->assertSame(
            'https://app.contoh.test/',
            $this->baseUrlUntukHost('app.contoh.test'),
            'Daftar dari .env tidak dipakai.'
        );
    }

    public function testHostDariBaseUrlSelaluIkutDiizinkan(): void
    {
        // Server yang app.baseURL-nya sudah benar tetapi lupa mengisi daftar
        // putih tidak boleh ikut terkunci — kegagalannya sulit dilacak dan
        // tidak menambah keamanan apa pun.
        putenv('app.baseURL=https://sys.karaya.site/');
        putenv('app.allowedHostnames=app.contoh.test');

        $this->assertSame(
            'https://sys.karaya.site/',
            $this->baseUrlUntukHost('sys.karaya.site')
        );
    }

    /**
     * Daftar yang sama juga dipakai framework di `SiteURIFactory::getValidHost()`
     * untuk menentukan host `current_url()`. Kalau properti ini kosong, sisi
     * framework kembali menebak sendiri.
     */
    public function testDaftarPutihIkutTerbacaOlehFramework(): void
    {
        $_SERVER = ['HTTP_HOST' => 'sys.transporindo.com', 'HTTPS' => 'on'];

        $config = new App();

        $this->assertNotEmpty($config->allowedHostnames);
        $this->assertContains('sys.transporindo.com', $config->allowedHostnames);
        $this->assertNotContains('evil.example', $config->allowedHostnames);
    }

    private function baseUrlUntukHost(string $host, bool $https = true): string
    {
        $_SERVER = ['HTTP_HOST' => $host];

        if ($https) {
            $_SERVER['HTTPS'] = 'on';
        }

        return (new App())->baseURL;
    }
}
