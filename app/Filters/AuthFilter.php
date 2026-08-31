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
                return $this->sessionEndedJson(base_url('login'));
            }

            return redirect()->to(base_url('login'));
        }

        return $this->enforceSingleLogout($request);
    }

    /**
     * Jawaban untuk request AJAX yang sesinya sudah tidak ada.
     *
     * Tanpa penanda, 401 ini sampai ke layar sebagai kegagalan biasa: tiap grid
     * dan grafik punya handler `error:` sendiri yang memunculkan dialog
     * "terjadi kesalahan saat mengambil data" — pesan yang salah, karena yang
     * terjadi bukan data yang gagal diambil melainkan sesi yang sudah berakhir.
     * Pengguna tinggal menatap dialog itu tanpa tahu ia sebenarnya sudah logout.
     *
     * `sessionExpired` sengaja dipakai sebagai kunci, BUKAN status 401 saja:
     * Webauthn dan lock screen juga menjawab 401 untuk keadaan lain dan sudah
     * punya penanganannya masing-masing. Penanda ini membuat penangan global di
     * partials/header.php hanya mengambil alih yang memang miliknya.
     *
     * `redirect` ikut dikirim supaya klien tidak perlu menebak tujuan — untuk
     * sesi SSO yang dicabut, alamatnya membawa ?sso=expired sehingga halaman
     * login menjelaskan sebabnya, bukan sekadar meminta login lagi.
     */
    private function sessionEndedJson(string $redirect)
    {
        return service('response')
            ->setStatusCode(401)
            ->setJSON([
                // Dipertahankan apa adanya: sudah ada call site yang membacanya.
                'error'          => 'Session expired',
                'sessionExpired' => true,
                'redirect'       => $redirect,
            ]);
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

        // M-07: baris log_message di atas memakai level `info`, dan
        // Config\Logger memakai ambang 4 di production — artinya di sanalah,
        // justru tempat yang paling penting, pengakhiran sesi lewat Single
        // Logout selama ini tidak meninggalkan jejak sama sekali. Ditulis ke
        // `log_activity` SEBELUM destroy(), selagi masih ada yang bisa
        // menjelaskan sesi siapa yang berakhir.
        (new \App\Models\MlogModel())->saveLog(
            \App\Models\MlogModel::LOGOUT,
            'Single Logout: sesi SSO dicabut di dashboard, sesi lokal diakhiri',
            ['sso_sid' => substr($sid, 0, 8) . '…']
        );

        $slo->forget($sid);
        session()->destroy();

        // Tujuan yang sama dengan cabang non-AJAX di bawah, supaya pengguna yang
        // sesinya berakhir saat menekan tombol mendapat penjelasan yang sama
        // dengan yang sesinya berakhir saat memuat halaman.
        if ($request->isAJAX()) {
            return $this->sessionEndedJson(base_url('login?sso=expired'));
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
