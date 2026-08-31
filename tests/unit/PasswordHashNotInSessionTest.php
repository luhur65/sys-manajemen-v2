<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Regresi untuk H-05 (Hash Password Disimpan di Dalam Session).
 *
 * Hash bcrypt tidak boleh pernah masuk ke session. Session driver aplikasi ini
 * adalah FileHandler, jadi apa pun yang ditaruh di sesi tertulis ke disk dalam
 * bentuk serialized di writable/session dan bertahan sampai garbage collection
 * — sehingga LFI, path traversal, atau akses baca ke writable/ langsung
 * membocorkan hash untuk cracking offline.
 *
 * Jalur login butuh database yang tidak tersedia di lingkungan test, jadi
 * pemeriksaan dilakukan di tingkat sumber. Test ini menemukan SENDIRI setiap
 * penyebutan kunci sesi 'password' di seluruh app/, jadi jalur baru yang
 * ditambahkan nanti otomatis ikut terperiksa.
 */
final class PasswordHashNotInSessionTest extends CIUnitTestCase
{
    /**
     * Bentuk yang dilarang, beserta penjelasan kalau tertangkap.
     */
    private const POLA_TERLARANG = [
        // session()->set([... SESSION_NAME.'password' => ...]), session()->get(SESSION_NAME.'password')
        '/SESSION_NAME\s*\.\s*[\'"]password[\'"]/'  => 'kunci sesi SESSION_NAME.\'password\'',
        // Akses superglobal langsung, melewati prefiks SESSION_NAME.
        '/\$_SESSION\s*\[\s*[\'"]password[\'"]\s*\]/' => 'akses langsung $_SESSION[\'password\']',
    ];

    public function testNoSourceFileTouchesThePasswordSessionKey(): void
    {
        $pelanggaran = [];

        foreach ($this->sourceFiles() as $file) {
            $source = file_get_contents($file);

            foreach (self::POLA_TERLARANG as $pola => $keterangan) {
                if (preg_match_all($pola, $source, $m, PREG_OFFSET_CAPTURE) === 0) {
                    continue;
                }

                foreach ($m[0] as [$cocok, $pos]) {
                    $pelanggaran[] = sprintf(
                        '%s:%d — %s (%s)',
                        str_replace(APPPATH, 'app/', $file),
                        substr_count(substr($source, 0, $pos), "\n") + 1,
                        $keterangan,
                        trim($cocok)
                    );
                }
            }
        }

        $this->assertSame(
            [],
            $pelanggaran,
            "Hash password tidak boleh disimpan atau dibaca dari session (H-05).\n"
            . "Ambil hash dari database lewat userpk saat dibutuhkan.\n"
            . implode("\n", $pelanggaran)
        );
    }

    /**
     * Penjagaan spesifik pada titik pembangun sesi login: array yang menulis
     * 'logged_in' => 1 tidak boleh memuat kunci password dalam bentuk apa pun.
     */
    public function testLoginSessionPayloadsCarryNoPasswordKey(): void
    {
        $diperiksa = 0;

        foreach ($this->sourceFiles() as $file) {
            $source = file_get_contents($file);
            $offset = 0;

            while (($pos = strpos($source, "'logged_in' => 1", $offset)) !== false) {
                $offset = $pos + 1;
                $diperiksa++;

                // Batas array literal yang sedang dibangun: dari '[' pembuka
                // terdekat sebelum penanda login, sampai ']' penutupnya.
                $awal = strrpos(substr($source, 0, $pos), '[');
                $akhir = strpos($source, '];', $pos);

                if ($awal === false || $akhir === false) {
                    continue;
                }

                $payload = substr($source, $awal, $akhir - $awal);

                $this->assertDoesNotMatchRegularExpression(
                    '/[\'"]password[\'"]\s*=>/',
                    $payload,
                    sprintf(
                        '%s menaruh hash password ke dalam payload sesi login (H-05).',
                        str_replace(APPPATH, 'app/', $file)
                    )
                );
            }
        }

        $this->assertGreaterThan(
            0,
            $diperiksa,
            'Tidak ada payload sesi login yang terdeteksi — pola pencarian test ini kemungkinan sudah usang.'
        );
    }

    /**
     * @return list<string>
     */
    private function sourceFiles(): array
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(APPPATH, \FilesystemIterator::SKIP_DOTS)
        );

        $hasil = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $hasil[] = $file->getPathname();
            }
        }

        sort($hasil);

        return $hasil;
    }
}
