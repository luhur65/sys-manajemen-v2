<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    /**
     * Memeriksa sesi login untuk setiap request.
     *
     * Pengecualian (mis. halaman login dan link reset password) didaftarkan
     * sebagai route eksplisit pada daftar `except` di Config\Filters, BUKAN
     * dengan mencocokkan bentuk string URL di sini. Pencocokan berbasis pola URL
     * pernah membuka celah bypass autentikasi karena pola dievaluasi terhadap
     * path apa pun, bukan terhadap route yang benar-benar cocok.
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // SESSION_NAME is defined in Constants.php
        if (! session()->get(SESSION_NAME . 'logged_in')) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON(['error' => 'Session expired']);
            }

            return redirect()->to(base_url('login'));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do something here
    }
}
