<?php

namespace Tests\Unit;

use CodeIgniter\Config\Services;
use CodeIgniter\Router\Router;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Inti perbaikan kunci preferensi grid: berapa pun ejaan URL yang dipakai user,
 * scope yang dikirim ke browser harus sama. Routes.php mendaftarkan empat
 * varian per modul ('Piutangemkl', 'piutangemkl', 'Piutangemkl/index',
 * 'piutangemkl/index'), dan kunci lama -- yang diturunkan dari
 * window.location.pathname -- melahirkan empat preferensi terpisah untuk satu
 * grid yang sama.
 *
 * Perhitungan di sini menyalin partials/header.php.
 */
final class GridPrefScopeTest extends CIUnitTestCase
{
    private function scopeFor(string $uri): string
    {
        $routes = Services::routes(false);
        $routes->loadRoutes();

        $router = new Router($routes, Services::request(null, false));
        $router->handle($uri);

        $controller = $router->controllerName();
        if (!is_string($controller) || $controller === '') {
            return '';
        }

        $pos        = strrpos($controller, '\\');
        $controller = $pos === false ? $controller : substr($controller, $pos + 1);
        $method     = (string) $router->methodName();

        return strtolower($controller) . '.' . strtolower($method !== '' ? $method : 'index');
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function urlSpellings(): array
    {
        return [
            'huruf kecil'            => ['piutangemkl', 'piutangemkl.index'],
            'huruf besar'            => ['Piutangemkl', 'piutangemkl.index'],
            'kecil + /index'         => ['piutangemkl/index', 'piutangemkl.index'],
            'besar + /index'         => ['Piutangemkl/index', 'piutangemkl.index'],
            'modul lain'             => ['tracing', 'tracing.index'],
            'modul lain + /index'    => ['tracing/index', 'tracing.index'],
        ];
    }

    /**
     * @dataProvider urlSpellings
     */
    public function testAllUrlSpellingsCollapseToOneScope(string $uri, string $expected): void
    {
        $this->assertSame($expected, $this->scopeFor($uri));
    }

    /**
     * Halaman berbeda pada controller yang sama tetap harus terpisah, kalau
     * tidak dua grid akan berebut satu preferensi.
     */
    public function testDifferentMethodsStaySeparate(): void
    {
        $this->assertSame('cabang.index', $this->scopeFor('cabang'));
        $this->assertSame('cabang.form', $this->scopeFor('cabang/form/MDN/edit'));
    }

    /**
     * Segmen dinamis tidak boleh ikut masuk kunci. Kunci lama diturunkan dari
     * pathname, jadi '/cabang/form/MDN/edit' dan '/cabang/form/SBY/edit'
     * melahirkan dua preferensi terpisah -- satu per record.
     */
    public function testDynamicSegmentsDoNotLeakIntoTheScope(): void
    {
        $this->assertSame(
            $this->scopeFor('cabang/form/MDN/edit'),
            $this->scopeFor('cabang/form/SBY/edit')
        );
    }

    /**
     * partials/header.php memancarkan scope lewat esc($scope, 'js'). Escaper
     * mengubah karakter di luar daftar amannya menjadi \xHH, dan scope memakai
     * titik sebagai pemisah — kalau titik ikut di-escape, seluruh kunci
     * preferensi rusak.
     */
    public function testScopeSurvivesJsEscaping(): void
    {
        $this->assertSame('piutangemkl.index', esc('piutangemkl.index', 'js'));
        $this->assertSame('omsetrekapmarketingmdn.index', esc('omsetrekapmarketingmdn.index', 'js'));
        $this->assertSame('', esc('', 'js'));
    }
}
