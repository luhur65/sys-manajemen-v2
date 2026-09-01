<?php

namespace Tests\Unit;

use App\Libraries\GridFilter;
use CodeIgniter\Database\SQLSRV\Connection as SqlsrvConnection;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Regresi filter kolom angka pada grid jqGrid.
 *
 * Kolom seperti Nilai Invoice dirender dengan pemisah ribuan ("14,300,000"),
 * dan setHighlight() menyorot persis teks yang diketik user. Filter server
 * karena itu harus dicocokkan ke angka BERFORMAT juga; kalau tidak, mengetik
 * ",3" dicari sebagai "3" dan setiap baris yang punya angka 3 di mana pun ikut
 * muncul walau tidak ter-highlight.
 */
final class GridFilterNumericTest extends CIUnitTestCase
{
    private GridFilter $filter;

    protected function setUp(): void
    {
        parent::setUp();

        // Koneksi SQL Server tanpa perlu server hidup: escape() dan DBDriver
        // tidak menyentuh koneksi, sementara aturan escaping-nya tetap asli.
        $this->filter = new GridFilter(new SqlsrvConnection([]));
    }

    private function build(string $field, string $op, string $data, array $fieldMap): string
    {
        $filters = json_encode([
            'groupOp' => 'AND',
            'rules'   => [['field' => $field, 'op' => $op, 'data' => $data]],
        ]);

        return $this->filter->build($filters, $fieldMap);
    }

    public function testThousandSeparatorSearchMatchesTheFormattedNumber(): void
    {
        $where = $this->build('FNominal', 'cn', ',3', [
            'FNominal' => ['sql' => 'FNominal', 'numeric' => true],
        ]);

        $this->assertSame("FORMAT(FNominal, '#,##0', 'en-US') LIKE '%,3%'", $where);
    }

    public function testThousandSeparatorSearchNoLongerFallsBackToBareDigit(): void
    {
        $where = $this->build('FNominal', 'cn', ',3', [
            'FNominal' => ['sql' => 'FNominal', 'numeric' => true],
        ]);

        // Inilah penyebab baris tanpa highlight ikut muncul: koma dibuang lalu
        // dicari sebagai "%3%".
        $this->assertStringNotContainsString("LIKE '%3%'", $where);
    }

    public function testFullyFormattedValueIsSearchedAsTyped(): void
    {
        $where = $this->build('FNominal', 'cn', '14,300,000', [
            'FNominal' => ['sql' => 'FNominal', 'numeric' => true],
        ]);

        $this->assertSame("FORMAT(FNominal, '#,##0', 'en-US') LIKE '%14,300,000%'", $where);
    }

    public function testDecimalsFollowTheJqGridColumnFormatter(): void
    {
        $where = $this->build('FOmset', 'cn', '1,234.56', [
            'FOmset' => ['sql' => 'FOmset', 'numeric' => true, 'decimals' => 2],
        ]);

        $this->assertSame("FORMAT(FOmset, '#,##0.00', 'en-US') LIKE '%1,234.56%'", $where);
    }

    public function testBeginsWithAndEndsWithAlsoUseTheFormattedNumber(): void
    {
        $map = ['FNominal' => ['sql' => 'FNominal', 'numeric' => true]];

        $this->assertSame(
            "FORMAT(FNominal, '#,##0', 'en-US') LIKE '14,3%'",
            $this->build('FNominal', 'bw', '14,3', $map)
        );
        $this->assertSame(
            "FORMAT(FNominal, '#,##0', 'en-US') NOT LIKE '%,3%'",
            $this->build('FNominal', 'nc', ',3', $map)
        );
    }

    /**
     * @dataProvider comparisonOperators
     */
    public function testComparisonOperatorsStayNumeric(string $op, string $expected): void
    {
        $where = $this->build('FNominal', $op, '14,300,000', [
            'FNominal' => ['sql' => 'FNominal', 'numeric' => true],
        ]);

        $this->assertSame("FNominal {$expected} '14300000'", $where);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function comparisonOperators(): array
    {
        return [
            'sama dengan'      => ['eq', '='],
            'tidak sama'       => ['ne', '!='],
            'lebih kecil'      => ['lt', '<'],
            'lebih besar'      => ['gt', '>'],
            'lebih kecil sama' => ['le', '<='],
            'lebih besar sama' => ['ge', '>='],
        ];
    }

    public function testNonNumericColumnsAreUntouched(): void
    {
        $where = $this->build('FNShipper', 'cn', 'RUDY', ['FNShipper']);

        $this->assertSame("FNShipper LIKE '%RUDY%'", $where);
    }

    public function testValueIsStillEscapedOnTheFormattedPath(): void
    {
        $where = $this->build('FNominal', 'cn', "3' OR 1=1--", [
            'FNominal' => ['sql' => 'FNominal', 'numeric' => true],
        ]);

        $this->assertSame("FORMAT(FNominal, '#,##0', 'en-US') LIKE '%3'' OR 1=1--%'", $where);
    }

    public function testUnknownColumnIsStillRejected(): void
    {
        $where = $this->build('FPassword', 'cn', ',3', [
            'FNominal' => ['sql' => 'FNominal', 'numeric' => true],
        ]);

        $this->assertSame('', $where);
    }

    public function testDecimalsOptionCannotInjectSql(): void
    {
        $where = $this->build('FNominal', 'cn', ',3', [
            'FNominal' => ['sql' => 'FNominal', 'numeric' => true, 'decimals' => "2', 'en-US') OR 1=1--"],
        ]);

        $this->assertSame("FORMAT(FNominal, '#,##0.00', 'en-US') LIKE '%,3%'", $where);
    }
}
