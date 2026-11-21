<?php

namespace Tests\Unit;

use App\Controllers\GridPreference;
use CodeIgniter\Test\CIUnitTestCase;
use ReflectionClass;

/**
 * Payload preferensi datang dari klien dan ditulis mentah ke disk sebelum ini.
 * Tidak ada batas ukuran, tidak ada pemeriksaan bentuk, dan nama kolomnya
 * berakhir di dalam atribut HTML pada panel "Atur Kolom".
 *
 * Tes ini menjaga gerbangnya: apa yang tersimpan selalu berbentuk kanonik, dan
 * apa yang tidak masuk akal ditolak sebelum menyentuh berkas.
 */
final class GridPreferenceValidationTest extends CIUnitTestCase
{
    private GridPreference $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = (new ReflectionClass(GridPreference::class))->newInstanceWithoutConstructor();
    }

    private function invoke(string $name, ...$args)
    {
        $method = (new ReflectionClass(GridPreference::class))->getMethod($name);
        $method->setAccessible(true);

        return $method->invoke($this->controller, ...$args);
    }

    private function sanitize($raw): ?array
    {
        return $this->invoke('sanitizePreferences', $raw);
    }

    // ─── Nama kunci grid ─────────────────────────────────────────────────────

    /**
     * @return array<string, array{0: mixed, 1: bool}>
     */
    public static function gridNames(): array
    {
        return [
            'kunci baru'                => ['piutangemkl.index.jqGrid', true],
            'kunci lama ber-underscore' => ['_sys_modern_piutangemkl_index_jqGrid', true],
            'ada tanda hubung'          => ['modul-a.index.jqGrid', true],
            'path traversal'            => ['../../../writable/env', false],
            'garis miring'              => ['piutangemkl/index/jqGrid', false],
            'null byte'                 => ["piutangemkl.index\0.jqGrid", false],
            'spasi'                     => ['piutangemkl index jqGrid', false],
            'kosong'                    => ['', false],
            'terlalu panjang'           => [null, false],
            'bukan string'              => [['a'], false],
            'angka'                     => [123, false],
        ];
    }

    /**
     * @dataProvider gridNames
     */
    public function testGridNameWhitelist($gridName, bool $expected): void
    {
        if ($gridName === null) {
            $gridName = str_repeat('a', 121);
        }

        $this->assertSame($expected, $this->invoke('isValidGridName', $gridName));
    }

    // ─── Bentuk preferensi ───────────────────────────────────────────────────

    public function testValidEnvelopeIsNormalised(): void
    {
        $result = $this->sanitize([
            'v'         => 2,
            'updatedAt' => 1,
            'columns'   => [
                ['name' => 'FTgl', 'width' => 100, 'hidden' => false, 'frozen' => false, 'order' => 0],
                ['name' => 'FNTrans', 'width' => 250, 'hidden' => true, 'frozen' => true, 'order' => 1],
            ],
        ]);

        $this->assertNotNull($result);
        $this->assertSame(2, $result['v']);
        $this->assertSame(
            [
                ['name' => 'FTgl', 'width' => 100, 'hidden' => false, 'frozen' => false, 'order' => 0],
                ['name' => 'FNTrans', 'width' => 250, 'hidden' => true, 'frozen' => true, 'order' => 1],
            ],
            $result['columns']
        );
    }

    /** Data lama tersimpan sebagai array telanjang tanpa amplop. */
    public function testLegacyBareArrayIsAccepted(): void
    {
        $result = $this->sanitize([['name' => 'FTgl', 'width' => 100, 'order' => 0]]);

        $this->assertNotNull($result);
        $this->assertSame('FTgl', $result['columns'][0]['name']);
        $this->assertFalse($result['columns'][0]['frozen'], 'field yang hilang diisi default');
    }

    /**
     * `updatedAt` diambil dari jam server, bukan dari kiriman klien. Klien tidak
     * boleh mengklaim stempel waktu masa depan agar selalu menang saat
     * konvergensi lintas perangkat dipasang nanti.
     */
    public function testUpdatedAtComesFromTheServer(): void
    {
        $result = $this->sanitize(['v' => 2, 'updatedAt' => 99999999999999, 'columns' => [['name' => 'FTgl']]]);

        $this->assertNotSame(99999999999999, $result['updatedAt']);
        $this->assertGreaterThan(1_700_000_000_000, $result['updatedAt']);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function rejectedPayloads(): array
    {
        return [
            'nama kolom mengandung markup' => [[['name' => '<img src=x onerror=alert(1)>']]],
            'nama kolom ada spasi'         => [[['name' => 'Nama Kolom']]],
            'nama kolom ada kutip'         => [[['name' => "FTgl'"]]],
            'nama kolom kosong'            => [[['name' => '']]],
            'nama kolom bukan string'      => [[['name' => 123]]],
            'entri tanpa nama'             => [[['width' => 100]]],
            'entri bukan array'            => [['FTgl']],
            'kolom kosong'                 => [[]],
            'amplop kolom kosong'          => [['v' => 2, 'columns' => []]],
            'bukan array'                  => ['FTgl'],
            'null'                         => [null],
        ];
    }

    /**
     * @dataProvider rejectedPayloads
     */
    public function testMalformedPayloadsAreRejectedEntirely($raw): void
    {
        $this->assertNull($this->sanitize($raw));
    }

    public function testTooManyColumnsIsRejected(): void
    {
        $columns = [];
        for ($i = 0; $i < 201; $i++) {
            $columns[] = ['name' => 'FKolom' . $i, 'order' => $i];
        }

        $this->assertNull($this->sanitize($columns));
    }

    /**
     * Satu nilai lebar yang aneh tidak boleh membatalkan tata letak yang sudah
     * benar — lebarnya saja yang dibuang, kolomnya tetap tersimpan.
     */
    public function testOutOfRangeWidthIsDroppedWithoutRejectingThePayload(): void
    {
        $result = $this->sanitize([
            ['name' => 'FTgl',      'width' => 999999, 'order' => 0],
            ['name' => 'FNTrans',   'width' => -5,     'order' => 1],
            ['name' => 'FNShipper', 'width' => 'lebar', 'order' => 2],
            ['name' => 'FNominal',  'width' => '250',  'order' => 3],
        ]);

        $this->assertNotNull($result);
        $this->assertCount(4, $result['columns']);
        $this->assertArrayNotHasKey('width', $result['columns'][0]);
        $this->assertArrayNotHasKey('width', $result['columns'][1]);
        $this->assertArrayNotHasKey('width', $result['columns'][2]);
        $this->assertSame(250, $result['columns'][3]['width'], 'angka berbentuk string tetap diterima');
    }

    public function testFlagsAreCoercedToBooleans(): void
    {
        $result = $this->sanitize([['name' => 'FTgl', 'hidden' => 'ya', 'frozen' => 0, 'order' => 0]]);

        $this->assertTrue($result['columns'][0]['hidden']);
        $this->assertFalse($result['columns'][0]['frozen']);
    }

    /** `order` selalu dihitung ulang dari posisi, tidak pernah dari kiriman klien. */
    public function testOrderIsRederivedFromPosition(): void
    {
        $result = $this->sanitize([
            ['name' => 'FNominal', 'order' => 70],
            ['name' => 'FTgl',     'order' => 3],
            ['name' => 'FNTrans',  'order' => 11],
        ]);

        $this->assertSame(['FTgl', 'FNTrans', 'FNominal'], array_column($result['columns'], 'name'));
        $this->assertSame([0, 1, 2], array_column($result['columns'], 'order'));
    }

    public function testDuplicateNamesKeepTheFirstOccurrence(): void
    {
        $result = $this->sanitize([
            ['name' => 'FTgl', 'width' => 100, 'order' => 0],
            ['name' => 'FTgl', 'width' => 999, 'order' => 1],
        ]);

        $this->assertCount(1, $result['columns']);
        $this->assertSame(100, $result['columns'][0]['width']);
    }

    // ─── Batas jumlah grid ───────────────────────────────────────────────────

    public function testGridsUnderTheCapAreLeftAlone(): void
    {
        $data = [];
        for ($i = 0; $i < 33; $i++) {
            $data['modul' . $i . '.index.jqGrid'] = ['v' => 2, 'updatedAt' => $i, 'columns' => []];
        }

        $this->assertSame($data, $this->invoke('pruneExcessGrids', $data, 'modul0.index.jqGrid'));
    }

    public function testOverflowDropsTheStalestGridsButNeverTheOneBeingSaved(): void
    {
        $data = [];
        for ($i = 0; $i < 205; $i++) {
            // updatedAt terkecil = paling lama tidak disentuh.
            $data['modul' . $i . '.index.jqGrid'] = ['v' => 2, 'updatedAt' => $i, 'columns' => []];
        }

        $result = $this->invoke('pruneExcessGrids', $data, 'modul0.index.jqGrid');

        $this->assertCount(200, $result);
        $this->assertArrayHasKey('modul0.index.jqGrid', $result, 'grid yang sedang disimpan harus selamat');
        $this->assertArrayHasKey('modul204.index.jqGrid', $result, 'yang terbaru harus selamat');
        $this->assertArrayNotHasKey('modul1.index.jqGrid', $result, 'yang terlama dibuang');
    }
}
