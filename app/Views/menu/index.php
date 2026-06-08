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
                    <h5 class="modal-title" id="crudModalLabel">Form Menu</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="action">
                    <input type="hidden" name="menuid" id="menuid">
                    
                    <div class="row form-group">
                        <div class="col-12 col-sm-3 col-md-2">
                            <label class="col-form-label">Menu Name <span class="text-danger">*</span></label>
                        </div>
                        <div class="col-12 col-sm-9 col-md-10">
                            <input type="text" class="form-control" name="menuname" id="menuname" required>
                        </div>
                    </div>
                    
                    <div class="row form-group">
                        <div class="col-12 col-sm-3 col-md-2">
                            <label class="col-form-label">Menu Sequence <span class="text-danger">*</span></label>
                        </div>
                        <div class="col-12 col-sm-9 col-md-10">
                            <input type="number" class="form-control" name="menuseq" id="menuseq" required>
                        </div>
                    </div>
                    
                    <div class="row form-group">
                        <div class="col-12 col-sm-3 col-md-2">
                            <label class="col-form-label">Menu Parent <span class="text-danger">*</span></label>
                        </div>
                        <div class="col-12 col-sm-9 col-md-10">
                            <input type="number" class="form-control" name="menuparent" id="menuparent" required>
                        </div>
                    </div>
                    
                    <div class="row form-group">
                        <div class="col-12 col-sm-3 col-md-2">
                            <label class="col-form-label">Menu Icon <span class="text-danger">*</span></label>
                        </div>
                        <div class="col-12 col-sm-9 col-md-10">
                            <input type="text" class="form-control" name="menuicon" id="menuicon" required>
                        </div>
                    </div>
                    
                    <div class="row form-group">
                        <div class="col-12 col-sm-3 col-md-2">
                            <label class="col-form-label">Route ID (ACO)</label>
                        </div>
                        <div class="col-12 col-sm-9 col-md-10">
                            <div class="input-group">
                                <input type="hidden" name="acoid" id="acoid">
                                <input type="text" class="form-control" name="routeid" id="routeid" readonly>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" id="btnBukaLookup">...</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row form-group">
                        <div class="col-12 col-sm-3 col-md-2">
                            <label class="col-form-label">Link</label>
                        </div>
                        <div class="col-12 col-sm-9 col-md-10">
                            <input type="text" class="form-control" name="link" id="link">
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

<!-- Modal Lookup ACO -->
<div class="modal fade" id="lookupModal" tabindex="-1" aria-labelledby="lookupModalLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lookupModalLabel">Lookup Route ID</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <table id="jqGridAco"></table>
                <div id="jqGridAcoPager"></div>
            </div>
        </div>
    </div>
</div>

