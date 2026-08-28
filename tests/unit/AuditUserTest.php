<?php

namespace Tests\Unit;

use App\Libraries\AuditUser;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Siapa yang tercatat di kolom `modifiedby`.
 *
 * Bug yang melatarbelakangi kelas ini: setiap penulis `modifiedby` membaca
 * `session()->get('USERNAME')` — kunci HURUF BESAR yang tidak pernah ditulis di
 * mana pun. Sesi menyimpan `sys_modernusername` (dan `username` huruf kecil).
 * Akibatnya `?? 'SYSTEM'` selalu menang, dan seluruh kolom `modifiedby` terisi
 * "SYSTEM" untuk SEMUA pengguna selama berbulan-bulan — tanpa error, tanpa
 * gejala, sampai ada yang membuka datanya.
 *
 * Kelas bug ini mahal justru karena diam: kunci sesi yang salah ketik tidak
 * pernah gagal, ia hanya mengembalikan null. Test di bawah menguncinya dari dua
 * sisi — perilakunya, dan daftar pemanggilnya.
 */
final class AuditUserTest extends CIUnitTestCase
{
    /** Setiap tempat yang menulis `modifiedby` dari identitas sesi. */
    private const PENULIS_MODIFIEDBY = [
        'Models/MuserModel.php',
        'Models/MuserrolesModel.php',
        'Models/RolesModel.php',
        'Controllers/Menu.php',
        'Controllers/Parameter.php',
        'Controllers/Profil.php',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Sesi dibawa antar test oleh CIUnitTestCase, jadi dibersihkan dulu.
        session()->remove([
            SESSION_NAME . 'username',
            SESSION_NAME . 'sso_impersonated',
            SESSION_NAME . 'sso_actor',
            SESSION_NAME . 'sso_approver',
        ]);
    }

    // ── Perilaku ────────────────────────────────────────────────────────────

    public function testSesiBiasaMencatatUsernameYangLoginBukanSYSTEM(): void
    {
        session()->set([SESSION_NAME . 'username' => 'Budi Santoso']);

        // Inti bug lamanya: nilai ini dulu SELALU 'SYSTEM'.
        $this->assertSame('Budi Santoso', AuditUser::modifiedBy());
        $this->assertNotSame(AuditUser::FALLBACK, AuditUser::modifiedBy());
    }

    public function testTanpaSesiJatuhKeSYSTEM(): void
    {
        // Seeder dan perintah CLI tidak punya sesi — di situlah 'SYSTEM' memang benar.
        $this->assertSame('SYSTEM', AuditUser::modifiedBy());
    }

    public function testSesiCastingMencatatPENYETUJUBukanKorbanDanBukanPelaku(): void
    {
        session()->set([
            SESSION_NAME . 'username'         => 'Budi Santoso', // yang ditiru
            SESSION_NAME . 'sso_impersonated' => 1,
            SESSION_NAME . 'sso_actor'        => 'dharma',       // yang menekan tombol
            SESSION_NAME . 'sso_approver'     => 'it.pusat',     // admin IT penyetuju
        ]);

        // Keputusan bisnis yang sudah dipakai HR, CRM, dan DISC: yang tercatat
        // sebagai pengubah data adalah admin IT penyetuju.
        $this->assertSame('it.pusat', AuditUser::modifiedBy());

        // Dan yang TIDAK boleh tercatat: orang yang sedang ditiru (ia tidak
        // melakukan apa pun), maupun admin yang menekan tombol.
        $this->assertNotSame('Budi Santoso', AuditUser::modifiedBy());
        $this->assertNotSame('dharma', AuditUser::modifiedBy());
    }

    public function testJejakPelakuTidakHilang(): void
    {
        session()->set([
            SESSION_NAME . 'username'         => 'Budi Santoso',
            SESSION_NAME . 'sso_impersonated' => 1,
            SESSION_NAME . 'sso_actor'        => 'dharma',
            SESSION_NAME . 'sso_approver'     => 'it.pusat',
        ]);

        $this->assertTrue(AuditUser::isImpersonating());
        $this->assertSame('dharma', AuditUser::actor());
        $this->assertSame('Budi Santoso', AuditUser::impersonated());

        // Satu baris yang menjawab "siapa sebenarnya" tanpa membuka tabel lain.
        $keterangan = AuditUser::describe();
        $this->assertStringContainsString('it.pusat', $keterangan);
        $this->assertStringContainsString('Budi Santoso', $keterangan);
        $this->assertStringContainsString('dharma', $keterangan);
    }

    public function testSesiBiasaTidakDianggapCasting(): void
    {
        session()->set([SESSION_NAME . 'username' => 'Budi Santoso']);

        $this->assertFalse(AuditUser::isImpersonating());
        $this->assertNull(AuditUser::actor());
        $this->assertNull(AuditUser::impersonated());
        $this->assertSame('Budi Santoso', AuditUser::describe());
    }

    public function testNilaiSesiKosongTidakDianggapNama(): void
    {
        // Spasi kosong pernah lolos sebagai "ada nilai" dan tercatat sebagai
        // modifiedby kosong — lebih buruk dari 'SYSTEM', karena tidak terlihat.
        session()->set([SESSION_NAME . 'username' => '   ']);

        $this->assertSame('SYSTEM', AuditUser::modifiedBy());
    }

    // ── Daftar pemanggil ────────────────────────────────────────────────────

    public function testSemuaPenulisModifiedbyLewatAuditUser(): void
    {
        foreach (self::PENULIS_MODIFIEDBY as $berkas) {
            $source = (string) file_get_contents(APPPATH . $berkas);

            $this->assertStringContainsString(
                'AuditUser::modifiedBy()',
                $source,
                "{$berkas} menulis modifiedby tanpa lewat AuditUser — identitasnya akan menyimpang dari penulis lain."
            );
        }
    }

    public function testTidakAdaPenulisModifiedbyYangMembacaKunciSesiUSERNAME(): void
    {
        foreach (self::PENULIS_MODIFIEDBY as $berkas) {
            $source = (string) file_get_contents(APPPATH . $berkas);

            // Kunci ini tidak pernah ditulis ke sesi. Membacanya selalu null,
            // dan fallback-nya diam-diam menang.
            $this->assertStringNotContainsString(
                "session()->get('USERNAME')",
                $source,
                "{$berkas} membaca kunci sesi 'USERNAME' yang tidak pernah ada — modifiedby akan kembali jadi SYSTEM."
            );
        }
    }
}
