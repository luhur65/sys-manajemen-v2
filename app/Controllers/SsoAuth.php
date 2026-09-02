<?php

namespace App\Controllers;

use App\Libraries\SsoNonceStore;
use App\Libraries\SsoTicket;
use App\Libraries\SsoTicketException;
use App\Libraries\UserStatus;
use App\Models\MlogModel;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Sso as SsoConfig;
use Throwable;

/**
 * Sisi sys-modern dari alur Single Sign-On.
 *
 * Urutan lengkapnya:
 *   1. Pengguna login di dashboard auth-sso lalu menekan kartu SYS.
 *   2. auth-sso meminta tiket ke auth-sso-api (POST /auth/generate-ticket).
 *      Di sanalah hak akses diperiksa: aplikasi terdaftar, `access_menu`
 *      pengguna memuat SYS, dan sesi SSO-nya masih hidup.
 *   3. Browser diarahkan ke sini: GET auth/sso-callback?ticket=<JWT RS256>.
 *   4. callback() memverifikasi tiket secara lokal dengan public key SSO,
 *      membakar `jti` supaya tiket tidak bisa dipakai dua kali, mencocokkan
 *      identitasnya ke satu baris `tbluser`, lalu membuat sesi sys-modern
 *      seperti login password biasa.
 *
 * sys-modern TIDAK PERNAH membuat akun sendiri dari tiket. Tiket membuktikan
 * "orang ini sudah diautentikasi SSO", bukan "orang ini berhak masuk SYS" —
 * yang kedua tetap ditentukan oleh ada tidaknya barisnya di `tbluser` beserta
 * hak ACL-nya. Auto-provisioning akan membuat siapa pun yang punya akun SSO
 * otomatis punya akun SYS, dan itu bukan keputusan yang boleh diambil di sini.
 */
