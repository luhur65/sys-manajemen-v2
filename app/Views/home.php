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
                    <hr>
                    <button type="button" class="btn btn-sm btn-light" id="btn-register-passkey">
                        <i class="fas fa-fingerprint"></i> Daftarkan Perangkat Ini untuk Quick Login
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="<?= asset('libraries/tas-lib/js/webauthn.js') ?>"></script>
<script>
$(document).ready(function() {
    // Check if browser supports WebAuthn, if not, hide the button
    if (!window.PublicKeyCredential) {
        $('#btn-register-passkey').hide();
    }

    // Handle manual registration click
    $('#btn-register-passkey').click(function() {
        startWebAuthnRegister(
            '<?= base_url('webauthn/getRegisterArgs') ?>',
            '<?= base_url('webauthn/processRegister') ?>',
            function() {
                Swal.fire(
                    'Berhasil!',
                    'Perangkat Anda telah ditambahkan. Anda dapat menggunakan tombol Login Biometrik di perangkat ini pada login berikutnya.',
                    'success'
                );
            }
        );
    });
});
</script>
