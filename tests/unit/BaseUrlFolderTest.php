<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Config\App;

/**
 * Bentuk baseURL untuk tiap environment.
 *
 * `app.baseURL` di .env TIDAK dibaca aplikasi ini — Config\App::__construct()
 * menyusun sendiri baseURL dari HTTP_HOST + app.folder. Artinya satu baris
 * app.folder menentukan setiap url yang keluar dari base_url(): url callback
 * SSO, redirect ke /home setelah login, sampai redirect kegagalan ke /login.
 *
 * Yang dijaga di sini adalah pembedaan antara app.folder yang **kosong**
 * (aplikasi di root domain — production) dan app.folder yang **tidak diset**
 * (jatuh ke default lama). Keduanya sama-sama falsy, jadi sangat mudah
 * disamakan lagi tanpa sengaja — dan begitu itu terjadi, seluruh url production
 * kembali menyelipkan segmen 'sys-modern' dan alur SSO putus setelah callback.
 */
final class BaseUrlFolderTest extends CIUnitTestCase
{
    /** @var array<string, mixed> */
    private array $savedServer = [];

    private string|false $savedFolder = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->savedServer = $_SERVER;
        $this->savedFolder = getenv('app.folder');
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->savedServer;

        if ($this->savedFolder === false) {
            putenv('app.folder');
        } else {
            putenv('app.folder=' . $this->savedFolder);
        }

        parent::tearDown();
    }

    public function testStagingDiSubfolderSys(): void
    {
        $this->assertSame(
            'https://staging.transporindo.com/sys/',
            $this->baseUrlFor('staging.transporindo.com', 'sys')
        );
    }

    public function testProductionDiRootDomain(): void
    {
        // app.folder sengaja dikosongkan -> tidak boleh ada segmen tambahan.
        $this->assertSame(
            'https://sys.transporindo.com/',
            $this->baseUrlFor('sys.transporindo.com', '')
        );
    }

    public function testAppFolderYangTidakDisetJatuhKeDefaultLama(): void
    {
        // Kompatibilitas ke belakang: instalasi yang tidak punya baris app.folder
        // sama sekali harus berperilaku persis seperti sebelumnya.
        $this->assertSame(
            'https://sys.transporindo.com/sys-modern/',
            $this->baseUrlFor('sys.transporindo.com', null)
        );
    }

    public function testPengembanganLokal(): void
    {
        $this->assertSame(
            'http://localhost/sys-modern/',
            $this->baseUrlFor('localhost', '/sys-modern/', false)
        );
    }

    public function testSchemeMengikutiHeaderProxy(): void
    {
        // Di belakang Cloudflare Tunnel, $_SERVER['HTTPS'] kosong sementara
        // permintaan aslinya https. Tanpa membaca X-Forwarded-Proto, setiap url
        // yang dihasilkan jadi http:// dan browser memblokir mixed content.
        $_SERVER = ['HTTP_HOST' => 'sys.transporindo.com', 'HTTP_X_FORWARDED_PROTO' => 'https'];
        putenv('app.folder=');

        $this->assertSame('https://sys.transporindo.com/', (new App())->baseURL);
    }

    private function baseUrlFor(string $host, ?string $folder, bool $https = true): string
    {
        $_SERVER = ['HTTP_HOST' => $host];

        if ($https) {
            $_SERVER['HTTPS'] = 'on';
        }

        if ($folder === null) {
            putenv('app.folder');
        } else {
            putenv('app.folder=' . $folder);
        }

        return (new App())->baseURL;
    }
}
