<?php

namespace Tests\Unit;

use App\Controllers\GridPreference;
use CodeIgniter\Test\CIUnitTestCase;
use ReflectionClass;

/**
 * Satu berkas menampung SELURUH grid milik satu user, dan setiap penyimpanan
 * menulis ulang keseluruhannya. Dengan 33 grid aktif berkas itu ~29 KB, dan
 * dua penyimpanan berbarengan — dua tab, atau dua grid pada satu halaman —
 * dulu bisa saling menimpa. Lebih buruk: pembacaan yang menangkap berkas dalam
 * keadaan terpotong membuat `json_decode` gagal, dan `?: []` di kode lama
 * mengubah kegagalan itu menjadi "user tidak punya preferensi" — seluruh grid
 * terhapus pada penyimpanan berikutnya.
 *
 * Tes ini menjaga dua jaminan: berkas rusak dikarantina (tidak pernah dianggap
 * kosong begitu saja), dan mengubah satu grid tidak menyentuh grid lain.
 */
final class GridPreferenceStorageTest extends CIUnitTestCase
{
    private const USER = '__test_gridpref_user__';

    private GridPreference $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = (new ReflectionClass(GridPreference::class))->newInstanceWithoutConstructor();
        $this->cleanUp();
    }

    protected function tearDown(): void
    {
        $this->cleanUp();
        parent::tearDown();
    }

    private function invoke(string $name, ...$args)
    {
        $method = (new ReflectionClass(GridPreference::class))->getMethod($name);
        $method->setAccessible(true);

        return $method->invoke($this->controller, ...$args);
    }

    private function filePath(): string
    {
        return $this->invoke('getFilePath', self::USER);
    }

    private function cleanUp(): void
    {
        foreach (glob($this->filePath() . '*') as $leftover) {
            @unlink($leftover);
        }
    }

    /** @param array<string, mixed> $data */
    private function seed(array $data): void
    {
        file_put_contents($this->filePath(), json_encode($data));
    }

    private function entry(string $name): array
    {
        return ['v' => 2, 'updatedAt' => 1, 'columns' => [['name' => $name, 'width' => 100, 'order' => 0]]];
    }

    public function testMutatingOneGridLeavesTheOthersUntouched(): void
    {
        $this->seed([
            'piutangemkl.index.jqGrid' => $this->entry('FTgl'),
            'tracing.index.jqGrid'     => $this->entry('FKode'),
            'menu.index.jqGrid'        => $this->entry('menuid'),
        ]);

        $this->invoke('mutateData', self::USER, static function (array $data): array {
            $data['tracing.index.jqGrid'] = ['v' => 2, 'updatedAt' => 2, 'columns' => []];

            return $data;
        });

        $after = $this->invoke('loadData', self::USER);

        $this->assertCount(3, $after);
        $this->assertSame($this->entry('FTgl'), $after['piutangemkl.index.jqGrid']);
        $this->assertSame($this->entry('menuid'), $after['menu.index.jqGrid']);
        $this->assertSame(2, $after['tracing.index.jqGrid']['updatedAt']);
    }

    /**
     * Mutator boleh mengembalikan null untuk "tidak ada yang perlu ditulis".
     * Berkasnya harus benar-benar tidak tersentuh.
     */
    public function testReturningNullSkipsTheWrite(): void
    {
        $this->seed(['piutangemkl.index.jqGrid' => $this->entry('FTgl')]);
        $before = file_get_contents($this->filePath());

        $this->invoke('mutateData', self::USER, static fn (array $data) => null);

        $this->assertSame($before, file_get_contents($this->filePath()));
    }

    public function testCorruptFileIsQuarantinedRatherThanSilentlyDiscarded(): void
    {
        // Persis bentuk yang dihasilkan tulisan terpotong: JSON separuh jalan.
        file_put_contents($this->filePath(), '{"piutangemkl.index.jqGrid":{"v":2,"colu');

        $this->assertSame([], $this->invoke('loadData', self::USER), 'pembacaan tidak boleh melempar');

        $quarantined = glob($this->filePath() . '.corrupt-*');
        $this->assertNotEmpty($quarantined, 'berkas rusak harus disalin, bukan dibuang diam-diam');
        $this->assertSame(
            '{"piutangemkl.index.jqGrid":{"v":2,"colu',
            file_get_contents($quarantined[0]),
            'salinan karantina harus berisi payload aslinya utuh'
        );
    }

    public function testWriterAlsoQuarantinesBeforeStartingOver(): void
    {
        file_put_contents($this->filePath(), 'bukan json sama sekali');

        $this->invoke('mutateData', self::USER, function (array $data): array {
            $this->assertSame([], $data, 'isi rusak tidak boleh diteruskan ke mutator');
            $data['piutangemkl.index.jqGrid'] = $this->entry('FTgl');

            return $data;
        });

        $this->assertNotEmpty(glob($this->filePath() . '.corrupt-*'));
        $this->assertSame(
            ['piutangemkl.index.jqGrid' => $this->entry('FTgl')],
            $this->invoke('loadData', self::USER)
        );
    }

    /**
     * Berkas ini dibaca mesin, bukan manusia. JSON_PRETTY_PRINT membuatnya 2,3x
     * lebih besar tanpa manfaat, dan setiap penyimpanan menulis ulang semuanya.
     */
    public function testStoredJsonIsCompact(): void
    {
        $this->invoke('mutateData', self::USER, fn (array $data) => ['a.index.jqGrid' => $this->entry('FTgl')]);

        $this->assertStringNotContainsString("\n", file_get_contents($this->filePath()));
    }
}
