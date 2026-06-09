<style>
.modal-fullscreen .modal-dialog {
    max-width: 100% !important;
    margin: 0 !important;
    height: 100vh;
}
.modal-fullscreen .modal-content {
    height: 100vh;
    border: 0;
    border-radius: 0;
}
</style>
<div class="modal fade modal-fullscreen" id="aclModal" tabindex="-1" aria-labelledby="aclModalLabel" aria-hidden="true">
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
                    
                    <h6 class="font-weight-bold border-bottom pb-2 mb-3">User Permission (Pilih Menu/Aksi)</h6>
                    
                    <?php 
                    // Siapkan array data yang terpilih dari User saat ini
                    $selectedAcos = [];
                    if (!empty($data) && isset($data->acos)) {
                        $selectedAcos = array_map('trim', explode(',', $data->acos));
                    }

                    // Format data untuk jqGrid
                    $gridData = [];
                    foreach($acos as $aco) {
                        $className = trim($aco->class ?? '');
                        $methodName = trim($aco->method ?? '');
                        $displayName = trim($aco->display_name ?? '');
                        
                        if ($className === '') {
                            $className = $displayName !== '' ? '[MENU] ' . $displayName : '[PARENT MENU / SEPARATOR]';
                        }
                        if ($methodName === '') {
                            $methodName = '-';
                        }
                        
                        $gridData[] = [
                            'acosid' => $aco->acosid,
                            'class' => $className,
                            'method' => $methodName
                        ];
                    }
                    ?>
                    
                    <div class="row">
                        <div class="col-12">
                            <table id="jqGridAcos"></table>
                            <div id="jqGridAcosPager"></div>
                        </div>
                    </div>
                    
                    <div id="hidden-inputs-container"></div>
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
        var mydata = <?= json_encode($gridData) ?>;
        var selectedIds = <?= json_encode($selectedAcos) ?>;
        
        $gridAcos = $("#jqGridAcos");
        
        $gridAcos.jqGrid({
            datatype: "local",
            data: mydata,
            localReader: { id: "acosid" },
            styleUI: 'Bootstrap4',
            iconSet: 'fontAwesome',
            colModel: [
                { label: 'ID', name: 'acosid', key: true, hidden: true },
                { label: 'Class / Modul', name: 'class', width: 200, searchoptions:{sopt:['cn']} },
                { label: 'Method / Aksi', name: 'method', width: 250, searchoptions:{sopt:['cn']} }
            ],
            viewrecords: true,
            autowidth: true,
            shrinkToFit: true,
            height: 'calc(100vh - 350px)',
            rowNum: 10000,
            multiselect: true,
            pager: '#jqGridAcosPager',
            gridComplete: function() {
                var grid = $("#jqGridAcos");
                // Cegah trigger event onSelectRow saat setSelection awal
                var i;
                for (i = 0; i < selectedIds.length; i++) {
                    grid.jqGrid('setSelection', selectedIds[i], false);
                }
            }
        });
        
        $gridAcos.jqGrid('filterToolbar', {
            stringResult: true,
            searchOnEnter: false,
            defaultSearch: 'cn'
        });

        // Hapus elemen pager bawaan jqGrid yang tidak diperlukan untuk data lokal
        $('#jqGridAcosPager_center').hide();

        // Event saat combo roles diganti
        $("#comboroles").change(function(){
            var nilai = $(this).val();
            var ex = nilai.split(",");

            // Uncheck all
            $gridAcos.jqGrid('resetSelection');
            
            // Check based on selected role
            for (var j = 0; j < ex.length; j++) {
                if (ex[j].trim() !== "") {
                    $gridAcos.jqGrid('setSelection', ex[j].trim(), false);
                }
            }
        });
        
        // Simpan Data ACL
        $('#btnSaveAcl').click(function() {
            var url = "<?= base_url('useracl/userroles/') . $userpk ?>";
            
            // Dapatkan ID yang dipilih di jqGrid
            var selRowIds = $gridAcos.jqGrid('getGridParam', 'selarrrow');
            
            // Masukkan ke dalam hidden inputs agar bisa di-serialize()
            var container = $('#hidden-inputs-container');
            container.empty();
            
            $.each(selRowIds, function(index, value) {
                container.append('<input type="hidden" name="role_permission[acos][]" value="' + value + '">');
            });
            
            // Jika kosong, tambahkan array kosong agar dikirim ke server
            if (selRowIds.length === 0) {
                container.append('<input type="hidden" name="role_permission[acos][]" value="">');
            }
            
            var form = $('#fmacl');
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
                        if (typeof window.$gridAcl !== 'undefined') {
                            window.$gridAcl.trigger("reloadGrid");
                        }
                        alert('Hak akses berhasil disimpan! Perubahan akan langsung aktif.');
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
