<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Regresi untuk M-02 (Username Enumeration pada Fitur Lupa Password).
 *
 * `Login::forgotPassword()` dulu menjawab berbeda untuk username yang ada dan
 * yang tidak: 400 "Username tidak ditemukan" versus 200 "link reset dikirim".
 * Satu request per username sudah cukup untuk menyusun daftar akun valid, dan
 * daftar itulah bahan baku credential stuffing.
 *
 * Jalurnya butuh database dan SMTP yang tidak tersedia di lingkungan test, jadi
 * yang diperiksa di sini adalah bentuk sumbernya: setiap jalan keluar setelah
 * pencarian user harus melewati satu pintu yang sama.
 */
final class ForgotPasswordEnumerationTest extends CIUnitTestCase
{
    /**
     * Kalimat yang dulu membocorkan keberadaan akun. Tidak boleh muncul lagi
     * di jalur mana pun — termasuk sebagai pesan "akun ini tidak punya email",
     * yang justru memastikan akunnya ada.
     */
    private const KALIMAT_TERLARANG = [
        'Username tidak ditemukan',
        'Akun ini tidak memiliki email',
    ];

    public function testTidakAdaPesanYangMemastikanAkunAda(): void
    {
        $body = $this->bodyForgotPassword();

        foreach (self::KALIMAT_TERLARANG as $kalimat) {
            $this->assertStringNotContainsString(
                $kalimat,
                $body,
                sprintf('Pesan "%s" memberi tahu pemanggil apakah akun ada (M-02).', $kalimat)
            );
        }
    }

    /**
     * Semua jalan keluar setelah pencarian user harus memakai respons yang sama.
     *
     * Yang boleh berbeda hanya penolakan yang tidak bergantung pada akun:
     * reset dimatikan (403) dan rate limit (429) — keduanya berada sebelum
     * aplikasi menyentuh database sama sekali.
     */
    public function testSemuaJalanKeluarSetelahPencarianUserMemakaiResponsSeragam(): void
    {
        $body  = $this->bodyForgotPassword();
        $mulai = strpos($body, "\$this->throttle->hit('forgot'");

        $this->assertNotFalse($mulai, 'Pemotongan jatah throttle tidak ditemukan di forgotPassword().');

        preg_match_all('/return\s+(.+?);/s', substr($body, $mulai), $m);

        $this->assertNotEmpty($m[1], 'Tidak ada return yang terbaca setelah pemotongan jatah.');

        foreach ($m[1] as $return) {
            $this->assertSame(
                '$this->forgotPasswordResponse()',
                trim(preg_replace('/\s+/', ' ', $return)),
                "Jalur ini menjawab berbeda dari jalur lain, sehingga bisa dipakai\n"
                . "memeriksa keberadaan akun (M-02). Pakai forgotPasswordResponse()."
            );
        }
    }

    /**
     * Jatah rate limit harus berkurang untuk setiap permintaan kirim, bukan
     * hanya yang benar-benar mengirim email.
     *
     * Kalau hanya username terdaftar yang memotong jatah, penyerang tidak perlu
     * membaca isi respons sama sekali: cukup memperhatikan username mana yang
     * akhirnya kena 429. Karena itu hit() wajib dipanggil sebelum baris
     * pencarian user, bukan sesudahnya.
     */
    public function testJatahThrottleDipotongSebelumKeberadaanAkunDiperiksa(): void
    {
        $body = $this->bodyForgotPassword();

        $posisiHit      = strpos($body, "\$this->throttle->hit('forgot'");
        $posisiPencarian = strpos($body, "->where('userid', \$username)");

        $this->assertNotFalse($posisiHit, 'Pemotongan jatah throttle tidak ditemukan.');
        $this->assertNotFalse($posisiPencarian, 'Pencarian user tidak ditemukan.');

        $this->assertLessThan(
            $posisiPencarian,
            $posisiHit,
            "Jatah throttle dipotong setelah keberadaan akun diperiksa, jadi pola\n"
            . "munculnya 429 ikut menunjukkan username mana yang terdaftar (M-02)."
        );
    }

    /** Respons seragamnya sendiri harus netral: satu status, satu pesan. */
    public function testResponsSeragamTidakMenyebutkanHasilPencarian(): void
    {
        $source = $this->sumberLoginController();

        $this->assertMatchesRegularExpression(
            '/private function forgotPasswordResponse\(\): ResponseInterface/',
            $source,
            'forgotPasswordResponse() tidak ada — satu-satunya pintu keluar seragam hilang.'
        );

        preg_match('/private function forgotPasswordResponse.*?\n    \}/s', $source, $m);

        $this->assertNotEmpty($m, 'Isi forgotPasswordResponse() tidak terbaca.');
        $this->assertStringContainsString("'status'    => 200", $m[0]);
        $this->assertStringContainsString('Jika username terdaftar', $m[0]);
        $this->assertStringNotContainsString('setStatusCode', $m[0]);
    }

    /**
     * Sisi klien tidak boleh lagi memanggil endpoint dua kali.
     *
     * Tahap "check" ada semata-mata karena dulu server mau memberi tahu apakah
     * username terdaftar. Membiarkannya hidup berarti menyisakan setengah alur
     * lama yang tidak lagi punya alasan — dan mengundang orang menghidupkan
     * kembali validasi yang membocorkan itu.
     */
    public function testKlienTidakLagiMemakaiTahapCekUsername(): void
    {
        $view = file_get_contents(APPPATH . 'Views/login.php');

        $this->assertStringNotContainsString(
            'check: true',
            $view,
            'Halaman login masih memanggil forgot-password dua kali (tahap cek username).'
        );
        $this->assertStringNotContainsString('checkValidation', $view);
    }

    private function sumberLoginController(): string
    {
        return file_get_contents(APPPATH . 'Controllers/Login.php');
    }

    /** Isi method forgotPassword(), dipotong sampai method berikutnya. */
    private function bodyForgotPassword(): string
    {
        $source = $this->sumberLoginController();
        $mulai  = strpos($source, 'public function forgotPassword()');

        $this->assertNotFalse($mulai, 'Method forgotPassword() tidak ditemukan.');

        $akhir = strpos($source, "\n    public function ", $mulai + 10);

        return $akhir === false ? substr($source, $mulai) : substr($source, $mulai, $akhir - $mulai);
    }
}
