<?php

namespace Tests\Unit;

use App\Libraries\LoginThrottle;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\Mock\MockCache;
use CodeIgniter\Throttle\Throttler;

/**
 * Regresi untuk H-04 (Tidak Ada Proteksi Brute Force / Rate Limiting).
 *
 * Memakai cache in-memory (MockCache) dan `setTestTime()` supaya waktu bisa
 * dimajukan tanpa benar-benar menunggu.
 */
final class LoginThrottleTest extends CIUnitTestCase
{
    private Throttler $throttler;
    private LoginThrottle $throttle;
    private int $now = 1_700_000_000;

    protected function setUp(): void
    {
        parent::setUp();

        // MockCache = penyimpanan in-memory bawaan CI4; validateKey() tetap
        // dijalankan, jadi uji IPv6 di bawah tetap bermakna.
        $cache = new MockCache();
        $cache->initialize();

        $this->throttler = (new Throttler($cache))->setTestTime($this->now);
        $this->throttle  = new LoginThrottle($this->throttler);
    }

    private function advance(int $seconds): void
    {
        $this->now += $seconds;
        $this->throttler->setTestTime($this->now);
    }

    // ------------------------------------------------------------------ login

    public function testCleanSlateIsNotThrottled(): void
    {
        $this->assertNull($this->throttle->retryAfter('login', '10.0.0.1', 'budi'));
    }

    public function testSuccessfulLoginsNeverConsumeQuota(): void
    {
        // Kantor di balik satu IP publik: 50 login sukses beruntun tidak boleh
        // membuat orang ke-51 terkunci.
        for ($i = 0; $i < 50; $i++) {
            $this->assertNull(
                $this->throttle->retryAfter('login', '10.0.0.1', 'user' . $i),
                'Login sukses ke-' . $i . ' seharusnya tidak mengurangi jatah.'
            );
        }
    }

    public function testFiveFailuresLockTheAccount(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->assertNull($this->throttle->retryAfter('login', '10.0.0.1', 'budi'), 'Percobaan ke-' . ($i + 1) . ' masih boleh.');
            $this->throttle->hit('login', '10.0.0.1', 'budi');
        }

        $wait = $this->throttle->retryAfter('login', '10.0.0.1', 'budi');

        $this->assertNotNull($wait, 'Percobaan ke-6 harus ditolak.');
        $this->assertGreaterThan(0, $wait);
    }

    public function testLockedAccountRecoversAfterWaiting(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->throttle->hit('login', '10.0.0.1', 'budi');
        }

        $this->assertNotNull($this->throttle->retryAfter('login', '10.0.0.1', 'budi'));

        // Kapasitas 5 per 900 detik -> satu token pulih tiap 180 detik.
        $this->advance(181);

        $this->assertNull($this->throttle->retryAfter('login', '10.0.0.1', 'budi'), 'Setelah 3 menit harus boleh mencoba lagi.');
    }

    public function testLockingOneAccountDoesNotLockAnother(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->throttle->hit('login', '10.0.0.1', 'budi');
        }

        $this->assertNotNull($this->throttle->retryAfter('login', '10.0.0.1', 'budi'));
        $this->assertNull($this->throttle->retryAfter('login', '10.0.0.1', 'siti'), 'Ember per-akun tidak boleh bocor ke akun lain.');
    }

    public function testPasswordSprayingAcrossManyAccountsIsStoppedByTheIpBucket(): void
    {
        // Tiap akun cuma dicoba sekali, jadi ember per-akun tidak pernah penuh.
        // Yang harus menangkap ini adalah ember per-IP.
        for ($i = 0; $i < 20; $i++) {
            $this->throttle->hit('login', '203.0.113.9', 'korban' . $i);
        }

        $this->assertNotNull(
            $this->throttle->retryAfter('login', '203.0.113.9', 'korban999'),
            'Spraying lintas akun dari satu IP harus tertahan ember IP.'
        );
    }

    public function testAccountBucketIsScopedPerIdentityNotPerIp(): void
    {
        // Berpindah IP tidak boleh mereset hukuman sebuah akun.
        for ($i = 0; $i < 5; $i++) {
            $this->throttle->hit('login', '10.0.0.' . $i, 'budi');
        }

        $this->assertNotNull($this->throttle->retryAfter('login', '198.51.100.7', 'budi'));
    }

    public function testUseridMatchingIsCaseInsensitive(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->throttle->hit('login', '10.0.0.1', 'budi');
        }

        $this->assertNotNull($this->throttle->retryAfter('login', '10.0.0.1', 'BUDI'), 'Ganti huruf besar-kecil tidak boleh mengelak dari batas.');
        $this->assertNotNull($this->throttle->retryAfter('login', '10.0.0.1', ' Budi '));
    }

    public function testSuccessfulLoginClearsTheAccountPenalty(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->throttle->hit('login', '10.0.0.1', 'budi');
        }

        $this->throttle->clear('login', 'budi');

        for ($i = 0; $i < 5; $i++) {
            $this->assertNull($this->throttle->retryAfter('login', '10.0.0.1', 'budi'), 'Jatah akun harus penuh lagi setelah login sukses.');
            $this->throttle->hit('login', '10.0.0.1', 'budi');
        }
    }

    public function testClearingAnAccountDoesNotWipeTheIpBucket(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->throttle->hit('login', '203.0.113.9', 'korban' . $i);
        }

        $this->throttle->clear('login', 'korban0');

        $this->assertNotNull(
            $this->throttle->retryAfter('login', '203.0.113.9', 'korban0'),
            'Satu login sukses tidak boleh menghapus jejak spraying dari IP yang sama.'
        );
    }

    // ----------------------------------------------------------------- forgot

    public function testForgotPasswordIsLimitedPerAccount(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->assertNull($this->throttle->retryAfter('forgot', '10.0.0.1', 'budi'));
            $this->throttle->hit('forgot', '10.0.0.1', 'budi');
        }

        $this->assertNotNull($this->throttle->retryAfter('forgot', '10.0.0.1', 'budi'), 'Email reset keempat dalam sejam harus ditahan.');
    }

    public function testForgotPasswordIsLimitedPerIpAcrossAccounts(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->throttle->hit('forgot', '203.0.113.9', 'korban' . $i);
        }

        $this->assertNotNull($this->throttle->retryAfter('forgot', '203.0.113.9', 'korban999'));
    }

    public function testForgotAndLoginBucketsAreIndependent(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->throttle->hit('forgot', '10.0.0.1', 'budi');
        }

        $this->assertNotNull($this->throttle->retryAfter('forgot', '10.0.0.1', 'budi'));
        $this->assertNull($this->throttle->retryAfter('login', '10.0.0.1', 'budi'), 'Kehabisan jatah lupa-password tidak boleh ikut mengunci login.');
    }

    // ------------------------------------------------------------------ lain

    public function testIpv6AddressDoesNotBreakTheCacheKey(): void
    {
        // Config\Cache::$reservedCharacters memuat ':' — alamat IPv6 mentah akan
        // melempar exception kalau kunci tidak di-hash.
        $this->assertNull($this->throttle->retryAfter('login', '2001:db8::1', 'budi'));

        $this->throttle->hit('login', '2001:db8::1', 'budi');

        $this->assertNull($this->throttle->retryAfter('login', '2001:db8::1', 'budi'));
    }

    public function testUnknownActionIsARefusalNotASilentPass(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->throttle->retryAfter('tidak-ada', '10.0.0.1', 'budi');
    }
}
