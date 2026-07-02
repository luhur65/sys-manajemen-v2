<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Allow custom reset password link
        $uri = $request->getUri()->getPath();
        if (preg_match('#.*-\d{2}-\d{2}-\d{4}-\d{2}-\d{2}-\d{2}-[a-f0-9]+$#i', urldecode($uri))) {
            return;
        }

        // SESSION_NAME is defined in Constants.php
        if (!session()->get(SESSION_NAME . 'logged_in')) {
            return redirect()->to(base_url('login'));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do something here
    }
}
