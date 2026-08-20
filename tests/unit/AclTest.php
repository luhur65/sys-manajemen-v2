<?php

namespace Tests\Unit;

use App\Libraries\MyAuth;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Regresi untuk C-02 (Broken Access Control).
 *
 * ACL sekarang ditegakkan oleh App\Filters\AclFilter yang memanggil
 * MyAuth::hasPermission(). Test ini mengunci tabel keputusannya tanpa
 * menyentuh database: subclass di bawah mengganti getAcosForClass()
 * dengan data ACL palsu.
 */
final class AclTest extends CIUnitTestCase
{
    /**
     * @param array<string, list<array<string, mixed>>> $acos
     */
    private function auth(int $isLogin, int $userPK, array $acos): MyAuth
    {
        return new class (['isLogin' => $isLogin, 'userPK' => $userPK, 'baseUrl' => '/'], $acos) extends MyAuth {
            private array $fakeAcos;

            public function __construct($params = [], array $fakeAcos = [])
            {
                parent::__construct($params);
                $this->fakeAcos = $fakeAcos;
            }

            protected function getAcosForClass($class)
            {
                return $this->fakeAcos[$class] ?? [];
            }
        };
    }

    /**
     * @param list<string> $methods
     *
     * @return list<array<string, mixed>>
     */
    private function acos(string $class, array $methods = ['index']): array
    {
        return array_map(
            static fn (string $m): array => ['acosid' => 1, 'class' => $class, 'method' => $m],
            $methods
        );
    }

    public function testUserWithAcoOnClassCanUseItsSiblingEndpoints(): void
    {
        $auth = $this->auth(1, 42, ['omset' => $this->acos('Omset')]);

        $this->assertTrue($auth->hasPermission('omset', 'index'));
        $this->assertTrue($auth->hasPermission('omset', 'grid'));
        $this->assertTrue($auth->hasPermission('omset', 'excel'));
    }

    public function testUserWithoutAcoIsDeniedEvenOnWhitelistedMethods(): void
    {
        // Inti C-02: sebelumnya semua ini lolos hanya bermodal sesi login.
        $auth = $this->auth(1, 42, ['omset' => $this->acos('Omset')]);

        $this->assertFalse($auth->hasPermission('user', 'grid'));
        $this->assertFalse($auth->hasPermission('user', 'crud'));
        $this->assertFalse($auth->hasPermission('roles', 'crud'));
        $this->assertFalse($auth->hasPermission('menu', 'crud'));
        $this->assertFalse($auth->hasPermission('tracing', 'excel'));
    }

    public function testUseraclIsNoLongerOpenToEveryLoggedInUser(): void
    {
        // /useracl/userroles/<userpk> menulis ke tbluseracl -> jalur eskalasi hak.
        $auth = $this->auth(1, 42, ['omset' => $this->acos('Omset')]);

        $this->assertFalse($auth->hasPermission('useracl', 'index'));
        $this->assertFalse($auth->hasPermission('useracl', 'grid'));
        $this->assertFalse($auth->hasPermission('useracl', 'userroles'));
        $this->assertFalse($auth->hasPermission('useracl', 'getacos'));
    }

    public function testUseraclInheritsPermissionFromUserClass(): void
    {
        // Layar Manage User Roles dibuka dari dalam admin User, jadi izinnya ikut 'user'.
        $auth = $this->auth(1, 1, ['user' => $this->acos('User')]);

        $this->assertTrue($auth->hasPermission('useracl', 'userroles'));
        $this->assertTrue($auth->hasPermission('useracl', 'getacos'));
        $this->assertFalse($auth->hasPermission('omset', 'grid'));
    }

    public function testUseraclAlsoInheritsPermissionFromRolesClass(): void
    {
        // app/Views/roles/index.php memanggil useracl/getAcos untuk grid katalog aco.
        $auth = $this->auth(1, 1, ['roles' => $this->acos('Roles')]);

        $this->assertTrue($auth->hasPermission('useracl', 'getacos'));
        $this->assertFalse($auth->hasPermission('user', 'crud'));
    }

    public function testSelfServiceAndInfrastructureClassesStayOpen(): void
    {
        $auth = $this->auth(1, 42, []);

        $this->assertTrue($auth->hasPermission('home', 'index'));
        $this->assertTrue($auth->hasPermission('profil', 'editpassword'));
        $this->assertTrue($auth->hasPermission('gridpreference', 'save'));
        $this->assertTrue($auth->hasPermission('webauthn', 'checkdevice'));
    }

    public function testAnonymousRequestIsDeniedEverythingButLogin(): void
    {
        // Jaring pengaman kalau ada request yang lolos AuthFilter (lihat C-04).
        $auth = $this->auth(0, 0, [
            'omset' => $this->acos('Omset'),
            'user'  => $this->acos('User'),
        ]);

        $this->assertFalse($auth->hasPermission('omset', 'grid'));
        $this->assertFalse($auth->hasPermission('user', 'crud'));
        $this->assertFalse($auth->hasPermission('useracl', 'userroles'));
        $this->assertTrue($auth->hasPermission('login', 'index'));
    }

    public function testEveryRoutableEndpointStaysReachableWithTheClassIndexAco(): void
    {
        // tblacos umumnya hanya menyimpan "<Class>::index" (dibuat dari menu).
        // Pastikan mengaktifkan filter tidak mematikan endpoint yang sah.
        $blocked = [];

        foreach ($this->routableEndpoints() as $class => $methods) {
            $auth = $this->auth(1, 1, [
                $class => $this->acos($class),
                'user' => $this->acos('User'),
            ]);

            foreach ($methods as $method) {
                if (! $auth->hasPermission($class, $method)) {
                    $blocked[] = $class . '/' . $method;
                }
            }
        }

        $this->assertSame([], $blocked, 'Endpoint sah ini akan kena 403: ' . implode(', ', $blocked));
    }

    /**
     * Pasangan class/method yang benar-benar bisa dicapai: terdaftar di Routes.php
     * DAN method-nya benar-benar ada sebagai public function di controllernya.
     *
     * @return array<string, list<string>>
     */
    private function routableEndpoints(): array
    {
        $routes = file_get_contents(APPPATH . 'Config/Routes.php');
        preg_match_all("/'([A-Za-z_]+)::([a-zA-Z0-9_]+)/", $routes, $matches, PREG_SET_ORDER);

        $surface = [];

        foreach ($matches as [, $controller, $method]) {
            $file = APPPATH . 'Controllers/' . $controller . '.php';
            if (! is_file($file)) {
                continue; // route warisan CI3, controllernya sudah tidak ada
            }
            if (! preg_match('/public\s+function\s+' . preg_quote($method, '/') . '\s*\(/i', file_get_contents($file))) {
                continue;
            }
            $surface[strtolower($controller)][strtolower($method)] = true;
        }

        return array_map('array_keys', $surface);
    }
}
