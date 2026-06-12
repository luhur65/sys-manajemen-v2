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
                    // Not registered yet, prompt user
                    Swal.fire({
                        title: 'Daftar Quick Login?',
                        text: "Browser Anda mendukung Quick Login (Biometrik/Face ID). Apakah Anda ingin mendaftarkan perangkat ini agar bisa login tanpa password di kemudian hari?",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ya, Daftarkan!',
                        cancelButtonText: 'Lain kali'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            startWebAuthnRegister(
                                '<?= base_url('webauthn/getRegisterArgs') ?>',
                                '<?= base_url('webauthn/processRegister') ?>',
                                function() {
                                    Swal.fire(
                                        'Berhasil!',
                                        'Perangkat Anda telah didaftarkan. Anda dapat menggunakan tombol Login Biometrik di halaman login berikutnya.',
                                        'success'
                                    );
                                }
                            );
                        }
                    });
                }
            }
        });
    }
});
</script>
<?php endif; ?>
