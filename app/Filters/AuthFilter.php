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

        return $this->enforceSingleLogout($request);
    }

    /**
     * Single Logout: sesi yang lahir dari SSO ikut berakhir saat sesi SSO-nya
     * dicabut.
     *
     * Tanpa ini, menekan logout di dashboard SSO hanya menutup dashboard —
     * sys-modern tetap terbuka sampai sesinya kedaluwarsa sendiri, yang justru
     * hal yang paling dihindari orang saat menekan logout.
     *
     * Sesi dari login lokal (tanpa `sso_sid`) tidak tersentuh sama sekali:
     * pemeriksaan berhenti di baris pertama. Sesi SSO pun hanya ditanyakan
     * sekali per sso.sloPollSeconds — sisanya dijawab dari cache (lihat SsoSlo).
     */
    private function enforceSingleLogout(RequestInterface $request)
    {
        $sid = (string) (session()->get(SESSION_NAME . 'sso_sid') ?? '');

        if ($sid === '') {
            return null;
        }

        $slo = new \App\Libraries\SsoSlo();

        if ($slo->isSessionActive($sid)) {
            return null;
        }

        log_message('info', sprintf(
            'SSO SLO: sesi SSO %s… sudah dicabut, sesi lokal user=%s diakhiri.',
            substr($sid, 0, 8),
            session()->get(SESSION_NAME . 'userid') ?: '-'
        ));

        $slo->forget($sid);
        session()->destroy();

        if ($request->isAJAX()) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['error' => 'Session expired']);
        }

        // Alasannya dititipkan lewat query string, bukan flashdata: sesi baru
        // saja dihancurkan, jadi tidak ada tempat menyimpan flashdata. Login
        // controller menerjemahkan kode ini jadi kalimat (lihat ssoMessage()).
        return redirect()->to(base_url('login?sso=expired'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do something here
    }
}
