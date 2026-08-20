<?php

namespace App\Filters;

use App\Libraries\MyAuth;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Closure;

/**
 * Menegakkan ACL (tblacos / tblacl / tbluseracl / tbluserroles) di lapisan filter.
 *
 * Sebelumnya ACL hanya dipakai untuk merender menu dan tombol dashboard, sehingga
 * setiap user yang sudah login bisa mencapai controller apa pun dengan mengetik
 * URL langsung. Filter ini memeriksa pasangan class/method dari router yang
 * sesungguhnya, bukan dari bentuk string URL.
 *
 * Mode dikontrol lewat .env:
 *   security.aclEnforce = true   -> tolak request yang tidak punya hak (default)
 *   security.aclEnforce = false  -> hanya mencatat ke log (untuk masa transisi)
 */
class AclFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Filter global berjalan sebelum BaseController memuat helper, jadi
        // base_url()/site_url() belum tentu tersedia di sini.
        helper('url');

        $router     = service('router');
        $controller = $router->controllerName();

        // Route berupa Closure tidak punya class/method untuk dicocokkan ke ACL.
        if ($controller instanceof Closure || $controller === null || $controller === '') {
            return;
        }

        $segments = explode('\\', str_replace('/', '\\', (string) $controller));
        $class    = strtolower(end($segments));
        $method   = strtolower($router->methodName() ?: 'index');

        $auth = new MyAuth([
            'isLogin' => session()->get(SESSION_NAME . 'logged_in') ? 1 : 0,
            'userPK'  => session()->get(SESSION_NAME . 'userpk') ?: 0,
            'baseUrl' => base_url(),
        ]);

        if ($auth->hasPermission($class, $method)) {
            return;
        }

        $enforce = filter_var(env('security.aclEnforce', true), FILTER_VALIDATE_BOOLEAN);

        log_message($enforce ? 'warning' : 'notice', sprintf(
            'ACL %s user=%s (pk=%s) %s/%s uri=%s ip=%s',
            $enforce ? 'DENY' : 'WOULD-DENY',
            session()->get(SESSION_NAME . 'userid') ?: '-',
            session()->get(SESSION_NAME . 'userpk') ?: '-',
            $class,
            $method,
            $request->getUri()->getPath(),
            $request->getIPAddress()
        ));

        if (! $enforce) {
            return;
        }

        if ($request->isAJAX() || $this->wantsJson($request)) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Anda tidak memiliki hak akses untuk ' . $class . '/' . $method,
                ]);
        }

        return service('response')
            ->setStatusCode(403)
            ->setBody(view('errors/html/error_403', [
                'message' => 'Anda tidak memiliki hak akses untuk ' . $class . '/' . $method . '.',
            ]));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak ada aksi setelah request.
    }

    private function wantsJson(RequestInterface $request): bool
    {
        $accept = (string) $request->getHeaderLine('Accept');

        return $accept !== '' && str_contains($accept, 'application/json');
    }
}
