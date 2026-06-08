<?php $siteConfig = config('Site'); ?>
<?php $validationErrors = session('errors'); ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $siteConfig->siteTitle; ?> | Log in</title>

  <!-- Meta Tags -->
  <meta name="description" content="<?= $siteConfig->metaDesc; ?>">
  <meta name="author" content="<?= $siteConfig->metaAuthor; ?>">
  <meta name="keywords" content="<?= $siteConfig->metaKeywords; ?>">
  <meta http-equiv="refresh" content="<?= 60 * 60 ?>">

  <link rel="icon" href="<?= asset($siteConfig->siteIcon); ?>" type="image/x-icon">

  <!-- Aesthetic Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Manrope:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="<?= asset('libraries/adminlte/plugins/fontawesome-free/css/all.min.css') ?>">
  <!-- Theme style -->
  <link rel="stylesheet" href="<?= asset('libraries/adminlte/dist/css/adminlte.min.css') ?>">
  <!-- Jquery UI -->
  <link rel="stylesheet" href="<?= asset('libraries/jquery-ui/cupertino/jquery-ui.min.css') ?>">
  <!-- Custom Style -->
  <link rel="stylesheet" href="<?= asset('libraries/tas-lib/css/styles.css?version=' . $siteConfig->siteVersion) ?>">

  <style>
    /* VERDANT CUSTOM STYLES */
    :root {
      --bg-light: #f5f0e6;
      --bg-dark: #151813;
      --paper-light: #fbf7ed;
      --paper-dark: #1c201a;
      --card-light: #ffffff;
      --card-dark: #1f231d;
      --ink-light: #1f241c;
      --ink-dark: #ede8db;
      --muted-light: #6e7567;
      --muted-dark: #8a8d80;
      --line-light: #e0d8c6;
      --line-dark: #2c302a;
      --sage: #a8b598;
      --sage-deep-light: #6d7d5e;
      --sage-deep-dark: #b8c4a6;
      --terracotta-light: #c98364;
      --terracotta-dark: #e09c80;
      --leaf-soft-light: #ecf0e0;
      --leaf-soft-dark: #252a22;
    }

    @keyframes verdant-reveal {
      from { opacity: 0; transform: translateY(10px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes verdant-sway {
      0%, 100% { transform: rotate(-3deg) translateY(0); }
      50%       { transform: rotate(3deg) translateY(-8px); }
    }

    .verdant-outer-shell *,
    .verdant-outer-shell *::before,
    .verdant-outer-shell *::after {
      text-transform: none !important;
    }

    body {
      background-color: var(--bg-light);
      font-family: 'Manrope', -apple-system, BlinkMacSystemFont, sans-serif !important;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      margin: 0;
      min-height: 100vh;
      overflow-x: hidden;
      display: block !important; /* override AdminLTE default flex */
    }
    body.dark-mode {
      background-color: var(--bg-dark);
      color: var(--ink-dark);
    }

    .verdant-blob-1 {
      position: absolute;
      left: -5rem; top: -5rem;
      z-index: 0;
      width: 480px; height: 480px;
      pointer-events: none;
      background: radial-gradient(circle at 30% 30%, rgba(168,181,152,0.35), transparent 60%);
    }
    .verdant-blob-2 {
      position: absolute;
      right: -9rem; bottom: -9rem;
      z-index: 0;
      width: 520px; height: 520px;
      pointer-events: none;
      background: radial-gradient(circle at 70% 70%, rgba(201,131,100,0.18), transparent 65%);
    }

    .verdant-svg-1 { position: absolute; right: 6%; top: 8%; z-index: 0; color: var(--sage); opacity: 0.6; animation: verdant-sway 8s ease-in-out infinite; pointer-events: none; }
    body.dark-mode .verdant-svg-1 { opacity: 0.35; }
    
    .verdant-svg-2 { position: absolute; left: 5%; bottom: 10%; z-index: 0; color: var(--sage); opacity: 0.5; animation: verdant-sway 10s ease-in-out infinite reverse; pointer-events: none; }
    body.dark-mode .verdant-svg-2 { opacity: 0.35; }

    .verdant-nav {
      position: fixed; top: 0; left: 0; right: 0; z-index: 9999;
      display: flex; align-items: center; justify-content: space-between;
      padding: max(2rem, env(safe-area-inset-top)) 2rem 1rem 2rem;
    }
    .verdant-brand {
      display: flex; align-items: center; gap: 0.625rem;
      font-size: 1.25rem;
      font-weight: 600; letter-spacing: -0.005em; color: var(--ink-light);
    }
    body.dark-mode .verdant-brand { color: var(--ink-dark); }
    .verdant-logo-circle {
      display: flex; align-items: center; justify-content: center;
      width: 30px; height: 30px; border-radius: 50%;
      background-color: var(--sage-deep-light);
    }
    body.dark-mode .verdant-logo-circle { background-color: var(--sage-deep-dark); }
    .verdant-brand-italic {
      font-family: 'Instrument Serif', Georgia, serif;
      font-style: italic; font-weight: 400;
      color: var(--terracotta-light);
    }
    body.dark-mode .verdant-brand-italic { color: var(--terracotta-dark); }

    .verdant-nav-right {
      display: flex; align-items: center; gap: 1rem;
      color: var(--muted-light); font-size: 1.25rem;
    }
    body.dark-mode .verdant-nav-right { color: var(--muted-dark); }
    .verdant-theme-toggle {
      display: flex; align-items: center; justify-content: center;
      width: 34px; height: 34px; border-radius: 50%;
      border: 1px solid var(--line-light); background-color: var(--paper-light);
      color: var(--ink-light); cursor: pointer;
      transition: all 0.2s;
    }
    .verdant-theme-toggle:hover { background-color: var(--leaf-soft-light); }
    body.dark-mode .verdant-theme-toggle {
      border-color: var(--line-dark); background-color: var(--paper-dark);
      color: var(--ink-dark);
    }
    body.dark-mode .verdant-theme-toggle:hover { background-color: var(--leaf-soft-dark); }

    .verdant-main-wrapper {
      display: flex; align-items: center; justify-content: center;
      min-height: 100vh; padding: 7rem 1.5rem 5rem 1.5rem;
      position: relative; z-index: 10;
    }
    .verdant-login-card {
      position: relative; width: 100%; max-width: 480px;
      border-radius: 28px; border: 1px solid var(--line-light);
      background-color: var(--card-light);
      padding: 3.5rem 3.5rem 3rem 3.5rem;
      box-shadow: 0 1px 1px rgba(31,36,28,0.04), 0 30px 60px -25px rgba(31,36,28,0.15);
    }
    body.dark-mode .verdant-login-card {
      border-color: var(--line-dark); background-color: var(--card-dark);
      box-shadow: 0 1px 1px rgba(0,0,0,0.3), 0 30px 60px -25px rgba(0,0,0,0.5);
    }

    .verdant-card-mark {
      position: absolute; top: -1.75rem; left: 50%; transform: translateX(-50%);
      width: 56px; height: 56px; border-radius: 50%;
      background-color: var(--terracotta-light);
      display: flex; align-items: center; justify-content: center; z-index: 10;
    }
    body.dark-mode .verdant-card-mark { background-color: var(--terracotta-dark); }

    .verdant-seal {
      color: var(--sage-deep-light);
      font-size: 0.8rem; font-weight: 600; letter-spacing: 0.32em; text-align: center;
      margin-top: 1rem; margin-bottom: 1.5rem;
      text-transform: uppercase !important;
      animation: verdant-reveal 0.8s cubic-bezier(0.16,1,0.3,1) 0.08s both;
    }
    body.dark-mode .verdant-seal { color: var(--sage-deep-dark); }

    .verdant-heading {
      font-family: 'Instrument Serif', Georgia, serif;
      font-size: 2.75rem; letter-spacing: -0.015em; font-weight: 400; line-height: 1.1;
      text-align: center; margin-bottom: 0.75rem; color: var(--ink-light);
      animation: verdant-reveal 0.8s cubic-bezier(0.16,1,0.3,1) 0.16s both;
    }
    body.dark-mode .verdant-heading { color: var(--ink-dark); }

    .verdant-subheading {
      color: var(--muted-light); text-align: center; line-height: 1.55;
      font-size: 1.0625rem; max-width: 340px; margin: 0 auto 1.5rem auto;
      animation: verdant-reveal 0.8s cubic-bezier(0.16,1,0.3,1) 0.24s both;
    }
    body.dark-mode .verdant-subheading { color: var(--muted-dark); }

    .verdant-form-group {
      margin-bottom: 1.75rem;
      animation: verdant-reveal 0.8s cubic-bezier(0.16,1,0.3,1) 0.32s both;
    }
    .verdant-form-group:nth-child(2) { animation-delay: 0.4s; }

    .verdant-label-row {
      display: flex; justify-content: space-between; align-items: center;
      margin-bottom: 0.625rem;
    }
    .verdant-label {
      font-size: 0.9375rem; font-weight: 600; letter-spacing: 0.005em;
      color: var(--ink-light); margin: 0;
    }
    body.dark-mode .verdant-label { color: var(--ink-dark); }
    
    .verdant-forgot {
      font-size: 0.875rem; font-weight: 500; color: var(--terracotta-light);
      border-bottom: 1px solid transparent; padding-bottom: 1px; cursor: pointer;
      text-decoration: none; transition: border-color 0.2s;
    }
    .verdant-forgot:hover { border-color: var(--terracotta-light); text-decoration: none; }
    body.dark-mode .verdant-forgot { color: var(--terracotta-dark); }
    body.dark-mode .verdant-forgot:hover { border-color: var(--terracotta-dark); }

    .verdant-input-wrapper { position: relative; }
    .verdant-input {
      width: 100%; border-radius: 14px; padding: 1rem 1.125rem;
      font-size: 1.0625rem;
      border: 1px solid var(--line-light); background-color: var(--paper-light);
      color: var(--ink-light); outline: none; transition: all 0.2s;
      text-transform: uppercase !important;
    }
    .verdant-input::placeholder { color: #a8a294; text-transform: uppercase !important; }
    body.dark-mode .verdant-input {
      border-color: var(--line-dark); background-color: var(--paper-dark);
      color: var(--ink-dark);
    }
    body.dark-mode .verdant-input::placeholder { color: #5d6055; }
    
    .verdant-input:focus {
      border-color: var(--sage-deep-light); background-color: #fff;
      box-shadow: 0 0 0 4px var(--leaf-soft-light);
    }
    body.dark-mode .verdant-input:focus {
      border-color: var(--sage-deep-dark); background-color: #161914;
      box-shadow: 0 0 0 4px var(--leaf-soft-dark);
    }
    
    .verdant-input-password { text-transform: none !important; padding-right: 3rem; }

    .verdant-toggle-pass {
      position: absolute; right: 1rem; top: 50%; transform: translateY(-50%);
      color: var(--muted-light); cursor: pointer; border: none; background: transparent; padding: 0.25rem;
    }
    body.dark-mode .verdant-toggle-pass { color: var(--muted-dark); }

    .verdant-btn {
      width: 100%; border-radius: 50px; background-color: var(--sage-deep-light);
      color: var(--paper-light); font-size: 1.0625rem; font-weight: 600; letter-spacing: 0.01em;
      padding: 1.1rem; border: none; cursor: pointer;
      display: flex; align-items: center; justify-content: center; gap: 0.5rem;
      transition: all 0.3s cubic-bezier(0.16,1,0.3,1);
      margin-top: 1rem; margin-bottom: 0.5rem;
      animation: verdant-reveal 0.8s cubic-bezier(0.16,1,0.3,1) 0.48s both;
    }
    .verdant-btn:hover {
      transform: translateY(-2px); background-color: var(--ink-light);
      box-shadow: 0 14px 28px -10px rgba(31,36,28,0.35); color: #fff;
    }
    body.dark-mode .verdant-btn { background-color: var(--sage-deep-dark); color: var(--bg-dark); }
    body.dark-mode .verdant-btn:hover { background-color: var(--terracotta-dark); box-shadow: 0 14px 28px -10px rgba(224,156,128,0.4); }

    .verdant-footer {
      text-align: center; margin-top: 2rem; color: var(--muted-light);
      font-size: 0.875rem; letter-spacing: 0.03em;
      animation: verdant-reveal 0.8s cubic-bezier(0.16,1,0.3,1) 0.64s both;
    }
    body.dark-mode .verdant-footer { color: var(--muted-dark); }
    .verdant-footer-bold { font-weight: 600; color: var(--ink-light); }
    body.dark-mode .verdant-footer-bold { color: var(--ink-dark); }

    .verdant-footnote {
      position: fixed; bottom: 1.5rem; left: 0; right: 0; z-index: 10;
      text-align: center; color: var(--muted-light); font-size: 0.875rem; letter-spacing: 0.03em;
    }
    body.dark-mode .verdant-footnote { color: var(--muted-dark); }
    
    @media (max-width: 576px) {
      .verdant-login-card { padding: 3.5rem 1.25rem 2rem 1.25rem; border: none; background: transparent; box-shadow: none; }
      body.dark-mode .verdant-login-card { background: transparent; border: none; box-shadow: none; }
      .verdant-nav-text { display: none; }
    }
  </style>
</head>

<body>
  <div id="dialog-success-message" title="Pesan" class="text-center text-success" style="display: none;">
    <span class="fa fa-check" aria-hidden="true" style="font-size:25px;"></span>
    <p></p>
  </div>

  <div id="dialog-message" title="Error" class="text-center text-danger" style="display: none;">
    <span class="fa fa-exclamation-triangle" aria-hidden="true" style="font-size:25px;"></span>
    <p></p>
  </div>

  <div class="processing-loader d-none" id="processingLoader">
    <img src="<?= asset('libraries/tas-lib/img/loading-color.gif') ?>" rel="preload">
    <span>Processing</span>
  </div>

  <div class="verdant-outer-shell">
    <div class="verdant-blob-1"></div>
    <div class="verdant-blob-2"></div>
    
    <svg class="verdant-svg-1" width="120" height="180" viewBox="0 0 120 180" fill="none">
        <path d="M60 10 C 80 50, 90 100, 60 170 C 30 100, 40 50, 60 10 Z" stroke="currentColor" stroke-width="1.5" fill="none" />
        <path d="M60 30 L 60 170" stroke="currentColor" stroke-width="1" opacity="0.5" />
        <path d="M60 60 L 78 75" stroke="currentColor" stroke-width="1" opacity="0.5" />
        <path d="M60 60 L 42 75" stroke="currentColor" stroke-width="1" opacity="0.5" />
        <path d="M60 90 L 82 105" stroke="currentColor" stroke-width="1" opacity="0.5" />
        <path d="M60 90 L 38 105" stroke="currentColor" stroke-width="1" opacity="0.5" />
    </svg>

    <svg class="verdant-svg-2" width="160" height="160" viewBox="0 0 160 160" fill="none">
        <circle cx="80" cy="80" r="22" stroke="currentColor" stroke-width="1.5" />
        <path d="M80 58 C 95 40, 110 35, 120 38" stroke="currentColor" stroke-width="1.5" />
        <path d="M80 58 C 65 40, 50 35, 40 38" stroke="currentColor" stroke-width="1.5" />
        <path d="M80 102 C 95 120, 110 125, 120 122" stroke="currentColor" stroke-width="1.5" />
        <path d="M80 102 C 65 120, 50 125, 40 122" stroke="currentColor" stroke-width="1.5" />
        <circle cx="120" cy="38" r="6" fill="currentColor" opacity="0.5" />
        <circle cx="40" cy="38" r="6" fill="currentColor" opacity="0.5" />
        <circle cx="120" cy="122" r="6" fill="currentColor" opacity="0.5" />
        <circle cx="40" cy="122" r="6" fill="currentColor" opacity="0.5" />
    </svg>

    <nav class="verdant-nav">
      <div class="verdant-brand">
        <div class="verdant-logo-circle">
          <img src="<?= asset('image/IcTas-Small.png') ?>" alt="TAS" width="18" height="18" onerror="this.src='<?= asset($siteConfig->siteLogo) ?>'; this.width=20;" style="object-fit: contain;">
        </div>
        <span>Transporindo <em class="verdant-brand-italic">Sys</em></span>
      </div>
      <div class="verdant-nav-right">
        <span class="verdant-nav-text d-none d-sm-inline">Management Information System</span>
        <button type="button" class="verdant-theme-toggle" id="themeToggleBtn" aria-label="Toggle theme">
          <i class="fas fa-moon" id="themeIcon"></i>
        </button>
      </div>
    </nav>

    <div class="verdant-main-wrapper">
      <main class="verdant-login-card">
        <div class="verdant-card-mark">
          <img src="<?= asset('image/IcTas-Small.png') ?>" alt="TAS" width="36" height="36" onerror="this.src='<?= asset($siteConfig->siteLogo) ?>'; this.width=36;" style="object-fit: contain;">
        </div>

        <p class="verdant-seal">— PT. Transporindo Agung Sejahtera —</p>
        <h1 class="verdant-heading">Selamat datang,</h1>
        <p class="verdant-subheading">Management Information System. Pusat kendali terpadu untuk mengelola data operasional secara efisien dan akurat.</p>

        <form action="<?= base_url(); ?>login/proses" method="POST">
          <?= csrf_field() ?>
          <input type="text" readonly hidden name="info" id="info" value="<?= $info ?? '' ?>">
          
          <div class="verdant-form-group">
            <div class="verdant-label-row">
              <label class="verdant-label" for="user">Username</label>
            </div>
            <div class="verdant-input-wrapper">
              <input type="text" name="userid" id="user" class="verdant-input <?= (isset($validationErrors['userid'])) ? 'is-invalid' : '' ?>" value="<?= old('userid') ?>" placeholder="USERNAME ANDA" autofocus autocomplete="off">
            </div>
            <?php if (isset($validationErrors['userid'])): ?>
              <div class="text-danger mt-1" style="font-size: 0.8rem; color: var(--terracotta-light) !important;"><?= $validationErrors['userid'] ?></div>
            <?php endif; ?>
          </div>

          <div class="verdant-form-group">
            <div class="verdant-label-row mt-3">
              <label class="verdant-label" for="password">Kata sandi</label>
              <a href="javascript:void(0)" id="resetPassword" class="verdant-forgot">Lupa?</a>
            </div>
            <div class="verdant-input-wrapper">
              <input type="password" name="password" id="password" class="verdant-input verdant-input-password <?= (isset($validationErrors['password'])) ? 'is-invalid' : '' ?>" placeholder="••••••••••" autocomplete="off">
              <button type="button" class="verdant-toggle-pass toggle-password" toggle="#password">
                <i class="fas fa-eye"></i>
              </button>
            </div>
            <?php if (isset($validationErrors['password'])): ?>
              <div class="text-danger mt-1" style="font-size: 0.8rem; color: var(--terracotta-light) !important;"><?= $validationErrors['password'] ?></div>
            <?php endif; ?>
          </div>

          <input type="text" readonly hidden name="latitude" id="latitude">
          <input type="text" readonly hidden name="longitude" id="longitude">
          <input type="text" readonly hidden name="clientippublic" id="clientippublic">
          
          <div id="error" class="text-danger mt-2 text-center" style="font-size: 0.85rem; color: var(--terracotta-light) !important;">
            <?= $error ?? '' ?>
          </div>

          <button type="submit" class="verdant-btn" onclick="signInFunction(this)">
            Masuk ke ruang kerja
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M5 12h14M13 5l7 7-7 7" />
            </svg>
          </button>

          <div class="verdant-footer">
            <p style="margin-bottom: 0.25rem;">Halaman dimuat dalam <span class="verdant-footer-bold"><?= number_format(timer()->getElapsedTime('total_execution'), 2) ?></span> detik</p>
            <p>Copyright &copy; <?= date('Y') ?> PT. Transporindo Agung Sejahtera</p>
          </div>
        </form>
      </main>
    </div>

    <p class="verdant-footnote">
      Dirawat oleh tim <em style="font-family: 'Instrument Serif', serif; font-style: italic; color: var(--ink-light);" class="dark-italic">Transporindo</em> &middot; Sistem Internal &middot; <?= date('Y') ?>
    </p>
  </div>

  <!-- jQuery -->
  <script src="<?= asset('libraries/adminlte/plugins/jquery/jquery.min.js') ?>"></script>
  <!-- jQuery UI -->
  <script src="<?= asset('libraries/jquery-ui/1.13.1/jquery-ui.min.js') ?>"></script>
  <!-- Bootstrap 4 -->
  <script src="<?= asset('libraries/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
  <!-- AdminLTE App -->
  <script src="<?= asset('libraries/adminlte/dist/js/adminlte.min.js') ?>"></script>
  <script src="<?= asset('libraries/tas-lib/js/connectionToast.js?version=' . time()) ?>"></script>

  <script>
    $(document).ready(function() {
      $("input").attr("autocomplete", "off");

      $('form').on('submit', function() {
        $(this).find('button[type="submit"]').prop('disabled', true);
        $('#processingLoader').removeClass('d-none');
      });

      $(document).on('click', ".toggle-password", function(event) {
        $(this).find('i').toggleClass("fa-eye fa-eye-slash");
        var input = $($(this).attr("toggle"));
        if (input.attr("type") == "password") {
          input.attr("type", "text");
        } else {
          input.attr("type", "password");
        }
      });

      $(document).on('click', '#resetPassword', function() {
        let user = $('#user').val();

        checkValidation(user)
          .then((response) => {
            $('#processingLoader').removeClass('d-none')
            $.ajax({
              url: `<?= base_url() ?>forgot-password`,
              method: 'POST',
              dataType: "JSON",
              data: {
                user: user
              },
              success: (response) => {
                $('#processingLoader').addClass('d-none')

                $("#dialog-success-message").find("p").remove();
                $("#dialog-success-message").append(
                  `<p> ${response.message} </p>`
                );
                $("#dialog-success-message").dialog({
                  modal: true,
                  width: 'auto',
                  height: 'auto',
                  resizable: false,
                  buttons: [{
                    text: "Ok",
                    click: function() {
                      $(this).dialog("close");
                    },
                  }, ],
                  open: function() {
                    $(this).css({
                      'max-width': '600px',
                    });
                    $(this).dialog("option", "position", {
                      my: "center",
                      at: "center",
                      of: window
                    });
                  },
                  create: function() {
                    $(this).closest(".ui-dialog")
                      .find(".ui-dialog-buttonset button")
                      .addClass("ui-button ui-corner-all ui-widget custom-success-btn");

                    $(this).closest(".ui-dialog")
                      .find(".ui-dialog-titlebar button")
                      .addClass("ui-button ui-corner-all ui-widget ui-button-icon-only");
                    $(this).closest(".ui-dialog")
                      .find(".ui-dialog-titlebar button")
                      .append(`<span class="ui-button-icon ui-icon ui-icon-closethick"></span>`);
                  }
                });
              },
            }).always(() => {
              $('#processingLoader').addClass('d-none')
            });
          })
          .catch((error) => {
            $("#dialog-message").html(`
                            <span class="fa fa-exclamation-triangle" aria-hidden="true" style="font-size:25px;"></span>
                          `)
            $("#dialog-message").append(
              `<br>${error.responseJSON.errors.user}`
            );
            $("#dialog-message").dialog({
              modal: true,
              buttons: [{
                text: "Ok",
                click: function() {
                  $(this).dialog("close");
                },
              }, ],
              create: function() {
                $(this).closest(".ui-dialog")
                  .find(".ui-dialog-buttonset button")
                  .addClass("ui-button ui-corner-all ui-widget custom-success-btn");

                $(this).closest(".ui-dialog")
                  .find(".ui-dialog-titlebar button")
                  .addClass("ui-button ui-corner-all ui-widget ui-button-icon-only");
                $(this).closest(".ui-dialog")
                  .find(".ui-dialog-titlebar button")
                  .append(`<span class="ui-button-icon ui-icon ui-icon-closethick"></span>`);
              }
            });
          })

      });

      function checkValidation(user) {
        return new Promise((resolve, reject) => {
          $('#processingLoader').removeClass('d-none')
          $.ajax({
              url: `<?= base_url() ?>forgot-password`,
              method: 'POST',
              dataType: "JSON",
              data: {
                user: user,
                check: true
              },
              success: (response) => {
                resolve(response);
              },
              error: error => {
                reject(error)
              }
            })
            .always(() => {
              $('#processingLoader').addClass('d-none')
            });
        });
      }

      // Theme toggle functionality
      $('#themeToggleBtn').on('click', function() {
        if ($('body').hasClass('dark-mode')) {
          applyLightMode();
        } else {
          applyDarkMode();
        }
      });
    })

    function signInFunction(button) {
      // Logic from original file to disable and show loader is already handled by 'submit' event above,
      // but keeping this for compatibility.
      // $(button).prop('disabled', true);
      // $('#processingLoader').removeClass('d-none');
      // $('form').submit();
    }

    function applyDarkMode() {
      const $body = $('body');
      localStorage.setItem('theme', 'dark');
      $body.addClass('dark-mode');
      $('#themeIcon').removeClass('fa-moon').addClass('fa-sun');
      $('.dark-italic').css('color', 'var(--ink-dark)');
    }

    function applyLightMode() {
      const $body = $('body');
      localStorage.setItem('theme', 'light');
      $body.removeClass('dark-mode');
      $('#themeIcon').removeClass('fa-sun').addClass('fa-moon');
      $('.dark-italic').css('color', 'var(--ink-light)');
    }

    $(function() {
      const savedTheme = localStorage.getItem('theme');
      if (savedTheme === 'dark') {
        applyDarkMode();
      } else if (savedTheme === 'light') {
        applyLightMode();
      } else {
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (systemPrefersDark) {
          applyDarkMode();
        } else {
          applyLightMode();
        }
      }
    });
  </script>
</body>
</html>
