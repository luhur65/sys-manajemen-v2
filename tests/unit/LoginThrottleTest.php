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
    private MockCache $cache;
    private LoginThrottle $throttle;
    private int $now = 1_700_000_000;

    protected function setUp(): void
    {
        parent::setUp();

        // MockCache = penyimpanan in-memory bawaan CI4; validateKey() tetap
        // dijalankan, jadi uji IPv6 di bawah tetap bermakna.
        $this->cache = new MockCache();
        $this->cache->initialize();

        $this->throttler = (new Throttler($this->cache))->setTestTime($this->now);
        // Cache yang sama dipakai ember throttle DAN penanda announceOnce().
        $this->throttle  = new LoginThrottle($this->throttler, $this->cache);
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

    // ----------------------------------------------- announceOnce (M-07)

    /**
     * Catatan: MockCache menyimpan TTL tapi `get()` mengabaikannya, jadi
     * berakhirnya penanda tidak bisa diuji di sini — yang diuji adalah
     * perilaku dedup dan cakupan kuncinya. Nilai TTL-nya sendiri
     * (`max($wait, 60)`) dijelaskan di LoginThrottle::announceOnce().
     */
    private function blockLoginFor(string $ip, string $account): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->throttle->hit('login', $ip, $account);
        }

        $this->assertNotNull($this->throttle->retryAfter('login', $ip, $account));
    }

    public function testAnnounceOnceIsSilentWhenNothingIsBlocked(): void
    {
        $this->assertFalse(
            $this->throttle->announceOnce('login', '10.0.0.1', 'budi'),
            'Tidak ada penolakan, jadi tidak ada yang perlu dicatat.'
        );
    }

    public function testOnlyTheFirstRejectionInAWindowIsAnnounced(): void
    {
        $this->blockLoginFor('10.0.0.1', 'budi');

        $this->assertTrue($this->throttle->announceOnce('login', '10.0.0.1', 'budi'));

        // Penyerang menghantam endpoint yang sama 99 kali lagi. Tanpa gerbang
        // ini, tiap request menulis satu baris ke log_activity.
        for ($i = 0; $i < 99; $i++) {
            $this->assertFalse(
                $this->throttle->announceOnce('login', '10.0.0.1', 'budi'),
                'Penolakan ke-' . ($i + 2) . ' dalam jendela yang sama tidak boleh dicatat lagi.'
            );
        }
    }

    /**
     * Inti dari pemilihan kunci: saat yang menahan adalah ember IP, seribu akun
     * berbeda tetap satu episode. Mengunci penanda pada pasangan ip+akun akan
     * membuat gerbang ini tidak berguna persis pada serangan yang paling perlu
     * ditahan.
     */
    public function testSprayingManyAccountsFromOneIpIsAnnouncedOnce(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->throttle->hit('login', '203.0.113.9', 'korban' . $i);
        }

        $this->assertTrue($this->throttle->announceOnce('login', '203.0.113.9', 'korban100'));

        for ($i = 101; $i < 200; $i++) {
            $this->assertFalse(
                $this->throttle->announceOnce('login', '203.0.113.9', 'korban' . $i),
                'Akun ke-' . $i . ' ditahan ember IP yang sama, jadi masih episode yang sama.'
            );
        }
    }

    public function testDifferentAccountsBlockedOnTheirOwnBucketsAreAnnouncedSeparately(): void
    {
        $this->blockLoginFor('10.0.0.1', 'budi');
        $this->blockLoginFor('10.0.0.2', 'siti');

        $this->assertTrue($this->throttle->announceOnce('login', '10.0.0.1', 'budi'));
        $this->assertTrue(
            $this->throttle->announceOnce('login', '10.0.0.2', 'siti'),
            'Dua akun yang terkunci karena embernya masing-masing adalah dua episode.'
        );
    }

    public function testLoginAndForgotEpisodesAreAnnouncedSeparately(): void
    {
        $this->blockLoginFor('10.0.0.1', 'budi');

        for ($i = 0; $i < 3; $i++) {
            $this->throttle->hit('forgot', '10.0.0.1', 'budi');
        }

        $this->assertTrue($this->throttle->announceOnce('login', '10.0.0.1', 'budi'));
        $this->assertTrue(
            $this->throttle->announceOnce('forgot', '10.0.0.1', 'budi'),
            'Ember forgot punya jendela sendiri, jadi punya catatan sendiri.'
        );
    }
}
