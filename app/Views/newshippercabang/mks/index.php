<style>
    #ui-datepicker-div { display: none; }
</style>
<div class="container-fluid">
    <!-- Filter Card -->
    <div class="card card-primary card-outline">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group filter-input-group">
                        <label class="filter-label">Tanggal Dari</label>
                        <input type="text" class="form-control" id="datefromMKS" autocomplete="off">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group filter-input-group">
                        <label class="filter-label">Tanggal Sampai</label>
                        <input type="text" class="form-control" id="datetoMKS" autocomplete="off">
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-group filter-input-group w-100 d-flex justify-content-between">
                        <button id="btnshowMKS" class="btn btn-primary" style="width: 48%;">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <button id="btnresetMKS" class="btn btn-secondary" style="width: 48%;">
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
            <h3 class="card-title">DATA NEW SHIPPER MKS</h3>
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
    var apiUrl = "<?= base_url('NewShipperCabang/getGridDataMks') ?>";
    var $grid = $("#jqGrid");
    var rowNum = 50;
    var activeGrid;
    let indexRow = 0;
    let triggerClick = true;
    let limit;
    let id = '';
    let postData;
    let isInitialLoad = true;

    $(document).ready(function() {
        var curdate = new Date();
        var pad = function(n) { return n < 10 ? '0' + n : n; }
        var first_day = curdate.getFullYear() + "-" + pad(curdate.getMonth() + 1) + "-01";
        var last_day_date = new Date(curdate.getFullYear(), curdate.getMonth() + 1, 0);
        var last_day = last_day_date.getFullYear() + "-" + pad(last_day_date.getMonth() + 1) + "-" + pad(last_day_date.getDate());

        $("#datefromMKS").datepicker({ dateFormat: 'yy-mm-dd' });
        $("#datefromMKS").val(first_day);
        
        $("#datetoMKS").datepicker({ dateFormat: 'yy-mm-dd' });
        $("#datetoMKS").val(last_day);

        const isDesktop = (detectDeviceType() == "desktop");

        $grid.jqGrid({
            styleUI: 'Bootstrap4',
            iconSet: 'fontAwesome',
            url: apiUrl,
            mtype: "POST",
            datatype: "local",
            postData: {
                datefrom: function() { return isInitialLoad ? '' : $('#datefromMKS').val(); },
                dateto: function() { return isInitialLoad ? '' : $('#datetoMKS').val(); }
            },
            colModel: [
                { 
                    label: 'Cabang', 
                    name: 'FNCabang', 
                    hidden: true 
                },
                { 
                    label: 'Nama Shipper', 
                    name: 'FNShipper', 
                    width: (isDesktop ? md_dekstop_5 : sm_mobile_4)
                },
                { 
                    label: 'Tanggal', 
                    name: 'FTgl', 
                    width: (isDesktop ? sm_dekstop_2 : sm_mobile_2), 
                    align: 'center', 
                    sorttype: 'date'
                },
                { 
                    label: 'Marketing', 
                    name: 'FNMarketing', 
                    width: (isDesktop ? md_dekstop_4 : sm_mobile_3) 
                }
            ],
            autowidth: true,
            // shrinkToFit: isDesktop,
            height: 400,
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
                var targetGridId = this.id ? '#' + this.id : '#jqGrid'; if (typeof gridState !== 'undefined' && gridState[targetGridId]) gridState[targetGridId].cachedData = {};
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
                
                var targetGridId = this.id ? '#' + this.id : '#jqGrid'; if (typeof gridState !== 'undefined' && gridState[targetGridId]) gridState[targetGridId].cachedData = {};
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

        $('#btnshowMKS').click(function() {
            isInitialLoad = false;
            var targetGridId = this.id ? '#' + this.id : '#jqGrid'; if (typeof gridState !== 'undefined' && gridState[targetGridId]) gridState[targetGridId].cachedData = {};
            $grid.jqGrid('clearGridData');
            var postData = $grid.jqGrid('getGridParam', 'postData');
            postData.datefrom = $('#datefromMKS').val();
            postData.dateto = $('#datetoMKS').val();
            $grid.jqGrid('setGridParam', { search: false, postData: postData });
            if(typeof loadGridData === 'function') {
                loadGridData("#jqGrid", apiUrl, postData, 1, rowNum, 'down', 'reload');
            } else {
                $grid.trigger("reloadGrid", [{ page: 1 }]);
            }
        });

        $('#btnresetMKS').click(function() {
            isInitialLoad = true;
            $("#datefromMKS").val(first_day);
            $("#datetoMKS").val(last_day);
            var targetGridId = this.id ? '#' + this.id : '#jqGrid'; if (typeof gridState !== 'undefined' && gridState[targetGridId]) gridState[targetGridId].cachedData = {};
            $grid.jqGrid('clearGridData');
            var postData = $grid.jqGrid('getGridParam', 'postData');
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
