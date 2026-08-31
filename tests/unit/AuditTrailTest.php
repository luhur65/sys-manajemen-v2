<?php

namespace Tests\Unit;

use App\Models\MlogModel;
use CodeIgniter\Test\CIUnitTestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Regresi untuk M-07 — audit trail.
 *
 * Sebelum perbaikan, `log_activity` hanya berisi login berhasil: tidak ada
 * penanda jenis peristiwa, tidak ada nilai lama/baru, dan tidak satu pun
 * controller CRUD yang menulis ke sana. Yang dijaga di sini ada tiga hal, dan
 * ketiganya gampang hilang lagi tanpa disadari:
 *
 *   1. `changes()` benar-benar menyisakan yang berubah saja — kalau ia ikut
 *      melaporkan kolom yang diam, satu kolom yang benar-benar diubah akan
 *      tenggelam di antara puluhan baris yang tidak bergerak.
 *   2. Nilai rahasia tidak ikut mengendap di kolom `context`. Tabel log dibaca
 *      lebih banyak orang daripada tabel asalnya.
 *   3. Setiap jalur tulis di controller CRUD benar-benar memanggil audit log,
 *      dan tidak ada lagi pemanggil yang memakai bentuk lama `saveLog($this)`.
 */
final class AuditTrailTest extends CIUnitTestCase
{
    private function changes($before, $after): array
    {
        return MlogModel::changes($before, $after);
    }

