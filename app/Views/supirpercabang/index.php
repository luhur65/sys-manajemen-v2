<div class="container-fluid">
    <!-- Filter Card -->
    <div class="card card-primary card-outline">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <div class="form-group filter-input-group mb-0">
                        <label class="filter-label">Cabang</label>
                        <select id="cabangSelect" class="form-control select2">
                            <option value="MDN">MEDAN</option>
                            <option value="JKT">JAKARTA</option>
                            <option value="SBY">SURABAYA</option>
                            <option value="MKS">MAKASSAR</option>
                            <option value="PKU">PEKANBARU</option>
                            <option value="BITUNG">BITUNG</option>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group filter-input-group mb-0">
                        <button type="button" id="btnFilter" class="btn btn-primary btn-md mr-2">
                            <i class="fas fa-search"></i> Tampilkan
                        </button>
                        <button type="button" id="btnReset" class="btn btn-danger btn-md">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grid Card -->
    <div class="card card-default">
        <div class="card-header">
            <h3 class="card-title">DAFTAR SUPIR PER CABANG</h3>
        </div>
        <div class="card-body p-0">
            <table id="jqGrid"></table>
            <div class="d-flex justify-content-between align-items-center p-2 mt-0">
                <div id="lastUpdateHandler"></div>
                <div id="jqGridInfoHandler"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Supir -->
<div class="modal modal-fullscreen fade" id="modalDetailSupir" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Supir</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <div class="modal-body" id="modalDetailBody">
                <div class="text-center"><i class="fas fa-spinner fa-spin fa-3x"></i></div>
            </div>
            <div class="modal-footer justify-content-start">
                <button type="button" class="btn btn-outline-primary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="<?= base_url('libraries/tas-lib/js/lazyLoadingGridMonolith.js') ?>"></script>

