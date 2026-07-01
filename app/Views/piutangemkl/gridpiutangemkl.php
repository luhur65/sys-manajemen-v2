<?php /* Refactored Piutang EMKL View - Sync with sys-ci4 headers */ ?>

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
                            <option value="BTG">BITUNG</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group filter-input-group">
                        <label class="filter-label">Jenis Job</label>
                        <select id="jnsjobSelect" class="form-control select2">
                            <option value="A" selected>Semua</option>
                            <option value="M">Muatan</option>
                            <option value="B">Bongkaran</option>
                            <option value="I">Import</option>
                            <option value="E">Eksport</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group filter-input-group">
                        <label class="filter-label">Jenis Piutang</label>
                        <select id="isTitipanSelect" class="form-control select2">
                            <option value="0" selected>Semua</option>
                            <option value="1">Titipan</option>
                            <option value="2">Non-Titipan</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
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
            <h3 class="card-title">DATA PIUTANG EMKL - CABANG MEDAN</h3>
        </div>
        <div class="card-body p-0">
            <table id="jqGrid"></table>
            <div id="jqGridPager"></div>

            <div class="d-flex justify-content-between align-items-center p-2 mt-0">
                <div id="lastUpdateHandler">Last Update : <?= $last_update ?></div>
                <div id="jqGridInfoHandler"></div>
            </div>

            <!-- Information boxes from sys-ci4 -->
            <!-- <div class="p-3">
                <table class="table-sm">
                    <tr>
                        <td colspan="3"><b>Keterangan</b></td>
                    </tr>
                    <tr>
                        <td>Jumlah Warna Merah</td>
                        <td>:</td>
                        <td style="text-align:right"><span id="jlhred" class="badge badge-danger">0</span> Item</td>
                    </tr>
                    <tr>
                        <td>Jumlah Warna Kuning</td>
                        <td>:</td>
                        <td style="text-align:right"><span id="jlhyellow" class="badge badge-warning">0</span> Item</td>
                    </tr>
                </table>
            </div> -->
        </div>
    </div>
</div>

