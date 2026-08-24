<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Security as SecurityConfig;

/**
 * Regresi untuk C-03 (CSRF Protection Dinonaktifkan Secara Global).
 *
 * Fokus utamanya bukan sekadar "filter aktif", tapi memastikan seluruh POLA
 * PENGIRIMAN yang dipakai aplikasi ini tetap jalan setelah filter dinyalakan:
 * header X-CSRF-TOKEN (semua AJAX jQuery), field form (csrf_field()), dan body
 * JSON. Termasuk memastikan token TIDAK berputar per-request, karena rotasi
 * adalah penyebab klasik seluruh grid/crud mendadak kena 403.
 */
final class CsrfTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    /**
     * Route uji yang tidak menyentuh database — filter csrf berjalan sebelum
     * controller, jadi ini cukup untuk menguji filternya secara utuh.
     *
     * @return list<array{0: string, 1: string, 2: callable}>
     */
    private function routes(): array
    {
        return [
            ['post', 'uji-csrf', static fn (): string => 'OK'],
            ['get', 'uji-csrf', static fn (): string => 'OK'],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Isolasi: hanya filter 'csrf' yang diuji di sini, bukan auth/acl.
        $filters                    = config('Filters');
        $filters->globals['before'] = ['csrf'];
        $filters->globals['after']  = [];
    }

    private function token(): string
    {
        return csrf_hash();
    }

    private function tokenName(): string
    {
        return csrf_token();
    }

    // ---------------------------------------------------------------- config

    public function testFilterIsRegisteredGlobally(): void
    {
        // config('Filters') sudah dimodifikasi setUp(), jadi baca berkas aslinya.
        $globals = (new \Config\Filters())->globals['before'];

        $names = array_map(
            static fn ($k, $v): string => is_int($k) ? $v : $k,
            array_keys($globals),
            $globals
        );

        $this->assertContains('csrf', $names, 'Filter csrf tidak terpasang di $globals[before].');
        $this->assertLessThan(
            array_search('auth', $names, true),
            array_search('csrf', $names, true),
            'csrf harus diverifikasi sebelum auth.'
        );
    }

    public function testTokenDoesNotRegeneratePerRequest(): void
    {
        // Inti kestabilan AJAX: cookie CSRF httpOnly, token cuma dikirim sekali
        // lewat <meta>. Kalau regenerate=true, POST kedua dst. pasti 403.
        $this->assertFalse(
            (new SecurityConfig())->regenerate,
            'Config\Security::$regenerate harus false; lihat catatan di berkas config.'
        );
    }

    public function testTokenCookieLivesAsLongAsTheBrowserSession(): void
    {
        // expires=7200 membuat token basi di tengah pemakaian aktif (cookie CSRF
        // tidak diperpanjang per request, sedangkan sesi aplikasi diperpanjang).
        $this->assertSame(
            0,
            (new SecurityConfig())->expires,
            'Config\Security::$expires harus 0; lihat catatan di berkas config.'
        );
    }

    // -------------------------------------------------------------- penolakan

    public function testPostWithoutTokenIsRejected(): void
    {
        $this->expectException(\CodeIgniter\Security\Exceptions\SecurityException::class);

        $this->withRoutes($this->routes())->post('uji-csrf');
    }

    public function testPostWithWrongTokenIsRejected(): void
    {
        $this->expectException(\CodeIgniter\Security\Exceptions\SecurityException::class);

        $this->withRoutes($this->routes())
            ->withHeaders(['X-CSRF-TOKEN' => str_repeat('a', 64)])
            ->post('uji-csrf');
    }

    // ------------------------------------------------- pola kiriman aplikasi

    public function testPostWithHeaderTokenPasses(): void
    {
        // Pola SELURUH AJAX jQuery: $.ajaxSetup({headers:{'X-CSRF-TOKEN': ...}}).
        $result = $this->withRoutes($this->routes())
            ->withHeaders(['X-CSRF-TOKEN' => $this->token()])
            ->post('uji-csrf');

        $result->assertOK();
        $result->assertSee('OK');
    }

    public function testPostWithFormFieldTokenPasses(): void
    {
        // Pola form biasa: csrf_field() (login, reset password).
        $result = $this->withRoutes($this->routes())
            ->withBodyFormat('json')
            ->post('uji-csrf', [$this->tokenName() => $this->token()]);

        $result->assertOK();
    }

    public function testGetIsNeverBlocked(): void
    {
        // Seluruh grid jqGrid memakai GET; CI4 hanya memeriksa POST/PUT/DELETE/PATCH.
        $result = $this->withRoutes($this->routes())->get('uji-csrf');

        $result->assertOK();
        $result->assertSee('OK');
    }

    // ------------------------------------------------------- stabilitas AJAX

    public function testSameTokenWorksForManySequentialPosts(): void
    {
        // Satu halaman -> puluhan AJAX POST memakai token dari <meta> yang sama.
        // Kalau ini gagal di POST ke-2, seluruh aplikasi rusak setelah aksi pertama.
        $token = $this->token();

        for ($i = 1; $i <= 5; $i++) {
            $result = $this->withRoutes($this->routes())
                ->withHeaders(['X-CSRF-TOKEN' => $token])
                ->post('uji-csrf');

            $result->assertOK();
            $this->assertStringContainsString('OK', $result->getBody(), "POST ke-$i ditolak dengan token yang sama.");
        }
    }

    public function testRandomizedTokensDifferPerRenderButAllRemainValid(): void
    {
        // tokenRandomize=true: tiap csrf_hash() menghasilkan string berbeda
        // (mitigasi BREACH) namun semuanya derandomize ke hash yang sama.
        $this->assertTrue((new SecurityConfig())->tokenRandomize);

        $a = $this->token();
        $b = $this->token();

        $this->assertNotSame($a, $b, 'tokenRandomize aktif tapi nilainya tidak diacak.');

        foreach ([$a, $b] as $i => $token) {
            $result = $this->withRoutes($this->routes())
                ->withHeaders(['X-CSRF-TOKEN' => $token])
                ->post('uji-csrf');

            $result->assertOK();
            $this->assertStringContainsString('OK', $result->getBody(), 'Token teracak #' . $i . ' ditolak.');
        }
    }
}
