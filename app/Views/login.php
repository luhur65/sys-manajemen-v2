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

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet"
    href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="<?= asset('libraries/adminlte/plugins/fontawesome-free/css/all.min.css') ?>">
  <!-- Theme style -->
  <link rel="stylesheet" href="<?= asset('libraries/adminlte/dist/css/adminlte.min.css') ?>">
  <!-- Jquery UI -->
  <link rel="stylesheet" href="<?= asset('libraries/jquery-ui/cupertino/jquery-ui.min.css') ?>">
  <!-- Custom Style -->
  <link rel="stylesheet" href="<?= asset('libraries/tas-lib/css/styles.css?version=' . $siteConfig->siteVersion) ?>">
</head>

<body class="hold-transition login-page">
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

  <div class="login-box">
    <div class="login-logo">
      <img class="mx-auto d-block" src="<?= asset($siteConfig->siteLogo) ?>" width="150" height="150">
      <h5 style="font-family: 'Open Sans Condensed';"><?= $siteConfig->siteName; ?></h5>
      <p style="font-family: 'Open Sans Condensed'; font-size:1rem" class="text-success"><?= $siteConfig->siteSlogan; ?></p>
    </div>
    <div class="card">
      <div class="card-body login-card-body">
        <p class="login-box-msg">Login</p>

        <form action="<?= base_url(); ?>login/proses" method="POST">
          <?= csrf_field() ?>
          <input type="text" readonly hidden name="info" id="info" value="<?= $info ?? '' ?>">
          <div class="input-group mb-3">
            <input type="text" name="userid" id="user" class="form-control <?= (isset($validationErrors['userid'])) ? 'is-invalid' : '' ?>" value="<?= old('userid') ?>" placeholder="User ID" autofocus>
            <div class="input-group-append">
              <div class="input-group-text input-group-login">
                <span class="fas fa-user"></span>
              </div>
            </div>
            <?php if (isset($validationErrors['userid'])): ?>
              <div class="invalid-feedback">
                <?= $validationErrors['userid'] ?>
              </div>
            <?php endif; ?>
          </div>
          <div class="input-group mb-3">
            <input type="password" name="password" id="password" class="form-control <?= (isset($validationErrors['password'])) ? 'is-invalid' : '' ?>" placeholder="Password" style="text-transform: none;">
            <div class="input-group-append">
              <div class="input-group-text input-group-login focusPass">
                <span class="fas fa-eye-slash toggle-password" toggle="#password"></span>
              </div>
            </div>
            <?php if (isset($validationErrors['password'])): ?>
              <div class="invalid-feedback">
                <?= $validationErrors['password'] ?>
              </div>
            <?php endif; ?>
          </div>
          <input type="text" readonly hidden name="latitude" id="latitude">
          <input type="text" readonly hidden name="longitude" id="longitude">
          <input type="text" readonly hidden name="clientippublic" id="clientippublic">
          <div id="error" class="text-danger">
            <?= $error ?? '' ?>
          </div>
          <a href="javascript: void(0)" id="resetPassword" style="text-decoration: underline ">lupa password?</a>
          <div class="row">
            <div class="col-md-4 offset-md-8">
              <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
  <p>Copyright &copy; <?= date('Y') ?></p>
  <p>Halaman ini dimuat selama <strong><?= number_format(timer()->getElapsedTime('total_execution'), 2) ?></strong> detik</p>

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
        $(this).toggleClass("fa-eye-slash fa-eye");
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

    })

    function signInFunction(button) {
      let userid = $('#user').val();
      let password = $('#password').val();

      // if (!userid || !password) {
      //     $("#dialog-message").html(`
      //         <span class="fa fa-exclamation-triangle" aria-hidden="true" style="font-size:25px;"></span>
      //         <p>User ID dan Password harus diisi!</p>
      //     `);
      //     $("#dialog-message").dialog({
      //         modal: true,
      //         buttons: [{
      //             text: "Ok",
      //             click: function() {
      //                 $(this).dialog("close");
      //             },
      //         }],
      //         create: function() {
      //             $(this).closest(".ui-dialog")
      //                 .find(".ui-dialog-buttonset button")
      //                 .addClass("ui-button ui-corner-all ui-widget custom-success-btn");

      //             $(this).closest(".ui-dialog")
      //                 .find(".ui-dialog-titlebar button")
      //                 .addClass("ui-button ui-corner-all ui-widget ui-button-icon-only");
      //             $(this).closest(".ui-dialog")
      //                 .find(".ui-dialog-titlebar button")
      //                 .append(`<span class="ui-button-icon ui-icon ui-icon-closethick"></span>`);
      //         }
      //     });
      //     return false;
      // }

      $(button).prop('disabled', true);
      $('#processingLoader').removeClass('d-none');
      $('form').submit();
    }

    function applyDarkMode() {
      const $body = $('body');
      localStorage.setItem('theme', 'dark');
      $body.addClass('dark-mode');
    }

    function applyLightMode() {
      const $body = $('body');
      localStorage.setItem('theme', 'light');
      $body.removeClass('dark-mode');
    }

    $(function() {
      const savedTheme = localStorage.getItem('theme');
      const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      if (systemPrefersDark) {
        applyDarkMode();
      } else {
        applyLightMode();
      }
    });
  </script>
</body>

</html>