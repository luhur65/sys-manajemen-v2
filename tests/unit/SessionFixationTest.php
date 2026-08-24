<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Regresi untuk H-01 (Session Fixation).
 *
 * Setiap jalur yang menaikkan level privilese anonim -> terautentikasi wajib
 * memanggil session()->regenerate(true) SEBELUM menulis penanda login, supaya
 * session ID yang mungkin sudah ditanam penyerang tidak ikut terautentikasi.
 *
 * Jalur login butuh database (MloginModel / tabel WebAuthn) yang tidak tersedia
 * di lingkungan test, jadi pemeriksaan dilakukan di tingkat sumber: test ini
 * menemukan SENDIRI semua titik yang menulis 'logged_in' => 1, sehingga jalur
 * login keempat yang ditambahkan nanti otomatis ikut terperiksa.
 */
final class SessionFixationTest extends CIUnitTestCase
{
    public function testEverySessionEstablishmentPointRegeneratesTheSessionIdFirst(): void
    {
        $points = $this->sessionEstablishmentPoints();

        $this->assertNotEmpty(
            $points,
            'Tidak ada titik pembuatan sesi yang terdeteksi — pola pencarian test ini kemungkinan sudah usang.'
        );

        foreach ($points as $point) {
            $this->assertMatchesRegularExpression(
                '/session\(\)->regenerate\(\s*true\s*\)/',
                $point['preceding'],
                sprintf(
                    '%s::%s() menulis logged_in tanpa session()->regenerate(true) lebih dulu — session fixation (H-01).',
                    $point['file'],
                    $point['method']
                )
            );
        }
    }

    public function testAllKnownLoginPathsAreCovered(): void
    {
        $found = array_map(
            static fn (array $p): string => $p['file'] . '::' . $p['method'],
            $this->sessionEstablishmentPoints()
        );
        sort($found);

        $this->assertSame([
            'Login.php::proses',
            'Login.php::unlock',
            'SsoAuth.php::callback',
            'Webauthn.php::processLogin',
        ], $found);
    }

    /**
     * Setiap titik yang menulis penanda login, beserta potongan sumber dari awal
     * method sampai tepat sebelum penulisan tersebut.
     *
     * @return list<array{file: string, method: string, preceding: string}>
     */
    private function sessionEstablishmentPoints(): array
    {
        $points = [];

        foreach (glob(APPPATH . 'Controllers/*.php') as $file) {
            $source = file_get_contents($file);
            $offset = 0;

            while (($pos = strpos($source, "'logged_in' => 1", $offset)) !== false) {
                $offset = $pos + 1;
                $before = substr($source, 0, $pos);

                // Method terakhir yang dideklarasikan sebelum baris ini.
                if (preg_match_all('/function\s+([a-zA-Z0-9_]+)\s*\(/', $before, $m, PREG_OFFSET_CAPTURE) === 0) {
                    continue;
                }

                $last        = end($m[1]);
                $methodStart = $last[1];

                $points[] = [
                    'file'      => basename($file),
                    'method'    => $last[0],
                    'preceding' => substr($source, $methodStart, $pos - $methodStart),
                ];
            }
        }

        return $points;
    }
}
