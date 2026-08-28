<?php

namespace Tests\Unit;

use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Sso as SsoConfig;

/**
 * Mode SSO-only (`sso.passwordLoginEnabled = false`).
 *
 * Menyembunyikan form password di halaman login TIDAK menutup apa pun: endpoint
 * di baliknya tetap menerima POST dari curl, dari tab lama yang masih terbuka,
 * atau dari bookmark. Jadi setiap jalur login lokal harus menolak sendiri, di
 * server. Test ini menemukan jalur-jalur itu di tingkat sumber supaya endpoint
 * baru yang lupa dipagari langsung ketahuan.
 *
 * Yang sengaja TIDAK ikut dipagari: cabang buka-kunci lock screen di
 * Webauthn::processLogin() saat sesi masih hidup. Itu penegasan ulang sesi yang
 * sudah ada, bukan pembuatan sesi baru — memagarinya akan mengurung pengguna
 * SSO di layar terkunci.
 */
final class SsoOnlyModeTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    /** Alamat dashboard SSO yang dipakai selama pengujian. */
    private const DASHBOARD_URL = 'https://testsso.transporindo.com/dashboard';

    /** Setiap endpoint di Login.php yang menerima kredensial lokal. */
    private const GATED_LOGIN_METHODS = [
        'proses',
        'unlock',
        'forgotPassword',
        'resetPasswordForm',
        'resetPasswordSubmit',
        'resetPasswordCustom',
    ];

    // ── Perilaku sesungguhnya lewat HTTP ────────────────────────────────────

    public function testHalamanLoginDiarahkanKeDashboardSsoSaatModeSsoOnly(): void
    {
        $this->bootHttp(false);

        $result = $this->get('login');

        $result->assertRedirect();
        $this->assertSame(self::DASHBOARD_URL, (string) $result->getRedirectUrl());
    }

    public function testHalamanLoginTetapTampilSaatMembawaPesanKegagalan(): void
    {
        $this->bootHttp(false);

        // Ini yang menjaga pengguna dari lingkaran tak berujung: gagal login SSO
        // -> dilempar ke halaman ini -> kalau ikut dialihkan, ia kembali ke
        // dashboard, menekan kartu SYS lagi, gagal lagi, tanpa pernah tahu
        // sebabnya. Halaman ini satu-satunya tempat pesan itu bisa muncul.
        $result = $this->get('login?sso=unknown');

        $result->assertOK();
        $result->assertSee('belum terdaftar di SYS');
    }

    public function testHalamanLoginTidakDialihkanSaatSsoBelumDikonfigurasi(): void
    {
        $this->bootHttp(false);

        // Login lokal mati DAN SSO belum siap: tidak ada satu pun jalan masuk.
        // Mengalihkan ke alamat kosong hanya menyembunyikan salah konfigurasi;
        // pengguna harus melihat halamannya beserta keterangannya.
        $sso               = config(SsoConfig::class);
        $sso->dashboardUrl = '';
        Factories::injectMock('config', 'Sso', $sso);

        $result = $this->get('login');

        $result->assertOK();
        $result->assertSee('Tidak ada metode login yang aktif');
    }

    public function testHalamanLoginTampilSepertiBiasaSaatLoginLokalHidup(): void
    {
        $this->bootHttp(true);

        $result = $this->get('login');

        $result->assertOK();
        $this->assertStringContainsString('name="userid"', (string) $result->getBody());
    }

    public function testPostKeLoginProsesDitolakSaatModeSsoOnly(): void
    {
        $this->bootHttp(false);

        // Inti mode ini: POST langsung — dari curl, tab lama, atau bookmark —
        // tidak pernah melihat form yang disembunyikan, jadi endpointnya sendiri
        // yang harus menolak.
        $result = $this->post('login/proses', ['userid' => 'budi', 'password' => 'rahasia']);

        $result->assertRedirect();
        $this->assertStringContainsString('sso=onlysso', (string) $result->getRedirectUrl());
    }

    public function testPostKeLoginProsesTetapDiprosesSaatLoginLokalHidup(): void
    {
        $this->bootHttp(true);

        // Pembanding: dengan saklar menyala, permintaan yang sama jalan terus ke
        // validasi biasa. Tanpa test ini, gerbang yang menolak SEMUA orang tanpa
        // peduli konfigurasi akan lolos dari test di atas.
        $result = $this->post('login/proses', ['userid' => '', 'password' => '']);

        $result->assertRedirect();
        $this->assertStringNotContainsString('sso=onlysso', (string) $result->getRedirectUrl());
    }

    public function testUnlockMenjawabPenandaSsoOnlyBukanPasswordSalah(): void
    {
        $this->bootHttp(false);

        $result = $this->post('login/unlock', ['password' => 'rahasia']);

        $this->assertSame(403, $result->response()->getStatusCode());

        $body = json_decode($this->jsonPayload($result->getBody()), true);

        $this->assertIsArray($body);
        $this->assertFalse($body['success'] ?? true);

        // `ssoOnly` inilah yang membuat lockscreen.js mengantar pengguna ke SSO.
        // Tanpa penanda ini jawabannya jatuh ke handleFailedUnlock, dihitung
        // sebagai percobaan gagal, dan pengguna dipaksa logout tanpa sebab.
        $this->assertTrue($body['ssoOnly'] ?? false, 'Jawaban unlock tidak membawa penanda ssoOnly.');
        $this->assertStringContainsString('sso/login', (string) ($body['redirect'] ?? ''));
    }

    public function testForgotPasswordDitolakSaatModeSsoOnly(): void
    {
        $this->bootHttp(false);

        $result = $this->post('forgot-password', ['user' => 'budi']);

        $this->assertSame(403, $result->response()->getStatusCode());
    }

    public function testHalamanResetPasswordDitolakSaatModeSsoOnly(): void
    {
        $this->bootHttp(false);

        $result = $this->get('reset-password?token=abc&user=budi');

        $result->assertRedirect();
        $this->assertStringContainsString('sso=onlysso', (string) $result->getRedirectUrl());
    }

    // ── Konfigurasi & sumber ────────────────────────────────────────────────

    public function testDefaultnyaLoginLokalTetapHidup(): void
    {
        // Default harus true: mematikan login lokal sebelum SSO terbukti jalan
        // berarti tidak ada seorang pun yang bisa masuk untuk memperbaikinya.
        $this->assertTrue(
            (new SsoConfig())->passwordLoginEnabled,
            'sso.passwordLoginEnabled harus true selama masa transisi.'
        );
    }

    public function testSemuaEndpointLoginLokalMemeriksaSaklarnya(): void
    {
        $source = (string) file_get_contents(APPPATH . 'Controllers/Login.php');

        foreach (self::GATED_LOGIN_METHODS as $method) {
            $body = $this->methodSource($source, $method);

            $this->assertNotSame('', $body, "Login::{$method}() tidak ditemukan — daftar di test ini sudah usang.");

            $this->assertStringContainsString(
                'passwordLoginDisabled()',
                $body,
                "Login::{$method}() tidak memeriksa sso.passwordLoginEnabled — endpoint ini tetap terbuka saat mode SSO-only."
            );
        }
    }

    public function testGerbangnyaBeradaDiAwalMethodSebelumApaPunDiproses(): void
    {
        $source = (string) file_get_contents(APPPATH . 'Controllers/Login.php');

        foreach (self::GATED_LOGIN_METHODS as $method) {
            $body     = $this->methodSource($source, $method);
            $position = strpos($body, 'passwordLoginDisabled()');

            // Gerbang yang diletakkan setelah kredensial dibaca, rate limit
            // dikurangi, atau email dikirim tetap membiarkan efek sampingnya
            // terjadi. Ia harus jadi hal pertama yang dikerjakan method.
            $this->assertLessThan(
                400,
                $position,
                "Gerbang di Login::{$method}() terlalu jauh dari awal method — pindahkan ke baris pertama."
            );
        }
    }

    public function testQuickLoginBiometrikDariHalamanLoginIkutDipagari(): void
    {
        $source = (string) file_get_contents(APPPATH . 'Controllers/Webauthn.php');
        $body   = $this->methodSource($source, 'processLogin');

        $this->assertStringContainsString(
            'passwordLoginEnabled',
            $body,
            'Webauthn::processLogin() tidak memeriksa sso.passwordLoginEnabled — Quick Login masih jadi jalan masuk lokal saat mode SSO-only.'
        );

        // Gerbangnya harus berada DI DALAM cabang pembuat sesi. Kalau ia
        // dipindah ke awal method, buka-kunci biometrik di lock screen ikut
        // mati dan pengguna SSO terkurung di layar terkunci.
        $branch = strpos($body, "if (!session()->has(SESSION_NAME . 'logged_in'))");
        $gate   = strpos($body, 'passwordLoginEnabled');

        $this->assertNotFalse($branch, 'Cabang pembuat sesi di processLogin() tidak ditemukan.');
        $this->assertGreaterThan(
            $branch,
            $gate,
            'Gerbang SSO-only harus di dalam cabang pembuat sesi, bukan di awal processLogin().'
        );
    }

    public function testHalamanLoginMenyembunyikanFormLokalSaatModeSsoOnly(): void
    {
        $view = (string) file_get_contents(APPPATH . 'Views/login.php');

        $this->assertStringContainsString('$localLogin', $view, 'View login tidak punya percabangan mode SSO-only.');
        $this->assertStringContainsString('passwordLoginEnabled', $view);
    }

    /**
     * Lock screen membuka kuncinya dengan password `tbluser`, sementara pengguna
     * SSO tidak pernah memakai — dan umumnya tidak tahu — password itu. Bagi dia
     * layar terkunci bukan pengaman melainkan jalan buntu, jadi sesi yang lahir
     * dari SSO tidak dikunci sama sekali.
     */
    public function testLockScreenTidakDirenderUntukSesiSso(): void
    {
        $html = $this->renderFooter(true);

        $this->assertStringNotContainsString('id="lockscreen-overlay"', $html, 'Overlay lock screen masih dirender untuk sesi SSO.');

        // Tanpa overlay, lockscreen.js berhenti di awal — tapi scriptnya pun
        // tidak perlu ikut dimuat.
        $this->assertStringNotContainsString('js/lockscreen.js', $html, 'lockscreen.js masih dimuat untuk sesi SSO.');
        $this->assertStringNotContainsString('sysmodern_lockscreen_userid', $html, 'userid masih ditulis ke localStorage untuk sesi SSO.');
    }

    public function testLockScreenTetapAktifUntukSesiLoginLokal(): void
    {
        // Pembanding: mematikan lock screen hanya berlaku untuk sesi SSO. Kalau
        // ia ikut mati untuk login lokal, satu kontrol keamanan hilang diam-diam.
        $html = $this->renderFooter(false);

        $this->assertStringContainsString('id="lockscreen-overlay"', $html, 'Lock screen ikut mati untuk sesi login lokal.');
        $this->assertStringContainsString('js/lockscreen.js', $html);
    }

    public function testPesanErrorLockScreenTetapAdaSaatFormPasswordHilang(): void
    {
        // lockscreen.js menulis pesan ke #lockscreen-error untuk jalur biometrik
        // dan rate limit juga, jadi elemennya tidak boleh ikut tersembunyi
        // bersama form password.
        $footer = (string) file_get_contents(APPPATH . 'Views/partials/footer.php');

        $this->assertStringContainsString('id="lockscreen-error"', $footer, 'Elemen #lockscreen-error hilang dari lock screen.');

        // Buang isi setiap blok $lockPassword, lalu pastikan elemennya masih
        // ada — kalau ikut terbuang berarti ia dirender bersyarat.
        $stripped = preg_replace('/<\?php if \(\$lockPassword\): \?>.*?<\?php endif; \?>/s', '', $footer) ?? '';

        $this->assertStringContainsString(
            'id="lockscreen-error"',
            $stripped,
            '#lockscreen-error berada di dalam blok $lockPassword — pesan error akan hilang saat mode SSO-only.'
        );
    }

    /**
     * Menyiapkan request HTTP sungguhan dengan saklar pada posisi tertentu.
     *
     * Filter global dimatikan seperti di LoginThrottleEndpointTest: yang diuji
     * di sini penolakan oleh controller, dan csrf hanya akan menghalangi POST
     * dari test. Konstruktor Model CI4 langsung membuka koneksi, jadi grup
     * database test (SQLite3) harus tersedia walau tidak ada query yang jalan.
     */
    private function bootHttp(bool $passwordLoginEnabled): void
    {
        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped(
                'Butuh ekstensi sqlite3. Jalankan: php -d extension=sqlite3 vendor/bin/phpunit'
            );
        }

        $filters                    = config('Filters');
        $filters->globals['before'] = [];
        $filters->globals['after']  = [];

        $sso                       = config(SsoConfig::class);
        $sso->passwordLoginEnabled = $passwordLoginEnabled;
        // Dipatok, tidak diambil dari .env: tujuan pengalihan mode SSO-only
        // adalah yang sedang diuji, jadi ia tidak boleh ikut berubah kalau
        // seseorang mengganti alamat dashboard di .env.
        $sso->enabled      = true;
        $sso->dashboardUrl = self::DASHBOARD_URL;
        Factories::injectMock('config', 'Sso', $sso);
    }

    /**
     * FeatureTestTrait membungkus body respons dengan kerangka HTML, jadi JSON-nya
     * diambil kembali dari antara kurung kurawal terluar.
     */
    private function jsonPayload(?string $body): string
    {
        $body  = (string) $body;
        $start = strpos($body, '{');
        $end   = strrpos($body, '}');

        return $start !== false && $end !== false ? substr($body, $start, $end - $start + 1) : $body;
    }

    /** Merender partial footer untuk sesi SSO atau sesi login lokal. */
    private function renderFooter(bool $fromSso): string
    {
        helper(['url', 'asset_helper']);

        session()->set([
            SESSION_NAME . 'logged_in' => 1,
            SESSION_NAME . 'userid'    => 'budi',
            SESSION_NAME . 'sso_login' => $fromSso ? 1 : null,
        ]);

        return view('partials/footer', [], ['saveData' => false]);
    }

    /** Potongan sumber satu method, dari deklarasinya sampai method berikutnya. */
    private function methodSource(string $source, string $method): string
    {
        if (preg_match('/(public|private|protected)\s+function\s+' . preg_quote($method, '/') . '\s*\(/', $source, $m, PREG_OFFSET_CAPTURE) !== 1) {
            return '';
        }

        $start = $m[0][1];
        $rest  = substr($source, $start + 1);

        $next = preg_match('/\n\s*(public|private|protected)\s+function\s/', $rest, $n, PREG_OFFSET_CAPTURE) === 1
            ? $n[0][1]
            : strlen($rest);

        return substr($rest, 0, $next);
    }
}
