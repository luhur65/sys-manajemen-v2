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
                <div class="col-md-3">
                    <div class="form-group filter-input-group">
                        <label class="filter-label">Marketing</label>
                        <select id="marketingSelect" class="form-control select2">
                            <option value="">ALL</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-group filter-input-group w-100">
                        <div class="d-flex w-100">
                            <button type="button" id="btnFilter" class="btn btn-primary w-50 mr-1">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <button type="button" id="btnReset" class="btn btn-secondary w-50 ml-1" >
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
            <h3 class="card-title">DATA OVER TOP EMKL (MARKETING) - CABANG MEDAN</h3>
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
        const apiUrl = `<?= base_url('overtopemklmarketing/grid') ?>`;
        const marketingUrl = `<?= base_url('overtopemklmarketing/get_marketing') ?>`;
        const $grid = $("#jqGrid");
        const formatMoney = (val) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(val);

        // Initialize Select2
        if($('.select2').length > 0) {
            $('.select2').select2({ theme: 'bootstrap4' });
        }
        
        // Load Marketing Combo
        function loadMarketingCombo(cabang) {
            $.ajax({
                url: marketingUrl,
                type: 'POST',
                data: { cabang: cabang },
                dataType: 'json',
                success: function(res) {
                    $('#marketingSelect').html(res.html);
                    $('#marketingSelect').trigger('change.select2');
                }
            });
        }
        loadMarketingCombo($('#cabangSelect').val());

        $('#cabangSelect').on('change', function() {
            loadMarketingCombo($(this).val());
        });

        // Detect Device Widths
        const isDesktop = (detectDeviceType() == "desktop");

        $grid.jqGrid({
            url: apiUrl,
            mtype: "POST", // we use post
            datatype: "local",
            postData: {
                cabang: function() { return $('#cabangSelect').val(); },
                marketing: function() { return $('#marketingSelect').val(); }
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
                // Gracefully clear footer by feeding an empty userdata object
                // This preserves custom footer text labels but zeroes out the totals
                if (res && (res.records === 0 || res.records === "0")) {
                    res.userdata = {};
                    try { $(this).jqGrid('setGridParam', { userData: null }); } catch(e) {}
                    $('#lastUpdateHandler, #jqGridInfoHandler').text('');
                }
                
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
                setTimeout(function() {
                    var currentGridIds = $grid.getDataIDs();
                    var currentSelection = $grid.jqGrid('getGridParam', 'selrow');
                    var state = (typeof getGridState === 'function') ? getGridState($grid) : {};
                    var minPageLoaded = state.minPageLoaded !== undefined ? state.minPageLoaded : 1;
                    
                    if (!currentSelection && currentGridIds.length > 0 && minPageLoaded === 1) {
                        $grid.find('tr[id="' + currentGridIds[0] + '"]').click();
                    }
                }, 50);

                $grid.removeClass('table-striped');

                // Grand Total Footer
                var $secondFooter = $gridObj.closest(".ui-jqgrid-view").find(".ui-jqgrid-sdiv tr.footrow");

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
                    cabang: $('#cabangSelect').val(),
                    marketing: $('#marketingSelect').val()
                }
            });
            const cabangText = $('#cabangSelect option:selected').text();
            $('.card-title').text('DATA OVER TOP EMKL (MARKETING) - CABANG ' + cabangText);
            
            if(typeof loadGridData === 'function') {
                loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $grid.jqGrid('getGridParam', 'rowNum'), 'down', 'reload');
            } else {
                $grid.trigger('reloadGrid', [{page:1}]);
            }
        });

    });

        $(document).off('click', '#btnReset').on('click', '#btnReset', function() {
            var curdate = new Date();
            var d_first = new Date(curdate.getFullYear(), curdate.getMonth(), 1);
            var d_last = new Date(curdate.getFullYear(), curdate.getMonth() + 1, 0);

            if ($('#tgl_dari').length) { 
                try { $('#tgl_dari').datepicker('setDate', d_first); } catch(e) { $('#tgl_dari').val(d_first); } 
            }
            if ($('#tgl_sampai').length) { 
                try { $('#tgl_sampai').datepicker('setDate', d_last); } catch(e) { $('#tgl_sampai').val(d_last); }
            }
            if ($('#datefrom').length) { $('#datefrom').val(d_first); }
            if ($('#dateto').length) { $('#dateto').val(d_last); }
            var curMonth = ("0" + (d_first.getMonth() + 1)).slice(-2) + '-' + d_first.getFullYear();
            var curYear = d_first.getFullYear();
            if ($('#blnInput').length) { $('#blnInput').val(curMonth); }
            if ($('#thnInput').length) { $('#thnInput').val(curYear); }
            if ($('#bulan').length) { $('#bulan').val(curMonth); }
            
            $('select.select2').each(function() {
                var firstVal = $(this).find('option:first').val();
                $(this).val(firstVal).trigger('change.select2');
            });
            
            $('input[type="text"]:not(.hasDatepicker):not(.monthpicker):not(.yearpicker):not(#bulan):not(#blnInput):not(#thnInput)').val('');
            
            try { $('#jqGrid')[0].clearToolbar(false); } catch(e) {}
            
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
            $('#btnFilter').trigger('click');
        });
</script>
