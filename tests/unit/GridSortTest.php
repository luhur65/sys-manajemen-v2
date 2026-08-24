<?php

namespace Tests\Unit;

use App\Libraries\GridSort;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Regresi untuk H-03 (SQL Injection pada klausa ORDER BY).
 */
final class GridSortTest extends CIUnitTestCase
{
    /**
     * Payload nyata untuk ORDER BY di SQL Server. Tidak satu pun boleh lolos.
     *
     * @return array<string, array{0: string}>
     */
    public static function injectionPayloads(): array
    {
        return [
            'stacked query'      => ["FTgl; DROP TABLE tbluser--"],
            'komentar'           => ["FTgl--"],
            'blok komentar'      => ["FTgl/*x*/"],
            'subquery'           => ["(SELECT TOP 1 password FROM tbluser)"],
            'CASE error-based'   => ["CASE WHEN (1=1) THEN 1/0 ELSE 1 END"],
            'kolom kedua'        => ["FTgl, (SELECT TOP 1 password FROM tbluser)"],
            'kutip tunggal'      => ["FTgl'"],
            'WAITFOR delay'      => ["1; WAITFOR DELAY '0:0:5'"],
            'spasi + arah palsu' => ["FTgl DESC, password ASC"],
            'kurung'             => ["IIF(1=1,1,2)"],
            'union'              => ["1 UNION SELECT NULL"],
            'nested select'      => ["(SELECT 1)"],
        ];
    }

    /**
     * @dataProvider injectionPayloads
     */
    public function testInjectionPayloadsFallBackToTheDeveloperDefault(string $payload): void
    {
        $this->assertSame('FTgl', GridSort::column($payload, 'FTgl'));
    }

    /**
     * @dataProvider injectionPayloads
     */
    public function testInjectionPayloadsNeverSurviveIntoTheOutput(string $payload): void
    {
        $result = GridSort::column($payload, 'FTgl');

        // Apa pun yang keluar harus identifier polos, tanpa sintaks SQL sama sekali.
        $this->assertMatchesRegularExpression('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?$/', $result);
    }

    public function testLegitimateColumnNamesPassThrough(): void
    {
        $this->assertSame('FTgl', GridSort::column('FTgl', 'FBulan'));
        $this->assertSame('FJumlahMuatan', GridSort::column('FJumlahMuatan', 'FBulan'));
        $this->assertSame('tbluser.userid', GridSort::column('tbluser.userid', 'userid'));
        $this->assertSame('waktulogin', GridSort::column('  waktulogin  ', 'FTgl'), 'Spasi di tepi harus dipangkas, bukan menggugurkan kolom.');
    }

    public function testEmptyOrMissingSidxUsesTheDefault(): void
    {
        $this->assertSame('FTgl', GridSort::column('', 'FTgl'));
        $this->assertSame('FTgl', GridSort::column(null, 'FTgl'));
        $this->assertSame('FTgl', GridSort::column([], 'FTgl'));
    }

    public function testDeveloperDefaultMayBeAMultiColumnExpression(): void
    {
        // Beberapa model memakai default majemuk; itu tulisan developer, bukan input.
        $default = 'substring(FBulan,4,4) DESC, FBulan DESC, FNMarketing';

        $this->assertSame($default, GridSort::column('bukan valid!', $default));
        $this->assertSame($default, GridSort::column('', $default));
    }

    // ------------------------------------------------------------- whitelist

    public function testWhitelistRejectsColumnsOutsideTheGrid(): void
    {
        $sortable = ['userid', 'username', 'modifiedon'];

        // Kolom nyata tapi sensitif dan tidak tampil di grid -> harus ditolak,
        // supaya urutan hasil tidak bisa dipakai menebak isinya.
        $this->assertSame('userid', GridSort::column('password', 'userid', $sortable));
        $this->assertSame('username', GridSort::column('username', 'userid', $sortable));
    }

    public function testWhitelistMatchIsCaseInsensitiveButOutputUsesTheWhitelistSpelling(): void
    {
        $this->assertSame('FTgl', GridSort::column('ftgl', 'FBulan', ['FTgl', 'FBulan']));
        $this->assertSame('FTgl', GridSort::column('FTGL', 'FBulan', ['FTgl', 'FBulan']));
    }

    // ------------------------------------------------------------- direction

    public function testDirectionOnlyEverYieldsAscOrDesc(): void
    {
        $this->assertSame('ASC', GridSort::direction('asc'));
        $this->assertSame('DESC', GridSort::direction('DESC'));
        $this->assertSame('ASC', GridSort::direction(' Asc '));

        foreach (["asc; DROP TABLE x--", "desc, password", "'", '', null, 1, []] as $bad) {
            $this->assertContains(GridSort::direction($bad), ['ASC', 'DESC']);
        }
    }

    public function testDirectionHonoursTheDeclaredDefault(): void
    {
        $this->assertSame('ASC', GridSort::direction('rubbish', 'ASC'));
        $this->assertSame('DESC', GridSort::direction('rubbish', 'DESC'));
    }

    // ----------------------------------------------------------------- paging

    public function testPagingValuesAreForcedToIntegers(): void
    {
        $this->assertSame(50, GridSort::limit('50'));
        $this->assertSame(50, GridSort::limit('50; DROP TABLE tbluser--'));
        $this->assertSame(0, GridSort::limit('abc'));
        $this->assertSame(0, GridSort::limit(null));

        $this->assertSame(100, GridSort::offset('100'));
        $this->assertSame(0, GridSort::offset('-5'), 'Offset negatif tidak masuk akal untuk BETWEEN/OFFSET.');
        // (int) memotong di karakter non-angka pertama: yang tersisa hanya angkanya.
        $this->assertSame(1, GridSort::offset("1 OR 1=1"));
        $this->assertSame(1, GridSort::offset("1; WAITFOR DELAY '0:0:5'"));
    }
}
