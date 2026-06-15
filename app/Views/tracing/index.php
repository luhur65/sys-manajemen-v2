<div class="container-fluid">
    <!-- Filter Card -->
    <div class="card card-primary card-outline">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <div class="form-group filter-input-group mb-0">
                        <label class="filter-label">Cabang</label>
                        <select id="cabangSelect" class="form-control select2">
                            <option value="All">ALL</option>
                            <option value="MEDAN">MEDAN</option>
                            <option value="JAKARTA">JAKARTA</option>
                            <option value="SURABAYA">SURABAYA</option>
                            <option value="MAKASSAR">MAKASSAR</option>
                            <option value="SEMARANG">SEMARANG</option>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group filter-input-group mb-0">
                        <div class="d-flex w-100">
                            <button type="button" id="btnFilter" class="btn btn-primary w-50 mr-1">
                                <i class="fas fa-search"></i> Tampilkan
                            </button>
                            <button type="button" id="btnReset" class="btn btn-secondary w-50 ml-1">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grid Card -->
    <div class="card card-default">
        <div class="card-header">
            <h3 class="card-title">DATA TRACING PER CABANG</h3>
        </div>
        <div class="card-body p-0">
            <table id="jqGrid"></table>
            <!-- <div id="jqGridPager"></div> -->
            <div class="d-flex justify-content-between align-items-center p-2 mt-0">
                <div id="lastUpdateHandler"></div>
                <div id="jqGridInfoHandler"></div>
            </div>
        </div>
    </div>
            
</div>

<!-- Scripts -->
<script src="<?= base_url('libraries/tas-lib/js/lazyLoadingGridMonolith.js') ?>"></script>

<script>
    $(document).ready(function() {
        let indexRow = 0;
        let triggerClick = true;
        let limit = 50;
        let postData;
        var activeGrid;
        let sortname = 'waktulogin';
        let sortorder = 'desc';
        let rowNum = 50;
        const apiUrl = `<?= base_url('tracing/grid') ?>`;
        const $grid = $("#jqGrid");

        // Initialize Select2
        if($('.select2').length > 0) {
            $('.select2').select2({ theme: 'bootstrap4' });
        }

        // Detect Device Widths (Inspired by Trucking)
        const isDesktop = (detectDeviceType() == "desktop");
        
        let sm_dekstop_3 = 100;
        let sm_mobile_3 = 100;
        
        let sm_dekstop_4 = 200;
        let sm_mobile_4 = 150;

        $grid.jqGrid({
            url: apiUrl,
            mtype: "POST", // we use post
            datatype: "local",
            postData: {
                cabang: function() { return $('#cabangSelect').val(); }
            },
            styleUI: 'Bootstrap4',
            iconSet: 'fontAwesome',
            colModel: [
                {
                    label: 'User ID',
                    name: 'UserId',
                    index: 'UserId',
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_3),
                    searchoptions: { sopt: ['cn'] }
                },
                {
                    label: 'Shipper',
                    name: 'shipper',
                    index: 'shipper',
                    width: (isDesktop ? sm_dekstop_4 : sm_mobile_4),
                    searchoptions: { sopt: ['cn'] }
                },
                {
                    label: 'Waktu Login',
                    name: 'waktulogin',
                    index: 'waktulogin',
                    width: (isDesktop ? sm_dekstop_4 : sm_mobile_4),
                    searchoptions: { sopt: ['cn'] }
                },
                {
                    label: 'Cabang',
                    name: 'cabang',
                    index: 'cabang',
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_3),
                    searchoptions: { sopt: ['cn'] }
                }
            ],
            autowidth: true,
            shrinkToFit: false,
            height: 400,
            rowNum: rowNum,
            rownumbers: true,
            rownumWidth: 45,
            rowList: [10, 20, 50, 100],
            toolbar: [true, "top"],
            sortable: true,
            sortname: sortname,
            sortorder: sortorder,
            page: 1,
            viewrecords: true,
            prmNames: {
                sort: "sidx",
                order: "sord",
                search: "_search"
            },
            onSelectRow: function(id) {
                activeGrid = $(this);
                indexRow = $(this).jqGrid("getCell", id, "rn") - 1;
                page = $(this).jqGrid("getGridParam", "page");
                let rows = $(this).jqGrid("getGridParam", "rowNum");
                indexRow = indexRow - rows * (page - 1);
            },
            onSortCol: function(index, iCol, sortorder) {
                var targetGridId = this.id || 'jqGrid'; if (typeof lazyStates !== 'undefined' && lazyStates[targetGridId]) lazyStates[targetGridId].cachedData = {};
                if (typeof loadGridData === 'function') {
                    loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $(this).jqGrid('getGridParam', 'rowNum'), 'jump', 'page');
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
                
                var $gridObj = $(this);
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
        });

        // Initialize Filter Toolbar
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
                
                var targetGridId = this.id || 'jqGrid'; if (typeof lazyStates !== 'undefined' && lazyStates[targetGridId]) lazyStates[targetGridId].cachedData = {};
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
            
            var targetGridId = this.id || 'jqGrid'; if (typeof lazyStates !== 'undefined' && lazyStates[targetGridId]) lazyStates[targetGridId].cachedData = {};
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
            $('#cabangSelect').val('All').trigger('change.select2');
            
            // Clear filter toolbar inputs
            var ts = $grid[0];
            if (ts.grid && ts.grid.hDiv) {
                $("input", ts.grid.hDiv).val("");
                $("select", ts.grid.hDiv).prop("selectedIndex", 0);
            }
            $grid.jqGrid('setGridParam', { search: false, postData: { filters: "" } });
            
            
            // Generic explicit reset for footerData and custom footers
            try {
                var colModel = $('#jqGrid').jqGrid('getGridParam', 'colModel');
                var footerObj = {};
                if (colModel) {
                    colModel.forEach(function(col) {
                        if (col.name !== 'rn' && col.name !== 'cb') {
                            if (col.formatter === 'number' || col.formatter === 'integer' || col.align === 'right') {
                                footerObj[col.name] = 0;
                            } else if (col.name.toLowerCase().includes('trans') || col.name.toLowerCase().includes('jenis') || col.name.toLowerCase().includes('shipper')) {
                                footerObj[col.name] = "Total";
                            } else {
                                footerObj[col.name] = "";
                            }
                        }
                    });
                    try { $('#jqGrid').jqGrid("footerData", "set", footerObj); } catch(e) {}
                }
                var gridObj = $('#jqGrid')[0].grid;
                if (gridObj && gridObj.sDiv) {
                    $(gridObj.sDiv).find('tr.footrow, tr[class*="myfootrow"]').each(function() {
                        $(this).find('td').each(function() {
                            var align = $(this).css('text-align');
                            var text = $(this).text().trim();
                            if (align === 'right') {
                                $(this).text(text === '' ? '' : 0);
                            } else if (/^[\d.,-]+$/.test(text)) {
                                $(this).text(0);
                            }
                        });
                    });
                }
                $('#lastUpdateHandler, #jqGridInfoHandler').html('');
            } catch(e) {}
            $('#btnFilter').click();
        });

        // Reload data when changing Cabang
        $('#cabangSelect').on('change', function() {
            $('#btnFilter').trigger('click');
        });
        
        // Auto load first time
        setTimeout(function(){
            $('#btnFilter').trigger('click');
        }, 300);
        
    });
</script>
