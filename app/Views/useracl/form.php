<style>

</style>
<div class="modal modal-fullscreen" id="aclModal" tabindex="-1" aria-labelledby="aclModalLabel" aria-hidden="true">
    <div class="modal-dialog">
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

                    ?>
                    
                    <table id="jqGridAcos"></table>
                    <div id="jqGridAcosPager"></div>
                    
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
        // Track ID secara global untuk mengatasi bug checkbox hilang saat filter lokal
        window.selectedAcosIds = <?= json_encode($selectedAcos) ?>.map(String);
        
        var $gridAcos = $("#jqGridAcos");
        const isDesktop = (detectDeviceType() == "desktop");
        
        $gridAcos.jqGrid({
            url: "<?= base_url('useracl/getAcos') ?>",
            mtype: "GET",
            datatype: "json",
            jsonReader: { repeatitems: true },
            styleUI: 'Bootstrap4',
            iconSet: 'fontAwesome',
            colModel: [
                { 
                    label: 'ID', 
                    name: 'acosid', 
                    key: true, 
                    hidden: true 
                },
                { 
                    label: 'Class / Modul', 
                    name: 'class', 
                    width: (isDesktop ? sm_dekstop_4 : sm_mobile_4),  
                    searchoptions:{sopt:['cn']} 
                },
                { 
                    label: 'Method / Aksi', 
                    name: 'method', 
                    width: (isDesktop ? sm_dekstop_4 : sm_mobile_4),  
                    searchoptions:{sopt:['cn']} 
                },
                { 
                    label: 'Display Name', 
                    name: 'displayname', 
                    width: (isDesktop ? md_dekstop_4 : sm_mobile_4),  
                    searchoptions:{sopt:['cn']} 
                }
            ],
            viewrecords: false,
            autowidth: true,
            shrinkToFit: false,
            height: 'calc(100vh - 350px)',
            rowNum: 50,
            multiselect: true,
            pager: '#jqGridAcosPager',
            loadonce: true,
            onSelectRow: function(rowid, status, e) {
                var strId = String(rowid);
                if (status) {
                    if (window.selectedAcosIds.indexOf(strId) === -1) {
                        window.selectedAcosIds.push(strId);
                    }
                } else {
                    var idx = window.selectedAcosIds.indexOf(strId);
                    if (idx > -1) {
                        window.selectedAcosIds.splice(idx, 1);
                    }
                }
            },
            onSelectAll: function(aRowids, status) {
                for (var i = 0; i < aRowids.length; i++) {
                    var strId = String(aRowids[i]);
                    if (status) {
                        if (window.selectedAcosIds.indexOf(strId) === -1) {
                            window.selectedAcosIds.push(strId);
                        }
                    } else {
                        var idx = window.selectedAcosIds.indexOf(strId);
                        if (idx > -1) {
                            window.selectedAcosIds.splice(idx, 1);
                        }
                    }
                }
            },
            loadComplete: function() {
                var grid = $("#jqGridAcos");
                var visibleIds = grid.jqGrid('getDataIDs');
                var selRows = grid.jqGrid('getGridParam', 'selarrrow') || [];
                // Restore selection
                for (var i = 0; i < visibleIds.length; i++) {
                    var strId = String(visibleIds[i]);
                    if (window.selectedAcosIds.indexOf(strId) > -1) {
                        if (selRows.indexOf(strId) === -1) {
                            grid.jqGrid('setSelection', strId, false);
                        }
                    }
                }
                
                // Ganti icon sorting dari caret ke arrow (menyamai jqgrid utama)
                setTimeout(function() {
                    $('#gview_jqGridAcos .fa-caret-up').removeClass('fa-caret-up').addClass('fa-fw fa-arrow-up');
                    $('#gview_jqGridAcos .fa-caret-down').removeClass('fa-caret-down').addClass('fa-fw fa-arrow-down');
                }, 10);
                
                // Add View Text correctly for modal grid since we bypass native loadPagerInfo
                var start = 1;
                var end = grid.jqGrid('getDataIDs').length;
                var records = grid.jqGrid('getGridParam', 'records');
                if (records === 0) start = 0;
                $('#jqGridAcosInfoHandler').html(`View ${start} - ${end} of ${records}`);
            }
        }).customPager({
            lazyLoading: true,
            buttons: []
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
            window.selectedAcosIds = [];
            
            // Check based on selected role
            for (var j = 0; j < ex.length; j++) {
                var t = ex[j].trim();
                if (t !== "") {
                    window.selectedAcosIds.push(t);
                    $gridAcos.jqGrid('setSelection', t, false);
                }
            }
        });
        
        // Simpan Data ACL
        $('#btnSaveAcl').click(function() {
            var url = "<?= base_url('useracl/userroles/') . $userpk ?>";
            
            // Dapatkan ID dari array tracking kita, BUKAN dari grid (karena grid membuang data yang ter-filter)
            var selRowIds = window.selectedAcosIds;
            
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

        $('#aclModal').on('shown.bs.modal', function () {
            $("#jqGridAcos").jqGrid('setGridWidth', $('#aclModal .modal-body').width());
        });
    });
</script>
