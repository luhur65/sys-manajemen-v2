<div class="modal fade" id="aclModal" tabindex="-1" aria-labelledby="aclModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="aclModalLabel">Manage User Roles</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="fmacl">
                    <input type="hidden" name="userpk" value="<?= esc($userpk) ?>">
                    
                    <div class="form-group row mb-4">
                        <label class="col-sm-3 col-form-label font-weight-bold">Copy Dari Roles:</label>
                        <div class="col-sm-6">
                            <select id="comboroles" class="form-control">
                                <option value="">Pilih Roles</option>
                                <?php foreach($roles as $role): ?>
                                    <option value="<?= trim($role->acos) ?>"><?= esc($role->rolename) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <h6 class="font-weight-bold border-bottom pb-2 mb-3">User Permission</h6>
                    
                    <div class="mb-3">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="checkall">
                            <label class="custom-control-label font-weight-bold" for="checkall">Check All</label>
                        </div>
                    </div>
                    
                    <?php 
                    // Siapkan array data yang terpilih dari User saat ini
                    $selectedAcos = [];
                    if (!empty($data) && isset($data->acos)) {
                        $selectedAcos = array_map('trim', explode(',', $data->acos));
                    }
                    ?>

                    <div class="row">
                        <?php 
                        $currentClass = '';
                        foreach($acos as $aco): 
                            if ($currentClass != $aco->class) {
                                if ($currentClass != '') echo '</div></div></div>'; // Tutup grid sebelumnya
                                $currentClass = $aco->class;
                                echo '<div class="col-md-3 mb-4">';
                                echo '<div class="card shadow-sm h-100">';
                                echo '<div class="card-header py-2 bg-light font-weight-bold text-uppercase" style="font-size: 0.85rem;">' . esc($aco->class) . '</div>';
                                echo '<div class="card-body p-2" style="max-height: 250px; overflow-y: auto;">';
                            }
                            
                            $isChecked = in_array($aco->acosid, $selectedAcos) ? 'checked' : '';
                        ?>
                            <div class="custom-control custom-checkbox mb-1">
                                <input type="checkbox" class="custom-control-input checkbox-<?= $aco->acosid ?>" 
                                       id="aco_<?= $aco->acosid ?>" 
                                       name="role_permission[acos][]" 
                                       value="<?= $aco->acosid ?>" <?= $isChecked ?>>
                                <label class="custom-control-label" style="font-size: 0.85rem;" for="aco_<?= $aco->acosid ?>">
                                    <?= esc($aco->method) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($currentClass != '') echo '</div></div></div>'; // Tutup yang terakhir ?>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-start">
                <button type="button" class="btn btn-primary" id="btnSaveAcl"><i class="fa fa-save"></i> Save</button>
                <button type="button" class="btn btn-outline-primary" data-dismiss="modal"><i class="fa fa-times"></i> Cancel</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Toggle Check All
        $('#checkall').click(function () {
            $('input[name="role_permission[acos][]"]').prop('checked', this.checked);
        });

        // Event saat combo roles diganti
        $("#comboroles").change(function(){
            var nilai = $(this).val();
            var ex = nilai.split(", ");

            // Uncheck all
            $('input[name="role_permission[acos][]"]').prop("checked", false);
            
            // Check based on selected role
            for (var j = 0; j < ex.length; j++) {
                if (ex[j] !== "") {
                    $(".checkbox-" + ex[j]).prop("checked", true);
                }
            }
        });
        
        // Simpan Data ACL
        $('#btnSaveAcl').click(function() {
            var form = $('#fmacl');
            var url = "<?= base_url('useracl/userroles/') . $userpk ?>";
            
            $('.modal-loader').removeClass('d-none');
            
            $.ajax({
                type: "POST",
                url: url,
                data: form.serialize(),
                dataType: "json",
                success: function(res) {
                    $('.modal-loader').addClass('d-none');
                    if (res.status === 'sukses') {
                        $('#aclModal').modal('hide');
                        if (typeof $gridAcl !== 'undefined') {
                            $gridAcl.trigger("reloadGrid");
                        }
                        alert('Hak akses berhasil disimpan!');
                    } else {
                        alert('Gagal menyimpan hak akses.');
                    }
                },
                error: function() {
                    $('.modal-loader').addClass('d-none');
                    alert('Terjadi kesalahan pada server saat menyimpan data.');
                }
            });
        });
        
        // Menghapus elemen form saat modal ditutup (cleanup)
        $('#aclModal').on('hidden.bs.modal', function () {
            $(this).remove();
        });
    });
</script>