<script type="text/javascript">
    let $activeCell = null;
    let activeColumnIndex = 0;
    $(document).ready(function() {
        let indexRow = 0
        let triggerClick = true
        let limit
        let postData
        var activeGrid
        let sortname = 'FSelisih'
        let sortorder = 'desc'
        let rowNum = 50
        let id = ''
        const apiUrl = `<?= base_url('piutangemkl/grid') ?>`;
        const $grid = $("#jqGrid");
        // Format money helper
        const formatMoney = (val) => new Intl.NumberFormat('en-US').format(val);

        // Detect Device Widths (Inspired by Trucking)
        const isDesktop = (detectDeviceType() == "desktop");

        $grid.jqGrid({
            url: `<?= base_url('piutangemkl/grid') ?>`,
            mtype: "GET",
            datatype: "local",
            postData: {
                cabang: function() {
                    return $('#cabangSelect').val();
                },
                jnsjob: function() {
                    return $('#jnsjobSelect').val();
                },
                isTitipan: function() {
                    return $('#isTitipanSelect').val();
                }
            },
            gridPreference: true,
            localReader: { repeatitems: false },
            jsonReader: { repeatitems: false },
            styleUI: 'Bootstrap4',
            iconSet: 'fontAwesome',
            colModel: [
                {
                    label: 'Tanggal EPE',
                    name: 'FTgl',
                    index: 'FTgl',
                    width: (isDesktop ? sm_dekstop_2 : sm_mobile_2),
                    sorttype: 'date'
                },
                {
                    label: 'No EPE',
                    name: 'FNTrans',
                    index: 'FNTrans',
                    width: (isDesktop ? md_dekstop_1 : md_mobile_1)
                },
                {
                    label: 'No Invoice',
                    name: 'FNInvoice',
                    index: 'FNInvoice',
                    width: (isDesktop ? md_dekstop_1 : md_mobile_1)
                },
                {
                    label: 'Nama Shipper',
                    name: 'FNShipper',
                    index: 'FNShipper',
                    width: (isDesktop ? md_dekstop_2 : md_mobile_2)
                },
                {
                    label: 'Nilai Invoice',
                    name: 'FNominal',
                    index: 'FNominal',
                    formatter: 'integer',
                    sorttype: 'int',
                    align: 'right',
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_2)
                },
                {
                    label: 'Sisa (Blm dilunasi)',
                    name: 'FSisa',
                    index: 'FSisa',
                    formatter: 'integer',
                    sorttype: 'int',
                    align: 'right',
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_2)
                },
                {
                    label: 'TOP (Hari)',
                    name: 'FTOP',
                    index: 'FTOP',
                    formatter: 'integer',
                    sorttype: 'int',
                    align: 'right',
                    width: (isDesktop ? sm_dekstop_1 : sm_mobile_1)
                },
                {
                    label: 'Tgl Jth Tempo',
                    name: 'FTglJT',
                    index: 'FTglJT',
                    width: (isDesktop ? sm_dekstop_2 : sm_mobile_2),
                    sorttype: 'date'
                },
                {
                    label: 'OverDue (Hari)',
                    name: 'FSelisih',
                    index: 'FSelisih',
                    formatter: 'integer',
                    sorttype: 'int',
                    align: 'right',
                    width: (isDesktop ? sm_dekstop_1 : sm_mobile_1)
                },
                {
                    label: 'Remind',
                    name: 'FJnsRemind',
                    index: 'FJnsRemind',
                    width: 100,
                    hidden: true
                },
                {
                    label: 'No Job',
                    name: 'FNoJob',
                    index: 'FNoJob',
                    width: (isDesktop ? md_dekstop_1 : md_mobile_1)
                },
                {
                    label: 'Bln',
                    name: 'FBlnJob',
                    index: 'FBlnJob',
                    width: 40,
                    hidden: true,
                    search: false
                },
                {
                    label: 'Thn',
                    name: 'FThnJob',
                    index: 'FThnJob',
                    width: 50,
                    hidden: true,
                    search: false
                },
                {
                    label: 'Thn-Bln Job',
                    name: 'FNTgl',
                    index: 'FNTgl',
                    width: (isDesktop ? sm_dekstop_2 : sm_mobile_1)
                },
                {
                    label: 'Jns Job',
                    name: 'FJnsJob',
                    index: 'FJnsJob',
                    width: (isDesktop ? sm_dekstop_2 : sm_mobile_2)
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
            rowNum: 50,
            toolbar: [true, "top"],
            rowList: [10, 20, 30, 50, 100],
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
            onSelectRow: function(rowid, status, e) {
                activeGrid = $grid;
                let getInd = $grid.jqGrid('getInd', rowid) - 1;
                indexRow = getInd;
                page = $grid.jqGrid('getGridParam', 'page')

                // Sinkronisasi Excel Active Cell jika di-trigger programmatically
                if (!e || $(e.target).closest('td').length === 0) {
                    let $selectedRow = $grid.find('tr[id="' + rowid + '"]');
                    if ($activeCell) $activeCell.removeClass('excel-active-cell');
                    
                    $activeCell = $selectedRow.find('td').eq(activeColumnIndex);
                    // Hindari hidden column
                    while ($activeCell.length && $activeCell.css('display') === 'none') {
                        $activeCell = $activeCell.next('td');
                        if ($activeCell.length) activeColumnIndex = $activeCell.index();
                    }
                    
                    if ($activeCell.length) {
                        $activeCell.addClass('excel-active-cell');
                    }
                }
            },
            onSortCol: function(index, iCol, sortorder) {
                if (typeof lazyStates !== 'undefined' && lazyStates["jqGrid"]) lazyStates["jqGrid"].cachedData = {};
                loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $(this).jqGrid('getGridParam', 'rowNum'), 'jump', 'reload');
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
                
                // Support both standard load and lazy load response
                var $gridObj = $(this);
                var userData = res.userdata || $(this).jqGrid('getGridParam', 'userData');

                if (userData && userData.last_update) {
                    $('#lastUpdateHandler').text('Last Update : ' + userData.last_update);
                }

                // Initialize custom bind keys
                $(document).off('keydown.grid');
                setCustomBindKeys($gridObj);

                /* Set global variables */
                sortname = $(this).jqGrid("getGridParam", "sortname")
                sortorder = $(this).jqGrid("getGridParam", "sortorder")
                limit = $(this).jqGrid('getGridParam', 'postData').limit
                postData = $(this).jqGrid('getGridParam', 'postData')
                setTimeout(function() {
                    var currentGridIds = $grid.getDataIDs();
                    var currentSelection = $grid.jqGrid('getGridParam', 'selrow');
                    var state = (typeof getGridState === 'function') ? getGridState($grid) : {};
                    var minPageLoaded = state.minPageLoaded !== undefined ? state.minPageLoaded : 1;
                    
                    // Trigger click pada row pertama HANYA jika tidak ada seleksi DAN kita di page 1
                    if (!currentSelection && currentGridIds.length > 0 && minPageLoaded === 1) {
                        $grid.find('tr[id="' + currentGridIds[0] + '"]').click();
                    }
                }, 50);

                if (typeof initJqGridInfo === 'function') {
                    initJqGridInfo($(this));
                }

                $grid.removeClass('table-striped');

                // Totals for current page
                // var TotalFNominal = $gridObj.jqGrid('getCol', 'FNominal', false, 'sum');
                // var TotalFSisa = $gridObj.jqGrid('getCol', 'FSisa', false, 'sum');
                var TotalFNominal = 0;
                var TotalFSisa = 0;

                if (typeof lazyStates !== 'undefined' && lazyStates["jqGrid"] && lazyStates["jqGrid"].cachedData) {
                    for (var pg in lazyStates["jqGrid"].cachedData) {
                        lazyStates["jqGrid"].cachedData[pg].forEach(function(row) {
                            // Menjumlahkan nilai murni mengabaikan format tampilan
                            TotalFNominal += parseFloat(row.FNominal) || 0;
                            TotalFSisa += parseFloat(row.FSisa) || 0;
                        });
                    }
                }

                // First footer row
                $gridObj.jqGrid('footerData', 'set', {
                    FNShipper: 'TOTAL :',
                    FNominal: TotalFNominal,
                    FSisa: TotalFSisa
                });

                // Apply alignment to first footer row label
                $gridObj.closest(".ui-jqgrid-view").find(".ui-jqgrid-sdiv tr.footrow td[aria-describedby$='_FNShipper']").css('text-align', 'right');

                // Second footer row for Grand Total
                var $secondFooter = $gridObj.closest(".ui-jqgrid-view").find(".ui-jqgrid-sdiv tr.footrow");

                if (userData) {
                    $secondFooter.find("td[aria-describedby$='_FNShipper']").text("GRAND TOTAL :").css('text-align', 'right');
                    $secondFooter.find("td[aria-describedby$='_FNominal']").text(formatMoney(userData.GrandTotalNominal)).css('text-align', 'right');
                    $secondFooter.find("td[aria-describedby$='_FSisa']").text(formatMoney(userData.GrandTotalSisa)).css('text-align', 'right');
                }

                // Initialize lazy loading scroll handler
                setupLazyLoadScrollHandler("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'));
                setHighlight($grid);

            }
        });

        $grid.jqGrid('filterToolbar', {
            stringResult: true,
            searchOnEnter: false,
            defaultSearch: 'cn',
            beforeSearch: function() {
                if (typeof lazyStates !== 'undefined' && lazyStates["jqGrid"]) lazyStates["jqGrid"].cachedData = {};
                $grid.jqGrid('clearGridData');
            

                loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $grid.jqGrid('getGridParam', 'rowNum'), 'jump', 'reload');
                return false;
            }
        });

        // Initial load
        loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, rowNum, 'down', 'reload');

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

        $('#btnFilter').click(function() {
            if (typeof lazyStates !== 'undefined' && lazyStates["jqGrid"]) lazyStates["jqGrid"].cachedData = {};
            $grid.jqGrid('setGridParam', {
                postData: {
                    cabang: $('#cabangSelect').val(),
                    jnsjob: $('#jnsjobSelect').val(),
                    isTitipan: $('#isTitipanSelect').val()
                }
            });
            const cabangText = $('#cabangSelect option:selected').text();
            $('.card-title').text('DATA PIUTANG EMKL - CABANG ' + cabangText);
            loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $grid.jqGrid('getGridParam', 'rowNum'), 'down', 'reload');
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
                    $('#jqGrid').jqGrid("footerData", "set", footerObj);
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
        // --- Excel-like Active Cell Navigation ---
        $('<style>.excel-active-cell { outline: 2px solid #217346 !important; outline-offset: -2px; background-color: rgba(33, 115, 70, 0.1) !important; z-index: 1000; position: relative; }</style>').appendTo('head');

        $('#jqGrid').on('click', 'tr.jqgrow td', function(e) {
            if ($activeCell) $activeCell.removeClass('excel-active-cell');
            $activeCell = $(this);
            activeColumnIndex = $activeCell.index();
            $activeCell.addClass('excel-active-cell');
        });

        $(document).on('keydown', function(e) {
            if (!$activeCell) return;
            // Prevent interference with input fields
            if ($(e.target).is('input, textarea, select')) return;

            let $tr = $activeCell.closest('tr.jqgrow');
            let cellIndex = $activeCell.index();
            let $nextCell = null;

            if (e.which >= 37 && e.which <= 40) {
                e.preventDefault(); // Prevent page scrolling
            }

            switch(e.which) {
                case 37: // Left
                    $nextCell = $activeCell.prevAll('td:visible').first();
                    break;
                case 38: // Up
                    let $prevTr = $tr.prevAll('tr.jqgrow:visible').first();
                    if ($prevTr.length) {
                        $nextCell = $prevTr.find('td').eq(cellIndex);
                        while ($nextCell.length && $nextCell.css('display') === 'none') {
                            $nextCell = $nextCell.prev('td');
                        }
                    }
                    break;
                case 39: // Right
                    $nextCell = $activeCell.nextAll('td:visible').first();
                    break;
                case 40: // Down
                    let $nextTr = $tr.nextAll('tr.jqgrow:visible').first();
                    if ($nextTr.length) {
                        $nextCell = $nextTr.find('td').eq(cellIndex);
                        while ($nextCell.length && $nextCell.css('display') === 'none') {
                            $nextCell = $nextCell.prev('td');
                        }
                    }
                    break;
                case 27: // Esc
                    $activeCell.removeClass('excel-active-cell');
                    $activeCell = null;
                    return;
                case 13: // Enter
                    let $enterTr = $tr.nextAll('tr.jqgrow:visible').first();
                    if ($enterTr.length) {
                        $nextCell = $enterTr.find('td').eq(cellIndex);
                    }
                    break;
                default:
                    return;
            }

            if ($nextCell && $nextCell.length) {
                $activeCell.removeClass('excel-active-cell');
                $activeCell = $nextCell;
                activeColumnIndex = $activeCell.index();
                $activeCell.addClass('excel-active-cell');

                // Auto-scroll logic
                let bdiv = $activeCell.closest('.ui-jqgrid-bdiv');
                if (bdiv.length) {
                    let offsetTop = $activeCell[0].offsetTop;
                    let offsetLeft = $activeCell[0].offsetLeft;
                    
                    if (offsetTop + $activeCell.outerHeight() > bdiv.scrollTop() + bdiv.height()) {
                        bdiv.scrollTop(offsetTop + $activeCell.outerHeight() - bdiv.height());
                    } else if (offsetTop < bdiv.scrollTop()) {
                        bdiv.scrollTop(offsetTop);
                    }

                    if (offsetLeft + $activeCell.outerWidth() > bdiv.scrollLeft() + bdiv.width()) {
                        bdiv.scrollLeft(offsetLeft + $activeCell.outerWidth() - bdiv.width());
                    } else if (offsetLeft < bdiv.scrollLeft()) {
                        bdiv.scrollLeft(offsetLeft);
                    }
                }
            }
        });
        // --- End Excel-like Active Cell Navigation ---
</script>