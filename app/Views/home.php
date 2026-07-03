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

<!-- Modal tawaran aktivasi login biometrik -->
<div class="modal fade" id="biometricOfferModal" tabindex="-1" role="dialog" aria-labelledby="biometricOfferLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="biometricOfferLabel"><i class="fas fa-fingerprint"></i> Aktifkan Login Biometrik?</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                Masuk lebih cepat tanpa mengetik password — gunakan sidik jari atau wajah yang tersimpan di perangkat ini.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link text-muted" id="btnBiometricNever" data-dismiss="modal">Jangan tanya lagi</button>
                <button type="button" class="btn btn-secondary" id="btnBiometricLater" data-dismiss="modal">Nanti</button>
                <button type="button" class="btn btn-primary" id="btnBiometricActivate"><i class="fas fa-fingerprint"></i> Aktifkan</button>
            </div>
        </div>
    </div>
</div>

<script src="<?= asset('libraries/tas-lib/js/webauthn.js') ?>"></script>
<script>
$(document).ready(function() {
    if (!window.PublicKeyCredential) return;

    let userId = '<?= session()->get(SESSION_NAME . "userid") ?>';
    let regKey = 'webauthn_registered_' + userId;
    let optOutKey = 'webauthn_optout_' + userId;
    let snoozeKey = 'webauthn_snooze_' + userId;
    const SNOOZE_MS = 3 * 24 * 60 * 60 * 1000; // tawarkan lagi setelah 3 hari

    // Kunci lama 'webauthn_dismissed_' terlanjur di-set otomatis untuk semua
    // user (di-set saat prompt ditembakkan, bukan saat user menolak) — abaikan
    // dan bersihkan agar user yang dulu membatalkan tetap mendapat tawaran.
    try { localStorage.removeItem('webauthn_dismissed_' + userId); } catch (e) {}

    let isRegistered = localStorage.getItem(regKey);
    let isOptOut = localStorage.getItem(optOutKey);
    let snoozeUntil = parseInt(localStorage.getItem(snoozeKey) || '0', 10);

    if (isRegistered || isOptOut || Date.now() < snoozeUntil) return;

    // Tawarkan hanya jika perangkat benar-benar punya authenticator biometrik
    PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable().then(function(available) {
        if (available) {
            $('#biometricOfferModal').modal('show');
        }
    }).catch(function() {});

    $('#btnBiometricActivate').on('click', function() {
        $('#biometricOfferModal').modal('hide');

        // Snooze dipasang dulu: kalau user membatalkan prompt native browser,
        // tawaran baru muncul lagi setelah masa snooze, bukan hilang selamanya
        localStorage.setItem(snoozeKey, String(Date.now() + SNOOZE_MS));

        startWebAuthnRegister(
            '<?= base_url('webauthn/getRegisterArgs') ?>',
            '<?= base_url('webauthn/processRegister') ?>',
            function() {
                localStorage.setItem(regKey, '1');
                localStorage.removeItem(snoozeKey);
                showDialog('Login biometrik berhasil diaktifkan! Gunakan sidik jari/wajah Anda saat login berikutnya.');
            },
            function(errMsg, info) {
                if (info && info.alreadyRegistered) {
                    // Perangkat ini ternyata sudah terdaftar (ditolak lewat
                    // excludeCredentials) — pulihkan flag yang hilang agar
                    // dialog berhenti menawarkan di perangkat ini
                    localStorage.setItem(regKey, '1');
                    localStorage.removeItem(snoozeKey);
                    showDialog('Perangkat ini sudah terdaftar. Anda bisa langsung login dengan sidik jari/wajah.');
                } else {
                    showDialog(errMsg);
                }
            }
        );
    });

    $('#btnBiometricLater').on('click', function() {
        localStorage.setItem(snoozeKey, String(Date.now() + SNOOZE_MS));
    });

    $('#btnBiometricNever').on('click', function() {
        localStorage.setItem(optOutKey, '1');
    });

    // Ditutup lewat tombol X / klik backdrop = perlakukan seperti "Nanti"
    $('#biometricOfferModal').on('hidden.bs.modal', function() {
        let decided = localStorage.getItem(regKey)
            || localStorage.getItem(optOutKey)
            || parseInt(localStorage.getItem(snoozeKey) || '0', 10) > Date.now();
        if (!decided) {
            localStorage.setItem(snoozeKey, String(Date.now() + SNOOZE_MS));
        }
    });
});
</script>
