<div class="row">
    <div class="col-12">
        <table id="jqGrid"></table>
    </div>
    <div id="dataacl" class="col-12"></div>
</div>

<!-- Modal Form -->
<div class="modal modal-fullscreen" id="crudModal" tabindex="-1" aria-labelledby="crudModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="fm" method="post">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="crudModalLabel">Form User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="action">
                    <input type="hidden" name="id" id="id">
                    
                    <div class="row form-group">
                        <div class="col-12 col-sm-3 col-md-2">
                            <label class="col-form-label">User ID <span class="text-danger">*</span></label>
                        </div>
                        <div class="col-12 col-sm-9 col-md-10">
                            <input type="text" class="form-control text-uppercase" name="userid" id="userid" required autofocus>
                        </div>
                    </div>
                    
                    <div class="row form-group">
                        <div class="col-12 col-sm-3 col-md-2">
                            <label class="col-form-label">Username <span class="text-danger">*</span></label>
                        </div>
                        <div class="col-12 col-sm-9 col-md-10">
                            <input type="text" class="form-control text-uppercase" name="username" id="username" required>
                        </div>
                    </div>

                    <!--
                        Karyawan (master HR). Nilai yang tersimpan adalah karyawanid —
                        identitas yang sama dengan klaim `karyawanId` pada tiket SSO,
                        jadi inilah yang dicocokkan saat pengguna masuk lewat SSO.
                        Sengaja hanya bisa diisi lewat lookup: id-nya harus datang
                        dari master HR, bukan diketik.
                        Boleh kosong — akun sistem seperti ADMIN tidak punya karyawan.
                    -->
                    <div class="row form-group">
                        <div class="col-12 col-sm-3 col-md-2">
                            <label class="col-form-label">Karyawan (HR)</label>
                        </div>
                        <div class="col-12 col-sm-9 col-md-10">
                            <div class="input-group">
                                <input type="hidden" name="karyawanid" id="karyawanid">
                                <input type="text" class="form-control" id="namakaryawan" readonly placeholder="(belum dipetakan)">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" id="btnLookupKaryawan" title="Cari karyawan">...</button>
                                    <button class="btn btn-outline-secondary" type="button" id="btnHapusKaryawan" title="Kosongkan"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                            <small class="text-muted">Dipakai untuk mencocokkan akun saat login lewat SSO.</small>
                        </div>
                    </div>

                    <div class="row form-group">
                        <div class="col-12 col-sm-3 col-md-2">
                            <label class="col-form-label">Status</label>
                        </div>
                        <div class="col-12 col-sm-9 col-md-10">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" name="aktif" id="aktif" value="1">
                                <label class="custom-control-label" for="aktif">Akun aktif</label>
                            </div>
                            <small class="text-muted">
                                Akun nonaktif ditolak di semua jalur masuk — password, biometrik,
                                SSO, dan Panel Casting — begitu <code>security.userAktifEnforce</code>
                                dinyalakan.
                            </small>
                        </div>
                    </div>

                    <div class="row form-group" id="password-container">
                        <div class="col-12 col-sm-3 col-md-2">
                            <label class="col-form-label">Password <span class="text-danger">*</span></label>
                        </div>
                        <div class="col-12 col-sm-9 col-md-10">
                            <div class="input-group">
                                <input type="password" class="form-control" name="password" id="password">
                                <div class="input-group-append">
                                    <span class="input-group-text" id="togglePassword" style="cursor: pointer;">
                                        <i class="fa fa-eye"></i>
                                    </span>
                                </div>
                            </div>
                            <small class="text-muted d-none" id="password-help">Biarkan kosong jika tidak ingin mengubah password.</small>
                        </div>
                    </div>
                    
                    <div class="row form-group">
                        <div class="col-12 col-sm-3 col-md-2">
                            <label class="col-form-label">Email</label>
                        </div>
                        <div class="col-12 col-sm-9 col-md-10">
                            <input type="email" class="form-control" name="email" id="email" placeholder="example@domain.com">
                        </div>
                    </div>

                    <div class="row form-group">
                        <div class="col-12 col-sm-3 col-md-2">
                            <label class="col-form-label">No WhatsApp</label>
                        </div>
                        <div class="col-12 col-sm-9 col-md-10">
                            <input type="text" class="form-control" name="nowhatsapp" id="nowhatsapp" placeholder="08123456789">
                        </div>
                    </div>
                    
                    <div class="row form-group">
                        <div class="col-12 col-sm-3 col-md-2">
                            <label class="col-form-label">Dashboard <span class="text-danger">*</span></label>
                        </div>
                        <div class="col-12 col-sm-9 col-md-10">
                            <input type="text" class="form-control" name="dashboard" id="dashboard" required>
                        </div>
                    </div>

                    <div class="row form-group">
                        <div class="col-12 col-sm-3 col-md-2">
                            <label class="col-form-label">Roles <span class="text-danger">*</span></label>
                        </div>
                        <div class="col-12 col-sm-9 col-md-10">
                            <link href="<?= asset('libraries/select2-3.4.6/select2.css') ?>" rel="stylesheet"/>
                            <script src="<?= asset('libraries/select2-3.4.6/select2.js') ?>"></script>
                            <select id="user_roles" name="user_roles[]" multiple="multiple" style="width:100%;" required>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-start">
                    <button type="submit" class="btn btn-primary" id="btnSave"><i class="fas fa-save"></i> Save</button>
                    <button type="button" class="btn btn-outline-primary" data-dismiss="modal"><i class="fas fa-times"></i> Close</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Lookup Karyawan (master HR) -->
