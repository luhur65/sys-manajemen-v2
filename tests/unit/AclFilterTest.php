<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Smoke test: AclFilter benar-benar terpasang dan menghasilkan respons 403
 * yang utuh (bukan fatal error karena helper/view belum dimuat).
 *
 * Dijalankan tanpa sesi login supaya keputusan "deny" diambil sebelum
 * MyAuth menyentuh database (database tidak tersedia di lingkungan test).
 */
final class AclFilterTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        // Isolasi: hanya filter 'acl' yang diuji di sini.
        $filters                  = config('Filters');
        $filters->globals['before'] = ['acl'];
        $filters->globals['after']  = [];

        session()->destroy();
    }

    public function testDeniedHtmlRequestGets403Page(): void
    {
        $result = $this->withRoutes([
            ['get', 'omset', '\App\Controllers\Omset::index'],
        ])->get('omset');

        $result->assertStatus(403);
        $this->assertStringContainsString('403', $result->getBody());
        $this->assertStringContainsString('omset/index', $result->getBody());
        $this->assertStringContainsString('Kembali ke Dashboard', $result->getBody());
    }

    public function testDeniedAjaxRequestGetsJson(): void
    {
        $result = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->withRoutes([['get', 'omset/grid', '\App\Controllers\Omset::grid']])
            ->get('omset/grid');

        $result->assertStatus(403);
        $result->assertJSONFragment(['status' => 'error']);
    }
}
