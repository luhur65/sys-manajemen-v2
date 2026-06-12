<!-- Main content -->
<div class="container-fluid">
    <!-- Info Boxes (The Dashboard buttons from sys-ci4) -->
    <div class="row">
        <?php if (!empty($buttons)): ?>
            <?php foreach ($buttons as $btn): ?>
                <div class="col-lg-3 col-6">
                    <div class="small-box <?= $btn['color'] ?>">
                        <div class="inner">
                            <p style="font-weight: bold; min-height: 50px;"><?= $btn['title'] ?></p>
                        </div>
                        <div class="icon">
                            <i class="<?= $btn['icon'] ?>"></i>
                        </div>
                        <a href="<?= $btn['link'] ?>" class="small-box-footer">
                            Buka Laporan <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <h5><i class="icon fas fa-info"></i> Welcome!</h5>
                    Anda berhasil login ke Management Information System.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="<?= asset('libraries/tas-lib/js/webauthn.js') ?>"></script>
<script>
$(document).ready(function() {
    // Check if browser supports WebAuthn
    if (window.PublicKeyCredential) {
        let userId = '<?= session()->get(SESSION_NAME . "userid") ?>';
        let regKey = 'webauthn_registered_' + userId;
        let disKey = 'webauthn_dismissed_' + userId;

        let isRegistered = localStorage.getItem(regKey);
        let isDismissed = localStorage.getItem(disKey);

        if (!isRegistered && !isDismissed) {
            // Eksekusi otomatis tanpa modal/sweetalert
            startWebAuthnRegister(
                '<?= base_url('webauthn/getRegisterArgs') ?>',
                '<?= base_url('webauthn/processRegister') ?>',
                function() {
                    localStorage.setItem(regKey, '1');
                    $('#webauthnPromoModal').modal('hide');
                    console.log('Perangkat berhasil didaftarkan.');
                    alert('Berhasil! Perangkat siap digunakan untuk Quick Login.');
                }
            );
            
            // Tandai dismissed agar tidak nge-loop auto-trigger 
            // jika user membatalkan (cancel) prompt native browser
            localStorage.setItem(disKey, '1');
            $('#webauthnPromoModal').modal('hide');
        });
    }
});
</script>

<!-- Bootstrap Modal untuk WebAuthn (Auto Popup) -->
<div class="modal fade" id="webauthnPromoModal" tabindex="-1" role="dialog" aria-labelledby="webauthnPromoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title text-primary" id="webauthnPromoModalLabel"><i class="fas fa-fingerprint"></i> Daftarkan Perangkat</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" id="btn-modal-dismiss-x">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center">
        <p>Perangkat ini belum terdaftar untuk akun Anda.</p>
        <p class="text-muted small">Aktifkan fitur <strong>Quick Login</strong> sekarang agar Anda bisa masuk dengan cepat menggunakan sidik jari atau pemindai wajah (tanpa mengetik password).</p>
      </div>
      <div class="modal-footer border-0 pt-0 justify-content-center">
        <button type="button" class="btn btn-light" id="btn-modal-dismiss">Lain Kali</button>
        <button type="button" class="btn btn-primary" id="btn-modal-register-passkey">Ya, Daftarkan Sekarang</button>
      </div>
    </div>
  </div>
</div>
