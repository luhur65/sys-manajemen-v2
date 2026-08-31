<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class App extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Base Site URL
     * --------------------------------------------------------------------------
     *
     * URL to your CodeIgniter root. Typically, this will be your base URL,
     * WITH a trailing slash:
     *
     * E.g., http://example.com/
     */
    public string $baseURL = 'http://localhost/';

    /**
     * Allowed Hostnames in the Site URL other than the hostname in the baseURL.
     * If you want to accept multiple Hostnames, set this.
     *
     * E.g.,
     * When your site URL ($baseURL) is 'http://example.com/', and your site
     * also accepts 'http://media.example.com/' and 'http://accounts.example.com/':
     *     ['media.example.com', 'accounts.example.com']
     *
     * M-05: daftar ini TIDAK ditulis tangan di sini — `__construct()` mengisinya
     * dari `app.allowedHostnames` di .env (atau {@see self::HOST_BAWAAN} kalau
     * .env tidak menyebutkannya), lalu memakainya untuk memutuskan apakah header
     * `Host` boleh dipakai membangun `$baseURL`. Framework membaca properti yang
     * sama lewat `SiteURIFactory::getValidHost()`, jadi satu daftar berlaku untuk
     * keduanya.
     *
     * @var list<string>
     */
    public array $allowedHostnames = [];

    /**
     * Host yang dipakai kalau .env tidak menyebutkan `app.allowedHostnames`.
     *
     * Isinya ketiga environment yang sudah berjalan (lihat dokumentasi_sso.md)
     * plus host pengembangan lokal. Sengaja berisi — bukan kosong — supaya
     * penutupan M-05 tidak menuntut perubahan .env di setiap server lebih dulu:
     * server yang sudah jalan tetap jalan, sementara `Host` di luar daftar ini
     * tidak lagi bisa menyetir url yang dikirim aplikasi.
     */
    private const HOST_BAWAAN = [
        'sys.transporindo.com',
        'staging.transporindo.com',
        'localhost',
        '127.0.0.1',
    ];

    /**
     * --------------------------------------------------------------------------
     * Index File
     * --------------------------------------------------------------------------
     *
     * Typically, this will be your `index.php` file, unless you've renamed it to
     * something else. If you have configured your web server to remove this file
     * from your site URIs, set this variable to an empty string.
     */
    public string $indexPage = '';

    /**
     * --------------------------------------------------------------------------
     * URI PROTOCOL
     * --------------------------------------------------------------------------
     *
     * This item determines which server global should be used to retrieve the
     * URI string. The default setting of 'REQUEST_URI' works for most servers.
     * If your links do not seem to work, try one of the other delicious flavors:
     *
     *  'REQUEST_URI': Uses $_SERVER['REQUEST_URI']
     * 'QUERY_STRING': Uses $_SERVER['QUERY_STRING']
     *    'PATH_INFO': Uses $_SERVER['PATH_INFO']
     *
     * WARNING: If you set this to 'PATH_INFO', URIs will always be URL-decoded!
     */
    public string $uriProtocol = 'REQUEST_URI';

    /*
    |--------------------------------------------------------------------------
    | Allowed URL Characters
    |--------------------------------------------------------------------------
    |
    | This lets you specify which characters are permitted within your URLs.
    | When someone tries to submit a URL with disallowed characters they will
    | get a warning message.
    |
    | As a security measure you are STRONGLY encouraged to restrict URLs to
    | as few characters as possible.
    |
    | By default, only these are allowed: `a-z 0-9~%.:_-`
    |
    | Set an empty string to allow all characters -- but only if you are insane.
    |
    | The configured value is actually a regular expression character group
    | and it will be used as: '/\A[<permittedURIChars>]+\z/iu'
    |
    | DO NOT CHANGE THIS UNLESS YOU FULLY UNDERSTAND THE REPERCUSSIONS!!
    |
    */
    public string $permittedURIChars = 'a-z 0-9~%.:_\-';

    /**
     * --------------------------------------------------------------------------
     * Default Locale
     * --------------------------------------------------------------------------
     *
     * The Locale roughly represents the language and location that your visitor
     * is viewing the site from. It affects the language strings and other
     * strings (like currency markers, numbers, etc), that your program
     * should run under for this request.
     */
    public string $defaultLocale = 'en';

    /**
     * --------------------------------------------------------------------------
     * Negotiate Locale
     * --------------------------------------------------------------------------
     *
     * If true, the current Request object will automatically determine the
     * language to use based on the value of the Accept-Language header.
     *
     * If false, no automatic detection will be performed.
     */
    public bool $negotiateLocale = false;

    /**
     * --------------------------------------------------------------------------
     * Supported Locales
     * --------------------------------------------------------------------------
     *
     * If $negotiateLocale is true, this array lists the locales supported
     * by the application in descending order of priority. If no match is
     * found, the first locale will be used.
     *
     * IncomingRequest::setLocale() also uses this list.
     *
     * @var list<string>
     */
    public array $supportedLocales = ['en'];

    /**
     * --------------------------------------------------------------------------
     * Application Timezone
     * --------------------------------------------------------------------------
     *
     * The default timezone that will be used in your application to display
     * dates with the date helper, and can be retrieved through app_timezone()
     *
     * @see https://www.php.net/manual/en/timezones.php for list of timezones
     *      supported by PHP.
     */
    public string $appTimezone = 'UTC';

    /**
     * --------------------------------------------------------------------------
     * Default Character Set
     * --------------------------------------------------------------------------
     *
     * This determines which character set is used by default in various methods
     * that require a character set to be provided.
     *
     * @see http://php.net/htmlspecialchars for a list of supported charsets.
     */
    public string $charset = 'UTF-8';

    /**
     * --------------------------------------------------------------------------
     * Force Global Secure Requests
     * --------------------------------------------------------------------------
     *
     * If true, this will force every request made to this application to be
     * made via a secure connection (HTTPS). If the incoming request is not
     * secure, the user will be redirected to a secure version of the page
     * and the HTTP Strict Transport Security (HSTS) header will be set.
     */
    public bool $forceGlobalSecureRequests = false;

    /**
     * --------------------------------------------------------------------------
     * Reverse Proxy IPs
     * --------------------------------------------------------------------------
     *
     * If your server is behind a reverse proxy, you must whitelist the proxy
     * IP addresses from which CodeIgniter should trust headers such as
     * X-Forwarded-For or Client-IP in order to properly identify
     * the visitor's IP address.
     *
     * You need to set a proxy IP address or IP address with subnets and
     * the HTTP header for the client IP address.
     *
     * Here are some examples:
     *     [
     *         '10.0.1.200'     => 'X-Forwarded-For',
     *         '192.168.5.0/24' => 'X-Real-IP',
     *     ]
     *
     * @var array<string, string>
     */
    public array $proxyIPs = [];

    /**
     * --------------------------------------------------------------------------
     * Content Security Policy
     * --------------------------------------------------------------------------
     *
     * Enables the Response's Content Secure Policy to restrict the sources that
     * can be used for images, scripts, CSS files, audio, video, etc. If enabled,
     * the Response object will populate default values for the policy from the
     * `ContentSecurityPolicy.php` file. Controllers can always add to those
     * restrictions at run time.
     *
     * For a better understanding of CSP, see these documents:
     *
     * @see http://www.html5rocks.com/en/tutorials/security/content-security-policy/
     * @see http://www.w3.org/TR/CSP/
     */
    public bool $CSPEnabled = true;


    public function __construct()
    {
        // M-05: `app.baseURL` di .env kini dipakai sebagai nilai STATIS — nilai
        // yang dipegang kalau header `Host` tidak dipercaya. Ini bukan pengganti
        // app.folder: untuk host yang lolos daftar putih, baseURL tetap disusun
        // dinamis di bawah supaya satu berkas .env tetap bisa dipakai lintas host.
        $baseUrlEnv = getenv('app.baseURL');

        if (is_string($baseUrlEnv) && trim($baseUrlEnv) !== '') {
            $this->baseURL = trim($baseUrlEnv);
        }

        $this->allowedHostnames = $this->hostYangDiizinkan();

        $host = $this->hostTepercaya();

        // Host tidak dikenal: `$baseURL` tidak disentuh sama sekali. Inti M-05 —
        // dulu isi header `Host` langsung masuk ke sini, jadi penyerang cukup
        // mengirim `Host: evil.example` ke endpoint lupa password untuk membuat
        // link reset milik korban menunjuk ke servernya sendiri.
        if ($host === null) {
            return;
        }

        // Respect X-Forwarded-Proto from Cloudflare Tunnel or other reverse proxies
        //
        // Header ini masih dipercaya tanpa memeriksa asal request (`$proxyIPs`
        // masih kosong karena alamat cloudflared di tiap server belum dipastikan).
        // Yang bisa dipengaruhinya sekarang tinggal pemilihan http/https pada host
        // yang SUDAH lolos daftar putih, bukan lagi ke mana url itu menunjuk.
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $scheme = 'https';
        } else {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                ? 'https'
                : 'http';
        }

        // ambil dari env
        //
        // getenv() mengembalikan false kalau app.folder tidak diset sama
        // sekali, dan '' kalau sengaja dikosongkan. Keduanya HARUS
        // dibedakan: string kosong berarti aplikasi berada di root domain
        // (production: https://sys.transporindo.com), sedangkan `?:` dulu
        // memperlakukan keduanya sama dan memaksa segmen 'sys-modern' ke
        // dalam setiap url — memakai `?:` di sini membuat deployment root
        // domain mustahil dikonfigurasi.
        $folder = getenv('app.folder');

        if ($folder === false || $folder === null) {
            $folder = 'sys-modern';
        }

        $folder = trim((string) $folder, '/');

        $this->baseURL = $scheme . '://' . $host . '/' . ($folder === '' ? '' : $folder . '/');
    }

    /**
     * Daftar putih host, dari .env kalau disebutkan di sana.
     *
     * `app.allowedHostnames` dipisah koma atau spasi:
     *     app.allowedHostnames = 'sys.transporindo.com, staging.transporindo.com'
     *
     * Host milik `app.baseURL` selalu ikut masuk. Tanpa itu, server yang
     * baseURL-nya sudah benar tetapi lupa menambah daftar ini akan menghasilkan
     * url yang menunjuk ke tempat lain — kegagalan yang sulit dilacak dan sama
     * sekali tidak perlu ada.
     *
     * @return list<string>
     */
    private function hostYangDiizinkan(): array
    {
        $dariEnv = getenv('app.allowedHostnames');

        $daftar = is_string($dariEnv) && trim($dariEnv) !== ''
            ? preg_split('/[\s,]+/', trim($dariEnv), -1, PREG_SPLIT_NO_EMPTY)
            : self::HOST_BAWAAN;

        $hostBaseUrl = parse_url($this->baseURL, PHP_URL_HOST);

        if (is_string($hostBaseUrl) && $hostBaseUrl !== '') {
            $daftar[] = $hostBaseUrl;
        }

        $daftar = array_map(
            static fn (string $host): string => strtolower(trim($host, " \t\n\r\0\x0B.")),
            $daftar
        );

        return array_values(array_unique(array_filter(
            $daftar,
            static fn (string $host): bool => $host !== ''
        )));
    }

    /**
     * Host request yang boleh dipakai membangun url, lengkap dengan port bila
     * ada. `null` berarti header `Host` tidak dipercaya.
     *
     * Pemisahan host dan port diserahkan ke parse_url() supaya bentuk seperti
     * `[::1]:8080` tidak perlu ditebak sendiri — sekaligus supaya nilai yang
     * menyelipkan path atau kredensial (`evil.example/x`, `a@evil.example`)
     * gugur di sini, bukan ikut terangkai ke dalam url.
     */
    private function hostTepercaya(): ?string
    {
        $httpHost = $_SERVER['HTTP_HOST'] ?? '';

        if (! is_string($httpHost) || trim($httpHost) === '') {
            return null;
        }

        $parts = parse_url('http://' . trim($httpHost));

        if (! is_array($parts)) {
            return null;
        }

        // Hanya host (dan boleh port) yang wajar; kunci lain berarti header ini
        // membawa sesuatu yang tidak seharusnya ada.
        if (array_diff(array_keys($parts), ['scheme', 'host', 'port']) !== []) {
            return null;
        }

        $host = isset($parts['host']) ? strtolower(rtrim($parts['host'], '.')) : '';

        if ($host === '' || ! in_array($host, $this->allowedHostnames, true)) {
            return null;
        }

        return isset($parts['port']) ? $host . ':' . $parts['port'] : $host;
    }
}
