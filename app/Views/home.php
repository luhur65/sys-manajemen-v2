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
    if (!window.PublicKeyCredential) return;

    let userId = '<?= session()->get(SESSION_NAME . "userid") ?>';
    let regKey = 'webauthn_registered_' + userId;

    // Bersihkan kunci mekanisme lama (dismissed/opt-out/snooze) — penolakan
    // tidak lagi disimpan; pengecekan perangkat kini diverifikasi ke server.
    try {
        localStorage.removeItem('webauthn_dismissed_' + userId);
        localStorage.removeItem('webauthn_optout_' + userId);
        localStorage.removeItem('webauthn_snooze_' + userId);
    } catch (e) {}

    // Daftarkan perangkat ini lewat prompt native browser. excludeCredentials
    // di server menjamin perangkat yang sudah terdaftar tidak membuat duplikat.
    function registerThisDevice() {
        startWebAuthnRegister(
            '<?= base_url('webauthn/getRegisterArgs') ?>',
            '<?= base_url('webauthn/processRegister') ?>',
            function(res) {
                // Simpan credentialId sebagai penanda perangkat agar bisa
                // diverifikasi diam-diam ke server di kunjungan berikutnya
                localStorage.setItem(regKey, (res && res.credentialId) ? res.credentialId : '1');
            },
            function(errMsg, info) {
                if (info && info.alreadyRegistered) {
                    // Perangkat ternyata sudah terdaftar — pulihkan penanda
                    localStorage.setItem(regKey, '1');
                }
                // Selain itu diam saja (mis. user membatalkan prompt);
                // pendaftaran ditawarkan lagi saat membuka home berikutnya
            }
        );
    }

    PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable().then(function(available) {
        if (!available) return;

        let storedCred = localStorage.getItem(regKey);

        // Perangkat baru (belum ada penanda) → langsung munculkan dialog
        // pendaftaran passkey bawaan browser
        if (!storedCred) {
            registerThisDevice();
            return;
        }

        // Penanda ada → verifikasi diam-diam ke server; kalau credential-nya
        // sudah dihapus (mis. oleh admin di halaman profil), daftarkan ulang
        $.ajax({
            url: '<?= base_url('webauthn/checkDevice') ?>',
            type: 'GET',
            dataType: 'json',
            data: (storedCred !== '1') ? { credid: storedCred } : {},
            success: function(res) {
                if (!res.registered) {
                    localStorage.removeItem(regKey);
                    registerThisDevice();
                }
            }
        });
    }).catch(function() {});
});
</script>
