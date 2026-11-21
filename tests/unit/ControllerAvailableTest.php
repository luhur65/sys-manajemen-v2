<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * controllerAvailable() / hasMenuAccess(): menu dan tombol Home yang menunjuk
 * ke controller yang belum dimigrasi (link-nya 404) tidak boleh ditampilkan.
 *
 * Tidak menyentuh database: hanya membaca daftar file app/Controllers.
 */
final class ControllerAvailableTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('my_helper');
    }

    public function testControllerYangAdaDianggapTersedia(): void
    {
        $this->assertTrue(controllerAvailable('Omset'));
        $this->assertTrue(controllerAvailable('Truckingtradoluar'));
        $this->assertTrue(controllerAvailable('Profil'));
    }

    public function testControllerWarisanYangBelumDimigrasiDisembunyikan(): void
    {
        foreach ([
            'Ritasitrado', 'Statuskendaraan', 'Statuskendaraantambah', 'Stnk',
            'Truckingemkllain', 'Truckingemkllaindetailexp',
            'Truckinghargatradoluarlebihmahal', 'Truckingtradoluardetailexpsby',
            'RekapMarketing',
        ] as $class) {
            $this->assertFalse(controllerAvailable($class), $class);
        }
    }

    public function testHurufBesarKecilTidakMempengaruhi(): void
    {
        // tblacos menyimpan 'piutangemkl' dan 'Omset' dengan huruf bebas.
        $this->assertTrue(controllerAvailable('piutangemkl'));
        $this->assertTrue(controllerAvailable('OMSET'));
        $this->assertFalse(controllerAvailable('RITASITRADO'));
    }

    public function testMenuTanpaClassTidakDiperiksa(): void
    {
        $this->assertTrue(controllerAvailable(''));
        $this->assertTrue(controllerAvailable(null));
    }

    public function testLogoutDialihkanKeLogin(): void
    {
        $this->assertTrue(controllerAvailable('Logout'));
    }

    public function testHasMenuAccessMenolakModulYangTidakAdaTanpaMenanyaAcl(): void
    {
        // Controller tidak ada -> hasPermission() (yang menyentuh DB) tidak dipanggil.
        $this->assertFalse(hasMenuAccess('ritasitrado', 'index'));
    }
}