class SsoAuth extends BaseController
{
    private SsoConfig $sso;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->sso = config(SsoConfig::class);
    }

    /**
     * Pintu masuk dari sys-modern ke SSO ("Masuk dengan SSO").
     *
     * auth-sso adalah IdP yang alurnya dimulai dari dashboard-nya sendiri: ia
     * belum punya konsep RelayState / "kembali ke halaman ini". Jadi yang bisa
     * dilakukan di sini adalah mengantar pengguna ke dashboard; dari sana ia
     * menekan kartu SYS dan kembali lagi lewat callback() di bawah.
     */
    public function start(): RedirectResponse
    {
        if (! $this->sso->enabled || trim($this->sso->dashboardUrl) === '') {
            return $this->fail('disabled', 'start: SSO belum diaktifkan atau sso.dashboardUrl kosong.');
        }

        return redirect()->to(rtrim($this->sso->dashboardUrl, '/'));
    }

    /**
     * Menukar tiket SSO menjadi sesi sys-modern.
     */
    public function callback()
    {
        if (! $this->sso->enabled) {
            return $this->fail('disabled', 'callback: SSO belum diaktifkan.');
        }

        $ticket = (string) ($this->request->getGet('ticket') ?? '');

        if (trim($ticket) === '') {
            return $this->fail('invalid', 'callback: parameter ticket kosong.');
        }

        try {
            $claims = (new SsoTicket($this->sso))->verify($ticket);
        } catch (SsoTicketException $e) {
            // Alasan sesungguhnya hanya masuk log. Pesan ke pengguna dibuat
            // seragam supaya respons tidak bisa dipakai menebak bentuk tiket
            // yang akan diterima.
            return $this->fail('invalid', 'callback: tiket ditolak — ' . $e->getMessage());
        }

        $jti = (string) $claims['jti'];

        if (! (new SsoNonceStore())->burn($jti, $this->sso->nonceTtl)) {
            return $this->fail('replay', 'callback: tiket dengan jti ' . substr($jti, 0, 6) . '… sudah pernah dipakai.');
        }

        // Tiket yang tidak membawa klaim yang dikonfigurasi adalah masalah di
        // sisi PENERBIT tiket, bukan di daftar akun SYS. Dulu keduanya jatuh ke
        // kode `unknown` yang sama, sehingga layar menyuruh pengguna menghubungi
        // admin SYS untuk persoalan yang hanya bisa diperbaiki admin SSO.
        //
        // Membedakannya di sini tidak membocorkan apa pun tentang akun yang
        // terdaftar — yang dibedakan adalah bentuk tiketnya, bukan isi tbluser.
        // Pembedaan "nol baris" vs "lebih dari satu baris" tetap TIDAK dilakukan,
        // karena yang itu memang berbicara tentang isi tbluser.
        // Tiket Panel Casting (login-as) diselesaikan lewat jalur yang BERBEDA.
        //
        // Pada tiket biasa, identitas dicari lewat sso.matchClaim. Pada tiket
        // casting itu justru berbahaya: admin memilih SATU BARIS tertentu di
        // panel, dan `sub` adalah id baris itu — sedangkan `email` maupun
        // `karyawanId` adalah data yang bisa bertabrakan antar akun. Mencocokkan
        // lewat klaim itu bisa mendaratkan admin di akun ORANG LAIN, tanpa error
        // apa pun. auth-sso-api menuliskan aturan yang sama di impersonateTicket():
        // `sub` satu-satunya yang otoritatif di sini.
        //
        // Perbandingannya `=== true`, bukan sekadar truthy: nilainya datang dari
        // tiket bertanda tangan, jadi bentuknya sudah pasti — tapi menerima "1"
        // atau "false" sebagai true akan mengubah jalur resolusi identitas, dan
        // itu bukan tempat untuk longgar.
        $impersonated = ($claims['impersonated'] ?? null) === true;

        if ($impersonated) {
            try {
                $user = $this->resolveImpersonationTarget($claims);
            } catch (Throwable $e) {
                log_message('error', 'SSO callback: gagal mencari target casting — ' . $e->getMessage());

                return $this->fail('server', null);
            }

            if ($user === null) {
                return $this->fail('casting', sprintf(
                    'callback: target Panel Casting userpk=%s tidak ada di tbluser.',
                    (string) $claims['sub']
                ));
            }
        } else {
            $claimName  = $this->sso->matchClaim;
            $claimValue = $claims[$claimName] ?? null;

            if (! is_string($claimValue) && ! is_int($claimValue)) {
                return $this->fail('noclaim', sprintf(
                    'callback: tiket tidak membawa klaim "%s" yang diminta sso.matchClaim (sub=%s). '
                    . 'Klaim yang ada pada tiket: %s.',
                    $claimName,
                    (string) $claims['sub'],
                    implode(', ', array_keys($claims))
                ));
            }

            try {
                $user = $this->resolveUser($claims);
            } catch (Throwable $e) {
                log_message('error', 'SSO callback: gagal mencari pengguna — ' . $e->getMessage());

                return $this->fail('server', null);
            }

            if ($user === null) {
                return $this->fail('unknown', sprintf(
                    'callback: tidak ada akun tbluser tunggal untuk %s=%s (sub=%s).',
                    $this->sso->matchColumn,
                    (string) ($claims[$this->sso->matchClaim] ?? '-'),
                    (string) $claims['sub']
                ));
            }
        }

        // Status akun diperiksa SETELAH identitas ketemu, dan berlaku untuk kedua
        // cabang di atas — termasuk Panel Casting. Casting sengaja tidak
        // dikecualikan: kalau akun nonaktif tidak boleh dipakai pemiliknya, ia
        // juga tidak boleh dipakai atas namanya. Selama security.userAktifEnforce
        // masih false, ini hanya mencatat WOULD-DENY.
        if (UserStatus::menolak($user['aktif'] ?? null, (string) $user['userid'], $impersonated ? 'casting' : 'sso')) {
            return $this->fail('nonaktif', sprintf(
                'callback: akun userid=%s nonaktif (tbluser.aktif=%s), jalur=%s.',
                (string) $user['userid'],
                var_export($user['aktif'] ?? null, true),
                $impersonated ? 'casting' : 'sso'
            ));
        }

        // Tiket tanpa klaim `sid` menghasilkan sesi yang TIDAK bisa dijangkau
        // Single Logout: AuthFilter melewatinya begitu saja, dan logout di
        // dashboard SSO tidak akan mengakhiri sesi ini. Itu kegagalan yang
        // diam-diam — pengguna tetap masuk dengan normal dan tidak ada yang
        // terlihat salah sampai berjam-jam kemudian seseorang bertanya kenapa
        // logout di dashboard tidak berpengaruh. Dicatat di sini supaya
        // penyebabnya ada hitam di atas putih sejak menit pertama.
        $sid = isset($claims['sid']) && is_string($claims['sid']) ? trim($claims['sid']) : '';

        if ($sid === '') {
            // Level `error`, bukan `warning`: Config\Logger memakai ambang 4 di
            // production, dan warning (level 5) dibuang diam-diam di sana —
            // justru di environment tempat diagnosis paling dibutuhkan.
            log_message('error', sprintf(
                'SSO callback: tiket untuk userid=%s tidak membawa klaim sid — '
                . 'sesi ini TIDAK akan ikut berakhir saat logout di dashboard SSO. '
                . 'Periksa apakah auth-sso-api berhasil menulis baris sso_sessions saat login.',
                (string) $user['userid']
            ));
        }

        // Cegah session fixation: naik level privilese (anonim -> terautentikasi)
        // harus memakai session ID baru, sama seperti jalur login password dan
        // biometrik. Lihat tests/unit/SessionFixationTest.php.
        session()->regenerate(true);

        $sessionData = [
            SESSION_NAME . 'userpk'    => $user['userpk'],
            SESSION_NAME . 'userid'    => $user['userid'],
            SESSION_NAME . 'username'  => $user['username'],
            SESSION_NAME . 'userlevel' => $user['userlevel'] ?? null,
            // H-05: hash password TIDAK disimpan di sesi.
            SESSION_NAME . 'logged_in' => 1,
            SESSION_NAME . 'cabangid'  => $user['authorityid'] ?? null,
            'username'                 => $user['username'],
            // Penanda asal sesi. `sso_sid` adalah tali ke sesi SSO: AuthFilter
            // memakainya untuk Single Logout, dan Login::logout memakainya
            // untuk mengembalikan pengguna ke dashboard SSO, bukan ke halaman
            // login lokal yang tidak ia pakai.
            SESSION_NAME . 'sso_login' => 1,
            SESSION_NAME . 'sso_sid'   => $sid !== '' ? $sid : null,
        ];

        $aktivitas = 'Login via SSO';

        if ($impersonated) {
            // `actor*` = yang menekan tombol; `approver*` = admin IT yang
            // menyetujui lewat OTP. auth-sso-api sengaja memisahkan keduanya,
            // dan aplikasi tujuan mencatat APPROVER sebagai pelaku perubahan
            // data — jejak "siapa yang benar-benar menekan" tetap disimpan
            // supaya tidak hilang dari audit.
            $aktor    = $this->klaimTeks($claims, 'actorUsername');
            $penyetuju = $this->klaimTeks($claims, 'approverUsername');

            $sessionData[SESSION_NAME . 'sso_impersonated'] = 1;
            $sessionData[SESSION_NAME . 'sso_actor']        = $aktor;
            $sessionData[SESSION_NAME . 'sso_approver']     = $penyetuju;

            $aktivitas = sprintf(
                'Login via SSO (Panel Casting: dijalankan %s, disetujui %s)',
                $aktor !== '' ? $aktor : '-',
                $penyetuju !== '' ? $penyetuju : '-'
            );

            // Sesi atas nama orang lain adalah peristiwa yang harus bisa
            // ditelusuri belakangan. Level `error` dipilih bukan karena ini
            // kesalahan, melainkan karena ambang log production adalah 4:
            // level di bawahnya tidak akan pernah sampai ke berkas. Jejak
            // utamanya tetap di tabel log aktivitas, yang tidak terpengaruh
            // ambang ini.
            log_message('error', sprintf(
                'SSO Panel Casting: sesi dibuka atas nama userid=%s (userpk=%s) '
                . 'oleh %s, disetujui %s. ip=%s',
                (string) $user['userid'],
                (string) $user['userpk'],
                $aktor !== '' ? $aktor : '-',
                $penyetuju !== '' ? $penyetuju : '-',
                $this->request->getIPAddress()
            ));
        }

        session()->set($sessionData);

        try {
            (new MlogModel())->saveLog(MlogModel::LOGIN_SUCCESS, $aktivitas);
        } catch (Throwable $e) {
            // Log aktivitas tidak boleh menggagalkan login yang sudah sah.
            log_message('error', 'SSO callback: gagal menulis log aktivitas — ' . $e->getMessage());
        }

        return redirect()->to(base_url('home'));
    }

    /**
     * Mencari satu baris `tbluser` yang cocok dengan identitas pada tiket.
     *
     * Dicocokkan pada kolom yang ditunjuk sso.matchColumn (default `email`).
     * Nol baris berarti pengguna belum punya akun SYS; lebih dari satu baris
     * berarti tidak jelas sesi siapa yang harus dibuat. Keduanya ditolak — dan
     * keduanya ditolak dengan cara yang sama, supaya jawaban endpoint ini tidak
     * bisa dipakai memetakan email mana yang terdaftar di sistem.
     *
     * @param array<string, mixed> $claims
     *
     * @return array<string, mixed>|null
     */
    private function resolveUser(array $claims): ?array
    {
        $claimName = $this->sso->matchClaim;
        $column    = $this->sso->matchColumn;

        if ($claimName === '' || $column === '') {
            log_message('error', 'SSO: sso.matchClaim / sso.matchColumn belum diisi.');

            return null;
        }

        $value = $claims[$claimName] ?? null;

        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        // Nilai numerik non-positif bukan identitas. Di master karyawan, 0 (juga
        // 9999/99999) adalah penanda "tidak punya karyawan"; auth-sso-api sudah
        // menyaringnya dengan `karyawanId > 0` sebelum menandatangani tiket, jadi
        // ini lapis kedua — tapi lapis yang justru dibutuhkan saat pemetaan
        // dikerjakan bertahap: selama proses itu akan tiba saat hanya TERSISA SATU
        // baris tbluser yang karyawanid-nya masih 0, dan pada saat itu tiket
        // tanpa karyawan akan cocok tepat satu baris — lalu mendarat di akun
        // orang lain. Email tidak pernah numerik, jadi jalur email tak tersentuh.
        if (is_numeric($value) && (float) $value <= 0) {
            return null;
        }

        $db = \Config\Database::connect();

        // Salah ketik pada sso.matchColumn akan jadi SQL error yang membingungkan;
        // diperiksa lebih dulu supaya pesannya menunjuk ke penyebab sebenarnya.
        if (! $db->fieldExists($column, 'tbluser')) {
            log_message('error', 'SSO: kolom tbluser.' . $column . ' tidak ada — periksa sso.matchColumn.');

            return null;
        }

        // Dua baris cukup untuk membedakan "tidak ada", "satu", dan "lebih dari satu".
        $rows = $db->table('tbluser')
            ->where($column, $value)
            ->limit(2)
            ->get()
            ->getResultArray();

        return count($rows) === 1 ? $rows[0] : null;
    }

    /**
     * Mencari baris `tbluser` tujuan sebuah tiket Panel Casting.
     *
     * Dicocokkan pada `userpk` lewat klaim `sub` — id baris yang dipilih admin
     * di panel, diresolve auth-sso-api dari direktori sys-modern sendiri
     * (APP_DIRECTORY_DB.sys: table `tbluser`, kolom id `userpk`). Sengaja TIDAK
     * memakai sso.matchColumn: email dan karyawanid bisa bertabrakan antar akun,
     * dan pada casting itu berarti admin mendarat di akun yang salah.
     *
     * @param array<string, mixed> $claims
     *
     * @return array<string, mixed>|null
     */
    private function resolveImpersonationTarget(array $claims): ?array
    {
        $sub = trim((string) $claims['sub']);

        // `userpk` bertipe int. Nilai non-numerik berarti tiket ini diterbitkan
        // untuk direktori dengan bentuk id lain (DISC memakai GUID) dan tidak
        // pernah dimaksudkan untuk sys-modern — ditolak, bukan dipaksakan.
        if ($sub === '' || ! ctype_digit($sub)) {
            log_message('error', 'SSO Panel Casting: klaim sub "' . $sub . '" bukan userpk yang sah.');

            return null;
        }

        $rows = \Config\Database::connect()
            ->table('tbluser')
            ->where('userpk', (int) $sub)
            ->limit(2)
            ->get()
            ->getResultArray();

        return count($rows) === 1 ? $rows[0] : null;
    }

    /**
     * Membaca satu klaim sebagai teks, atau string kosong kalau bentuknya bukan
     * teks/angka. Dipakai untuk klaim audit (actor/approver) yang hanya ikut
     * dicatat, tidak pernah menentukan identitas.
     *
     * @param array<string, mixed> $claims
     */
    private function klaimTeks(array $claims, string $nama): string
    {
        $nilai = $claims[$nama] ?? null;

        return is_string($nilai) || is_int($nilai) ? trim((string) $nilai) : '';
    }

    /**
     * Mengakhiri percakapan SSO yang gagal: catat alasan teknisnya, kembalikan
     * pengguna ke halaman login dengan penanda yang sudah ditentukan.
     *
     * Penanda dikirim sebagai kode pendek, bukan sebagai pesan bebas, karena
     * view login merender pesannya tanpa escaping — kode dari daftar tertutup
     * membuat parameter URL ini mustahil jadi jalur XSS.
     */
    private function fail(string $code, ?string $logMessage): RedirectResponse
    {
        if ($logMessage !== null) {
            // Level `error`, bukan `warning`. Ambang log production adalah 4,
            // sehingga warning (5) tidak pernah sampai ke berkas — artinya SETIAP
            // alasan penolakan SSO tak terlihat persis di environment tempat ia
            // paling perlu dilacak. Pesan ke pengguna tetap seragam; yang naik
            // levelnya hanya catatan internal.
            log_message('error', 'SSO ' . $logMessage . ' ip=' . $this->request->getIPAddress());
        }

        // M-07: setiap penolakan SSO adalah percobaan otentikasi yang gagal,
        // setara dengan salah password di jalur lokal. Dua di antaranya lebih
        // dari sekadar kesalahan konfigurasi: `invalid` berarti ada tiket dengan
        // tanda tangan yang tidak sah, dan `replay` berarti tiket yang sama
        // dipakai dua kali. Keduanya harus bisa dihitung dan disandingkan dengan
        // percobaan login lain, bukan hanya mengendap di berkas log yang
        // dirotasi.
        (new MlogModel())->saveLog(
            MlogModel::LOGIN_FAILED,
            'Login via SSO ditolak (kode: ' . $code . ')',
            ['sso' => $code],
            $logMessage
        );

        return redirect()->to(base_url('login?sso=' . $code));
    }
}
