<?php $siteConfig = config('Site'); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <meta name="csrf-token-name" content="<?= csrf_token() ?>">
    <title><?= (isset($title) ? ucwords(strtolower($title)) . ' | ' : '') . $siteConfig->siteTitle; ?></title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= asset('libraries/adminlte/plugins/fontawesome-free/css/all.min.css') ?>">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?= asset('libraries/adminlte/dist/css/adminlte-customized.min.css') ?>">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="<?= asset('libraries/adminlte/plugins/overlayScrollbars/css/OverlayScrollbars.min.css') ?>">
    
    <!-- JQGrid 570 Bootstrap 4 (From Trucking) --> 
    <link rel="stylesheet" href="<?= asset('libraries/jqgrid/590/css/ui.jqgrid-bootstrap4.css') ?>" />

    <!-- Select2 -->
    <link rel="stylesheet" href="<?= asset('libraries/adminlte/plugins/select2/css/select2.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('libraries/adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') ?>">

    <!-- Nestable2 -->
    <link rel="stylesheet" href="<?= asset('libraries/nestable2/1.6.0/css/jquery.nestable.min.css') ?>" />

    <!-- Jquery UI -->
    <link id="jquery-theme" rel="stylesheet" href="<?= asset('libraries/jquery-ui/cupertino/jquery-ui.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('libraries/jquery-ui/1.13.1/jquery-ui.min.css') ?>">

    <!-- Custom Style (From Trucking) -->
    <link rel="stylesheet" href="<?= asset('libraries/tas-lib/css/pager.css') ?>">
    <link rel="stylesheet" href="<?= asset('libraries/tas-lib/css/MonthPicker.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('libraries/tas-lib/css/YearPicker.css') ?>">
    <link rel="stylesheet" href="<?= asset('libraries/tas-lib/css/styles.css') ?>?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= asset('libraries/tas-lib/css/button-styles.css') ?>">
    
    <!-- Legacy Icons -->
    <link rel="stylesheet" href="<?= asset('libraries/menu/menustyle.css') ?>">
    <style>
        /* Patch for legacy background-image icons inside AdminLTE nav-link */
        .nav-icon[class*="icon-"] {
            display: inline-block;
            height: 16px !important;
            width: 16px !important;
            background-size: contain !important;
            vertical-align: sub;
        }
    </style>

    <!-- Scripts - Moved to header to support legacy inline scripts in views (Matching Trucking placement) -->
    <script src="<?= asset('libraries/adminlte/plugins/jquery/jquery.min.js') ?>"></script>
    <script src="<?= asset('libraries/jquery-ui/1.13.1/jquery-ui.min.js') ?>"></script>

    <!-- CSRF: satu titik pemasangan token untuk SELURUH request jQuery (C-03).
         Harus tepat setelah jQuery dimuat dan sebelum skrip apa pun yang ber-AJAX.
         'headers' pada $.ajaxSetup di-deep-merge oleh jQuery, jadi call site yang
         punya headers sendiri (mis. Authorization) tetap ikut membawa token ini. -->
    <script>
        (function () {
            var token = document.querySelector('meta[name="csrf-token"]');
            if (!token || !window.jQuery) return;

            window.csrfTokenName = document.querySelector('meta[name="csrf-token-name"]').getAttribute('content');
            window.csrfTokenValue = token.getAttribute('content');

            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': window.csrfTokenValue } });

            // Jaring pengaman: token bisa basi kalau cookie sesi browser hilang
            // sementara halaman lama masih terbuka (mis. browser di-restart).
            // Muat ulang SEKALI supaya dapat token baru; kalau sesinya juga sudah
            // habis, AuthFilter yang akan mengarahkan ke halaman login.
            //
            // Penolakan ACL (AclFilter) juga berstatus 403 tapi selalu berbadan
            // JSON, jadi dibedakan lewat responseJSON — jangan sampai user yang
            // memang tidak punya hak akses malah terjebak reload berulang.
            var RELOAD_FLAG = 'csrfReloadedAt';

            // Sesi berakhir di tengah jalan (habis sendiri, atau dicabut lewat
            // Single Logout dari dashboard SSO) sementara halaman masih terbuka.
            // Kalau pengguna sedang menekan tombol dan bukan memuat ulang
            // halaman, yang sampai ke layar hanyalah 401 — dan tiap grid serta
            // grafik punya handler `error:` sendiri yang menerjemahkannya jadi
            // "terjadi kesalahan saat mengambil data". Pesan itu keliru: datanya
            // tidak gagal diambil, sesinyalah yang sudah tidak ada. Pengguna
            // menatap dialog error tanpa pernah tahu ia sudah logout.
            //
            // Ditangani sekali di sini, bukan di puluhan call site: layar baru
            // yang dibuat nanti ikut terlindungi tanpa perlu diingat.
            var sessionEnded = false;

            function showSessionEndedNotice() {
                // jQuery menjalankan handler `error:` milik call site LEBIH DULU
                // daripada ajaxError global, jadi dialog "gagal mengambil data"
                // sudah sempat terender saat kita sampai di sini. Lapisan ini
                // menutupinya dengan keterangan yang benar selama browser
                // berpindah halaman.
                var el = document.createElement('div');
                el.setAttribute('style',
                    'position:fixed;top:0;left:0;right:0;bottom:0;z-index:2147483647;'
                    + 'display:flex;align-items:center;justify-content:center;padding:1.5rem;'
                    + 'background:rgba(0,0,0,.72);color:#fff;text-align:center;'
                    + 'font:600 16px/1.5 system-ui,-apple-system,Segoe UI,sans-serif;');
                el.textContent = 'Sesi Anda telah berakhir. Mengalihkan ke halaman login…';
                document.body.appendChild(el);
            }

            $(document).ajaxError(function (event, xhr) {
                // Dikenali lewat penanda, bukan status 401 saja: Webauthn dan
                // lock screen juga menjawab 401 untuk keadaan lain dan sudah
                // punya penanganannya sendiri (lihat AuthFilter::sessionEndedJson).
                if (xhr.status === 401 && xhr.responseJSON && xhr.responseJSON.sessionExpired) {
                    // Satu halaman bisa punya beberapa AJAX berjalan bersamaan,
                    // dan semuanya gagal berbarengan. Tanpa penjaga ini,
                    // pengalihan dipanggil berkali-kali.
                    if (sessionEnded) return;
                    sessionEnded = true;

                    showSessionEndedNotice();
                    window.location.href = xhr.responseJSON.redirect || ((window.apiUrl || '') + 'login');

                    return;
                }

                if (xhr.status !== 403 || xhr.responseJSON) return;

                var last = parseInt(sessionStorage.getItem(RELOAD_FLAG) || '0', 10);
                if (Date.now() - last < 30000) return;

                sessionStorage.setItem(RELOAD_FLAG, String(Date.now()));
                window.location.reload();
            });
        })();
    </script>

    <script>
        const appUrl = '<?= base_url() ?>';
        const baseUrl = '<?= base_url() ?>';
        window.apiUrl = appUrl;

        (function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark-mode');
            }
        })();
    </script>
</head>

<body class="hold-transition sidebar-collapse layout-fixed">
    <div class="modal-loader d-none">
        <div class="modal-loader-content d-flex align-items-center justify-content-center">
            <img src="<?= asset('libraries/tas-lib/img/loading-blue.gif') ?>" rel="preload">
            Loading...
        </div>
    </div>

    <div class="loader" id="loader">
        <img src="<?= asset('libraries/tas-lib/img/hour-glass.gif') ?>" rel="preload">
        <span>Loading</span>
    </div>

    <div class="loaderGrid d-none" id="loaderGrid">
        <span><img src="<?= asset('libraries/tas-lib/img/loading-red.gif') ?>" rel="preload">Loading ...</span>
    </div>

    <div class="lookup-loader d-none">
        <div class="lookup-loader-content d-flex align-items-center justify-content-center">
            <img src="<?= asset('libraries/tas-lib/img/loading-blue.gif') ?>" rel="preload">
            Loading...
        </div>
    </div>

    <div class="processing-loader d-none" id="processingLoader">
        <img src="<?= asset('libraries/tas-lib/img/loading-color.gif') ?>" rel="preload">
        <span>Processing</span>
    </div>

    <div class="wrapper">
