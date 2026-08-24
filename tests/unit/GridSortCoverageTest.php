<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Regresi cakupan untuk H-03.
 *
 * GridSortTest menguji librarynya; test ini memastikan library itu benar-benar
 * DIPAKAI di setiap tempat yang menerima kolom pengurutan dari client. Berkas
 * model dipindai sendiri oleh test, jadi model atau method baru yang menerima
 * parameter pengurutan otomatis ikut terperiksa.
 */
final class GridSortCoverageTest extends CIUnitTestCase
{
    /** Nama parameter yang dipakai aplikasi untuk kolom/arah pengurutan. */
    private const SORT_PARAM = '/\$(sidx|sord|sort_?name|sort_?order|sortBy|orderBy)\b/i';

    public function testEveryMethodTakingASortParameterSanitisesIt(): void
    {
        $unguarded = [];
        $checked   = 0;

        foreach ($this->methodsTakingSortParameters() as $method) {
            $checked++;

            $hasColumn    = str_contains($method['body'], 'GridSort::column(');
            $hasDirection = str_contains($method['body'], 'GridSort::direction(');

            if (! $hasColumn || ! $hasDirection) {
                $unguarded[] = $method['name'];
            }
        }

        $this->assertGreaterThan(20, $checked, 'Pemindai tidak menemukan method pengurutan — polanya kemungkinan sudah usang.');
        $this->assertSame([], $unguarded, 'Method berikut menerima kolom pengurutan dari client tanpa GridSort: ' . implode(', ', $unguarded));
    }

    public function testNoModelStillConcatenatesRawSortInputIntoSql(): void
    {
        $offenders = [];

        foreach (glob(APPPATH . 'Models/*.php') as $file) {
            $source = file_get_contents($file);

            // ORDER BY yang diikuti langsung oleh $sidx/$sord mentah adalah bentuk
            // persis yang dilaporkan di H-03.
            if (preg_match('/ORDER BY[^"\']{0,40}\$(sidx|sord)\b/i', $source) === 1
                && ! str_contains($source, 'GridSort::column(')) {
                $offenders[] = basename($file);
            }
        }

        $this->assertSame([], $offenders, 'Model masih mengkonkatenasi sidx/sord mentah: ' . implode(', ', $offenders));
    }

    /**
     * Query Builder CI4 BUKAN pengaman ORDER BY.
     *
     * Test ini mendokumentasikan alasan `MshippernewModel` tetap harus memakai
     * GridSort meski memakai `$builder->orderBy()`. Kalau suatu saat CI4 berubah
     * dan test ini gagal, itu kabar baik — tapi jangan lantas melepas GridSort.
     */
    public function testCodeIgniterQueryBuilderDoesNotEscapeOrderByExpressions(): void
    {
        $db = db_connect('dbtruck', false);

        $sql = $db->table('x')->select('a')
            ->orderBy('(SELECT TOP 1 password FROM tbluser)', 'desc')
            ->getCompiledSelect(true);

        $this->assertStringContainsString(
            'SELECT TOP 1 password FROM tbluser',
            $sql,
            'Query Builder ternyata sudah mengamankan ORDER BY — perbarui catatan di MshippernewModel.'
        );
    }

    public function testQueryBuilderPathIsGuardedByGridSort(): void
    {
        $source = file_get_contents(APPPATH . 'Models/MshippernewModel.php');

        $this->assertStringContainsString('GridSort::column(', $source);
        $this->assertStringContainsString('GridSort::direction(', $source);
    }

    /**
     * @return list<array{name: string, body: string}>
     */
    private function methodsTakingSortParameters(): array
    {
        $methods = [];

        foreach (glob(APPPATH . 'Models/*.php') as $file) {
            $source = file_get_contents($file);

            preg_match_all('/function\s+(\w+)\s*\(([^)]*)\)/', $source, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

            foreach ($matches as $match) {
                if (preg_match(self::SORT_PARAM, $match[2][0]) !== 1) {
                    continue;
                }

                $start = $match[0][1] + strlen($match[0][0]);
                $rest  = substr($source, $start);
                $end   = preg_match('/\n    (?:public|protected|private)\s+function\s/', $rest, $m, PREG_OFFSET_CAPTURE) === 1
                    ? $m[0][1]
                    : strlen($rest);

                $methods[] = [
                    'name' => basename($file) . '::' . $match[1][0],
                    'body' => substr($rest, 0, $end),
                ];
            }
        }

        return $methods;
    }
}
