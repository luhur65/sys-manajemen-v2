<?php

namespace Tests\Unit;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;
use Config\Services;

/**
 * Regresi untuk penentuan IP pengunjung di belakang cloudflared.
 *
 * Ada dua kemunduran berlawanan arah yang sama-sama tidak terlihat dari layar,
 * dan test ini menahan keduanya sekaligus:
 *
 *   1. TERLALU CURIGA — kembali membaca REMOTE_ADDR mentah. Karena cloudflared
 *      berjalan di mesin yang sama dan menyambung lewat loopback, peer TCP
 *      aplikasi selamanya `::1`. Semua orang tercatat sebagai `::1`: throttle
 *      login jadi satu ember untuk seluruh internet, dan kolom `ip` di log
 *      aktivitas kehilangan seluruh nilainya. Ini persis keadaan sebelum
 *      perbaikan ini.
 *
 *   2. TERLALU PERCAYA — memakai isi header tanpa memeriksa pengirimnya.
 *      Pengunjung yang menembak port aplikasi langsung sambil melampirkan
 *      `X-Forwarded-For: 8.8.8.8` akan memilih sendiri identitasnya, sehingga
 *      throttle login bisa dilewati cukup dengan mengganti angka di header dan
 *      jejak di log audit bisa dikarang oleh pelakunya.
 *
 * Keduanya menghasilkan halaman yang tampak normal, jadi tidak ada yang akan
 * mengeluh kalau salah satunya kembali.
 */
final class ClientIpTest extends CIUnitTestCase
{
    /**
     * `service('superglobals')` — bukan `$_SERVER` langsung.
     *
     * IncomingRequest membaca REMOTE_ADDR lewat service itu
     * ({@see \CodeIgniter\HTTP\RequestTrait::populateGlobals()}), yang sudah
     * memotret superglobal saat boot. Menulis ke `$_SERVER` di dalam test tidak
     * pernah sampai ke request, dan getIPAddress() diam-diam mengembalikan
     * '0.0.0.0' — hijau atau merah karena alasan yang salah.
     */
    private function request(string $remoteAddr, ?string $header = null, string $nilai = ''): IncomingRequest
    {
        service('superglobals')->setServer('REMOTE_ADDR', $remoteAddr);

        $config  = new App();
        $request = new IncomingRequest($config, new SiteURI($config), null, new UserAgent());

        if ($header !== null) {
            $request->setHeader($header, $nilai);
        }

        return $request;
    }

    public function testDiBelakangTunnelMemakaiHeaderBukanLoopback(): void
    {
        $request = $this->request('::1', 'X-Forwarded-For', '103.10.20.30');

        $this->assertSame('103.10.20.30', $request->getIPAddress());
    }

    public function testRequestLangsungTidakBisaMemalsukanIpnya(): void
    {
        $request = $this->request('203.0.113.9', 'X-Forwarded-For', '8.8.8.8');

        $this->assertSame('203.0.113.9', $request->getIPAddress());
    }

    /**
     * `ip()` dipakai MlogModel untuk kolom `ip` di log aktivitas. Sebelum
     * perbaikan ini ia membaca HTTP_CLIENT_IP / HTTP_X_FORWARDED_FOR sendiri
     * tanpa memeriksa asal request sama sekali.
     */
    public function testHelperIpMengikutiAturanProxyYangSama(): void
    {
        helper('global_helper');

        Services::injectMock('request', $this->request('::1', 'X-Forwarded-For', '103.10.20.30'));

        $this->assertSame('103.10.20.30', ip());
    }

    public function testHelperIpMengabaikanHeaderDariBukanProxy(): void
    {
        helper('global_helper');

        Services::injectMock('request', $this->request('203.0.113.9', 'Client-IP', '8.8.8.8'));

        $this->assertSame('203.0.113.9', ip());
    }

    /**
     * Tanpa `app.proxyIPs` di .env, Config\App jatuh ke PROXY_BAWAAN. Kalau
     * daftar ini kosong, kedua test di atas tetap hijau untuk request langsung
     * tetapi seluruh pengunjung lewat tunnel kembali tercatat `::1`.
     */
    public function testProxyBawaanTetapMempercayaiLoopback(): void
    {
        $config = new App();

        $this->assertArrayHasKey('127.0.0.1', $config->proxyIPs);
        $this->assertArrayHasKey('::1', $config->proxyIPs);
    }
}
