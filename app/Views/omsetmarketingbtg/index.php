<style>
    #ui-datepicker-div { display: none; }
</style>
<div class="container-fluid">
    <!-- Filter Card -->
    <div class="card card-primary card-outline">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group filter-input-group">
                        <label class="filter-label">Tanggal Dari</label>
                        <input type="text" class="form-control" id="tgl_dari" autocomplete="off">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group filter-input-group">
                        <label class="filter-label">Tanggal Sampai</label>
                        <input type="text" class="form-control" id="tgl_sampai" autocomplete="off">
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-group filter-input-group w-100">
                        <button id="btnFilter" class="btn btn-primary btn-block">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grid Card -->
    <div class="card card-default">
        <div class="card-header">
            <h3 class="card-title">DATA OMSET MARKETING - CABANG BITUNG</h3>
        </div>
        <div class="card-body p-0">
            <table id="jqGrid"></table>
            <div id="jqGridPager"></div>

            <div class="d-flex justify-content-between align-items-center p-2 mt-0">
                <div id="lastUpdateHandler">Last Update : <?= $last_update ?></div>
                <div id="jqGridInfoHandler"></div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        let id = '';

        let indexRow = 0
        let triggerClick = true
        let limit
        let postData
        var activeGrid
        let sortname = 'FTgl'
        let sortorder = 'desc'
        let rowNum = 50
        const apiUrl = `<?= base_url('omsetmarketingbtg/grid') ?>`;
        const $grid = $("#jqGrid");
        const formatMoney = (val) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val);

        // Initialize Datepickers
        var curdate = new Date();
        var first_day = new Date(curdate.getFullYear(), curdate.getMonth(), 1);
        var last_day = new Date(curdate.getFullYear(), curdate.getMonth() + 1, 0);

        $("#tgl_dari").datepicker({ dateFormat: 'yy-mm-dd' });
        $("#tgl_dari").datepicker('setDate', first_day);
        
        $("#tgl_sampai").datepicker({ dateFormat: 'yy-mm-dd' });
        $("#tgl_sampai").datepicker('setDate', last_day);

        // Initialize Select2
        if($('.select2').length > 0) {
            $('.select2').select2({ theme: 'bootstrap4' });
        }

        // Detect Device Widths (Inspired by Trucking)
        const isDesktop = (detectDeviceType() == "desktop");

        $grid.jqGrid({
            url: apiUrl,
            mtype: "POST", // we use post
            datatype: "local",
            postData: {
                tgl_dari: function() { return $('#tgl_dari').val(); },
                tgl_sampai: function() { return $('#tgl_sampai').val(); }
            },
            styleUI: 'Bootstrap4',
            iconSet: 'fontAwesome',
            colModel: [
                {
                    label: 'Marketing',
                    name: 'FNMarketing',
                    index: 'FNMarketing',
                    width: (isDesktop ? sm_dekstop_4 : sm_mobile_4)
                },
                {
                    label: 'Tgl',
                    name: 'FTgl',
                    index: 'FTgl',
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_3),
                    sorttype: 'date',
                    // searchoptions: {
                    //     sopt: ['eq'],
                    //     dataInit: function(elem) {
                    //         $(elem).datepicker({
                    //             dateFormat: 'yy-mm-dd',
                    //             changeYear: true,
                    //             changeMonth: true,
                    //             showWeek: true,
                    //             onSelect: function() {
                    //                 setTimeout(function() {
                    //                     $grid[0].triggerToolbar();
                    //                 }, 100);
                    //             }
                    //         });
                    //     }
                    // }
                },
                {
                    label: 'Muatan',
                    name: 'FJumlahMuatan',
                    index: 'FJumlahMuatan',
                    formatter: 'integer',
                    sorttype: 'int',
                    align: 'right',
                    width: (isDesktop ? sm_dekstop_2 : sm_mobile_2)
                },
                {
                    label: 'Bongkaran',
                    name: 'FJumlahBongkaran',
                    index: 'FJumlahBongkaran',
                    formatter: 'integer',
                    sorttype: 'int',
                    align: 'right',
                    width: (isDesktop ? sm_dekstop_2 : sm_mobile_2)
                },
                {
                    label: 'Exim',
                    name: 'FJumlahExim',
                    index: 'FJumlahExim',
                    formatter: 'integer',
                    sorttype: 'int',
                    align: 'right',
                    width: (isDesktop ? sm_dekstop_2 : sm_mobile_2)
                },
                {
                    label: 'Omset',
                    name: 'FOmset',
                    index: 'FOmset',
                    formatter: 'number',
                    formatoptions: { decimalSeparator: ".", thousandsSeparator: ",", decimalPlaces: 2 },
                    sorttype: 'float',
                    align: 'right',
                    width: (isDesktop ? sm_dekstop_4 : sm_mobile_4)
                },
                {
                    label: 'B. Lapangan',
                    name: 'FBiayaLapangan',
                    index: 'FBiayaLapangan',
                    formatter: 'number',
                    formatoptions: { decimalSeparator: ".", thousandsSeparator: ",", decimalPlaces: 2 },
                    sorttype: 'float',
                    align: 'right',
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_3)
                },
                {
                    label: 'PPh 23',
                    name: 'FNomPph23',
                    index: 'FNomPph23',
                    formatter: 'number',
                    formatoptions: { decimalSeparator: ".", thousandsSeparator: ",", decimalPlaces: 2 },
                    sorttype: 'float',
                    align: 'right',
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_3)
                },
                {
                    label: 'Profit',
                    name: 'FProfit',
                    index: 'FProfit',
                    formatter: 'number',
                    formatoptions: { decimalSeparator: ".", thousandsSeparator: ",", decimalPlaces: 2 },
                    sorttype: 'float',
                    align: 'right',
                    width: (isDesktop ? sm_dekstop_4 : sm_mobile_4)
                },
                {
                    label: 'Margin',
                    name: 'FMargin',
                    index: 'FMargin',
                    formatter: 'number',
                    formatoptions: { decimalSeparator: ".", thousandsSeparator: ",", decimalPlaces: 2, suffix: "%" },
                    sorttype: 'float',
                    align: 'right',
                    width: (isDesktop ? sm_dekstop_2 : sm_mobile_2)
                }
            ],
            autowidth: true,
            shrinkToFit: false,
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
            footerrow: true,
            sortable: true,
            sortname: sortname,
            sortorder: sortorder,
            userDataOnFooter: true,
            onSelectRow: onSelectRowFunction = function(id) {
                activeGrid = $grid
                selectedId = $grid.jqGrid('getCell', id, 'id')
                indexRow = $grid.jqGrid('getCell', id, 'rn') - 1
                page = $grid.jqGrid('getGridParam', 'page')
                let limit = $grid.jqGrid('getGridParam', 'postData').limit
                if (indexRow >= limit) indexRow = (indexRow - limit * (page - 1))
            },
            onSortCol: function(index, iCol, sortorder) {
                var targetGridId = this.id || 'jqGrid'; if (typeof lazyStates !== 'undefined' && lazyStates[targetGridId]) lazyStates[targetGridId].cachedData = {};
                if(typeof loadGridData === 'function') {
                    loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $(this).jqGrid('getGridParam', 'rowNum'), 'jump', 'page');
                }
                return 'stop';
            },
            loadComplete: function(res) {
                var $gridObj = $(this);
                var userData = res.userdata || $(this).jqGrid('getGridParam', 'userData');

                if (userData && userData.last_update) {
                    $('#lastUpdateHandler').text('Last Update : ' + userData.last_update);
                }

                $(document).off('keydown.grid');
                if(typeof setCustomBindKeys === 'function') setCustomBindKeys($gridObj);

                sortname = $(this).jqGrid("getGridParam", "sortname")
                sortorder = $(this).jqGrid("getGridParam", "sortorder")
                limit = $(this).jqGrid('getGridParam', 'postData').limit
                postData = $(this).jqGrid('getGridParam', 'postData')
                triggerClick = true
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

                $grid.removeClass('table-striped');

                // Grand Total Footer
                var $footerRow = $gridObj.closest(".ui-jqgrid-view").find(".ui-jqgrid-sdiv tr.footrow");
                var $secondFooter = $gridObj.closest(".ui-jqgrid-view").find(".ui-jqgrid-sdiv tr.myfootrow");

                if ($secondFooter.length === 0) {
                    $secondFooter = $footerRow.clone().removeClass("footrow").addClass("myfootrow");
                    $secondFooter.children("td").each(function() { this.style.width = ""; });
                    $secondFooter.insertAfter($footerRow);
                }

                var totalRecords = $gridObj.jqGrid("getGridParam", "records");
                if (userData && parseInt(totalRecords, 10) > 0) {
                    $secondFooter.show();
                    var GrandTotalMargin = 0;
                    if(parseFloat(userData.GrandTotalOmset) != 0 && !isNaN(parseFloat(userData.GrandTotalOmset))) {
                        GrandTotalMargin = (parseFloat(userData.GrandTotalProfit) / parseFloat(userData.GrandTotalOmset)) * 100;
                    }

                    $secondFooter.find("td[aria-describedby$='_FTgl']").text("GRAND TOTAL :").css('text-align', 'right').css('font-weight', 'bold');
                    $secondFooter.find("td[aria-describedby$='_FJumlahMuatan']").text(userData.GrandTotalMuatan || 0).css('text-align', 'right').css('font-weight', 'bold');
                    $secondFooter.find("td[aria-describedby$='_FJumlahBongkaran']").text(userData.GrandTotalBongkaran || 0).css('text-align', 'right').css('font-weight', 'bold');
                    $secondFooter.find("td[aria-describedby$='_FJumlahExim']").text(userData.GrandTotalExim || 0).css('text-align', 'right').css('font-weight', 'bold');
                    $secondFooter.find("td[aria-describedby$='_FOmset']").text(formatMoney(userData.GrandTotalOmset || 0)).css('text-align', 'right').css('font-weight', 'bold');
                    $secondFooter.find("td[aria-describedby$='_FBiayaLapangan']").text(formatMoney(userData.GrandTotalBiayaLapangan || 0)).css('text-align', 'right').css('font-weight', 'bold');
                    $secondFooter.find("td[aria-describedby$='_FNomPph23']").text(formatMoney(userData.GrandTotalPph23 || 0)).css('text-align', 'right').css('font-weight', 'bold');
                    $secondFooter.find("td[aria-describedby$='_FProfit']").text(formatMoney(userData.GrandTotalProfit || 0)).css('text-align', 'right').css('font-weight', 'bold');
                    $secondFooter.find("td[aria-describedby$='_FMargin']").text(formatMoney(GrandTotalMargin) + '%').css('text-align', 'right').css('font-weight', 'bold');
                } else {
                    $secondFooter.hide();
                }

                if(typeof setupLazyLoadScrollHandler === 'function') {
                    setupLazyLoadScrollHandler("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'));
                }
                if(typeof setHighlight === 'function') {
                    setHighlight($grid);
                }
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
                
                var targetGridId = this.id || 'jqGrid'; if (typeof lazyStates !== 'undefined' && lazyStates[targetGridId]) lazyStates[targetGridId].cachedData = {};
                $grid.jqGrid('clearGridData');
                if(typeof loadGridData === 'function') {
                    loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $grid.jqGrid('getGridParam', 'rowNum'), 'jump', 'page');
                }
                return false;
            }
        });

        // Trigger load
        if(typeof loadGridData === 'function') {
            loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, rowNum, 'down', 'reload');
        } else {
            $grid.jqGrid('setGridParam',{datatype:'json'}).trigger('reloadGrid');
        }

        // --- Logic for red circle clear button based on provided HTML ---
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

        // Filter Action
        $('#btnFilter').click(function() {
            var targetGridId = this.id || 'jqGrid'; if (typeof lazyStates !== 'undefined' && lazyStates[targetGridId]) lazyStates[targetGridId].cachedData = {};
            $grid.jqGrid('setGridParam', {
                postData: {
                    tgl_dari: $('#tgl_dari').val(),
                    tgl_sampai: $('#tgl_sampai').val()
                }
            });
            
            if(typeof loadGridData === 'function') {
                loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $grid.jqGrid('getGridParam', 'rowNum'), 'down', 'reload');
            } else {
                $grid.trigger('reloadGrid', [{page:1}]);
            }
        });

    });
</script>
