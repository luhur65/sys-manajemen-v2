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
                        <label class="filter-label">Cabang</label>
                        <select id="cabangSelect" class="form-control select2">
                            <option value="MDN" selected>MEDAN</option>
                            <option value="JKT">JAKARTA</option>
                            <option value="SBY">SURABAYA</option>
                            <option value="MKS">MAKASSAR</option>
                            <option value="SMG">SEMARANG</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
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
            <h3 class="card-title">DATA OVER TOP EMKL - CABANG MEDAN</h3>
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
        let sortname = 'FSelisih'
        let sortorder = 'desc'
        let rowNum = 50
        const apiUrl = `<?= base_url('overtopemkl/grid') ?>`;
        const $grid = $("#jqGrid");
        const formatMoney = (val) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(val);

        // Initialize Select2
        if($('.select2').length > 0) {
            $('.select2').select2({ theme: 'bootstrap4' });
        }

        // Detect Device Widths
        const isDesktop = (detectDeviceType() == "desktop");

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
                    label: 'Tanggal',
                    name: 'FTgl',
                    index: 'FTgl',
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_3),
                    sorttype: 'date'
                },
                {
                    label: 'No Trans',
                    name: 'FNTrans',
                    index: 'FNTrans',
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_3)
                },
                {
                    label: 'No Invoice',
                    name: 'FNInvoice',
                    index: 'FNInvoice',
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_3)
                },
                {
                    label: 'Shipper',
                    name: 'FNShipper',
                    index: 'FNShipper',
                    width: (isDesktop ? sm_dekstop_4 : sm_mobile_4)
                },
                {
                    label: 'Nominal',
                    name: 'FNominal',
                    index: 'FNominal',
                    formatter: 'integer',
                    sorttype: 'int',
                    align: 'right',
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_3)
                },
                {
                    label: 'Sisa',
                    name: 'FSisa',
                    index: 'FSisa',
                    formatter: 'integer',
                    sorttype: 'int',
                    align: 'right',
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_3)
                },
                {
                    label: 'TOP',
                    name: 'FTOP',
                    index: 'FTOP',
                    formatter: 'integer',
                    sorttype: 'int',
                    align: 'right',
                    width: (isDesktop ? sm_dekstop_2 : sm_mobile_2)
                },
                {
                    label: 'Tgl JT',
                    name: 'FTglJT',
                    index: 'FTglJT',
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_3),
                    sorttype: 'date'
                },
                {
                    label: 'Over/Top',
                    name: 'FSelisih',
                    index: 'FSelisih',
                    formatter: 'integer',
                    sorttype: 'int',
                    align: 'right',
                    width: (isDesktop ? sm_dekstop_2 : sm_mobile_2)
                },
                {
                    label: 'Status',
                    name: 'FJnsRemind',
                    index: 'FJnsRemind',
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_3)
                },
                {
                    label: 'No Job',
                    name: 'FNoJob',
                    index: 'FNoJob',
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_3)
                },
                {
                    label: 'Bln',
                    name: 'FBlnJob',
                    index: 'FBlnJob',
                    hidden: true,
                    search: false
                },
                {
                    label: 'Thn',
                    name: 'FThnJob',
                    index: 'FThnJob',
                    hidden: true,
                    search: false
                },
                {
                    label: 'Thn-Bln Job',
                    name: 'FNTgl',
                    index: 'FNTgl',
                    hidden: true,
                    search: false
                },
                {
                    label: 'Jns Job',
                    name: 'FJnsJob',
                    index: 'FJnsJob',
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_3)
                },
                {
                    label: 'Jns Piutang',
                    name: 'FJnsPiutang',
                    index: 'FJnsPiutang',
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

                    $secondFooter.find("td[aria-describedby$='_FTgl']").text("GRAND TOTAL :").css('text-align', 'right').css('font-weight', 'bold');
                    $secondFooter.find("td[aria-describedby$='_FNominal']").text(formatMoney(userData.TotalNominal || 0)).css('text-align', 'right').css('font-weight', 'bold');
                    $secondFooter.find("td[aria-describedby$='_FSisa']").text(formatMoney(userData.TotalSisa || 0)).css('text-align', 'right').css('font-weight', 'bold');
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
                    cabang: $('#cabangSelect').val()
                }
            });
            const cabangText = $('#cabangSelect option:selected').text();
            $('.card-title').text('DATA OVER TOP EMKL - CABANG ' + cabangText);
            
            if(typeof loadGridData === 'function') {
                loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $grid.jqGrid('getGridParam', 'rowNum'), 'down', 'reload');
            } else {
                $grid.trigger('reloadGrid', [{page:1}]);
            }
        });

    });
</script>
