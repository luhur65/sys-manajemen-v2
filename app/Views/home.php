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

<!-- Biometric Registration Prompt (Runs once per login if enabled) -->
<?php if (session()->getFlashdata('prompt_webauthn')): ?>
<script src="<?= asset('libraries/tas-lib/js/webauthn.js') ?>"></script>
<script>
$(document).ready(function() {
    // Check if browser supports WebAuthn
    if (window.PublicKeyCredential) {
        // Check if user already registered any device
        $.ajax({
            url: '<?= base_url('webauthn/checkRegistered') ?>',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (!res.registered) {
                    // Directly trigger the native browser WebAuthn prompt without Swal
                    startWebAuthnRegister(
                        '<?= base_url('webauthn/getRegisterArgs') ?>',
                        '<?= base_url('webauthn/processRegister') ?>',
                        function() {
                            // Optional: Silently success or small toast
                            console.log('Perangkat berhasil didaftarkan untuk Quick Login.');
                        }
                    );
                }
            }
        });
    }
});
</script>
<?php endif; ?>