<div class="modal fade" id="lookupKaryawanModal" tabindex="-1" aria-labelledby="lookupKaryawanModalLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lookupKaryawanModalLabel">Lookup Karyawan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <table id="jqGridKaryawan"></table>
                <div id="jqGridKaryawanPager"></div>
            </div>
        </div>
    </div>
</div>

<script>
    const apiUrl = `<?= base_url('User/grid') ?>`;
    const crudUrl = `<?= base_url('User/crud') ?>`;
    const getUrl = `<?= base_url('User/getById') ?>`;
    const lookupKaryawanUrl = `<?= base_url('User/lookupKaryawan') ?>`;
    let $grid = $("#jqGrid");
    let $gridKaryawan = $("#jqGridKaryawan");
    let karyawanGridLoaded = false;

    let indexRow = 0;
    let triggerClick = true;
    let limit;
    let postData;
    let sortname = 'userid';
    let sortorder = 'asc';
    let rowNum = 50;
    let id = '';
    let activeGrid;

    function loadAclGrid(userpk) {
        $('.modal-loader').removeClass('d-none');
        var page = "<?= base_url('useracl/index') ?>?userpk=" + userpk + "&_=" + new Date().getTime();
        $('#dataacl').load(page, function() {
            $('.modal-loader').addClass('d-none');
        });
    }

    function loadRoles(selectedRoles = []) {
        $.ajax({
            url: "<?= base_url('User/getRoles') ?>",
            type: "GET",
            dataType: "json",
            success: function(data) {
                var select = $('#user_roles');
                select.empty();
                $.each(data, function(index, item) {
                    var isSelected = selectedRoles.includes(item.roleid) ? 'selected' : '';
                    select.append('<option value="' + item.roleid + '" ' + isSelected + '><span style="color:rgb(255,0,0);">' + item.rolename + '</span></option>');
                });
                select.trigger('change');
            }
        });
    }

    $(document).ready(function() {
        // Detect Device Widths (Inspired by Trucking)
        const isDesktop = (detectDeviceType() == "desktop");

        // Initialize Select2 inline like CI3 or basic select2
        $("#user_roles").select2({
            placeholder: "Select Roles"
        });

        // End select2 init
        
        // Toggle password visibility
        $('#togglePassword').click(function() {
            const passwordField = $('#password');
            const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
            passwordField.attr('type', type);
            
            // Toggle icon
            $(this).find('i').toggleClass('fa-eye fa-eye-slash');
        });

        $grid.jqGrid({
            url: apiUrl,
            mtype: "POST",
            datatype: "local",
            styleUI: 'Bootstrap4',
            iconSet: 'fontAwesome',
            colModel: [
                {
                    label: 'ID', 
                    name: 'userpk', 
                    index: 'userpk', 
                    key: true, 
                    hidden:true,
                    search: false
                },
                {
                    label: 'User ID', 
                    name: 'userid', 
                    index: 'userid', 
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_2), 
                    
                },
                {
                    label: 'Username',
                    name: 'username',
                    index: 'username',
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_2),

                },
                {
                    // Dibaca dari database HR, bukan dari tbluser — jadi tidak
                    // bisa ikut difilter atau diurutkan lewat query grid ini.
                    label: 'Karyawan (HR)',
                    name: 'namakaryawan',
                    index: 'namakaryawan',
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_2),
                    search: false,
                    sortable: false,
                },
                {
                    // Nilainya sudah diterjemahkan di server lewat UserStatus,
                    // jadi grid tidak ikut menebak arti 0/1 sendiri.
                    label: 'Status',
                    name: 'statusaktif',
                    index: 'statusaktif',
                    width: (isDesktop ? sm_dekstop_2 : sm_mobile_2),
                    search: false,
                    sortable: false,
                },
                {
                    label: 'Dashboard', 
                    name: 'dashboard', 
                    index: 'dashboard', 
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_2), 
                    
                },
                {
                    label: 'Email', 
                    name: 'email', 
                    index: 'email', 
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_3), 
                    
                },
                {
                    label: 'No WhatsApp', 
                    name: 'nowhatsapp', 
                    index: 'nowhatsapp', 
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_3), 
                    
                },
                {
                    label: 'Roles', 
                    name: 'rolename', 
                    index: 'rolename', 
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_3), 
                    sortable: false,
                },
                {
                    label: 'Modified By', 
                    name: 'modifiedby', 
                    index: 'modifiedby', 
                    width: (isDesktop ? sm_dekstop_2 : sm_mobile_2), 
                    
                },
                {
                    label: 'Modified On', 
                    name: 'modifiedonview', 
                    index: 'modifiedon', 
                    width: (isDesktop ? sm_dekstop_4 : sm_mobile_4), 
                    
                }
            ],
            autowidth: true,
            shrinkToFit: false,
            height: 400,
            rowNum: rowNum,
            toolbar: [true, "top"],
            rowList: [10, 20, 50, 100],
            viewrecords: false,
            rownumbers: true,
            rownumWidth: 45,
            gridview: true,
            altRows: true,
            altclass: 'myAltRowClass',
            sortable: true,
            sortname: sortname,
            sortorder: sortorder,
            onSelectRow: function(id) {
                activeGrid = $(this);
                indexRow = $grid.jqGrid('getCell', id, 'rn') - 1;
                let page = $grid.jqGrid('getGridParam', 'page');
                let limit = $grid.jqGrid('getGridParam', 'postData').limit;
                if (indexRow >= limit) indexRow = (indexRow - limit * (page - 1));
                
                loadAclGrid(id);
            },
            onSortCol: function(index, iCol, sortorder) {
                // Fix: Update jqGrid internal state so it toggles next time and sends correct sort to server
                this.p.sortorder = sortorder;
                this.p.sortname = index;
                var pd = $(this).jqGrid('getGridParam', 'postData');
                if (pd) {
                    pd.sidx = index;
                    pd.sord = sortorder;
                }

                // Update UI sorting arrows manually since we are using return 'stop'
                var previousSelectedTh = this.grid.headers[this.p.lastsort].el;
                var newSelectedTh = this.grid.headers[iCol].el;
                $("span.s-ico", previousSelectedTh).hide();
                $("span.s-ico", newSelectedTh).show();
                var disabledClass = "ui-state-disabled";
                $("span.ui-icon-asc, span.ui-icon-desc", newSelectedTh).addClass(disabledClass);
                $("span.ui-icon-" + sortorder, newSelectedTh).removeClass(disabledClass);
                this.p.lastsort = iCol;


                var targetGridId = this.id || 'jqGrid'; if (typeof lazyStates !== 'undefined' && lazyStates[targetGridId]) lazyStates[targetGridId].cachedData = {};
                if(typeof loadGridData === 'function') {
                    loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $(this).jqGrid('getGridParam', 'rowNum'), 'jump', 'reload');
                }
                return 'stop';
            },
            loadComplete: function(res) {
                // Gracefully clear footer by feeding an empty userdata object
                // This preserves custom footer text labels but zeroes out the totals
                if (res && (res.records === 0 || res.records === "0")) {
                    res.userdata = {};
                    try { $(this).jqGrid('setGridParam', { userData: null }); } catch(e) {}
                    $('#lastUpdateHandler, #jqGridInfoHandler').text('');
                }
                
                sortname = $(this).jqGrid("getGridParam", "sortname");
                sortorder = $(this).jqGrid("getGridParam", "sortorder");
                limit = $(this).jqGrid('getGridParam', 'postData').limit;
                postData = $(this).jqGrid('getGridParam', 'postData');
                triggerClick = true;

                if (indexRow > $(this).getDataIDs().length - 1) {
                    indexRow = $(this).getDataIDs().length - 1;
                }

                if (triggerClick) {
                    if (id != '') {
                        indexRow = parseInt($('#jqGrid').jqGrid('getInd', id)) - 1;
                        $(`#jqGrid [id="${$('#jqGrid').getDataIDs()[indexRow]}"]`).click();
                        id = '';
                    } else if (indexRow != undefined) {
                        $(`#jqGrid [id="${$('#jqGrid').getDataIDs()[indexRow]}"]`).click();
                    }
                    if ($('#jqGrid').getDataIDs()[indexRow] == undefined) {
                        $(`#jqGrid [id="${$('#jqGrid').getDataIDs()[0]}"]`).click();
                    }
                    triggerClick = false;
                } else {
                    $('#jqGrid').setSelection($('#jqGrid').getDataIDs()[indexRow]);
                }
                
                $(document).unbind('keydown');
                if(typeof setCustomBindKeys === 'function') {
                    setCustomBindKeys($(this));
                }

                if(typeof setupLazyLoadScrollHandler === 'function') {
                    setupLazyLoadScrollHandler("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'));
                }
                if(typeof setHighlight === 'function') {
                    setHighlight($grid);
                }

                $grid.removeClass('table-striped');

                // Add View Text
                $('#jqGrid_center').css('width', '405px');
                var jumlah = res.rows == undefined ? 0 : res.rows.length;
                if ($("#showList").length == 0) {
                    $("#jqGridPager_center table tbody tr").append(`<td><span id="showList"></span></td>`);
                }
                $("#showList").html(`View 1 - ${jumlah} of ${res.records}`);
            }
        })
        .jqGrid('filterToolbar', {
            stringResult: true,
            searchOnEnter: false,
            defaultSearch: 'cn',
            beforeSearch: function() {
                var targetGridId = this.id || 'jqGrid'; if (typeof lazyStates !== 'undefined' && lazyStates[targetGridId]) lazyStates[targetGridId].cachedData = {};
                $grid.jqGrid('clearGridData');
            

                loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $grid.jqGrid('getGridParam', 'rowNum'), 'down', 'reload');
                return false;
            }
        })
        .customPager({
            lazyLoading: true,
            buttons: [
                {
                    id: 'add',
                    innerHTML: '<i class="fa fa-plus"></i> ADD',
                    class: 'btn btn-primary btn-sm mr-1',
                    onClick: () => {
                        newData();
                    }
                },
                {
                    id: 'edit',
                    innerHTML: '<i class="fa fa-pen"></i> EDIT',
                    class: 'btn btn-success btn-sm mr-1',
                    onClick: () => {
                        let selectedId = $grid.jqGrid('getGridParam', 'selrow');
                        if (selectedId) {
                            editData(selectedId);
                        } else {
                            $('.modal-loader').addClass('d-none');
                            alert('Pilih data terlebih dahulu!');
                        }
                    }
                },
                {
                    id: 'delete',
                    innerHTML: '<i class="fa fa-trash"></i> DELETE',
                    class: 'btn btn-danger btn-sm mr-1',
                    onClick: () => {
                        let selectedId = $grid.jqGrid('getGridParam', 'selrow');
                        if (selectedId) {
                            deleteData(selectedId);
                        } else {
                            $('.modal-loader').addClass('d-none');
                            alert('Pilih data terlebih dahulu!');
                        }
                    }
                },
                {
                    id: 'view',
                    innerHTML: '<i class="fa fa-eye"></i> VIEW',
                    class: 'btn btn-info btn-sm mr-1',
                    onClick: () => {
                        let selectedId = $grid.jqGrid('getGridParam', 'selrow');
                        if (selectedId) {
                            viewData(selectedId);
                        } else {
                            $('.modal-loader').addClass('d-none');
                            alert('Pilih data terlebih dahulu!');
                        }
                    }
                }
            ]
        });

        // Initial load
        loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, rowNum, 'down', 'reload');

        // Logic for clear search button
        $(document).on('keyup input', '.ui-search-input input', function() {
            const $input = $(this);
            const $clearBtn = $input.closest('tr').find('.clearsearchclass');

            if ($input.val().length > 0) {
                $clearBtn.attr('style', 'display: flex !important');
            } else {
                $clearBtn.attr('style', 'display: none !important');
            }
        });

        $(document).on('click', '.clearsearchclass', function() {
            $(this).attr('style', 'display: none !important');
        });

        // --- Lookup Karyawan (master HR) ---------------------------------
        // datatype 'local' saat init supaya membuka halaman User tidak ikut
        // memanggil database HR; baru dialihkan ke 'json' ketika lookup-nya
        // benar-benar dibuka pertama kali.
        $gridKaryawan.jqGrid({
            url: lookupKaryawanUrl,
            mtype: "POST",
            datatype: "local",
            styleUI: 'Bootstrap4',
            iconSet: 'fontAwesome',
            colModel: [
                { label: 'ID', name: 'id', index: 'id', key: true, hidden: true, search: false },
                { label: 'Kode', name: 'kodekaryawan', index: 'kodekaryawan', width: 110 },
                { label: 'Nama Karyawan', name: 'namakaryawan', index: 'namakaryawan', width: 220 },
                { label: 'Cabang', name: 'cabang', index: 'cabang', width: 130 },
                { label: 'Jabatan', name: 'jabatan', index: 'jabatan', width: 150 }
            ],
            autowidth: true,
            shrinkToFit: true,
            height: 400,
            rowNum: 10,
            rowList: [10, 20, 50],
            pager: '#jqGridKaryawanPager',
            viewrecords: true,
            rownumbers: true,
            gridview: true,
            altRows: true,
            altclass: 'myAltRowClass',
            sortable: true,
            sortname: 'namakaryawan',
            sortorder: 'asc',
            onSelectRow: function(rowid) {
                let rowData = $(this).getRowData(rowid);
                setKaryawan(rowData.id, rowData.namakaryawan);
                $('#lookupKaryawanModal').modal('hide');
            }
        }).jqGrid('filterToolbar', {
            stringResult: true,
            searchOnEnter: false,
            defaultSearch: 'cn'
        });

        $('#btnLookupKaryawan').on('click', function() {
            $('#lookupKaryawanModal').modal('show');
            setTimeout(function() {
                $gridKaryawan.jqGrid('setGridWidth', $('#lookupKaryawanModal .modal-body').width());
                if (!karyawanGridLoaded) {
                    karyawanGridLoaded = true;
                    $gridKaryawan.jqGrid('setGridParam', { datatype: 'json' }).trigger('reloadGrid');
                }
            }, 300);
        });

        $('#btnHapusKaryawan').on('click', function() {
            setKaryawan('', '');
        });
    });

    // Cerminan App\Libraries\UserStatus::aktif() di sisi klien: apa pun yang
    // bukan penanda nonaktif dianggap aktif, termasuk nilai kosong. Daftarnya
    // sengaja sama supaya centang di layar tidak pernah berbeda pendapat dengan
    // keputusan yang diambil server saat login.
    const PENANDA_NONAKTIF = ['0', 'N', 'T', 'TIDAK', 'NONAKTIF', 'FALSE'];

    function setAktif(nilai) {
        const teks = String(nilai ?? '').trim().toUpperCase();
        $('#aktif').prop('checked', teks === '' || !PENANDA_NONAKTIF.includes(teks));
    }

    // Kosong = user tidak dipetakan ke karyawan mana pun. Itu sah: akun sistem
    // seperti ADMIN atau ITMKS memang tidak punya padanan di master HR.
    function setKaryawan(id, nama) {
        $('#karyawanid').val(id || '');
        $('#namakaryawan').val(nama || '');
    }

    // Tombol lookup bukan <input>, jadi tidak ikut terkena selector disable
    // massal di newData/editData/viewData/deleteData — diatur terpisah di sini.
    function setKaryawanEnabled(enabled) {
        $('#btnLookupKaryawan, #btnHapusKaryawan').prop('disabled', !enabled);
    }

    function newData() {
        $('.modal-loader').addClass('d-none');
        $('#fm')[0].reset();
        $('#action').val('add');
        $('#id').val('');
        $('#crudModalLabel').text('Add User');
        
        $('#password').prop('required', true);
        $('#password-help').addClass('d-none');
        $('#fm input:not([type="hidden"]), #fm select').prop('disabled', false);
        $('#btnSave').show();
        $('#btnSave').removeClass('btn-danger').addClass('btn-primary');
        $('#btnSave').html('<i class="fa fa-save"></i> Save');

        setKaryawan('', '');
        setKaryawanEnabled(true);
        setAktif(1); // user baru default aktif

        loadRoles();
        $('#crudModal').modal('show');
        setTimeout(() => $('#userid').focus(), 500);
    }

    function editData(rowid) {
        $.get(getUrl + '/' + rowid, function(res) {
            $('.modal-loader').addClass('d-none');
            $('#fm')[0].reset();
            $('#action').val('edit');
            $('#id').val(rowid);
            $('#crudModalLabel').text('Edit User');
            
            $('#password').prop('required', false);
            $('#password-help').removeClass('d-none');
            $('#fm input:not([type="hidden"]), #fm select').prop('disabled', false);
            $('#btnSave').show();
            $('#btnSave').removeClass('btn-danger').addClass('btn-primary');
            $('#btnSave').html('<i class="fa fa-save"></i> Save');
            
            $('#userid').val(res.userid);
            $('#username').val(res.username);
            $('#email').val(res.email);
            $('#nowhatsapp').val(res.nowhatsapp);
            $('#dashboard').val(res.dashboard);
            setKaryawan(res.karyawanid > 0 ? res.karyawanid : '', res.namakaryawan);
            setAktif(res.aktif);
            setKaryawanEnabled(true);
            loadRoles(res.user_roles);
            $('#crudModal').modal('show');
            setTimeout(() => $('#userid').focus(), 500);
        }).fail(function() {
            $('.modal-loader').addClass('d-none');
        });
    }

    function deleteData(rowid) {
        $.get(getUrl + '/' + rowid, function(res) {
            $('.modal-loader').addClass('d-none');
            $('#fm')[0].reset();
            $('#action').val('del');
            $('#id').val(rowid);
            $('#crudModalLabel').text('Delete User');
            
            $('#password').prop('required', false);
            $('#password-help').removeClass('d-none');
            
            $('#userid').val(res.userid);
            $('#username').val(res.username);
            $('#dashboard').val(res.dashboard);
            setKaryawan(res.karyawanid > 0 ? res.karyawanid : '', res.namakaryawan);
            setAktif(res.aktif);
            setKaryawanEnabled(false);
            loadRoles(res.user_roles);

            $('#fm input:not([type="hidden"]), #fm select').prop('disabled', true);
            
            $('#btnSave').show();
            $('#btnSave').removeClass('btn-primary').addClass('btn-danger');
            $('#btnSave').html('<i class="fa fa-trash"></i> Delete');
            
            $('#crudModal').modal('show');
        }).fail(function() {
            $('.modal-loader').addClass('d-none');
        });
    }

    function viewData(rowid) {
        $.get(getUrl + '/' + rowid, function(res) {
            $('.modal-loader').addClass('d-none');
            $('#fm')[0].reset();
            $('#action').val('view');
            $('#id').val(rowid);
            $('#crudModalLabel').text('View User');
            
            $('#userid').val(res.userid);
            $('#username').val(res.username);
            $('#dashboard').val(res.dashboard);
            setKaryawan(res.karyawanid > 0 ? res.karyawanid : '', res.namakaryawan);
            setAktif(res.aktif);
            setKaryawanEnabled(false);
            loadRoles(res.user_roles);

            $('#fm input, #fm select').prop('disabled', true);
            $('#btnSave').hide();
            $('#crudModal').modal('show');
        }).fail(function() {
            $('.modal-loader').addClass('d-none');
        });
    }

    $('#fm').on('submit', function(e) {
        e.preventDefault();
        
        let action = $('#action').val();
        let url = crudUrl;
        
        let data = $(this).serializeArray();
        let operFound = false;
        $.each(data, function(i, field) {
            if(field.name === 'action') {
                data[i].name = 'oper';
                if(data[i].value == 'add' || data[i].value == 'edit' || data[i].value == 'del') operFound = true;
            }
        });
        if(!operFound) {
            data.push({name: 'oper', value: action});
        }

        $.post(url, $.param(data), function(res) {
            if (res.status === 'sukses') {
                $('#crudModal').modal('hide');
                if (action == 'add') {
                    if(typeof refreshLazyGrid === 'function') {
                        refreshLazyGrid($grid, apiUrl);
                    }
                } else if (action == 'edit') {
                    if(typeof refreshLazyGrid === 'function') {
                        refreshLazyGrid($grid, apiUrl);
                    }
                } else if (action == 'del') {
                    if(typeof deleteLazyRow === 'function') {
                        deleteLazyRow($grid, $('#id').val());
                    }
                }
            } else {
                alert('Gagal: ' + res.message);
            }
        });
    });

</script>