    /** encodeContext() privat: yang diuji adalah hasil akhir yang masuk kolom. */
    private function encodeContext(array $context): ?string
    {
        $model  = (new ReflectionClass(MlogModel::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(MlogModel::class, 'encodeContext');
        $method->setAccessible(true);

        return $method->invoke($model, $context);
    }

    public function testChangesHanyaMengembalikanKolomYangBerubah(): void
    {
        $perubahan = $this->changes(
            ['userid' => 'BUDI', 'username' => 'Budi Santoso', 'dashboard' => 'home'],
            ['userid' => 'BUDI', 'username' => 'Budi S.',      'dashboard' => 'home']
        );

        $this->assertSame(['username'], array_keys($perubahan));
        $this->assertSame('Budi Santoso', $perubahan['username']['dari']);
        $this->assertSame('Budi S.', $perubahan['username']['ke']);
    }

    /**
     * Nilai dari SQL Server datang sebagai string, nilai dari POST sering int.
     * Perbandingan ketat akan menandai setiap angka sebagai "berubah" pada
     * setiap penyimpanan, dan log jadi penuh perubahan palsu.
     */
    public function testAngkaYangSamaTapiBedaTipeTidakDianggapBerubah(): void
    {
        $this->assertSame([], $this->changes(['menuseq' => '3'], ['menuseq' => 3]));
    }

    public function testKolomBaruDicatatSebagaiBerangkatDariNull(): void
    {
        $perubahan = $this->changes(['userid' => 'BUDI'], ['userid' => 'BUDI', 'email' => 'budi@example.test']);

        $this->assertArrayHasKey('email', $perubahan);
        $this->assertNull($perubahan['email']['dari']);
        $this->assertSame('budi@example.test', $perubahan['email']['ke']);
    }

    /** Model CI4 mengembalikan objek; controller mengirim array POST. */
    public function testChangesMenerimaObjekMaupunArray(): void
    {
        $sebelum = (object) ['rolename' => 'ADMIN'];

        $this->assertSame(
            ['rolename' => ['dari' => 'ADMIN', 'ke' => 'SUPERADMIN']],
            $this->changes($sebelum, ['rolename' => 'SUPERADMIN'])
        );
    }

    public function testPasswordDanTokenTidakIkutTersimpan(): void
    {
        $json = $this->encodeContext([
            'userid'   => 'BUDI',
            'password' => '$2y$10$abcdefghijklmnopqrstuv',
            'token'    => 'a1b2c3',
        ]);

        $this->assertIsString($json);
        $this->assertStringNotContainsString('$2y$10$', $json);
        $this->assertStringNotContainsString('a1b2c3', $json);
        $this->assertStringContainsString('BUDI', $json);

        $isi = json_decode($json, true);
        // Fakta bahwa password ikut diubah tetap terlihat; isinya tidak.
        $this->assertSame('***', $isi['password']);
        $this->assertSame('***', $isi['token']);
    }

    /**
     * Bentuk yang sebenarnya dipakai controller adalah hasil changes(), yaitu
     * password terkubur dua tingkat di dalam 'perubahan'. Penyaringan yang hanya
     * melihat tingkat teratas akan meloloskannya.
     */
    public function testRedaksiMenjangkauNilaiBersarang(): void
    {
        $json = $this->encodeContext([
            'perubahan' => [
                'password' => ['dari' => '$2y$10$lama', 'ke' => '$2y$10$baru'],
                'username' => ['dari' => 'Budi', 'ke' => 'Budi S.'],
            ],
        ]);

        $this->assertIsString($json);
        $this->assertStringNotContainsString('$2y$10$', $json);
        $this->assertStringContainsString('Budi S.', $json);
    }

    public function testContextKosongTidakMenulisJsonKosong(): void
    {
        $this->assertNull($this->encodeContext([]));
    }

    /**
     * Bentuk lama `saveLog($this)` hanya mencatat nama controller/method dari
     * router — bukan peristiwa bisnis. Kalau ada pemanggil yang tertinggal, ia
     * sekarang juga melanggar tipe parameter dan akan meledak saat dipakai.
     */
    public function testTidakAdaLagiPemanggilSaveLogGayaLama(): void
    {
        foreach ($this->phpFilesIn(APPPATH) as $file) {
            $source = file_get_contents($file);

            $this->assertDoesNotMatchRegularExpression(
                '/saveLog\s*\(\s*\$this\b/',
                $source,
                $this->relative($file) . ' masih memanggil saveLog($this) — argumen pertama sekarang jenis peristiwa.'
            );
        }
    }

    /**
     * Setiap jalur tulis CRUD harus meninggalkan jejak. Daftarnya ditulis
     * eksplisit supaya menambah controller CRUD baru tanpa audit log tidak lolos
     * begitu saja lewat asumsi "kan sudah ada di BaseController".
     *
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function jalurTulis(): array
    {
        return [
            'User'      => ['User.php', [MlogModel::DATA_CREATE, MlogModel::DATA_UPDATE, MlogModel::DATA_DELETE]],
            'Roles'     => ['Roles.php', [MlogModel::DATA_CREATE, MlogModel::DATA_UPDATE, MlogModel::DATA_DELETE]],
            'Menu'      => ['Menu.php', [MlogModel::DATA_CREATE, MlogModel::DATA_UPDATE, MlogModel::DATA_DELETE]],
            'Parameter' => ['Parameter.php', [MlogModel::DATA_CREATE, MlogModel::DATA_UPDATE, MlogModel::DATA_DELETE]],
            'UserAcl'   => ['UserAcl.php', [MlogModel::DATA_UPDATE]],
            'Profil'    => ['Profil.php', [MlogModel::DATA_UPDATE, MlogModel::DATA_DELETE]],
        ];
    }

    /**
     * @param list<string> $events
     *
     * @dataProvider jalurTulis
     */
    public function testControllerCrudMencatatPerubahanData(string $berkas, array $events): void
    {
        $source = file_get_contents(APPPATH . 'Controllers/' . $berkas);

        $this->assertStringContainsString('$this->auditLog(', $source, $berkas . ' tidak mencatat perubahan data sama sekali.');

        foreach ($events as $event) {
            $this->assertStringContainsString(
                'MlogModel::' . $event,
                $source,
                $berkas . ' tidak mencatat peristiwa ' . $event . '.'
            );
        }
    }

    /**
     * Gagal login adalah setengah dari alasan M-07 ada: tanpa baris ini,
     * serangan tebak-password tidak meninggalkan jejak apa pun di database.
     */
    public function testLoginGagalDanLogoutIkutDicatat(): void
    {
        $source = file_get_contents(APPPATH . 'Controllers/Login.php');

        foreach ([MlogModel::LOGIN_SUCCESS, MlogModel::LOGIN_FAILED, MlogModel::LOGIN_BLOCKED, MlogModel::LOGOUT, MlogModel::UNLOCK_FAILED] as $event) {
            $this->assertStringContainsString(
                'MlogModel::' . $event,
                $source,
                'Login.php tidak mencatat peristiwa ' . $event . '.'
            );
        }

        // LOGOUT harus ditulis selagi sesi masih ada.
        $posisiLog     = strpos($source, 'MlogModel::LOGOUT');
        $posisiDestroy = strpos($source, 'session()->destroy()');

        $this->assertIsInt($posisiLog);
        $this->assertIsInt($posisiDestroy);
        $this->assertLessThan(
            $posisiDestroy,
            $posisiLog,
            'Log logout ditulis setelah session()->destroy() — identitas penggunanya sudah hilang saat itu.'
        );
    }

    /**
     * `LOGIN_BLOCKED` ditulis dari endpoint yang bisa dipanggil tanpa
     * autentikasi. Tanpa gerbang `announceOnce()`, satu baris ditulis per
     * request dan penyerang bisa menumbuhkan `log_activity` sesukanya — tabel
     * yang justru dipakai untuk melacak dirinya.
     */
    public function testPenolakanRateLimitDicatatSekaliPerJendela(): void
    {
        $source = file_get_contents(APPPATH . 'Controllers/Login.php');

        $posisiGerbang = strpos($source, 'announceOnce(');
        $posisiLog     = strpos($source, 'MlogModel::LOGIN_BLOCKED');

        $this->assertIsInt(
            $posisiGerbang,
            'logThrottled() tidak lagi memakai LoginThrottle::announceOnce() — LOGIN_BLOCKED kembali ditulis tiap request.'
        );
        $this->assertIsInt($posisiLog);
        $this->assertLessThan(
            $posisiLog,
            $posisiGerbang,
            'Gerbang announceOnce() harus dievaluasi sebelum baris LOGIN_BLOCKED ditulis.'
        );
    }

    /**
     * SSO punya jalur otentikasinya sendiri: `SsoAuth::fail()` menolak tiket
     * yang tanda tangannya tidak sah (`invalid`) dan tiket yang dipakai dua kali
     * (`replay`). Keduanya percobaan otentikasi gagal, dan sebelumnya hanya
     * mengendap di berkas log yang dirotasi.
     */
    public function testPenolakanTiketSsoIkutDicatat(): void
    {
        $source = file_get_contents(APPPATH . 'Controllers/SsoAuth.php');

        $this->assertStringContainsString('MlogModel::LOGIN_SUCCESS', $source);
        $this->assertStringContainsString(
            'MlogModel::LOGIN_FAILED',
            $source,
            'SsoAuth::fail() tidak mencatat penolakan tiket ke log_activity.'
        );
    }

    /**
     * Single Logout mengakhiri sesi dari luar aplikasi. Jejaknya dulu hanya
     * `log_message('info', …)` — dan Config\Logger memakai ambang 4 di
     * production, jadi di sana ia tidak pernah sampai ke berkas sama sekali.
     */
    public function testSingleLogoutDicatatSebelumSesiDihancurkan(): void
    {
        $source = file_get_contents(APPPATH . 'Filters/AuthFilter.php');

        $this->assertStringContainsString(
            'MlogModel::LOGOUT',
            $source,
            'AuthFilter::enforceSingleLogout() tidak mencatat pengakhiran sesi.'
        );

        $posisiLog     = strpos($source, 'MlogModel::LOGOUT');
        $posisiDestroy = strpos($source, 'session()->destroy()');

        $this->assertIsInt($posisiLog);
        $this->assertIsInt($posisiDestroy);
        $this->assertLessThan(
            $posisiDestroy,
            $posisiLog,
            'Log SLO ditulis setelah session()->destroy() — sesi siapa yang berakhir sudah tidak terbaca saat itu.'
        );
    }

    /** @return list<string> */
    private function phpFilesIn(string $dir): array
    {
        $files    = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function relative(string $path): string
    {
        return str_replace(ROOTPATH, '', $path);
    }
}
