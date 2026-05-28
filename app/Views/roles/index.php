
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
                    
                    <div class="mb-3">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="checkall">
                            <label class="custom-control-label font-weight-bold" for="checkall">Check All</label>
                        </div>
                    </div>
                    
                    <div class="row" id="acl-container">
                        <?php 
                        $currentClass = '';
                        if (isset($acos)) {
                            foreach($acos as $aco): 
                                if ($currentClass != $aco->class) {
                                    if ($currentClass != '') echo '</div></div></div>'; // Tutup grid sebelumnya
                                    $currentClass = $aco->class;
                                    echo '<div class="col-md-3 mb-4">';
                                    echo '<div class="card shadow-sm h-100">';
                                    echo '<div class="card-header py-2 bg-light font-weight-bold text-uppercase" style="font-size: 0.85rem;">' . esc($aco->class) . '</div>';
                                    echo '<div class="card-body p-2" style="max-height: 250px; overflow-y: auto;">';
                                }
                            ?>
                                <div class="custom-control custom-checkbox mb-1">
                                    <input type="checkbox" class="custom-control-input acl-checkbox checkbox-<?= $aco->acosid ?>" 
                                           id="aco_<?= $aco->acosid ?>" 
                                           name="role_permission[acos][]" 
                                           value="<?= $aco->acosid ?>">
                                    <label class="custom-control-label" style="font-size: 0.85rem;" for="aco_<?= $aco->acosid ?>">
                                        <?= esc($aco->method) ?>
                                    </label>
                                </div>
                            <?php 
                            endforeach; 
                            if ($currentClass != '') echo '</div></div></div>'; // Tutup yang terakhir 
                        }
                        ?>
                    </div>
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

        $('#checkall').click(function () {
            $('.acl-checkbox').prop('checked', this.checked);
        });

        $grid.jqGrid({
            url: apiUrl,
            mtype: "POST",
            datatype: "json",
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
            height: 350,
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
    });

    function newData() {
        $('.modal-loader').addClass('d-none');
        $('#fm')[0].reset();
        $('.acl-checkbox').prop('checked', false);
        $('#checkall').prop('checked', false);
        
        $('#action').val('add');
        $('#id').val('');
        
        $('#fm input:not([type="hidden"]), #fm select').prop('disabled', false);
        $('.acl-checkbox').prop('disabled', false);
        $('#checkall').prop('disabled', false);
        
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
            $('.acl-checkbox').prop('checked', false);
            $('#checkall').prop('checked', false);
            
            $('#action').val('edit');
            $('#id').val(id);
            $('#rolename').val(res.rolename);
            
            // Check the ACL boxes
            if (res.role_permission && res.role_permission.length > 0) {
                res.role_permission.forEach(function(acoid) {
                    $('.checkbox-' + acoid.trim()).prop('checked', true);
                });
            }
            
            $('#fm input:not([type="hidden"]), #fm select').prop('disabled', false);
            $('.acl-checkbox').prop('disabled', false);
            $('#checkall').prop('disabled', false);
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
            $('.acl-checkbox').prop('checked', false);
            $('#checkall').prop('checked', false);
            
            $('#action').val('view');
            $('#id').val(id);
            $('#rolename').val(res.rolename);
            
            // Check the ACL boxes
            if (res.role_permission && res.role_permission.length > 0) {
                res.role_permission.forEach(function(acoid) {
                    $('.checkbox-' + acoid.trim()).prop('checked', true);
                });
            }
            
            $('#fm input, #fm select').prop('disabled', true);
            $('.acl-checkbox').prop('disabled', true);
            $('#checkall').prop('disabled', true);
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
            $('.acl-checkbox').prop('checked', false);
            $('#checkall').prop('checked', false);
            
            $('#action').val('del');
            $('#id').val(id);
            $('#rolename').val(res.rolename);
            
            // Check the ACL boxes
            if (res.role_permission && res.role_permission.length > 0) {
                res.role_permission.forEach(function(acoid) {
                    $('.checkbox-' + acoid.trim()).prop('checked', true);
                });
            }
            
            $('#fm input:not([type="hidden"]), #fm select').prop('disabled', true);
            $('.acl-checkbox').prop('disabled', true);
            $('#checkall').prop('disabled', true);
            
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
