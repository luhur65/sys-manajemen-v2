<?php

namespace Tests\Unit;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;

/**
 * Proxy tepercaya di belakang tunnel.
 *
 * Ketiga server yang berjalan memutus TLS di depan aplikasi (Cloudflare/
 * cloudflared), sehingga PHP menerima http polos dari localhost dan hanya tahu
 * koneksi aslinya https lewat header `X-Forwarded-Proto`. CodeIgniter menolak
 * mempercayai header itu selama pengirimnya bukan proxy terdaftar di
 * Config\App::$proxyIPs, dan selama daftar itu kosong dua hal ikut rusak diam-
 * diam:
 *
 *   1. `isSecure()` selalu false, jadi cookie sesi bertanda `secure`
 *      (cookie.secure = true di staging/production) gagal dikirim dan
 *      ResponseTrait::dispatchCookies() melempar SecurityException.
 *   2. `getIPAddress()` mengembalikan alamat proxy, bukan alamat pengunjung --
 *      throttle login dan audit trail mencatat 127.0.0.1 untuk SEMUA orang.
 *
 * Keduanya tidak terlihat dari layar sampai ada yang gagal login, jadi
 * perilakunya dikunci di sini.
 */
final class ProxyTepercayaTest extends CIUnitTestCase
{
    /** @var array<string, mixed> */
    private array $savedServer = [];

    private string|false $savedProxy = false;

    private string|false $savedHeader = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->savedServer = $_SERVER;
        $this->savedProxy  = getenv('app.proxyIPs');
        $this->savedHeader = getenv('app.proxyHeader');
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->savedServer;

        if ($this->savedProxy === false) {
            putenv('app.proxyIPs');
        } else {
            putenv('app.proxyIPs=' . $this->savedProxy);
        }

        if ($this->savedHeader === false) {
            putenv('app.proxyHeader');
        } else {
            putenv('app.proxyHeader=' . $this->savedHeader);
        }

        $this->resetServices();

        parent::tearDown();
    }

    public function testBawaanMempercayaiLoopbackSaja(): void
    {
        putenv('app.proxyIPs');

        $this->assertSame(
            ['127.0.0.1' => 'X-Forwarded-For', '::1' => 'X-Forwarded-For'],
            (new App())->proxyIPs
        );
    }

    public function testDaftarDiisiDariEnv(): void
    {
        putenv('app.proxyIPs=10.0.0.0/8, 192.168.1.5');

        $this->assertSame(
            ['10.0.0.0/8' => 'X-Forwarded-For', '192.168.1.5' => 'X-Forwarded-For'],
            (new App())->proxyIPs
        );
    }

    public function testBarisYangSengajaDikosongkanMematikanKepercayaan(): void
    {
        // Beda dengan barisnya tidak ada sama sekali: server yang melayani
        // internet langsung harus bisa menolak semua header proxy.
        putenv('app.proxyIPs=');

        $this->assertSame([], (new App())->proxyIPs);
    }

    public function testEntriTidakValidDibuang(): void
    {
        // getIPAddress() melempar ConfigException untuk bentuk daftar yang
        // salah, dan itu mematikan seluruh aplikasi -- bukan cuma pencatatan IP.
        putenv('app.proxyIPs=bukan-ip, 127.0.0.1');

        $this->assertSame(['127.0.0.1' => 'X-Forwarded-For'], (new App())->proxyIPs);
    }

    public function testKoneksiLewatTunnelDianggapAman(): void
    {
        putenv('app.proxyIPs');

        $request = $this->requestDari('127.0.0.1', ['HTTP_X_FORWARDED_PROTO' => 'https']);

        $this->assertTrue($request->isSecure());
    }

    public function testHeaderDariLuarTetapDitolak(): void
    {
        putenv('app.proxyIPs');

        // Menembak port aplikasi langsung dari internet sambil mengaku https.
        $request = $this->requestDari('203.0.113.9', ['HTTP_X_FORWARDED_PROTO' => 'https']);

        $this->assertFalse($request->isSecure());
    }

    public function testIpPengunjungAsliYangTercatat(): void
    {
        putenv('app.proxyIPs');
        putenv('app.proxyHeader');

        $request = $this->requestDari('127.0.0.1', ['HTTP_X_FORWARDED_FOR' => '198.51.100.7, 172.16.0.1']);

        $this->assertSame('198.51.100.7', $request->getIPAddress());
    }

    public function testTanpaProxyTerdaftarIpKlienJatuhKeAlamatProxy(): void
    {
        putenv('app.proxyIPs=');

        $request = $this->requestDari('127.0.0.1', ['HTTP_X_FORWARDED_FOR' => '198.51.100.7']);

        $this->assertSame('127.0.0.1', $request->getIPAddress());
    }

    public function testHeaderIpKlienBisaDipilihDariEnv(): void
    {
        putenv('app.proxyIPs');
        putenv('app.proxyHeader=CF-Connecting-IP');

        $this->assertSame(
            ['127.0.0.1' => 'CF-Connecting-IP', '::1' => 'CF-Connecting-IP'],
            (new App())->proxyIPs
        );
    }

    public function testHeaderNgawurJatuhKeBawaan(): void
    {
        putenv('app.proxyIPs=127.0.0.1');
        putenv('app.proxyHeader=bukan header valid');

        $this->assertSame(['127.0.0.1' => 'X-Forwarded-For'], (new App())->proxyIPs);
    }

    public function testCfConnectingIpMenangAtasXffYangDipalsukan(): void
    {
        // Cloudflare menambahkan IP asli ke rantai X-Forwarded-For yang dikirim
        // pengunjung, dan framework mengambil entri PERTAMA -- jadi lewat XFF,
        // 1.2.3.4 di bawah ini yang akan tercatat di throttle dan audit trail.
        putenv('app.proxyIPs');
        putenv('app.proxyHeader=CF-Connecting-IP');

        $request = $this->requestDari('127.0.0.1', [
            'HTTP_X_FORWARDED_FOR'  => '1.2.3.4, 198.51.100.7',
            'HTTP_CF_CONNECTING_IP' => '198.51.100.7',
        ]);

        $this->assertSame('198.51.100.7', $request->getIPAddress());
    }

    /**
     * @param array<string, string> $headers
     */
    private function requestDari(string $remoteAddr, array $headers): IncomingRequest
    {
        $_SERVER = array_merge($this->savedServer, ['REMOTE_ADDR' => $remoteAddr], $headers);

        // Tunnel menyerahkan http polos ke PHP; ini yang membuat isSecure()
        // bergantung sepenuhnya pada daftar proxy.
        unset($_SERVER['HTTPS']);

        // Superglobals memotret $_SERVER saat dibuat, jadi service lama harus
        // dibuang dulu supaya isSecure() melihat $_SERVER yang baru.
        $this->resetServices();

        return new IncomingRequest(new App(), new URI('http://localhost/'), '', new UserAgent());
    }
}
