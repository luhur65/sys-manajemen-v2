<?php

namespace App\Filters;

use App\Libraries\SsoExit;
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
            // Sesi yang sudah tidak ada tidak bisa ditanya lagi apakah ia lahir
            // dari SSO, jadi tujuannya ditentukan sepenuhnya oleh
            // sso.logoutToSso. Halaman /login sendiri tidak melewati filter ini
            // — ia ada di daftar `except` — sehingga login lokal tetap bisa
            // dibuka langsung sekalipun setiap jalan lain berujung di SSO.
            return $this->sessionEnded($request, SsoExit::target());
        }

        return $this->enforceSingleLogout($request);
    }

    /**
     * Satu jawaban untuk setiap sesi yang berakhir, dalam dua rupa: halaman
     * berpindah untuk navigasi biasa, 401 ber-JSON untuk AJAX. Alamat tujuannya
     * sama persis — yang memutuskan alamat itu App\Libraries\SsoExit, bukan
     * masing-masing cabang di sini.
     */
    private function sessionEnded(RequestInterface $request, string $target)
    {
        return $request->isAJAX()
            ? $this->sessionEndedJson($target)
            : redirect()->to($target);
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

        // Alasannya dititipkan lewat query string, bukan flashdata: sesi baru
        // saja dihancurkan, jadi tidak ada tempat menyimpan flashdata. Login
        // controller menerjemahkan kode ini jadi kalimat (lihat ssoMessage()).
        // Jalur AJAX memakai tujuan yang sama, supaya pengguna yang sesinya
        // berakhir saat menekan tombol mendapat penjelasan yang sama dengan
        // yang sesinya berakhir saat memuat halaman.
        //
        // `fromSso` sengaja dibiarkan false walaupun sesi ini jelas lahir dari
        // SSO — `sso_sid` tidak akan ada kalau bukan. Perilaku lama jalur ini
        // memang mendaratkan pengguna di halaman login beserta sebabnya, dan
        // itu tidak boleh berubah selama sso.logoutToSso masih mati. Saklar
        // itulah satu-satunya yang memindahkan tujuannya ke dashboard SSO.
        return $this->sessionEnded($request, SsoExit::target(false, 'expired'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do something here
    }
}
