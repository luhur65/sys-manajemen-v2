<?php

namespace Tests\Unit;

use App\Libraries\LoginThrottle;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\Mock\MockCache;
use CodeIgniter\Throttle\Throttler;

/**
 * H-04 di tingkat endpoint.
 *
 * Jalur "kredensial benar/salah" butuh database yang tidak tersedia di
 * lingkungan test, jadi yang diuji di sini adalah bagian yang berjalan SEBELUM
 * database disentuh: penolakan karena rate limit beserta bentuk responsnya —
 * redirect+flash untuk halaman login, JSON 429 untuk endpoint AJAX.
 *
 * Ember diisi lebih dulu lewat LoginThrottle yang memakai throttler yang sama
 * dengan yang dipakai controller (di-inject ke service container).
 */
final class LoginThrottleEndpointTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private LoginThrottle $throttle;

    protected function setUp(): void
    {
        parent::setUp();

        // Login::initController membuat model, dan konstruktor Model CI4 langsung
        // membuka koneksi. Grup database test memakai SQLite3, jadi tanpa ekstensi
        // itu controllernya bahkan tidak bisa diinstansiasi.
        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped(
                'Butuh ekstensi sqlite3. Jalankan: php -d extension=sqlite3 vendor/bin/phpunit'
            );
        }

        // Throttler yang sama dipakai test dan controller.
        $cache = new MockCache();
        $cache->initialize();
        \Config\Services::injectMock('throttler', new Throttler($cache));

        $this->throttle = new LoginThrottle();

        // Hanya filter csrf yang dimatikan; auth/acl tidak menghalangi rute login.
        $filters                    = config('Filters');
        $filters->globals['before'] = [];
        $filters->globals['after']  = [];
    }

    private function exhaust(string $action, string $account, int $times): void
    {
        for ($i = 0; $i < $times; $i++) {
            $this->throttle->hit($action, '0.0.0.0', $account);
        }
    }

    public function testLoginRedirectsWithAMessageOnceTheAccountIsLockedOut(): void
    {
        $this->exhaust('login', 'budi', 5);

        $result = $this->post('login/proses', ['userid' => 'budi', 'password' => 'salah']);

        $result->assertRedirect();
        $this->assertStringContainsString(
            'Terlalu banyak percobaan login',
            (string) session()->getFlashdata(SESSION_NAME . 'message')
        );
    }

    public function testLoginThrottleRepliesBeforeTouchingTheDatabase(): void
    {
        // Kalau penolakannya terjadi setelah query, test ini akan gagal dengan
        // CriticalError sqlite3 alih-alih redirect biasa.
        $this->exhaust('login', 'budi', 5);

        $result = $this->post('login/proses', ['userid' => 'budi', 'password' => 'salah']);

        $result->assertRedirect();
    }

    public function testUnlockReturns429Json(): void
    {
        $this->exhaust('login', 'budi', 5);

        $result = $this->post('login/unlock', ['userid' => 'budi', 'password' => 'salah']);

        $result->assertStatus(429);
        $result->assertJSONFragment(['success' => false]);
        $this->assertStringContainsString('Terlalu banyak percobaan', $result->getBody());
    }

    public function testForgotPasswordReturns429JsonWithACsrfToken(): void
    {
        $this->exhaust('forgot', 'budi', 3);

        $result = $this->post('forgot-password', ['user' => 'budi']);

        $result->assertStatus(429);
        $this->assertStringContainsString('Terlalu banyak permintaan reset password', $result->getBody());

        // JS halaman login membaca responseJSON.errors.user / .error, dan
        // memperbarui token dari .csrfToken. Ketiganya harus ada.
        // (getJSON() dipakai karena harness test membungkus body dengan HTML.)
        $body = json_decode((string) $result->getJSON(), true);

        $this->assertArrayHasKey('user', $body['errors'] ?? []);
        $this->assertArrayHasKey('error', $body);
        $this->assertNotEmpty($body['csrfToken'] ?? null);
    }

    public function testForgotPasswordCheckModeIsAlsoRefusedOnceLimited(): void
    {
        // Mode "check" tidak mengurangi jatah, tapi begitu jatah habis ia ikut
        // ditolak — kalau tidak, endpoint ini tetap bisa dipukul terus-menerus.
        $this->exhaust('forgot', 'budi', 3);

        $result = $this->post('forgot-password', ['user' => 'budi', 'check' => true]);

        $result->assertStatus(429);
    }

    public function testUnthrottledLoginIsNotBlockedByTheRateLimiter(): void
    {
        // Tanpa kegagalan sebelumnya, request harus LOLOS throttle dan berlanjut
        // sampai menyentuh database — bukti bahwa limiter tidak menghalangi user
        // biasa. Skema test tidak punya tabel aslinya, jadi error database di
        // sini justru hasil yang diharapkan.
        try {
            $result = $this->post('login/proses', ['userid' => 'budi', 'password' => 'apapun']);
        } catch (\Throwable $e) {
            $this->assertMatchesRegularExpression(
                '/no such table|sqlite|database/i',
                $e->getMessage(),
                'Diharapkan berlanjut sampai database, bukan berhenti di throttle.'
            );

            return;
        }

        $this->assertStringNotContainsString(
            'Terlalu banyak percobaan login',
            (string) session()->getFlashdata(SESSION_NAME . 'message'),
            'Login pertama tidak boleh kena rate limit.'
        );
        $this->assertNotSame(429, $result->response()->getStatusCode());
    }
}
