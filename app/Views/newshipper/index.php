<style>
    #ui-datepicker-div { display: none; }
    .filter-input-group {
        margin-bottom: 0;
    }
    .filter-label {
        font-weight: 500;
        margin-bottom: 0.2rem;
        font-size: 0.9rem;
    }
    .btn-block {
        height: 38px;
    }
    .myAltRowClass { background-color: #f8f9fa; }
</style>

<div class="container-fluid">
    <!-- Filter Card -->
    <div class="card card-primary card-outline">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group filter-input-group">
                        <label class="filter-label">Cabang</label>
                        <select id="cabangSelect" class="form-control select2">
                            <option value="ALL">SEMUA CABANG</option>
                            <option value="MEDAN">MEDAN</option>
                            <option value="JAKARTA">JAKARTA</option>
                            <option value="SURABAYA">SURABAYA</option>
                            <option value="MAKASSAR">MAKASSAR</option>
                            <option value="SEMARANG">SEMARANG</option>
                            <option value="BITUNG">BITUNG</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group filter-input-group">
                        <label class="filter-label">Tanggal Dari</label>
                        <input type="text" class="form-control" id="datefrom" autocomplete="off">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group filter-input-group">
                        <label class="filter-label">Tanggal Sampai</label>
                        <input type="text" class="form-control" id="dateto" autocomplete="off">
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-group filter-input-group w-100 d-flex justify-content-between">
                        <button id="btnFilter" class="btn btn-primary" style="width: 48%;">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <button id="btnReset" class="btn btn-secondary" style="width: 48%;">
                            <i class="fas fa-sync"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grid Card -->
    <div class="card card-default">
        <div class="card-header">
            <h3 class="card-title">DATA NEW SHIPPER</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="jqGrid"></table>
                <div id="jqGridPager"></div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center p-2 mt-0">
                <div id="jqGridInfoHandler"></div>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('libraries/tas-lib/js/lazyLoadingGridHelper.js') ?>"></script>
<script>
    var apiUrl = "<?= base_url('NewShipper/getGridData') ?>";
    var $grid = $("#jqGrid");
    var rowNum = 50;
    var activeGrid;
    let indexRow = 0;
    let triggerClick = true;
    let limit;
    let postData;
    let isInitialLoad = true;

    $(document).ready(function() {
        var curdate = new Date();
        var pad = function(n) { return n < 10 ? '0' + n : n; }
        var first_day = curdate.getFullYear() + "-" + pad(curdate.getMonth() + 1) + "-01";
        var last_day_date = new Date(curdate.getFullYear(), curdate.getMonth() + 1, 0);
        var last_day = last_day_date.getFullYear() + "-" + pad(last_day_date.getMonth() + 1) + "-" + pad(last_day_date.getDate());

        $("#datefrom").datepicker({ dateFormat: 'yy-mm-dd' });
        $("#datefrom").val(first_day);
        
        $("#dateto").datepicker({ dateFormat: 'yy-mm-dd' });
        $("#dateto").val(last_day);

        if($('.select2').length > 0) {
            $('.select2').select2({ theme: 'bootstrap4' });
        }

        const isDesktop = (detectDeviceType() == "desktop");

        $grid.jqGrid({
            styleUI: 'Bootstrap4',
            iconSet: 'fontAwesome',
            url: apiUrl,
            mtype: "POST",
            datatype: "local",
            postData: {
                cabang: function() { return $('#cabangSelect').val(); },
                datefrom: function() { return isInitialLoad ? '' : $('#datefrom').val(); },
                dateto: function() { return isInitialLoad ? '' : $('#dateto').val(); }
            },
            colModel: [
                { label: 'Cabang', name: 'FNCabang', width: 100, align: 'center' },
                { label: 'Nama Shipper', name: 'FNShipper', width: 250 },
                { label: 'Tanggal', name: 'FTgl', width: 120, align: 'center', sorttype: 'date' },
                { label: 'Marketing', name: 'FNMarketing', width: 200 }
            ],
            autowidth: true,
            shrinkToFit: isDesktop,
            height: isDesktop ? 450 : 350,
            rowNum: rowNum,
            toolbar: [true, "top"],
            rowList: [10, 20, 50, 100, 500],
            viewrecords: false,
            rownumbers: true,
            rownumWidth: 45,
            gridview: true,
            ignoreCase: true,
            altRows: true,
            altclass: 'myAltRowClass',
            sortable: true,
            sortname: 'FTgl',
            sortorder: 'desc',
            onSelectRow: function(id) {
                activeGrid = $grid;
                indexRow = $grid.jqGrid('getCell', id, 'rn') - 1;
                page = $grid.jqGrid('getGridParam', 'page');
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
                if (typeof setupLazyLoadScrollHandler === 'function') {
                    setupLazyLoadScrollHandler("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'));
                }
                if (typeof setHighlight === 'function') {
                    setHighlight($grid);
                }
                
                setTimeout(function() {
                    var currentGridIds = $grid.getDataIDs();
                    var currentSelection = $grid.jqGrid('getGridParam', 'selrow');
                    if (!currentSelection && currentGridIds.length > 0) {
                        $grid.setSelection(currentGridIds[0], false);
                    }
                }, 50);

                $grid.removeClass('table-striped');
            }
        });

        $grid.jqGrid('filterToolbar', {
            stringResult: true,
            searchOnEnter: false,
            defaultSearch: 'cn',
            beforeSearch: function() {
                var postData = $grid.jqGrid('getGridParam', 'postData');
                if (postData.filters) {
                    var filtersObj = JSON.parse(postData.filters);
                    postData._search = (filtersObj.rules && filtersObj.rules.length > 0);
                }
                $grid.jqGrid('setGridParam', { postData: postData });
                
                if (typeof cachedData !== 'undefined') cachedData = {};
                $grid.jqGrid('clearGridData');
                if(typeof loadGridData === 'function') {
                    loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $grid.jqGrid('getGridParam', 'rowNum'), 'jump', 'page');
                }
                return false;
            }
        });

        if(typeof loadGridData === 'function') {
            loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, rowNum, 'down', 'reload');
        } else {
            $grid.jqGrid('setGridParam',{datatype:'json'}).trigger('reloadGrid');
        }

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

        $('#btnFilter').click(function() {
            isInitialLoad = false;
            if (typeof cachedData !== 'undefined') cachedData = {};
            $grid.jqGrid('clearGridData');
            var postData = $grid.jqGrid('getGridParam', 'postData');
            postData.cabang = $('#cabangSelect').val();
            postData.datefrom = $('#datefrom').val();
            postData.dateto = $('#dateto').val();
            $grid.jqGrid('setGridParam', { search: false, postData: postData });
            if(typeof loadGridData === 'function') {
                loadGridData("#jqGrid", apiUrl, postData, 1, rowNum, 'down', 'reload');
            } else {
                $grid.trigger("reloadGrid", [{ page: 1 }]);
            }
        });

        $('#btnReset').click(function() {
            isInitialLoad = true;
            $("#cabangSelect").val('ALL').trigger('change');
            $("#datefrom").val(first_day);
            $("#dateto").val(last_day);
            if (typeof cachedData !== 'undefined') cachedData = {};
            $grid.jqGrid('clearGridData');
            var postData = $grid.jqGrid('getGridParam', 'postData');
            postData.cabang = 'ALL';
            postData.datefrom = '';
            postData.dateto = '';
            $grid.jqGrid('setGridParam', { search: false, postData: postData });
            if(typeof loadGridData === 'function') {
                loadGridData("#jqGrid", apiUrl, postData, 1, rowNum, 'down', 'reload');
            } else {
                $grid.trigger("reloadGrid", [{ page: 1 }]);
            }
        });
    });
</script>
