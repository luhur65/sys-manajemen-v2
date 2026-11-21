<?php

namespace Tests\Unit;

use App\Controllers\GridPreference;
use CodeIgniter\Test\CIUnitTestCase;
use ReflectionClass;

/**
 * Kunci preferensi grid berpindah dari turunan window.location.pathname
 * ("_sys_modern_piutangemkl_index_jqGrid") ke scope dari router
 * ("piutangemkl.index.jqGrid"). Tes ini menjaga pemetaan kunci lama -> baru:
 * salah petakan berarti preferensi user hilang, atau lebih buruk, preferensi
 * modul lain tertarik ke grid yang salah.
 *
 * Aturan yang sama dipakai klien, lihat legacyTailMatches() di
 * public/libraries/tas-lib/js/GridPreferenceManager.js.
 */
final class GridPreferenceKeyTest extends CIUnitTestCase
{
    /**
     * legacyKeysFor() tidak menyentuh request, session, maupun berkas, jadi
     * instance tanpa konstruktor sudah cukup.
     *
     * @param array<string, mixed> $data
     * @return list<string>
     */
    private function legacyKeysFor(string $gridName, array $data): array
    {
        $controller = (new ReflectionClass(GridPreference::class))->newInstanceWithoutConstructor();
        $method     = (new ReflectionClass(GridPreference::class))->getMethod('legacyKeysFor');
        $method->setAccessible(true);

        return $method->invoke($controller, $gridName, $data);
    }

    /**
     * @return array<string, array{0: string, 1: list<string>, 2: list<string>}>
     */
    public static function keyMappings(): array
    {
        return [
            // Bentuk yang benar-benar ada di writable/grid_preferences.
            'pathname lengkap' => [
                'piutangemkl.index.jqGrid',
                ['_sys_modern_piutangemkl_index_jqGrid'],
                ['_sys_modern_piutangemkl_index_jqGrid'],
            ],
            // Routes.php mendaftarkan 'piutangemkl' dan 'piutangemkl/index'
            // untuk controller yang sama, jadi URL tanpa /index ikut dipetakan.
            'pathname tanpa /index' => [
                'tracing.index.jqGrid',
                ['_sys_modern_tracing_jqGrid'],
                ['_sys_modern_tracing_jqGrid'],
            ],
            // Routes.php juga mendaftarkan ejaan berhuruf besar.
            'pathname berhuruf besar' => [
                'menu.index.jqGrid',
                ['_sys_modern_Menu_index_jqGrid'],
                ['_sys_modern_Menu_index_jqGrid'],
            ],
            // Base path berbeda (docroot pindah) tetap harus ketemu.
            'tanpa base path' => [
                'piutangemkl.index.jqGrid',
                ['_piutangemkl_index_jqGrid'],
                ['_piutangemkl_index_jqGrid'],
            ],
            'semua ejaan URL untuk satu grid' => [
                'piutangemkl.index.jqGrid',
                ['_sys_modern_piutangemkl_jqGrid', '_sys_modern_Piutangemkl_index_jqGrid'],
                ['_sys_modern_piutangemkl_jqGrid', '_sys_modern_Piutangemkl_index_jqGrid'],
            ],
            // Grid modul lain tidak boleh ikut tersedot.
            'modul lain diabaikan' => [
                'roles.index.jqGrid',
                ['_sys_modern_menu_index_jqGrid', '_sys_modern_piutangemkl_index_jqGrid'],
                [],
            ],
            // Dua grid di halaman yang sama dibedakan oleh gridId.
            'gridId berbeda tidak tertukar' => [
                'useracl.index.jqGridAcl',
                ['_sys_modern_useracl_index_jqGrid', '_sys_modern_useracl_index_jqGridAcl'],
                ['_sys_modern_useracl_index_jqGridAcl'],
            ],
            // method non-index harus cocok persis, tidak boleh jatuh ke controller saja.
            'method non-index' => [
                'trucking.omset.jqGrid',
                ['_sys_modern_trucking_omset_jqGrid', '_sys_modern_trucking_jqGrid'],
                ['_sys_modern_trucking_omset_jqGrid'],
            ],
            'kunci yang sudah baru tidak dianggap lama' => [
                'piutangemkl.index.jqGrid',
                ['piutangemkl.index.jqGrid'],
                [],
            ],
        ];
    }

    /**
     * @param list<string> $existingKeys
     * @param list<string> $expected
     *
     * @dataProvider keyMappings
     */
    public function testLegacyKeysAreMappedToTheRouterScope(string $gridName, array $existingKeys, array $expected): void
    {
        $data = [];
        foreach ($existingKeys as $key) {
            $data[$key] = [['name' => 'FTgl', 'width' => 100, 'order' => 0]];
        }

        $found = $this->legacyKeysFor($gridName, $data);

        sort($found);
        sort($expected);
        $this->assertSame($expected, $found);
    }

    public function testMalformedGridNameMatchesNothing(): void
    {
        $data = ['_sys_modern_piutangemkl_index_jqGrid' => []];

        $this->assertSame([], $this->legacyKeysFor('jqGrid', $data));
        $this->assertSame([], $this->legacyKeysFor('piutangemkl.jqGrid', $data));
        $this->assertSame([], $this->legacyKeysFor('', $data));
    }

    /**
     * Amplop v2 dan array telanjang v1 sama-sama harus terbaca; bentuk lain
     * (mis. map, atau kosong) dianggap tidak ada isinya.
     */
    public function testColumnsOfAcceptsBothStorageVersions(): void
    {
        $controller = (new ReflectionClass(GridPreference::class))->newInstanceWithoutConstructor();
        $method     = (new ReflectionClass(GridPreference::class))->getMethod('columnsOf');
        $method->setAccessible(true);

        $columns = [['name' => 'FTgl', 'width' => 100, 'order' => 0]];

        $this->assertSame($columns, $method->invoke($controller, $columns), 'v1: array telanjang');
        $this->assertSame(
            $columns,
            $method->invoke($controller, ['v' => 2, 'updatedAt' => 1, 'columns' => $columns]),
            'v2: amplop'
        );
        $this->assertNull($method->invoke($controller, []), 'kosong');
        $this->assertNull($method->invoke($controller, ['v' => 2, 'columns' => []]), 'amplop kosong');
        $this->assertNull($method->invoke($controller, 'bukan array'), 'bukan array');
    }
}
