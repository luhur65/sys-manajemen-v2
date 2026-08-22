<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Stores the default settings for the ContentSecurityPolicy, if you
 * choose to use it. The values here will be read in and set as defaults
 * for the site. If needed, they can be overridden on a page-by-page basis.
 *
 * Suggested reference for explanations:
 *
 * @see https://www.html5rocks.com/en/tutorials/security/content-security-policy/
 */
class ContentSecurityPolicy extends BaseConfig
{
    // -------------------------------------------------------------------------
    // Broadbrush CSP management
    // -------------------------------------------------------------------------

    /**
     * Default CSP report context
     */
    public bool $reportOnly = false;

    /**
     * Specifies a URL where a browser will send reports
     * when a content security policy is violated.
     */
    public ?string $reportURI = null;

    /**
     * Specifies a reporting endpoint to which violation reports ought to be sent.
     */
    public ?string $reportTo = null;

    /**
     * Instructs user agents to rewrite URL schemes, changing
     * HTTP to HTTPS. This directive is for websites with
     * large numbers of old URLs that need to be rewritten.
     */
    public bool $upgradeInsecureRequests = false;

    // -------------------------------------------------------------------------
    // CSP DIRECTIVES SETTINGS
    // NOTE: once you set a policy to 'none', it cannot be further restricted
    // -------------------------------------------------------------------------

    /**
     * Will default to `'self'` if not overridden
     *
     * @var list<string>|string|null
     */
    public $defaultSrc;

    /**
     * Lists allowed scripts' URLs.
     *
     * @var list<string>|string
     */
    public $scriptSrc = ['self', 'unsafe-inline', 'unsafe-eval', 'https://cdnjs.cloudflare.com', 'https://code.highcharts.com', 'https://static.cloudflareinsights.com'];

    /**
     * Specifies valid sources for JavaScript <script> elements.
     *
     * @var list<string>|string
     */
    public array|string $scriptSrcElem = ['self', 'unsafe-inline', 'https://cdnjs.cloudflare.com', 'https://code.highcharts.com', 'https://static.cloudflareinsights.com'];

    /**
     * Specifies valid sources for JavaScript inline event
     * handlers and JavaScript URLs.
     *
     * @var list<string>|string
     */
    public array|string $scriptSrcAttr = ['self', 'unsafe-inline'];

    /**
     * Lists allowed stylesheets' URLs.
     *
     * @var list<string>|string
     */
    public $styleSrc = ['self', 'unsafe-inline', 'https://fonts.googleapis.com', 'https://cdnjs.cloudflare.com'];

    /**
     * Specifies valid sources for stylesheets <link> elements.
     *
     * @var list<string>|string
     */
    public array|string $styleSrcElem = ['self', 'unsafe-inline', 'https://fonts.googleapis.com', 'https://cdnjs.cloudflare.com'];

    /**
     * Specifies valid sources for stylesheets inline
     * style attributes and `<style>` elements.
     *
     * @var list<string>|string
     */
    public array|string $styleSrcAttr = ['self', 'unsafe-inline'];

    /**
     * Defines the origins from which images can be loaded.
     *
     * @var list<string>|string
     */
    public $imageSrc = ['self', 'data:', 'https://taspusat-storage.s3.us-east-1.amazonaws.com'];

    /**
     * Restricts the URLs that can appear in a page's `<base>` element.
     *
     * Will default to self if not overridden
     *
     * @var list<string>|string|null
     */
    public $baseURI;

    /**
     * Lists the URLs for workers and embedded frame contents
     *
     * @var list<string>|string
     */
    public $childSrc = 'self';

    /**
     * Limits the origins that you can connect to (via XHR,
     * WebSockets, and EventSource).
     *
     * @var list<string>|string
     */
    public $connectSrc = 'self';

    /**
     * Specifies the origins that can serve web fonts.
     *
     * @var list<string>|string
     */
    public $fontSrc = ['self', 'data:', 'https://fonts.gstatic.com', 'https://cdnjs.cloudflare.com'];

    /**
     * Lists valid endpoints for submission from `<form>` tags.
     *
     * @var list<string>|string
     */
    public $formAction = 'self';

    /**
     * Specifies the sources that can embed the current page.
     * This directive applies to `<frame>`, `<iframe>`, `<embed>`,
     * and `<applet>` tags. This directive can't be used in
     * `<meta>` tags and applies only to non-HTML resources.
     *
     * @var list<string>|string|null
     */
    public $frameAncestors;

    /**
     * The frame-src directive restricts the URLs which may
     * be loaded into nested browsing contexts.
     *
     * @var list<string>|string|null
     */
    public $frameSrc;

    /**
     * Restricts the origins allowed to deliver video and audio.
     *
     * @var list<string>|string|null
     */
    public $mediaSrc;

    /**
     * Allows control over Flash and other plugins.
     *
     * @var list<string>|string
     */
    public $objectSrc = 'self';

    /**
     * @var list<string>|string|null
     */
    public $manifestSrc;

    /**
     * @var list<string>|string
     */
    public array|string $workerSrc = [];

    /**
     * Limits the kinds of plugins a page may invoke.
     *
     * @var list<string>|string|null
     */
    public $pluginTypes;

    /**
     * List of actions allowed.
     *
     * @var list<string>|string|null
     */
    public $sandbox;

    /**
     * Nonce placeholder for style tags.
     */
    public string $styleNonceTag = '{csp-style-nonce}';

    /**
     * Nonce placeholder for script tags.
     */
    public string $scriptNonceTag = '{csp-script-nonce}';

    /**
     * Replace nonce tag automatically?
     */
    public bool $autoNonce = false;

    public function __construct()
    {
        parent::__construct();

        // Di development, Debug Toolbar menyuntikkan tag-nya memakai
        // csp_script_nonce()/csp_style_nonce(). Begitu sebuah nonce masuk ke
        // direktif script-src/style-src (dan *-elem), browser mengabaikan
        // 'unsafe-inline' pada direktif tersebut sesuai spec CSP, sehingga
        // seluruh <script>/<style> inline di view ikut terblokir dan halaman
        // terlihat seperti kehilangan asset.
        //
        // Karena itu saat debug aktif CSP dijalankan sebagai report-only:
        // pelanggaran tetap terlihat di console browser, tapi tidak memblokir.
        // Production (CI_DEBUG = false) tetap enforcing.
        //
        // Sengaja memakai CI_DEBUG, bukan ENVIRONMENT, karena CI_DEBUG persis
        // menandai kapan Debug Toolbar (dan nonce-nya) ikut aktif.
        if (defined('CI_DEBUG') && CI_DEBUG) {
            $this->reportOnly = true;
        }
    }
}
