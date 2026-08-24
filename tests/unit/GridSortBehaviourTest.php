<?php

namespace Tests\Unit;

use App\Libraries\GridSort;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * H-03: memastikan perbaikannya tidak diam-diam mengubah hasil pengurutan.
 *
 * Model butuh database yang tidak tersedia di lingkungan test, jadi logika
 * percabangan tiap model direplikasi di sini persis seperti di berkasnya, lalu
 * klausa ORDER BY yang terbentuk dibandingkan dengan perilaku LAMA untuk input
 * yang sah. Yang boleh berubah hanya perilaku untuk input berbahaya.
 */
final class GridSortBehaviourTest extends CIUnitTestCase
{
    /** Replika MomsetModel::get() dan enam Momsetmarketing*Model::get(). */
    private function omsetOrderBy($sidx, $sord): string
    {
        $sidx = GridSort::column($sidx, '');
        $sord = GridSort::direction($sord);

        $surut = ! empty($sidx) ? $sidx : 'FTgl';

        return $surut . ' ' . $sord;
    }

    public function testOmsetKeepsLegitimateSorting(): void
    {
        $this->assertSame('FOmset ASC', $this->omsetOrderBy('FOmset', 'asc'));
        $this->assertSame('FProfit DESC', $this->omsetOrderBy('FProfit', 'desc'));
        $this->assertSame('FTgl DESC', $this->omsetOrderBy('', 'desc'), 'Tanpa kolom dipilih harus jatuh ke FTgl.');
    }

    public function testOmsetNeutralisesInjection(): void
    {
        $this->assertSame('FTgl DESC', $this->omsetOrderBy('FTgl, (SELECT TOP 1 password FROM tbluser)', 'desc'));
        // Kolomnya sendiri sah, jadi tetap dipakai; yang dinetralkan arahnya.
        $this->assertSame('FOmset DESC', $this->omsetOrderBy('FOmset', "desc; DROP TABLE tbluser--"));
    }

    /** Replika enam Momsetrekapmarketing*Model::get(). */
    private function rekapOrderBy($sidx, $sord): string
    {
        $sidx = GridSort::column($sidx, '');
        $sord = GridSort::direction($sord);

        $surut = ! empty($sidx) ? $sidx : 'substring(FBulan,4,4) DESC, FBulan DESC, FNMarketing';

        if (strpos($surut, 'FBulan') === false && strpos($surut, 'FNMarketing') === false) {
            $surut .= ' ' . $sord;
        } else {
            $surut = 'substring(FBulan,4,4) DESC, FBulan DESC, FNMarketing ASC';

            if (! empty($sidx)) {
                $surut = $sidx . ' ' . $sord;
            }
        }

        return $surut;
    }

    public function testRekapMarketingKeepsItsCompoundDefault(): void
    {
        $this->assertSame(
            'substring(FBulan,4,4) DESC, FBulan DESC, FNMarketing ASC',
            $this->rekapOrderBy('', 'desc'),
            'Default majemuk tulisan developer harus tetap utuh.'
        );
    }

    public function testRekapMarketingKeepsLegitimateSorting(): void
    {
        $this->assertSame('FOmset ASC', $this->rekapOrderBy('FOmset', 'asc'));
        $this->assertSame('FBulan DESC', $this->rekapOrderBy('FBulan', 'desc'));
        $this->assertSame('FNMarketing ASC', $this->rekapOrderBy('FNMarketing', 'asc'));
    }

    public function testRekapMarketingFallsBackToDefaultOnInjection(): void
    {
        $this->assertSame(
            'substring(FBulan,4,4) DESC, FBulan DESC, FNMarketing ASC',
            $this->rekapOrderBy('(SELECT TOP 1 password FROM tbluser)', 'desc')
        );
    }

    /** Replika MuserModel::get(). */
    private function userOrderBy($sidx, $sord): string
    {
        if ($sidx === 'rolename') {
            $sidx = 'tbluser.userid';
        }

        $sortable = ['userpk', 'userid', 'username', 'email', 'nowhatsapp', 'dashboard', 'modifiedby', 'modifiedonview', 'tbluser.userid'];

        $sidx = GridSort::column($sidx, 'tbluser.userid', $sortable);
        $sord = GridSort::direction($sord, 'ASC');

        return $sidx !== '1' ? trim($sidx . ' ' . $sord) : 'tbluser.userid asc';
    }

    public function testUserGridKeepsItsRolenameRemapAndDefault(): void
    {
        $this->assertSame('tbluser.userid ASC', $this->userOrderBy('rolename', 'asc'), 'rolename tetap dipetakan ke tbluser.userid.');
        $this->assertSame('tbluser.userid ASC', $this->userOrderBy(1, 'asc'), 'sidx=1 (tanpa kolom dipilih) tetap default.');
        $this->assertSame('username DESC', $this->userOrderBy('username', 'desc'));
        $this->assertSame('email ASC', $this->userOrderBy('email', 'asc'));
    }

    public function testUserGridRefusesToSortByPassword(): void
    {
        // Kolom ini nyata ada di SELECT tbluser.*, tapi tidak boleh jadi kunci
        // pengurutan: urutan barisnya bisa dipakai menebak isi hash.
        $this->assertSame('tbluser.userid ASC', $this->userOrderBy('password', 'asc'));
    }

    /** Replika MmenuModel::getAcos(). */
    private function acosOrderBy($sidx, $sord): string
    {
        $sortable = ['acosid', 'class', 'method', 'displayname', 'modifiedby', 'modifiedonview'];

        $sidx = GridSort::column($sidx, 'class, method', $sortable);
        $sord = GridSort::direction($sord, 'ASC');

        if ($sidx === 'class' || $sidx === '1' || $sidx === '') {
            $sidx = 'class, method';
        }

        return $sidx . ' ' . $sord;
    }

    public function testAcosGridKeepsItsTwoColumnDefault(): void
    {
        $this->assertSame('class, method ASC', $this->acosOrderBy('', 'asc'));
        $this->assertSame('class, method ASC', $this->acosOrderBy('class', 'asc'), 'Klik kolom class tetap mengurutkan class lalu method.');
        $this->assertSame('class, method DESC', $this->acosOrderBy(1, 'desc'));
        $this->assertSame('method ASC', $this->acosOrderBy('method', 'asc'));
    }

    /** Replika MtruckingtradoluarModel::get(). */
    public function testTruckingTradoLuarBuildsASingleSafeClause(): void
    {
        $surut = GridSort::column('FNTrans', 'FTgl') . ' ' . GridSort::direction('asc');
        $this->assertSame('FNTrans ASC', $surut);

        $surut = GridSort::column("FTgl; WAITFOR DELAY '0:0:5'", 'FTgl') . ' ' . GridSort::direction('asc');
        $this->assertSame('FTgl ASC', $surut);
    }

    /** Paging: BETWEEN / OFFSET tetap menghasilkan angka yang sama untuk input wajar. */
    public function testPagingArithmeticIsUnchangedForNormalInput(): void
    {
        foreach ([[0, 50], [50, 50], [100, 25]] as [$rawStart, $rawLimit]) {
            $limit = GridSort::limit($rawLimit);
            $start = GridSort::offset($rawStart);

            $start++;
            $sampai = $limit + $start - 1;

            $this->assertSame($rawStart + 1, $start);
            $this->assertSame($rawStart + $rawLimit, $sampai);
        }
    }
}