<script>
    let triggerClick = true;
    let indexRow = 0;
    
    // Define viewDetail globally so the button onclick can find it
    function viewDetail(id) {
        var cabang = $('#cabangSelect').val();
        
        $.post(`<?= base_url('supirpercabang/detail') ?>`, { id: id, cabang: cabang }, function(response) {
            $('.modal-loader').addClass('d-none');
            $('#modalDetailBody').html(response);
            $('#modalDetailSupir').modal('show');
        }).fail(function() {
            $('.modal-loader').addClass('d-none');
            alert('Gagal mengambil data');
        });
    }

    $(document).ready(function() {
        $('#modalDetailSupir').on('hidden.bs.modal', function () {
            $('.modal-loader').addClass('d-none');
        });

        const apiUrl = `<?= base_url('supirpercabang/grid') ?>`;
        const $grid = $("#jqGrid");

        if($('.select2').length > 0) {
            $('.select2').select2({ theme: 'bootstrap4' });
        }

        const isDesktop = (detectDeviceType() == "desktop");
        let sm_dekstop_3 = 100;
        let sm_mobile_3 = 100;
        let sm_dekstop_4 = 200;
        let sm_mobile_4 = 150;

        $grid.jqGrid({
            url: apiUrl,
            mtype: "POST",
            datatype: "local",
            postData: {
                cabang: function() { return $('#cabangSelect').val(); }
            },
            styleUI: 'Bootstrap4',
            iconSet: 'fontAwesome',
            colModel: [
                {
                    label: 'ID Supir',
                    name: 'FKSupir',
                    index: 'FKSupir',
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_3),
                    searchoptions: { sopt: ['cn'] }
                },
                {
                    label: 'Nama Supir',
                    name: 'FNSupir',
                    index: 'FNSupir',
                    width: (isDesktop ? sm_dekstop_4 : sm_mobile_4),
                    searchoptions: { sopt: ['cn'] }
                },
                {
                    label: 'Alamat',
                    name: 'FAlamat',
                    index: 'FAlamat',
                    width: (isDesktop ? sm_dekstop_4 * 1.5 : sm_mobile_4 * 1.5),
                    searchoptions: { sopt: ['cn'] }
                },
                {
                    label: 'Kota',
                    name: 'FKota',
                    index: 'FKota',
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_3),
                    searchoptions: { sopt: ['cn'] }
                },
                {
                    label: 'No. Telp',
                    name: 'FTelp',
                    index: 'FTelp',
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_3),
                    searchoptions: { sopt: ['cn'] }
                },
                {
                    label: 'Status',
                    name: 'FAktif',
                    index: 'FAktif',
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_3),
                    searchoptions: { sopt: ['cn'] }
                }
            ],
            autowidth: true,
            shrinkToFit: false,
            height: 400,
            rowNum: 50,
            rownumbers: true,
            rownumWidth: 45,
            toolbar: [true, "top"],
            sortable: true,
            sortname: 'FKSupir',
            sortorder: 'asc',
            page: 1,
            viewrecords: true,
            prmNames: {
                sort: "sidx",
                order: "sord",
                search: "_search"
            },
            onSortCol: function(index, iCol, sortorder) {
                if (typeof cachedData !== 'undefined') cachedData = {};
                if (typeof loadGridData === 'function') {
                    loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $(this).jqGrid('getGridParam', 'rowNum'), 'jump', 'page');
                }
                return 'stop';
            },
            loadComplete: function(res) {
                if (typeof setupLazyLoadScrollHandler === 'function') {
                    setupLazyLoadScrollHandler("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'));
                }
                if (typeof initJqGridInfo === 'function') {
                    initJqGridInfo($(this));
                }
                if (res.rows && res.rows.length > 0 && triggerClick) {
                    triggerClick = false;
                    $('#btnFilter').removeClass('disabled');
                }
            }
        }).customPager({
            lazyLoading: true,
            buttons: [
                {
                    id: 'view',
                    innerHTML: '<i class="fa fa-eye"></i> VIEW',
                    class: 'btn btn-info btn-sm mr-1',
                    onClick: () => {
                        let selectedId = $grid.jqGrid('getGridParam', 'selrow');
                        if (selectedId) {
                            viewDetail(selectedId);
                        } else {
                            alert('Pilih data terlebih dahulu!');
                        }
                    }
                }
            ]
        });

        $grid.jqGrid('filterToolbar', {
            stringResult: true,
            searchOnEnter: false,
            defaultSearch: 'cn',
            groupOp: 'AND',
            beforeSearch: function() {
                var postData = $grid.jqGrid('getGridParam', 'postData');
                if (postData.filters) {
                    var filtersObj = JSON.parse(postData.filters);
                    postData._search = (filtersObj.rules && filtersObj.rules.length > 0);
                }
                $grid.jqGrid('setGridParam', { postData: postData });
                
                if (typeof cachedData !== 'undefined') cachedData = {};
                $grid.jqGrid('clearGridData');
                if (typeof loadGridData === 'function') {
                    loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $grid.jqGrid('getGridParam', 'rowNum'), 'jump', 'page');
                }
                return false;
            }
        });

        $('#btnFilter').click(function() {
            $(this).addClass('disabled');
            triggerClick = true;
            
            if (typeof cachedData !== 'undefined') cachedData = {};
            $grid.jqGrid("setGridParam", {
                postData: {
                    cabang: $('#cabangSelect').val()
                }
            });

            if (typeof loadGridData === 'function') {
                loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $grid.jqGrid('getGridParam', 'rowNum'), 'down', 'reload');
            } else {
                $grid.jqGrid("setGridParam", { datatype: "json", search: true, page: 1 }).trigger("reloadGrid");
            }
        });
        
        $('#btnReset').click(function() {
            $('#cabangSelect').val('MDN').trigger('change.select2');
            
            var ts = $grid[0];
            if (ts.grid && ts.grid.hDiv) {
                $("input", ts.grid.hDiv).val("");
                $("select", ts.grid.hDiv).prop("selectedIndex", 0);
            }
            $grid.jqGrid('setGridParam', { search: false, postData: { filters: "" } });
            
            $('#btnFilter').click();
        });

        $('#cabangSelect').on('change', function() {
            $('#btnFilter').trigger('click');
        });
        
        setTimeout(function(){
            $('#btnFilter').trigger('click');
        }, 300);
        
    });
</script>
