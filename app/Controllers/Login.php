<?php

namespace App\Controllers;

use App\Libraries\LoginThrottle;
use App\Libraries\UserStatus;
use App\Models\MlogModel;
use App\Models\MloginModel;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

// Migrated from CI3: application/controllers/Login.php

class Login extends BaseController
{
    protected MlogModel $mlogModel;
    protected MloginModel $mloginModel;
    protected LoginThrottle $throttle;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->mloginModel = new MloginModel();
        $this->mlogModel = new MlogModel();
        $this->throttle = new LoginThrottle();
    }

    /**
     * H-04: satu tempat untuk mencatat penolakan karena rate limit, supaya
     * lonjakan percobaan terlihat di writable/logs.
     *
     * M-07: penolakan ini juga masuk ke `log_activity`. Berkas log hanya
     * bertahan sampai rotasi berikutnya dan tidak bisa disandingkan dengan
     * riwayat login pengguna; tabel log bisa.
     *
     * `$bucket` adalah nama ember throttle, yang tidak selalu sama dengan
     * `$action` yang muncul di pesan: `unlock` sengaja berbagi ember dengan
     * `login`, dan `forgot-password` memakai ember `forgot`.
     */
    private function logThrottled(string $action, string $bucket, string $account, int $wait): void
    {
        log_message('warning', sprintf(
            'Rate limit %s: account=%s ip=%s tunggu=%dd',
            $action,
            $account !== '' ? $account : '-',
            $this->request->getIPAddress(),
            $wait
        ));

        // Satu baris per jendela hukuman, bukan satu per request — lihat
        // LoginThrottle::announceOnce(). Berkas log di atas sengaja tetap
        // ditulis tiap kali: berkas itu dirotasi, tabel log tidak.
        if (! $this->throttle->announceOnce($bucket, $this->request->getIPAddress(), $account)) {
            return;
        }

        $this->mlogModel->saveLog(
            MlogModel::LOGIN_BLOCKED,
            sprintf('Percobaan %s ditolak rate limit (tunggu %d detik)', $action, $wait),
            ['userid' => $account !== '' ? $account : '-', 'aksi' => $action]
        );
    }

    /** Ubah detik menjadi keterangan tunggu yang enak dibaca. */
    private function waitText(int $seconds): string
    {
        return $seconds >= 60
            ? 'sekitar ' . (int) ceil($seconds / 60) . ' menit'
            : $seconds . ' detik';
    }

    /**
     * M-02: respons tunggal untuk seluruh hasil `forgotPassword()`.
     *
     * Pesannya sengaja tidak memastikan apa pun tentang username yang dikirim.
     * Semua keadaan — username tidak ada, akun tanpa email/WhatsApp, email
     * terkirim, sampai SMTP gagal — memakai status, isi, dan bentuk yang sama,
     * sehingga endpoint ini tidak bisa dipakai memeriksa keberadaan akun.
     *
     * Yang belum seragam: waktu respons. Permintaan yang benar-benar mengirim
     * email selesai lebih lama daripada yang berhenti di username tak dikenal.
     * Menutupnya menuntut antrean pengiriman terpisah; sementara ini laju
     * pengukurannya ditahan rate limit `forgot` (H-04).
     */
    private function forgotPasswordResponse(): ResponseInterface
    {
        return $this->response->setJSON([
            'status'    => 200,
            'message'   => 'Jika username terdaftar, link reset akan dikirim ke email atau WhatsApp yang tercatat pada akun tersebut.',
            'csrfToken' => csrf_hash(),
        ]);
    }

    public function index()
    {
        if (session()->has(SESSION_NAME . 'logged_in')) {
            return redirect()->to(base_url('home'));
        }
        
        $message = session()->getFlashdata(SESSION_NAME . 'message') ?: $this->ssoMessage();
        $ssoOnly = $this->ssoOnlyEntryPoint();

        // Mode SSO-only: halaman ini tidak punya apa pun untuk ditawarkan —
        // form lokalnya disembunyikan dan yang tersisa hanya satu tombol menuju
        // dashboard SSO. Jadi antar langsung ke sana, tanpa perantara.
        //
        // KECUALI kalau ada pesan yang perlu dibaca. Halaman ini satu-satunya
        // tempat kegagalan SSO bisa muncul ("akun belum terdaftar di SYS",
        // "tiket sudah dipakai", "sesi SSO berakhir"). Kalau ia dilewati juga
        // saat membawa pesan, pengguna yang gagal akan terlempar kembali ke
        // dashboard, menekan kartu SYS lagi, gagal lagi — berputar tanpa pernah
        // tahu apa yang salah. Pesannya dibiarkan tampil, dengan tombol SSO
        // tetap ada sebagai jalan lanjutnya.
        if ($message === null && $ssoOnly !== null) {
            return redirect()->to($ssoOnly);
        }

        $time = microtime();
        $time = explode(' ', $time);
        $time = $time[1] + $time[0];

        $data['start'] = $time;
        $data['versi'] = CONS_VERSI;
        $data['error'] = $message;
        $data['sso']   = config(\Config\Sso::class);

        return view('login', $data);
    }

    /**
     * Alamat dashboard SSO bila halaman login lokal sudah tidak berguna, atau
     * null bila halaman ini masih perlu ditampilkan.
     *
     * Mengembalikan null saat login lokal masih hidup, dan juga saat SSO belum
     * dikonfigurasi. Yang kedua penting: kalau login lokal dimatikan sementara
     * SSO belum siap, tidak ada satu pun jalan masuk — dan pengguna harus
     * melihat halaman ini beserta pesan "tidak ada metode login yang aktif",
     * bukan diarahkan ke alamat kosong.
     */
    private function ssoOnlyEntryPoint(): ?string
    {
        if (! $this->passwordLoginDisabled()) {
            return null;
        }

        $sso = config(\Config\Sso::class);

        if (! $sso->enabled) {
            return null;
        }

        $url = rtrim(trim($sso->dashboardUrl), '/');

        return $url !== '' ? $url : null;
    }

    /**
     * Pesan untuk kegagalan SSO, dipilih dari kode pada ?sso=.
     *
     * Alur SSO berakhir dengan redirect ke halaman ini, dan sesi lokal saat itu
     * belum tentu ada (sesi lama baru saja dihancurkan), jadi flashdata bukan
     * jalur yang bisa diandalkan — penandanya ikut di URL. Karena view login
     * merender $error tanpa escaping, yang lewat URL hanya KODE; teksnya
     * diambil dari daftar tertutup di bawah, tidak pernah dari input.
     */
    private function ssoMessage(): ?string
    {
        $messages = [
            'disabled' => 'Login SSO belum diaktifkan pada aplikasi ini.',
            'invalid'  => 'Tiket SSO tidak valid atau sudah kedaluwarsa. Silakan buka kembali dari dashboard SSO.',
            'replay'   => 'Tiket SSO sudah pernah dipakai. Silakan buka kembali dari dashboard SSO.',
            'unknown'  => 'Akun Anda belum terdaftar di SYS. Harap hubungi admin SYS untuk dibuatkan akun.',
            // Beda pemilik masalah dari 'unknown': tiketnya yang kurang, bukan
            // akunnya yang belum ada. Mengarahkan ke admin SYS di kasus ini
            // hanya membuang waktu semua pihak.
            'noclaim'  => 'Tiket SSO tidak membawa identitas yang dibutuhkan aplikasi ini. Harap hubungi admin SSO.',
            // Panel Casting: yang membaca pesan ini adalah admin, bukan pemilik
            // akun. "Belum terdaftar" akan menyesatkan — barisnya memang dipilih
            // dari daftar, jadi kalau hilang berarti daftarnya yang basi.
            'casting'  => 'Target Panel Casting tidak ditemukan di SYS. Daftar user mungkin sudah berubah — muat ulang panel lalu coba lagi.',
            'nonaktif' => 'Akun Anda sudah tidak aktif. Silakan hubungi administrator.',
            'expired'  => 'Sesi SSO Anda telah berakhir. Silakan login kembali.',
            'server'   => 'Terjadi kesalahan saat memproses login SSO. Coba lagi nanti.',
            'onlysso'  => 'Login userid/password sudah dinonaktifkan. Silakan masuk lewat SSO.',
        ];

        $code = (string) ($this->request->getGet('sso') ?? '');

        return $messages[$code] ?? null;
    }

    /**
     * Apakah login lokal (userid/password + reset password) sedang dimatikan?
     *
     * Dipanggil di SETIAP endpoint jalur itu, bukan hanya di view. Menyembunyikan
     * form di halaman login tidak menutup apa pun — POST langsung ke
     * login/proses tetap akan diproses kalau endpointnya sendiri tidak menolak.
     */
    private function passwordLoginDisabled(): bool
    {
        return ! config(\Config\Sso::class)->passwordLoginEnabled;
    }

    public function proses()
    {
        if ($this->passwordLoginDisabled()) {
            log_message('warning', sprintf(
                'Login lokal ditolak (sso.passwordLoginEnabled=false): userid=%s ip=%s',
                (string) $this->request->getPost('userid'),
                $this->request->getIPAddress()
            ));

            return redirect()->to(base_url('login?sso=onlysso'));
        }

        // Validate input fields first
        if (!$this->validate([
            'userid'   => 'required',
            'password' => 'required',
        ], [
            'userid'   => ['required' => 'User ID harus diisi!'],
            'password' => ['required' => 'Password harus diisi!'],
        ])) {
            return redirect()->to(base_url('login'))->withInput()->with('errors', $this->validator->getErrors());
        }

        $time = microtime();
        $time = explode(' ', $time);
        $time = $time[1] + $time[0];
        $data['start'] = $time;
        $data['versi'] = CONS_VERSI;

        $this->response->setHeader("Cache-Control", "no-cache, must-revalidate");
        $userid = $this->request->getPost('userid');
        $password = (string)$this->request->getPost('password');

        // H-04: tolak lebih dulu kalau jatah percobaan sudah habis. Pemeriksaan
        // ini tidak mengurangi jatah — yang mengurangi hanya kegagalan di bawah,
        // sehingga user yang selalu berhasil login tidak pernah kena batas.
        $wait = $this->throttle->retryAfter('login', $this->request->getIPAddress(), (string) $userid);

        if ($wait !== null) {
            $this->logThrottled('login', 'login', (string) $userid, $wait);

            return redirect()->to(base_url('login'))->with(
                SESSION_NAME . 'message',
                'Terlalu banyak percobaan login gagal. Silakan coba lagi dalam ' . $this->waitText($wait) . '.'
            );
        }

        $cek = $this->mloginModel->login($userid, $password);

        if ($cek != "" && $cek->getNumRows() > 0) {
            $row = $cek->getRow();

            // Password benar, tapi akunnya mungkin sudah dinonaktifkan. Diperiksa
            // SEBELUM throttle dibersihkan: akun nonaktif tidak boleh jadi cara
            // mengosongkan hitungan percobaan. Selama security.userAktifEnforce
            // masih false, ini hanya mencatat WOULD-DENY dan login diteruskan.
            if (UserStatus::menolak($row->aktif ?? null, (string) $userid, 'password')) {
                $this->mlogModel->saveLog(
                    MlogModel::LOGIN_FAILED,
                    'Login ditolak: akun nonaktif',
                    ['userid' => (string) $userid]
                );

                return redirect()->to(base_url('login'))
                    ->with(SESSION_NAME . 'message', UserStatus::pesan());
            }

            // Login berhasil: hapus hukuman pada akun ini. Ember per-IP sengaja
            // dibiarkan, supaya satu tebakan yang kebetulan benar tidak menghapus
            // jejak percobaan lain dari IP yang sama.
            $this->throttle->clear('login', (string) $userid);

            // Cegah session fixation: naik level privilese (anonim -> terautentikasi)
            // harus memakai session ID baru, dan record sesi pra-login dihancurkan
            // supaya ID yang mungkin sudah ditanam penyerang tidak lagi berlaku.
            session()->regenerate(true);

            $sessionData = [
                SESSION_NAME . 'userpk' => $row->userpk,
                SESSION_NAME . 'userid' => $row->userid,
                SESSION_NAME . 'username' => $row->username,
                SESSION_NAME . 'userlevel' => $row->userlevel,
                // H-05: hash password TIDAK disimpan di sesi. Session driver adalah
                // FileHandler, jadi apa pun yang masuk ke sini tertulis ke disk di
                // writable/session dan bertahan sampai garbage collection.
                SESSION_NAME . 'logged_in' => 1,
                SESSION_NAME . 'cabangid' => $row->authorityid,
                'username' => $row->username // For compatibility with some controllers using session()->get('username')
            ];
            session()->set($sessionData);

            $this->mlogModel->saveLog(MlogModel::LOGIN_SUCCESS, 'Login berhasil (userid & password)');
            return redirect()->to(base_url("home"));
        }

        $this->throttle->hit('login', $this->request->getIPAddress(), (string) $userid);

        // M-07: tanpa baris ini, serangan tebak-password tidak meninggalkan
        // jejak apa pun di database — yang tercatat hanya percobaan yang
        // kebetulan berhasil.
        $this->mlogModel->saveLog(
            MlogModel::LOGIN_FAILED,
            'Kombinasi userid/password salah',
            ['userid' => (string) $userid]
        );

        return redirect()->to(base_url('login'))
            ->with(SESSION_NAME . 'message', 'Kombinasi userid Atau Password Salah');
    }

    public function logout()
    {
        $this->response->setHeader("Cache-Control", "no-cache, must-revalidate");

        // Yang diakhiri di sini HANYA sesi sys-modern. Mencabut sesi SSO-nya
        // (POST /auth/session/revoke ke auth-sso-api) akan melogout pengguna
        // dari HR dan CRM sekaligus — itu wewenang dashboard SSO, bukan satu
        // aplikasi anggota.
        $sso     = config(\Config\Sso::class);
        $fromSso = (bool) session()->get(SESSION_NAME . 'sso_login');
        $sid     = (string) (session()->get(SESSION_NAME . 'sso_sid') ?? '');

        if ($sid !== '') {
            (new \App\Libraries\SsoSlo($sso))->forget($sid);
        }

        // M-07: harus ditulis SEBELUM sesi dihancurkan — sesudahnya tidak ada
        // lagi yang bisa menjawab siapa yang keluar. Akhir sesi adalah batas
        // atas rentang waktu yang bisa dipertanggungjawabkan seorang pengguna.
        $this->mlogModel->saveLog(
            MlogModel::LOGOUT,
            $fromSso ? 'Logout (sesi berasal dari SSO)' : 'Logout'
        );

        session()->destroy();

        // Pengguna SSO dikembalikan ke dashboard SSO, bukan ke halaman login
        // lokal yang tidak pernah ia pakai.
        if ($fromSso && $sso->enabled && trim($sso->dashboardUrl) !== '') {
            return redirect()->to(rtrim($sso->dashboardUrl, '/'));
        }

        return redirect()->to(base_url("login"));
    }

    public function unlock()
    {
        // Lock screen memverifikasi password tbluser yang sama dengan halaman
        // login, jadi ia ikut mati bersama login lokal. Dijawab dengan penanda
        // `ssoOnly` supaya lockscreen.js mengantar pengguna ke SSO alih-alih
        // menghitungnya sebagai percobaan gagal lalu memaksa logout.
        if ($this->passwordLoginDisabled()) {
            return $this->response->setStatusCode(403)->setJSON([
                'success'  => false,
                'ssoOnly'  => true,
                'redirect' => base_url('sso/login'),
                'message'  => 'Login userid/password sudah dinonaktifkan. Membuka kunci lewat SSO...',
            ]);
        }

        $userid = session()->get(SESSION_NAME . 'userid');

        // Auto-relogin: Gunakan userid dari localStorage browser jika sesi server expired
        if (!$userid) {
            $userid = $this->request->getPost('userid');
        }

        if (!$userid) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Sesi telah berakhir permanen. Silakan muat ulang halaman.']);
        }
        
        // H-04: unlock memverifikasi password yang sama dengan halaman login,
        // jadi sengaja memakai ember yang sama ('login'). Percobaan lewat lock
        // screen dan lewat halaman login dihitung bersama-sama.
        $wait = $this->throttle->retryAfter('login', $this->request->getIPAddress(), (string) $userid);

        if ($wait !== null) {
            $this->logThrottled('unlock', 'login', (string) $userid, $wait);

            return $this->response->setStatusCode(429)->setJSON([
                'success' => false,
                'message' => 'Terlalu banyak percobaan. Silakan coba lagi dalam ' . $this->waitText($wait) . '.',
            ]);
        }

        $password = (string)$this->request->getPost('password');
        $cek = $this->mloginModel->login($userid, $password);
        
        if ($cek != "" && $cek->getNumRows() > 0) {
            $this->throttle->clear('login', (string) $userid);

            // Rebuild session if it was expired
            $sesiDibangunUlang = !session()->has(SESSION_NAME . 'logged_in');

            if ($sesiDibangunUlang) {
                $row = $cek->getRow();

                // Sesi dibangun ulang dari kondisi anonim -> perlakukan seperti login baru.
                session()->regenerate(true);

                $sessionData = [
                    SESSION_NAME . 'userpk' => $row->userpk,
                    SESSION_NAME . 'userid' => $row->userid,
                    SESSION_NAME . 'username' => $row->username,
                    SESSION_NAME . 'userlevel' => $row->userlevel,
                    // H-05: hash password TIDAK disimpan di sesi.
                    SESSION_NAME . 'logged_in' => 1,
                    SESSION_NAME . 'cabangid' => $row->authorityid,
                    'username' => $row->username
                ];
                session()->set($sessionData);
            }

            // M-07: membuka lock screen adalah pembuktian identitas ulang, jadi
            // dicatat seperti login. Termasuk saat sesi server masih hidup —
            // jalur itu sebelumnya tidak meninggalkan jejak sama sekali.
            $this->mlogModel->saveLog(
                MlogModel::UNLOCK_SUCCESS,
                $sesiDibangunUlang
                    ? 'Lock screen dibuka; sesi server sudah kedaluwarsa dan dibangun ulang'
                    : 'Lock screen dibuka'
            );

            return $this->response->setJSON(['success' => true]);
        }

        $this->throttle->hit('login', $this->request->getIPAddress(), (string) $userid);

        $this->mlogModel->saveLog(
            MlogModel::UNLOCK_FAILED,
            'Password salah saat membuka lock screen',
            ['userid' => (string) $userid]
        );

        return $this->response->setJSON(['success' => false, 'message' => 'Password salah']);
    }

    public function forgotPassword()
    {
        // Reset password lokal tidak ada gunanya kalau password lokal tidak bisa
        // dipakai masuk — dan membiarkannya hidup berarti endpoint yang mengirim
        // email tetap terbuka untuk disalahgunakan.
        if ($this->passwordLoginDisabled()) {
            return $this->response->setStatusCode(403)->setJSON([
                'errors'    => ['user' => 'Reset password dinonaktifkan. Silakan masuk lewat SSO.'],
                'error'     => 'Reset password dinonaktifkan. Silakan masuk lewat SSO.',
                'csrfToken' => csrf_hash(),
            ]);
        }

        $username = $this->request->getPost('user');
        $check = $this->request->getPost('check');

        // H-04: setiap link reset yang terkirim memakai kuota SMTP Brevo
        // perusahaan, jadi endpoint ini dibatasi walaupun requestnya "berhasil".
        // Pemeriksaan menutup kedua mode (validasi maupun kirim) supaya setelah
        // batas tercapai endpoint ini benar-benar diam. Yang mengurangi jatah
        // adalah setiap permintaan kirim, terdaftar atau tidak (lihat hit() di
        // bawah) — sejak M-02 jatahnya tidak boleh ikut menunjukkan akun mana
        // yang ada.
        $wait = $this->throttle->retryAfter('forgot', $this->request->getIPAddress(), (string) $username);

        if ($wait !== null) {
            $this->logThrottled('forgot-password', 'forgot', (string) $username, $wait);

            $message = 'Terlalu banyak permintaan reset password. Silakan coba lagi dalam ' . $this->waitText($wait) . '.';

            return $this->response->setStatusCode(429)->setJSON([
                'errors'    => ['user' => $message],
                'error'     => $message,
                'csrfToken' => csrf_hash(),
            ]);
        }

        // M-02: sejak titik ini pemanggil selalu menerima respons yang sama,
        // apa pun hasilnya. Respons yang berbeda-beda di sinilah yang dulu bisa
        // dipakai menyusun daftar username valid untuk credential stuffing.
        if ($check) {
            // Tahap "cek dulu" milik klien lama. Tidak ada lagi yang bisa
            // divalidasi tanpa membocorkan sesuatu, jadi dijawab seragam tanpa
            // mengirim apa pun; klien baru cukup memanggil endpoint ini sekali.
            return $this->forgotPasswordResponse();
        }

        // Jatah dipotong untuk SETIAP permintaan yang sampai di sini, bukan
        // hanya yang benar-benar mengirim email. Kalau hanya username terdaftar
        // yang memotong jatah, penyerang cukup memperhatikan kapan 429 muncul
        // untuk tahu akun mana yang ada — kebocoran yang sama lewat pintu lain.
        // Tetap dipotong sebelum SMTP dipanggil, supaya pengiriman yang gagal
        // pun ikut terhitung dan endpoint ini tidak bisa dipakai memukul server
        // SMTP berulang kali.
        $this->throttle->hit('forgot', $this->request->getIPAddress(), (string) $username);

        $muserModel = new \App\Models\MuserModel();
        // Use asArray to handle potential SQL Server column case sensitivity
        $userRow = $muserModel->asArray()->where('userid', $username)->first();

        if (!$userRow) {
            log_message('info', 'Reset password diminta untuk username tak dikenal: {user} (ip: {ip})', [
                'user' => (string) $username,
                'ip'   => $this->request->getIPAddress(),
            ]);

            return $this->forgotPasswordResponse();
        }

        // Lowercase all keys to avoid issues if they created columns like 'Email' or 'EMAIL'
        $userRow = array_change_key_case($userRow, CASE_LOWER);

        $email = $userRow['email'] ?? '';
        $nowhatsapp = $userRow['nowhatsapp'] ?? '';

        if (empty($email) && empty($nowhatsapp)) {
            // Keadaan ini dulu diberitahukan ke pemanggil — sekaligus memastikan
            // akunnya ada. Sekarang hanya terlihat oleh admin, lewat log.
            log_message('warning', 'Reset password: akun {user} tidak memiliki email maupun nomor WhatsApp.', [
                'user' => (string) $username,
            ]);

            return $this->forgotPasswordResponse();
        }

        $resetModel = new \App\Models\PasswordResetModel();
        $resetModel->where('username', $username)->delete();

        $rawToken = substr(bin2hex(random_bytes(32)), 0, 11);
        $hashedToken = hash('sha256', $rawToken);

        $resetModel->insert([
            'username' => $username,
            'token' => $hashedToken,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
        ]);

        $datetime = date('d-m-Y-H-i-s');
        $resetLink = base_url('reset/' . urlencode($username) . "-{$datetime}-{$rawToken}");

        // Send Email
        if (!empty($email)) {
            $emailSent = false;

            try {
                $emailService = \Config\Services::email();
                $emailService->setMailType('html');
                $emailService->setTo($email);
                $emailService->setSubject(config('email')->subjectResetPassword);

                $htmlMessage = view('auth/email_reset_password', [
                    'userName' => $username,
                    'resetLink' => $resetLink
                ]);
                $emailService->setMessage($htmlMessage);

                $emailSent = $emailService->send();

                if (!$emailSent) {
                    // Detail teknis SMTP hanya masuk ke log, tidak pernah ditampilkan ke user
                    log_message('error', 'Reset password: gagal mengirim email ke {email}. Debug: {debug}', [
                        'email' => $email,
                        'debug' => $emailService->printDebugger(['headers'])
                    ]);
                }
            } catch (\Throwable $e) {
                log_message('error', 'Reset password: exception saat mengirim email ke {email}. {message}', [
                    'email' => $email,
                    'message' => $e->getMessage()
                ]);
            }

            if (!$emailSent) {
                // Token yang sudah terlanjur dibuat dibuang supaya tidak menggantung
                $resetModel->where('username', $username)->delete();

                // M-02: kegagalan SMTP hanya mungkin terjadi pada akun yang ADA
                // dan punya email, jadi pesan khusus di sini sama saja dengan
                // mengumumkan akun itu terdaftar. Kegagalannya sudah dicatat ke
                // log di atas untuk ditindaklanjuti Admin IT.
                return $this->forgotPasswordResponse();
            }
        }

        // Send WA
        if (!empty($nowhatsapp)) {
            // TODO: Implement WA API Call Here
        }

        return $this->forgotPasswordResponse();
    }

    public function resetPasswordForm()
    {
        if ($this->passwordLoginDisabled()) {
            return redirect()->to(base_url('login?sso=onlysso'));
        }

        $token = $this->request->getGet('token');
        $user = $this->request->getGet('user');

        if (!$token || !$user) {
            return redirect()->to('login')->with(SESSION_NAME . 'message', 'Link tidak valid.');
        }

        $resetModel = new \App\Models\PasswordResetModel();
        $row = $resetModel->where('username', $user)->first();

        if (!$row || !hash_equals($row->token, hash('sha256', $token))) {
            return redirect()->to('login')->with(SESSION_NAME . 'message', 'Link reset tidak valid atau sudah tidak berlaku.');
        }

        if (strtotime($row->expires_at) < time()) {
            $resetModel->where('username', $user)->delete();
            return redirect()->to('login')->with(SESSION_NAME . 'message', 'Link reset sudah kedaluwarsa.');
        }

        $siteConfig = config('Site');
        return view('auth/reset_password', [
            'token' => $token,
            'user' => $user,
            'siteConfig' => $siteConfig
        ]);
    }

    public function resetPasswordSubmit()
    {
        if ($this->passwordLoginDisabled()) {
            return redirect()->to(base_url('login?sso=onlysso'));
        }

        $token = $this->request->getPost('token');
        $user = $this->request->getPost('user');
        $password = $this->request->getPost('password');

        if (!$this->validate(['password' => 'required|min_length[5]'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $resetModel = new \App\Models\PasswordResetModel();
        $row = $resetModel->where('username', $user)->first();

        if (!$row || !hash_equals($row->token, hash('sha256', $token))) {
            return redirect()->to('login')->with(SESSION_NAME . 'message', 'Link reset tidak valid atau sudah tidak berlaku.');
        }

        if (strtotime($row->expires_at) < time()) {
            $resetModel->where('username', $user)->delete();
            return redirect()->to('login')->with(SESSION_NAME . 'message', 'Link reset sudah kedaluwarsa.');
        }

        $muserModel = new \App\Models\MuserModel();
        $userRow = $muserModel->where('userid', $user)->first();
        
        if ($userRow) {
            $muserModel->update($userRow->userpk, [
                'password' => password_hash($password, PASSWORD_BCRYPT)
            ]);
        }

        $resetModel->where('username', $user)->delete();

        return redirect()->to('login')->with(SESSION_NAME . 'message', 'Password berhasil direset. Silakan login dengan password baru.');
    }

    public function resetPasswordCustom($param)
    {
        if ($this->passwordLoginDisabled()) {
            return redirect()->to(base_url('login?sso=onlysso'));
        }

        $param = urldecode($param);

        if (preg_match('/^(.*)-(\d{2}-\d{2}-\d{4}-\d{2}-\d{2}-\d{2})-([a-f0-9]+)$/i', $param, $matches)) {
            $user = $matches[1];
            $token = $matches[3];

            $resetModel = new \App\Models\PasswordResetModel();
            $row = $resetModel->where('username', $user)->first();

            if (!$row) {
                return redirect()->to('login')->with(SESSION_NAME . 'message', 'Link reset tidak valid atau sudah tidak berlaku.');
            }

            $hashedInputToken = hash('sha256', $token);
            if (!hash_equals($row->token, $hashedInputToken)) {
                return redirect()->to('login')->with(SESSION_NAME . 'message', 'Link reset tidak valid atau sudah tidak berlaku.');
            }

            if (strtotime($row->expires_at) < time()) {
                $resetModel->where('username', $user)->delete();
                return redirect()->to('login')->with(SESSION_NAME . 'message', 'Link reset sudah kedaluwarsa.');
            }

            $siteConfig = config('Site');
            return view('auth/reset_password', [
                'token' => $token,
                'user' => $user,
                'siteConfig' => $siteConfig
            ]);
        }

        throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    }
}
