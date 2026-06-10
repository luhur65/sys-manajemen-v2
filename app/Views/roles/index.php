
<div class="row">
    <div class="col-12">
        <table id="jqGrid"></table>
        <div id="jqGridPager"></div>
    </div>
</div>

<!-- Modal CRUD Roles -->
<div class="modal fade" id="crudModal" tabindex="-1" aria-labelledby="crudModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="crudModalLabel">Role Form</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="fm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="action">
                    <input type="hidden" name="id" id="id">
                    
                    <div class="form-group row">
                        <label for="rolename" class="col-sm-2 col-form-label">Role Name <span class="text-danger">*</span></label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="rolename" name="rolename" required maxlength="50" style="text-transform: uppercase;">
                        </div>
                    </div>

                    <h6 class="font-weight-bold border-bottom pb-2 mb-3 mt-4">Role Permissions (ACL)</h6>
                    
                    <div id="hidden-inputs-container"></div>
                    <table id="jqGridAcos"></table>
                    <div id="jqGridAcosPager"></div>
                </div>
                <div class="modal-footer justify-content-start">
                    <button type="submit" class="btn btn-primary" id="btnSave"><i class="fa fa-save"></i> Save</button>
                    <button type="button" class="btn btn-outline-primary" data-dismiss="modal"><i class="fa fa-times"></i> Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    var apiUrl = "<?= base_url('roles/grid') ?>";
    var crudUrl = "<?= base_url('roles/crud') ?>";
    var getUrl = "<?= base_url('roles/getById') ?>";
    
    var $grid = $("#jqGrid");
    
    let indexRow = 0;
    let triggerClick = true;
    let limit;
    let postData;
    let sortname = 'rolename';
    let sortorder = 'asc';
    let rowNum = 50;

    
    $(document).ready(function() {

        // Detect Device Widths (Inspired by Trucking)
        const isDesktop = (detectDeviceType() == "desktop");

        $grid.jqGrid({
            url: apiUrl,
            mtype: "POST",
            datatype: "local",
            styleUI: 'Bootstrap4',
            iconSet: 'fontAwesome',
            colModel: [
                {
                    label: 'ID', 
                    name: 'roleid', 
                    index: 'roleid', 
                    key: true, 
                    hidden:true,
                    search: false
                },
                {
                    label: 'Role Name', 
                    name: 'rolename', 
                    index: 'rolename', 
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_3), 
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
            viewrecords: true,
            rownumbers: true,
            rownumWidth: 45,
            gridview: true,
            altRows: true,
            altclass: 'myAltRowClass',
            sortable: true,
            sortname: sortname,
            sortorder: sortorder,
            onSelectRow: function(id) {
                indexRow = $grid.jqGrid('getCell', id, 'rn') - 1;
                let page = $grid.jqGrid('getGridParam', 'page');
                let limit = $grid.jqGrid('getGridParam', 'postData').limit;
                if (indexRow >= limit) indexRow = (indexRow - limit * (page - 1));
            },
            onSortCol: function(index, iCol, sortorder) {
                if (typeof cachedData !== 'undefined') cachedData = {};
                if(typeof loadGridData === 'function') {
                    loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $(this).jqGrid('getGridParam', 'rowNum'), 'jump', 'page');
                }
                return 'stop';
            },
            loadComplete: function(res) {
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
                setCustomBindKeys($(this));

                if(typeof setupLazyLoadScrollHandler === 'function') {
                    setupLazyLoadScrollHandler("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'));
                }
                if(typeof setHighlight === 'function') {
                    setHighlight($grid);
                }

                $grid.removeClass('table-striped');
            }
        })
        .jqGrid('filterToolbar', {
            stringResult: true,
            searchOnEnter: false,
            defaultSearch: 'cn',
            beforeSearch: function() {
                if (typeof cachedData !== 'undefined') cachedData = {};
                $grid.jqGrid('clearGridData');
                if(typeof loadGridData === 'function') {
                    loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $grid.jqGrid('getGridParam', 'rowNum'), 'down', 'reload');
                } else {
                    return false; // let jqGrid handle it naturally if no lazy loading
                }
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
        
        $('#crudModal').on('hidden.bs.modal', function () {
            $('.modal-loader').addClass('d-none');
        });

        // ====================== GRID ACOS (MODAL) ======================
        var $gridAcos = $("#jqGridAcos");
        window.selectedAcosIds = [];

        $gridAcos.jqGrid({
            url: "<?= base_url('useracl/getAcos') ?>",
            mtype: "GET",
            datatype: "json",
            jsonReader: { repeatitems: true },
            styleUI: 'Bootstrap4',
            iconSet: 'fontAwesome',
            colModel: [
                { label: 'ID', name: 'acosid', key: true, hidden: true },
                { label: 'Class / Modul', name: 'class', width: (isDesktop ? sm_dekstop_4 : sm_mobile_4), searchoptions:{sopt:['cn']} },
                { label: 'Method / Aksi', name: 'method', width: (isDesktop ? sm_dekstop_4 : sm_mobile_4), searchoptions:{sopt:['cn']} },
                { label: 'Display Name', name: 'displayname', width: (isDesktop ? md_dekstop_4 : sm_mobile_4), searchoptions:{sopt:['cn']} }
            ],
            viewrecords: false,
            autowidth: true,
            shrinkToFit: false,
            height: 300,
            rowNum: 50,
            scroll: 1,
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
                // Restore selection
                for (var i = 0; i < visibleIds.length; i++) {
                    if (window.selectedAcosIds.indexOf(String(visibleIds[i])) > -1) {
                        grid.jqGrid('setSelection', visibleIds[i], false);
                    }
                }
                
                // Ganti icon sorting
                setTimeout(function() {
                    $('#gview_jqGridAcos .fa-caret-up').removeClass('fa-caret-up').addClass('fa-fw fa-arrow-up');
                    $('#gview_jqGridAcos .fa-caret-down').removeClass('fa-caret-down').addClass('fa-fw fa-arrow-down');
                }, 10);
            }
        }).customPager({
            buttons: []
        });
        
        $gridAcos.jqGrid('filterToolbar', {
            stringResult: true,
            searchOnEnter: false, 
            defaultSearch: 'cn'
        });
        
        // Hapus elemen pager bawaan jqGrid yang tidak diperlukan untuk data lokal
        $('#jqGridAcosPager_center').hide();

        if(typeof loadGridData === 'function') {
            loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, rowNum, 'down', 'reload');
        }

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
    });

    function newData() {
        $('.modal-loader').addClass('d-none');
        $('#fm')[0].reset();
        
        window.selectedAcosIds = [];
        $("#jqGridAcos").jqGrid('resetSelection');
        
        $('#action').val('add');
        $('#id').val('');
        
        $('#fm input:not([type="hidden"]), #fm select').prop('disabled', false);
        
        // Panggil re-layout autowidth saat modal ditampilkan agar grid ukurannya pas
        setTimeout(function() {
            $("#jqGridAcos").jqGrid('setGridWidth', $("#jqGridAcos").closest('.modal-body').width());
        }, 300);
        
        $('#btnSave').show();
        $('#btnSave').removeClass('btn-danger').addClass('btn-primary');
        $('#btnSave').html('<i class="fa fa-save"></i> Save');
        $('#crudModalLabel').text('Add Role');
        $('#crudModal').modal('show');
    }

    function editData(id) {
        $('.modal-loader').removeClass('d-none');
        $.get(getUrl + '/' + id, function(res) {
            $('.modal-loader').addClass('d-none');
            
            $('#fm')[0].reset();
            
            window.selectedAcosIds = [];
            $("#jqGridAcos").jqGrid('resetSelection');
            
            $('#action').val('edit');
            $('#id').val(id);
            $('#rolename').val(res.rolename);
            
            // Check the ACL boxes
            if (res.role_permission && res.role_permission.length > 0) {
                res.role_permission.forEach(function(acoid) {
                    var strId = String(acoid.trim());
                    window.selectedAcosIds.push(strId);
                    $("#jqGridAcos").jqGrid('setSelection', strId, false);
                });
            }
            
            $('#fm input:not([type="hidden"]), #fm select').prop('disabled', false);
            
            setTimeout(function() {
                $("#jqGridAcos").jqGrid('setGridWidth', $("#jqGridAcos").closest('.modal-body').width());
            }, 300);
            $('#btnSave').show();
            $('#btnSave').removeClass('btn-danger').addClass('btn-primary');
            $('#btnSave').html('<i class="fa fa-save"></i> Save');
            $('#crudModalLabel').text('Edit Role');
            $('#crudModal').modal('show');
        }).fail(function() {
            $('.modal-loader').addClass('d-none');
            alert('Gagal mengambil data');
        });
    }

    function viewData(id) {
        $('.modal-loader').removeClass('d-none');
        $.get(getUrl + '/' + id, function(res) {
            $('.modal-loader').addClass('d-none');
            
            $('#fm')[0].reset();
            
            window.selectedAcosIds = [];
            $("#jqGridAcos").jqGrid('resetSelection');
            
            $('#action').val('view');
            $('#id').val(id);
            $('#rolename').val(res.rolename);
            
            // Check the ACL boxes
            if (res.role_permission && res.role_permission.length > 0) {
                res.role_permission.forEach(function(acoid) {
                    var strId = String(acoid.trim());
                    window.selectedAcosIds.push(strId);
                    $("#jqGridAcos").jqGrid('setSelection', strId, false);
                });
            }
            
            $('#fm input, #fm select').prop('disabled', true);
            
            setTimeout(function() {
                $("#jqGridAcos").jqGrid('setGridWidth', $("#jqGridAcos").closest('.modal-body').width());
            }, 300);
            $('#btnSave').hide();
            $('#crudModalLabel').text('View Role');
            $('#crudModal').modal('show');
        }).fail(function() {
            $('.modal-loader').addClass('d-none');
            alert('Gagal mengambil data');
        });
    }

    function deleteData(id) {
        $('.modal-loader').removeClass('d-none');
        $.get(getUrl + '/' + id, function(res) {
            $('.modal-loader').addClass('d-none');
            
            $('#fm')[0].reset();
            
            window.selectedAcosIds = [];
            $("#jqGridAcos").jqGrid('resetSelection');
            
            $('#action').val('del');
            $('#id').val(id);
            $('#rolename').val(res.rolename);
            
            // Check the ACL boxes
            if (res.role_permission && res.role_permission.length > 0) {
                res.role_permission.forEach(function(acoid) {
                    var strId = String(acoid.trim());
                    window.selectedAcosIds.push(strId);
                    $("#jqGridAcos").jqGrid('setSelection', strId, false);
                });
            }
            
            $('#fm input:not([type="hidden"]), #fm select').prop('disabled', true);
            
            setTimeout(function() {
                $("#jqGridAcos").jqGrid('setGridWidth', $("#jqGridAcos").closest('.modal-body').width());
            }, 300);
            
            $('#btnSave').show();
            $('#btnSave').removeClass('btn-primary').addClass('btn-danger');
            $('#btnSave').html('<i class="fa fa-trash"></i> Delete');
            
            $('#crudModalLabel').text('Delete Role');
            $('#crudModal').modal('show');
        }).fail(function() {
            $('.modal-loader').addClass('d-none');
            alert('Gagal mengambil data');
        });
    }

    $('#fm').on('submit', function(e) {
        e.preventDefault();
        
        $('.modal-loader').removeClass('d-none');
        
        let action = $('#action').val();
        let url = crudUrl;
        
        // Inject selected Acos IDs into hidden inputs before serializing
        var selRowIds = window.selectedAcosIds;
        var container = $('#hidden-inputs-container');
        container.empty();
        $.each(selRowIds, function(index, value) {
            container.append('<input type="hidden" name="role_permission[acos][]" value="' + value + '">');
        });
        if (selRowIds.length === 0) {
            container.append('<input type="hidden" name="role_permission[acos][]" value="">');
        }
        
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
            $('.modal-loader').addClass('d-none');
            if (res.status === 'sukses') {
                $('#crudModal').modal('hide');
                if (action == 'add') {
                    if(typeof loadGridData === 'function') {
                        loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, rowNum, 'jump', 'page');
                    } else {
                        $grid.trigger("reloadGrid");
                    }
                } else if (action == 'edit') {
                    if(typeof refreshLazyGrid === 'function') {
                        refreshLazyGrid($grid, apiUrl);
                    } else {
                        $grid.trigger("reloadGrid");
                    }
                } else if (action == 'del') {
                    if(typeof deleteLazyRow === 'function') {
                        deleteLazyRow($grid, $('#id').val());
                    } else {
                        $grid.trigger("reloadGrid");
                    }
                }
            } else {
                alert('Gagal: ' + res.message);
            }
        }).fail(function() {
            $('.modal-loader').addClass('d-none');
            alert('Terjadi kesalahan pada server');
        });
    });
</script>
