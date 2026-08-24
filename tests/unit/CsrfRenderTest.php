<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Regresi C-03 tingkat render.
 *
 * CsrfClientCoverageTest memeriksa sumber template; test ini merender template
 * itu sungguhan lalu membuktikan token yang keluar benar-benar token yang sah —
 * menutup celah "meta tag ada tapi isinya kosong / salah helper".
 *
 * Controller-nya sendiri tidak dipanggil karena menyentuh database yang tidak
 * tersedia di lingkungan test; yang diuji di sini murni lapisan view.
 */
final class CsrfRenderTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper(['url', 'asset_helper', 'form']);
    }

    private function renderLogin(): string
    {
        $level = ob_get_level();
        $html  = view('login', ['error' => null]);

        while (ob_get_level() > $level) {
            ob_end_clean();
        }

        return $html;
    }

    public function testLoginViewEmitsAUsableToken(): void
    {
        $html = $this->renderLogin();

        preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $meta);
        $this->assertNotEmpty($meta, 'Meta csrf-token tidak dirender di halaman login.');

        preg_match('/<meta name="csrf-token-name" content="([^"]+)"/', $html, $metaName);
        $this->assertNotEmpty($metaName, 'Meta csrf-token-name tidak dirender di halaman login.');

        $this->assertSame(csrf_token(), $metaName[1], 'Nama token di meta tidak cocok dengan konfigurasi.');

        // tokenRandomize aktif -> nilainya hex dua kali panjang hash.
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64,}$/', $meta[1], 'Nilai token tidak berbentuk hash yang wajar.');

        // Token dari meta harus derandomize ke hash yang sama dengan token form.
        $security = service('security');
        $method   = new \ReflectionMethod($security, 'derandomize');
        $method->setAccessible(true);

        $this->assertSame(
            $security->getHash() !== null ? $method->invoke($security, csrf_hash()) : null,
            $method->invoke($security, $meta[1]),
            'Token di meta tidak merujuk hash CSRF yang sama dengan csrf_hash().'
        );
    }

    public function testLoginViewStillShipsTheFormFieldForNonAjaxSubmit(): void
    {
        // Form login dikirim biasa (bukan AJAX), jadi csrf_field() tetap wajib ada.
        $html = $this->renderLogin();

        $this->assertMatchesRegularExpression(
            '/<input type="hidden" name="' . preg_quote(csrf_token(), '/') . '" value="[a-f0-9]+"/',
            $html,
            'csrf_field() hilang dari form login.'
        );
    }
}
