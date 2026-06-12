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
                    console.log('Perangkat berhasil didaftarkan.');
                }
            );
            
            // Tandai dismissed agar tidak nge-loop auto-trigger 
            // jika user membatalkan (cancel) prompt native browser
            localStorage.setItem(disKey, '1');
        }
    }
});
</script>
