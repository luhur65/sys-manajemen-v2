<script type="text/javascript">
	$(document).ready(function(){

	    $("#btnsimpanprofil").on('click',function(){
		var username = $("#username").val();
		var userid = $("#userid").val();
	        $.ajax({
                url: "<?= base_url()?>profil/editprofil",
                data : "userid="+userid+"&username="+username,
				success: function(data) {
					if(data==1){
						alert("data berhasil di ganti");
					}
					else{
						alert("data gagal di ganti");
					}
				}
		    });
	    });

	    $("#btnsimpanpassword").on('click',function(){
		var password1 = $("#password1").val();
		var password2 = $("#password2").val();
		var password3 = $("#password3").val();

		if(password1==""){
			alert("password tidak boleh kosong");
			$("#password1").focus();
			return false;
		}
		if(password2==""){
			alert("password tidak boleh kosong");
			$("#password2").focus();
			return false;
		}
		if(password3==""){
			alert("password tidak boleh kosong");
			$("#password3").focus();
			return false;
		}
	        $.ajax({
                url: "<?= base_url()?>profil/editpassword",
				dataType: 'JSON',
                data : "password1="+encodeURIComponent(password1)+"&password2="+encodeURIComponent(password2)+"&password3="+encodeURIComponent(password3),
				success: function(data) {
					console.log(data)
					if(data==1){
						alert("Password Lama Salah");
					$("#password1").focus();
					}
					if(data==2){
						alert("Password Baru Tidak Sama");
					$("#password2").focus();
					}
					if(data==3){
						alert("Password Berhasil Diganti");
					}
				}
		    });
	    });

	    $("#btnRegisterBiometric").on('click',function(){
            startWebAuthnRegister(
                '<?= base_url() ?>webauthn/getRegisterArgs',
                '<?= base_url() ?>webauthn/processRegister',
                function() {
                    alert("Pendaftaran biometrik berhasil! Anda sekarang bisa login menggunakan sidik jari/wajah.");
                }
            );
        });

	});
</script>
<script src="<?= asset('libraries/tas-lib/js/webauthn.js?version=' . time()) ?>"></script>

<div class="row">
    <div class="col-md">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Edit Password</h3>
            </div>
            <div class="card-body">
                <div class="form-group row">
                    <label for="password1" class="col-sm-3 col-form-label">Password Lama</label>
                    <div class="col-sm-6">
                        <div class="input-group">
                            <input type="password" class="form-control" name="password1" id="password1">
                            <div class="input-group-append">
                                <div class="input-group-text focusPass">
                                    <span class="fas fa-eye toggle-password" toggle="#password1" style="cursor: pointer;"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group row">
                    <label for="password2" class="col-sm-3 col-form-label">Password Baru</label>
                    <div class="col-sm-6">
                        <div class="input-group">
                            <input type="password" class="form-control" name="password2" id="password2">
                            <div class="input-group-append">
                                <div class="input-group-text focusPass">
                                    <span class="fas fa-eye toggle-password" toggle="#password2" style="cursor: pointer;"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group row">
                    <label for="password3" class="col-sm-3 col-form-label">Ulang Password Baru</label>
                    <div class="col-sm-6">
                        <div class="input-group">
                            <input type="password" class="form-control" name="password3" id="password3">
                            <div class="input-group-append">
                                <div class="input-group-text focusPass">
                                    <span class="fas fa-eye toggle-password" toggle="#password3" style="cursor: pointer;"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-right">
                <button type="button" class="btn btn-primary" id="btnsimpanpassword"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </div>
        
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title">Login Biometrik (Sidik Jari / Passkey)</h3>
            </div>
            <div class="card-body">
                <p>Anda dapat mendaftarkan perangkat ini (Sidik Jari, Face ID, atau Windows Hello) agar bisa digunakan untuk login ke depannya tanpa memasukkan password.</p>
                <button type="button" class="btn btn-primary" id="btnRegisterBiometric">
                    <i class="fas fa-fingerprint"></i> Daftarkan Perangkat Ini
                </button>

                <hr>
                <h5 class="mt-4">Perangkat Terdaftar</h5>
                <?php if (!empty($webauthn_devices)): ?>
                    <div class="table-responsive mt-3">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>ID Kredensial</th>
                                    <th>Tanggal Didaftarkan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($webauthn_devices as $device): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td>
                                            <code><?= substr($device['credentialId'], 0, 15) ?>...</code>
                                        </td>
                                        <td><?= date('d M Y H:i:s', strtotime($device['created_at'])) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger btn-delete-device" data-id="<?= $device['id'] ?>">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle"></i> Belum ada perangkat yang terdaftar untuk Login Biometrik.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.btn-delete-device').on('click', function() {
        if (confirm('Yakin ingin menghapus perangkat ini? Anda tidak akan bisa menggunakannya untuk login lagi sebelum didaftarkan ulang.')) {
            let id = $(this).data('id');
            $.ajax({
                url: '<?= base_url('profil/deleteWebauthnDevice') ?>',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        // Clear localStorage so if they login again on that device, it prompts again
                        let userId = '<?= session()->get(SESSION_NAME . "userid") ?>';
                        localStorage.removeItem('webauthn_registered_' + userId);
                        localStorage.removeItem('webauthn_dismissed_' + userId);
                        
                        alert('Perangkat berhasil dihapus.');
                        location.reload();
                    } else {
                        alert('Gagal menghapus perangkat: ' + res.message);
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan jaringan.');
                }
            });
        }
    });
});
</script>
