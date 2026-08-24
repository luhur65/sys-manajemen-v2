<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Security extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * CSRF Protection Method
     * --------------------------------------------------------------------------
     *
     * Protection Method for Cross Site Request Forgery protection.
     *
     * @var string 'cookie' or 'session'
     */
    public string $csrfProtection = 'cookie';

    /**
     * --------------------------------------------------------------------------
     * CSRF Token Randomization
     * --------------------------------------------------------------------------
     *
     * Randomize the CSRF Token for added security.
     */
    public bool $tokenRandomize = true;

    /**
     * --------------------------------------------------------------------------
     * CSRF Token Name
     * --------------------------------------------------------------------------
     *
     * Token name for Cross Site Request Forgery protection.
     */
    public string $tokenName = 'csrf_test_name';

    /**
     * --------------------------------------------------------------------------
     * CSRF Header Name
     * --------------------------------------------------------------------------
     *
     * Header name for Cross Site Request Forgery protection.
     */
    public string $headerName = 'X-CSRF-TOKEN';

    /**
     * --------------------------------------------------------------------------
     * CSRF Cookie Name
     * --------------------------------------------------------------------------
     *
     * Cookie name for Cross Site Request Forgery protection.
     */
    public string $cookieName = 'csrf_cookie_name';

    /**
     * --------------------------------------------------------------------------
     * CSRF Expires
     * --------------------------------------------------------------------------
     *
     * Expiration time for Cross Site Request Forgery protection cookie.
     *
     * Defaults to two hours (in seconds).
     *
     * CATATAN KEAMANAN (C-03):
     * 0 = cookie sesi browser (hilang saat browser ditutup).
     *
     * Sebelumnya 7200. Masalahnya cookie CSRF TIDAK diperpanjang setiap request
     * (Security::__construct hanya membuat hash baru kalau cookienya sudah tidak
     * ada), sementara sesi aplikasi ikut diperpanjang selama user aktif. Jadi
     * dengan 7200, user yang bekerja terus-menerus lebih dari 2 jam akan tetap
     * login tetapi token di <meta> halamannya sudah basi -> seluruh AJAX POST
     * mendadak 403 sampai halaman di-refresh manual.
     *
     * Dengan 0, token berlaku selama tab/browser masih hidup — selalu sama
     * umurnya dengan halaman yang membawanya. Ini tidak melemahkan proteksi:
     * token CSRF berfungsi sebagai bukti asal-request, bukan sebagai kredensial.
     */
    public int $expires = 0;

    /**
     * --------------------------------------------------------------------------
     * CSRF Regenerate
     * --------------------------------------------------------------------------
     *
     * Regenerate CSRF Token on every submission.
     *
     * CATATAN KEAMANAN (C-03):
     * Sengaja FALSE. Cookie CSRF bersifat httpOnly (Config\Cookie::$httponly),
     * jadi JavaScript tidak bisa membaca ulang token yang baru. Token dikirim ke
     * browser satu kali lewat <meta name="csrf-token"> saat halaman dirender.
     * Kalau setelan ini TRUE, token berputar setiap POST sukses sementara nilai
     * di meta tag tetap yang lama -> seluruh AJAX berikutnya (grid, crud, lookup)
     * langsung kena 403. Token per-sesi seperti ini tetap perlindungan CSRF yang
     * sah dan merupakan pola default Django/Rails/Laravel; masa berlakunya
     * dibatasi $expires di bawah.
     */
    public bool $regenerate = false;

    /**
     * --------------------------------------------------------------------------
     * CSRF Redirect
     * --------------------------------------------------------------------------
     *
     * Redirect to previous page with error on failure.
     *
     * @see https://codeigniter4.github.io/userguide/libraries/security.html#redirection-on-failure
     */
    public bool $redirect = (ENVIRONMENT === 'production');
}
