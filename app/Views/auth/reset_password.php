<?php $validationErrors = session('errors'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $siteConfig->siteTitle ?? 'SYS TRANSPORINDO'; ?> | Reset Password</title>

  <!-- Aesthetic Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Manrope:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="<?= asset('libraries/adminlte/plugins/fontawesome-free/css/all.min.css') ?>">
  <!-- Custom Style -->
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

    .verdant-outer-shell {
      position: relative;
      width: 100%;
      overflow: hidden;
      min-height: 100vh;
    }
    
    .verdant-outer-shell *,
    .verdant-outer-shell *::before,
    .verdant-outer-shell *::after {
      text-transform: none !important;
      box-sizing: border-box !important;
    }

    body {
      background-color: var(--bg-light);
      font-family: 'Manrope', -apple-system, BlinkMacSystemFont, sans-serif !important;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      margin: 0;
      min-height: 100vh;
      overflow-x: hidden;
    }
    body.dark-mode {
      background-color: var(--bg-dark) !important;
      color: var(--ink-dark) !important;
    }

    .verdant-blob-1 {
      position: absolute;
      left: -5rem; top: -5rem;
      z-index: 0;
      width: 480px; height: 480px;
      pointer-events: none;
      background: radial-gradient(circle at 30% 30%, rgba(168,181,152,0.35), transparent 60%);
    }

    .verdant-nav {
      position: fixed; top: 0; left: 0; right: 0; z-index: 10;
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

    .verdant-main-wrapper {
      display: flex; align-items: center; justify-content: center;
      min-height: 100vh; padding: 7rem 1.5rem 5rem 1.5rem;
      position: relative;
    }
    .verdant-login-card {
      position: relative; width: 100%; max-width: 480px; z-index: 10;
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

    .verdant-label-row {
      display: flex; justify-content: space-between; align-items: center;
      margin-bottom: 0.625rem;
    }
    .verdant-label {
      font-size: 0.9375rem; font-weight: 600; letter-spacing: 0.005em;
      color: var(--ink-light); margin: 0;
    }
    body.dark-mode .verdant-label { color: var(--ink-dark); }

    .verdant-input-wrapper { position: relative; }
    .verdant-input {
      width: 100%; border-radius: 14px; padding: 1.15rem 1.125rem;
      font-size: 1.0625rem;
      border: 1px solid var(--line-light); background-color: var(--paper-light);
      color: var(--ink-light); outline: none; transition: all 0.2s;
    }
    body.dark-mode .verdant-input {
      border-color: var(--line-dark); background-color: var(--paper-dark);
      color: var(--ink-dark);
    }
    
    .verdant-input:focus {
      border-color: var(--sage-deep-light); background-color: #fff;
      box-shadow: 0 0 0 4px var(--leaf-soft-light);
    }
    body.dark-mode .verdant-input:focus {
      border-color: var(--sage-deep-dark); background-color: #161914;
      box-shadow: 0 0 0 4px var(--leaf-soft-dark);
    }
    
    .verdant-input-password { padding-right: 3rem; }

    .verdant-toggle-pass {
      position: absolute; right: 1rem; top: 50%; transform: translateY(-50%);
      color: var(--muted-light); cursor: pointer; border: none; background: transparent; padding: 0.25rem;
    }
    body.dark-mode .verdant-toggle-pass { color: var(--muted-dark); }

    .verdant-btn {
      width: 100%; border-radius: 50px; background-color: var(--sage-deep-light);
      color: var(--paper-light); font-size: 1.0625rem; font-weight: 600; letter-spacing: 0.01em;
      padding: 1.15rem 2rem; border: none; cursor: pointer;
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

    .text-danger { color: #dc3545 !important; font-size: 0.85rem; margin-top: 5px; }
  </style>
</head>

<body class="dark-mode"> <!-- Force dark mode to match preference for now -->
  <div class="verdant-outer-shell">
    <div class="verdant-blob-1"></div>

    <nav class="verdant-nav">
      <div class="verdant-brand">
        <div class="verdant-logo-circle">
          <img src="<?= asset('image/IcTas-Small.png') ?>" alt="TAS" width="18" height="18" onerror="this.src='<?= asset($siteConfig->siteLogo ?? '') ?>'; this.width=20;" style="object-fit: contain;">
        </div>
        <span>Transporindo <em class="verdant-brand-italic">Sys</em></span>
      </div>
    </nav>

    <div class="verdant-main-wrapper">
      <main class="verdant-login-card">
        <div class="verdant-card-mark">
          <img src="<?= asset('image/IcTas-Small.png') ?>" alt="TAS" width="36" height="36" onerror="this.src='<?= asset($siteConfig->siteLogo ?? '') ?>'; this.width=36;" style="object-fit: contain;">
        </div>

        <p class="verdant-seal">— PT. Transporindo Agung Sejahtera —</p>
        <h1 class="verdant-heading">Reset Password</h1>
        <p class="verdant-subheading">Masukkan password baru untuk akun <strong><?= esc($user) ?></strong></p>

        <form action="<?= base_url(); ?>reset-password" method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="token" value="<?= esc($token) ?>">
          <input type="hidden" name="user" value="<?= esc($user) ?>">
          
          <div class="verdant-form-group">
            <div class="verdant-label-row">
              <label class="verdant-label" for="password">Password Baru</label>
            </div>
            <div class="verdant-input-wrapper">
              <input type="password" name="password" id="password" class="verdant-input verdant-input-password <?= (isset($validationErrors['password'])) ? 'is-invalid' : '' ?>" placeholder="••••••••••" autocomplete="new-password" required minlength="5">
              <button type="button" class="verdant-toggle-pass toggle-password" toggle="#password">
                <i class="fas fa-eye"></i>
              </button>
            </div>
            <?php if (isset($validationErrors['password'])): ?>
              <div class="text-danger"><?= $validationErrors['password'] ?></div>
            <?php endif; ?>
          </div>

          <button type="submit" class="verdant-btn">
            Simpan Password Baru
            <i class="fas fa-arrow-right"></i>
          </button>
        </form>
      </main>
    </div>
  </div>

  <script src="<?= asset('libraries/adminlte/plugins/jquery/jquery.min.js') ?>"></script>
  <script>
    $(document).ready(function() {
      // Toggle theme logic from local storage
      const savedTheme = localStorage.getItem('theme');
      if (savedTheme === 'light') {
        $('body').removeClass('dark-mode');
      }

      $(document).on('click', ".toggle-password", function(event) {
        $(this).find('i').toggleClass("fa-eye fa-eye-slash");
        var input = $($(this).attr("toggle"));
        if (input.attr("type") == "password") {
          input.attr("type", "text");
        } else {
          input.attr("type", "password");
        }
      });
    });
  </script>
</body>
</html>
