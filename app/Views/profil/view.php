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

	});
</script>

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
    </div>
</div>
