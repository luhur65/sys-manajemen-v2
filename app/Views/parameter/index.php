
<div class="row">
    <div class="col-12">
        <table id="jqGrid"></table>
        <div id="jqGridPager"></div>
    </div>
</div>

<!-- Modal Form -->
<div class="modal modal-fullscreen" id="crudModal" tabindex="-1" aria-labelledby="crudModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="fm" method="post">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="crudModalLabel">Form Parameter</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="action">
                    <input type="hidden" name="parameter_key" id="parameter_key">
                    
                    <div class="row form-group">
                        <div class="col-12 col-sm-3 col-md-2">
                            <label class="col-form-label">Parameter Group ID <span class="text-danger">*</span></label>
                        </div>
                        <div class="col-12 col-sm-9 col-md-10">
                            <input type="text" class="form-control" name="parametergrpid" id="parametergrpid" required>
                        </div>
                    </div>
                    
                    <div class="row form-group">
                        <div class="col-12 col-sm-3 col-md-2">
                            <label class="col-form-label">Parameter ID <span class="text-danger">*</span></label>
                        </div>
                        <div class="col-12 col-sm-9 col-md-10">
                            <input type="text" class="form-control" name="parameterid" id="parameterid" required>
                        </div>
                    </div>
                    
                    <div class="row form-group">
                        <div class="col-12 col-sm-3 col-md-2">
                            <label class="col-form-label">Parameter Text <span class="text-danger">*</span></label>
                        </div>
                        <div class="col-12 col-sm-9 col-md-10">
                            <input type="text" class="form-control" name="parametertext" id="parametertext" required>
                        </div>
                    </div>
                    
                    <div class="row form-group">
                        <div class="col-12 col-sm-3 col-md-2">
                            <label class="col-form-label">Parameter Memo</label>
                        </div>
                        <div class="col-12 col-sm-9 col-md-10">
                            <textarea class="form-control" name="parametermemo" id="parametermemo" rows="4"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-start">
                    <button type="submit" class="btn btn-primary" id="btnSave"><i class="fas fa-save"></i> Save</button>
                    <button type="button" class="btn btn-outline-primary" data-dismiss="modal"><i class="fas fa-times"></i> Close</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const apiUrl = `<?= base_url('Parameter/grid') ?>`;
    const crudUrl = `<?= base_url('Parameter/crud') ?>`;
    const getUrl = `<?= base_url('Parameter/getById') ?>`;
    let $grid = $("#jqGrid");
    
    let indexRow = 0;
    let triggerClick = true;
    let limit;
    let postData;
    let sortname = 'parameter_key';
    let sortorder = 'asc';
    let rowNum = 50;
    let id = '';
    let activeGrid;

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
                    label: 'Parameter Key', 
                    name: 'parameter_key', 
                    index: 'parameter_key', 
                    width: (isDesktop ? sm_dekstop_2 : sm_mobile_2), 
                    key: true, 
                    fixed:true,
                    sortable:true,
                    search: false
                },
                {
                    label: 'Group ID', 
                    name: 'parametergrpid', 
                    index: 'parametergrpid', 
                    width: (isDesktop ? sm_dekstop_2 : sm_mobile_2), 
                    
                },
                {
                    label: 'Parameter ID', 
                    name: 'parameterid', 
                    index: 'parameterid', 
                    width: (isDesktop ? sm_dekstop_2 : sm_mobile_2), 
                    
                },
                {
                    label: 'Text', 
                    name: 'parametertext', 
                    index: 'parametertext', 
                    width: (isDesktop ? sm_dekstop_2 : sm_mobile_2), 
                    
                },
                {
                    label: 'Memo', 
                    name: 'parametermemo', 
                    index: 'parametermemo', 
                    width: (isDesktop ? sm_dekstop_2 : sm_mobile_2), 
                    
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
                    width: (isDesktop ? sm_dekstop_4 : sm_mobile_2), 
                    
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
            },
            onSortCol: function(index, iCol, sortorder) {
                var targetGridId = this.id || 'jqGrid'; if (typeof lazyStates !== 'undefined' && lazyStates[targetGridId]) lazyStates[targetGridId].cachedData = {};
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
    });

    function newData() {
        $('.modal-loader').addClass('d-none');
        $('#fm')[0].reset();
        $('#parameter_key').val('');
        $('#action').val('add');
        $('#crudModalLabel').text('Tambah Data Parameter');
        $('#btnSave').show();
        $('#btnSave').removeClass('btn-danger').addClass('btn-primary');
        $('#btnSave').html('<i class="fa fa-save"></i> Save');
        disableFields(false);
        $('#crudModal').modal('show');
    }

    function editData(id) {
        $.post(getUrl, {id: id}, function(data) {
            $('.modal-loader').addClass('d-none');
            $('#fm')[0].reset();
            $('#action').val('edit');
            $('#parameter_key').val(data.parameter_key);
            $('#parametergrpid').val(data.parametergrpid);
            $('#parameterid').val(data.parameterid);
            $('#parametertext').val(data.parametertext);
            $('#parametermemo').val(data.parametermemo);
            $('#crudModalLabel').text('Edit Data Parameter');
            $('#btnSave').show();
            $('#btnSave').removeClass('btn-danger').addClass('btn-primary');
            $('#btnSave').html('<i class="fa fa-save"></i> Save');
            disableFields(false);
            $('#crudModal').modal('show');
        }).fail(function() {
            $('.modal-loader').addClass('d-none');
        });
    }

    function viewData(id) {
        $.post(getUrl, {id: id}, function(data) {
            $('.modal-loader').addClass('d-none');
            $('#fm')[0].reset();
            $('#action').val('view');
            $('#parameter_key').val(data.parameter_key);
            $('#parametergrpid').val(data.parametergrpid);
            $('#parameterid').val(data.parameterid);
            $('#parametertext').val(data.parametertext);
            $('#parametermemo').val(data.parametermemo);
            $('#crudModalLabel').text('View Data Parameter');
            $('#btnSave').hide();
            disableFields(true);
            $('#crudModal').modal('show');
        }).fail(function() {
            $('.modal-loader').addClass('d-none');
        });
    }

    function deleteData(selectedId) {
        $.post(getUrl, {id: selectedId}, function(data) {
            $('.modal-loader').addClass('d-none');
            $('#fm')[0].reset();
            $('#action').val('delete');
            $('#parameter_key').val(data.parameter_key);
            $('#parametergrpid').val(data.parametergrpid);
            $('#parameterid').val(data.parameterid);
            $('#parametertext').val(data.parametertext);
            $('#parametermemo').val(data.parametermemo);
            $('#crudModalLabel').text('Hapus Data Parameter');
            
            $('#btnSave').show();
            $('#btnSave').removeClass('btn-primary').addClass('btn-danger');
            $('#btnSave').html('<i class="fa fa-trash"></i> Delete');
            
            disableFields(true);
            $('#crudModal').modal('show');
        }).fail(function() {
            $('.modal-loader').addClass('d-none');
        });
    }

    function disableFields(status) {
        $('#parametergrpid').prop('readonly', status);
        $('#parameterid').prop('readonly', status);
        $('#parametertext').prop('readonly', status);
        $('#parametermemo').prop('readonly', status);
    }

    $('#fm').on('submit', function(e) {
        e.preventDefault();
        let action = $('#action').val();
        
        $.post(crudUrl, $(this).serialize(), function(res) {
            if (res.status === 'sukses') {
                if (res.id) {
                    id = String(res.id);
                }
                
                $('#crudModal').modal('hide');
                
                if (action == 'add' || action == 'edit') {
                    refreshLazyGrid($grid, apiUrl);
                } else if (action == 'delete') {
                    deleteLazyRow($grid, $('#parameter_key').val());
                }
            } else {
                alert('Terjadi kesalahan saat menyimpan data.');
            }
        });
    });
</script>