<script>
    const apiUrl = `<?= base_url('Menu/grid') ?>`;
    const crudUrl = `<?= base_url('Menu/crud') ?>`;
    const getUrl = `<?= base_url('Menu/getById') ?>`;
    const lookupUrl = `<?= base_url('Menu/lookupAco') ?>`;
    
    let $grid = $("#jqGrid");
    let $gridAco = $("#jqGridAco");
    
    let indexRow = 0;
    let triggerClick = true;
    let limit;
    let postData;
    let sortname = 'menuid';
    let sortorder = 'asc';
    let rowNum = 50;
    let id = '';
    let activeGrid;

    $(document).ready(function() {
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
                    name: 'menuid', 
                    index: 'menuid', 
                    width: (isDesktop ? sm_dekstop_2 : sm_mobile_2), 
                    key: true, 
                    fixed:true,
                    sortable:true,
                    search: false,
                    hidden: true
                },
                {
                    label: 'Menu Name', 
                    name: 'menuname', 
                    index: 'menuname', 
                    width: (isDesktop ? md_dekstop_3 : sm_mobile_3), 
                },
                {
                    label: 'Seq', 
                    name: 'menuseq', 
                    index: 'menuseq', 
                    width: (isDesktop ? sm_dekstop_2 : sm_mobile_2), 
                },
                {
                    label: 'Parent', 
                    name: 'menuparent', 
                    index: 'menuparent', 
                    width: (isDesktop ? sm_dekstop_2 : sm_mobile_2), 
                },
                {
                    label: 'Icon', 
                    name: 'menuicon', 
                    index: 'menuicon', 
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_2), 
                },
                {
                    label: 'Route', 
                    name: 'routeid', 
                    index: 'acoid', 
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_3), 
                    search: false
                },
                {
                    label: 'Link', 
                    name: 'link', 
                    index: 'link', 
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
                    width: (isDesktop ? sm_dekstop_4 : sm_mobile_3), 
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
            onSelectRow: function(rowid) {
                activeGrid = $(this);
                indexRow = $grid.jqGrid('getCell', rowid, 'rn') - 1;
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
                            alert('Silakan pilih baris yang akan diedit.');
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
                            alert('Silakan pilih baris yang akan dihapus.');
                        }
                    }
                },
                {
                    id: 'reseq',
                    innerHTML: '<i class="fa fa-list-ol"></i> RESEQ',
                    class: 'btn btn-info btn-sm mr-1',
                    onClick: () => {
                        if (confirm('Apakah Anda yakin ingin mengurutkan ulang (Re-Sequence) data menu?')) {
                            $.post(`<?= base_url('Menu/reseq') ?>`, function(res) {
                                if (res.status === 'sukses') {
                                    alert('Re-Sequence berhasil.');
                                    refreshLazyGrid($grid, apiUrl);
                                } else {
                                    alert('Gagal melakukan Re-Sequence: ' + res.message);
                                }
                            });
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

        // Lookup Grid Configuration
        $gridAco.jqGrid({
            url: lookupUrl,
            mtype: "POST",
            datatype: "json",
            styleUI: 'Bootstrap4',
            iconSet: 'fontAwesome',
            colModel: [
                { label: 'ID', name: 'acosid', index: 'acosid', key: true, hidden: true, search: false },
                { label: 'Class', name: 'class', index: 'class', width: 150 },
                { label: 'Method', name: 'method', index: 'method', width: 150 },
                { label: 'Display Name', name: 'displayname', index: 'displayname', width: 200 }
            ],
            autowidth: true,
            shrinkToFit: true,
            height: 400,
            rowNum: 10,
            rowList: [10, 20, 50],
            pager: '#jqGridAcoPager',
            viewrecords: true,
            rownumbers: true,
            gridview: true,
            altRows: true,
            altclass: 'myAltRowClass',
            sortable: true,
            sortname: 'acosid',
            sortorder: 'asc',
            onSelectRow: function(rowid) {
                let rowData = $(this).getRowData(rowid);
                $('#acoid').val(rowData.acosid);
                $('#routeid').val(rowData.class + '/' + rowData.method);
                $('#lookupModal').modal('hide');
            }
        }).jqGrid('filterToolbar', {
            stringResult: true,
            searchOnEnter: false,
            defaultSearch: 'cn'
        });

        $('#btnBukaLookup').on('click', function() {
            $('#lookupModal').modal('show');
            setTimeout(function() {
                $gridAco.jqGrid('setGridWidth', $('#lookupModal .modal-body').width());
            }, 300);
        });

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
                        deleteLazyRow($grid, $('#menuid').val());
                    }
                } else {
                    alert('Terjadi kesalahan saat menyimpan data: ' + res.message);
                }
            });
        });
    });

    function newData() {
        $('.modal-loader').addClass('d-none');
        $('#fm')[0].reset();
        $('#action').val('add');
        $('#menuid').val('');
        $('#acoid').val('');
        
        $('#fm input:not([type="hidden"]), #fm select').prop('disabled', false);
        $('#btnBukaLookup').prop('disabled', false);
        $('#btnSave').show();
        $('#btnSave').removeClass('btn-danger').addClass('btn-primary');
        $('#btnSave').html('<i class="fa fa-save"></i> Save');
        
        $('#crudModalLabel').text('Add Menu');
        $('#crudModal').modal('show');
    }

    function editData(rowid) {
        $.get(getUrl + '/' + rowid, function(res) {
            $('.modal-loader').addClass('d-none');
            if (res.status === 'sukses') {
                $('#fm')[0].reset();
                $('#action').val('edit');
                $('#menuid').val(res.data.menuid);
                $('#menuname').val(res.data.menuname);
                $('#menuseq').val(res.data.menuseq);
                $('#menuparent').val(res.data.menuparent);
                $('#menuicon').val(res.data.menuicon);
                $('#acoid').val(res.data.acoid);
                $('#routeid').val(res.route);
                $('#link').val(res.data.link);

                $('#fm input:not([type="hidden"]), #fm select').prop('disabled', false);
                $('#btnBukaLookup').prop('disabled', false);
                $('#btnSave').show();
                $('#btnSave').removeClass('btn-danger').addClass('btn-primary');
                $('#btnSave').html('<i class="fa fa-save"></i> Save');

                $('#crudModalLabel').text('Edit Menu');
                $('#crudModal').modal('show');
            } else {
                alert('Gagal mengambil data.');
            }
        }).fail(function() {
            $('.modal-loader').addClass('d-none');
        });
    }

    function deleteData(rowid) {
        $.get(getUrl + '/' + rowid, function(res) {
            $('.modal-loader').addClass('d-none');
            if (res.status === 'sukses') {
                $('#fm')[0].reset();
                $('#action').val('delete');
                $('#menuid').val(res.data.menuid);
                $('#menuname').val(res.data.menuname);
                $('#menuseq').val(res.data.menuseq);
                $('#menuparent').val(res.data.menuparent);
                $('#menuicon').val(res.data.menuicon);
                $('#acoid').val(res.data.acoid);
                $('#routeid').val(res.route);
                $('#link').val(res.data.link);

                $('#fm input:not([type="hidden"]), #fm select').prop('disabled', true);
                $('#btnBukaLookup').prop('disabled', true);
                
                $('#btnSave').show();
                $('#btnSave').removeClass('btn-primary').addClass('btn-danger');
                $('#btnSave').html('<i class="fa fa-trash"></i> Delete');

                $('#crudModalLabel').text('Delete Menu');
                $('#crudModal').modal('show');
            } else {
                alert('Gagal mengambil data.');
            }
        }).fail(function() {
            $('.modal-loader').addClass('d-none');
        });
    }
</script>
