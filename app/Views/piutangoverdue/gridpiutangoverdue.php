<?php /* Laporan Piutang Overdue - konsep mengikuti piutangemkl/gridpiutangemkl */ ?>

<div class="container-fluid">
    <!-- Filter Card -->
    <div class="card card-primary card-outline">
        <div class="card-body">
            <div class="row">
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="form-group filter-input-group">
                        <label class="filter-label">Cabang</label>
                        <select id="cabangSelect" class="form-control select2"></select>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="form-group filter-input-group">
                        <label class="filter-label">Bulan</label>
                        <select id="bulanSelect" class="form-control select2"></select>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="form-group filter-input-group">
                        <label class="filter-label">Marketing</label>
                        <select id="marketingSelect" class="form-control select2"></select>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 col-sm-6">
                    <div class="form-group filter-input-group">
                        <label class="filter-label">Shipper</label>
                        <select id="shipperSelect" class="form-control select2"></select>
                    </div>
                </div>
                <div class="col-lg-2 col-md-2 col-sm-6">
                    <div class="form-group filter-input-group">
                        <label class="filter-label text-nowrap">Jml Hari</label>
                        <input type="number" id="jumlahHariInput" class="form-control" value="14" min="0" step="1">
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-12 d-flex align-items-end">
                    <div class="form-group filter-input-group w-100">
                        <div class="d-flex w-100">
                            <button type="button" id="btnFilter" class="btn btn-primary w-50 mr-1 text-nowrap">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <button type="button" id="btnReset" class="btn btn-secondary w-50 ml-1 text-nowrap">
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
            <h3 class="card-title" id="gridTitle">DATA PIUTANG OVERDUE</h3>
        </div>
        <div class="card-body p-0">
            <table id="jqGrid"></table>
            <div id="jqGridPager"></div>

            <div class="d-flex justify-content-end align-items-center p-2 mt-0">
                <div id="jqGridInfoHandler"></div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    let $activeCell = null;
    let activeColumnIndex = 0;
    $(document).ready(function() {
        // Default tanpa sort: urutan mengikuti hasil SP usp_getlistshipperoverdue
        let sortname = '';
        let sortorder = 'asc';
        let rowNum = 50;
        const apiUrl = `<?= base_url('piutangoverdue/grid') ?>`;
        const optUrl = `<?= base_url('piutangoverdue/options') ?>`;
        const $grid = $("#jqGrid");
        const formatMoney = (val) => new Intl.NumberFormat('en-US').format(val || 0);
        const isDesktop = (detectDeviceType() == "desktop");

        // ---------- Dropdown filter (sumber: stored procedure) ----------
        function fillSelect($sel, list, preferred) {
            $sel.empty();
            (list || []).forEach(v => $sel.append(new Option(v, v)));
            if (preferred && list.indexOf(preferred) !== -1) {
                $sel.val(preferred);
            }
            $sel.trigger('change.select2');
        }

        function loadOptions(type, params) {
            return $.getJSON(optUrl, Object.assign({ type: type }, params))
                .fail(function() {
                    if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal memuat data ' + type, 'error');
                });
        }

        // Bulan default = bulan terakhir (MM-YYYY terbesar), bukan 'ALL'
        function latestMonth(list) {
            let best = null, bestKey = '';
            (list || []).forEach(v => {
                const m = /^(\d{2})-(\d{4})$/.exec(v);
                if (m && (m[2] + m[1]) > bestKey) { bestKey = m[2] + m[1]; best = v; }
            });
            return best;
        }

        function reloadShipper(keep) {
            return loadOptions('shipper', {
                cabang: $('#cabangSelect').val(),
                bulan: $('#bulanSelect').val(),
                marketing: $('#marketingSelect').val()
            }).then(list => fillSelect($('#shipperSelect'), list, keep ? $('#shipperSelect').val() : 'ALL'));
        }

        function reloadMarketing(keep) {
            return loadOptions('marketing', {
                cabang: $('#cabangSelect').val(),
                bulan: $('#bulanSelect').val()
            }).then(list => fillSelect($('#marketingSelect'), list, keep ? $('#marketingSelect').val() : 'ALL'))
              .then(() => reloadShipper(keep));
        }

        function reloadBulan(keep) {
            const cur = $('#bulanSelect').val();
            return loadOptions('bulan', { cabang: $('#cabangSelect').val() })
                .then(list => fillSelect($('#bulanSelect'), list, (keep && cur) ? cur : latestMonth(list)))
                .then(() => reloadMarketing(keep));
        }

        function initFilters() {
            return loadOptions('cabang', {})
                .then(list => fillSelect($('#cabangSelect'), list, 'ALL'))
                .then(() => reloadBulan(false));
        }

        $('#cabangSelect').on('change', function() { reloadBulan(true); });
        $('#bulanSelect').on('change', function() { reloadMarketing(true); });
        $('#marketingSelect').on('change', function() { reloadShipper(true); });

        function currentPostData() {
            return {
                cabang: $('#cabangSelect').val() || 'ALL',
                bulan: $('#bulanSelect').val() || 'ALL',
                marketing: $('#marketingSelect').val() || 'ALL',
                shipper: $('#shipperSelect').val() || 'ALL',
                jumlahhari: parseInt($('#jumlahHariInput').val(), 10) || 0
            };
        }

        function updateTitle() {
            $('#gridTitle').text('DATA PIUTANG OVERDUE - CABANG ' + ($('#cabangSelect').val() || 'ALL') +
                ' - BULAN ' + ($('#bulanSelect').val() || 'ALL'));
        }

        function reloadGrid() {
            if (typeof lazyStates !== 'undefined' && lazyStates["jqGrid"]) lazyStates["jqGrid"].cachedData = {};
            $grid.jqGrid('setGridParam', { postData: currentPostData() });
            updateTitle();
            loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $grid.jqGrid('getGridParam', 'rowNum'), 'down', 'reload');
        }

        // ---------- Grid ----------
        $grid.jqGrid({
            url: apiUrl,
            mtype: "GET",
            datatype: "local",
            postData: {},
            gridPreference: true,
            localReader: { repeatitems: false },
            jsonReader: { repeatitems: false },
            styleUI: 'Bootstrap4',
            iconSet: 'fontAwesome',
            colModel: [
                { label: 'Cabang', name: 'FCabang', index: 'FCabang', width: (isDesktop ? sm_dekstop_2 : sm_mobile_2) },
                { label: 'Nama Shipper', name: 'FShipper', index: 'FShipper', width: (isDesktop ? md_dekstop_2 : md_mobile_2) },
                { label: 'Marketing', name: 'FMarketing', index: 'FMarketing', width: (isDesktop ? md_dekstop_1 : md_mobile_1) },
                { label: 'Tgl Invoice', name: 'FTglInvoice', index: 'FTglInvoice', width: (isDesktop ? sm_dekstop_2 : sm_mobile_2), sorttype: 'date' },
                { label: 'Tgl Tanda Terima', name: 'FTglTT', index: 'FTglTT', width: (isDesktop ? sm_dekstop_2 : sm_mobile_2), sorttype: 'date' },
                { label: 'No Invoice', name: 'FNInvoice', index: 'FNInvoice', width: (isDesktop ? md_dekstop_2 : md_mobile_2) },
                { label: 'Nominal Invoice', name: 'FNominal', index: 'FNominal', formatter: 'integer', sorttype: 'int', align: 'right', width: (isDesktop ? sm_dekstop_3 : sm_mobile_2) },
                { label: 'TOP (Hari)', name: 'FTop', index: 'FTop', formatter: 'integer', sorttype: 'int', align: 'right', width: (isDesktop ? sm_dekstop_1 : sm_mobile_1) },
                { label: 'Real (Hari)', name: 'FReal', index: 'FReal', formatter: 'integer', sorttype: 'int', align: 'right', width: (isDesktop ? sm_dekstop_1 : sm_mobile_1) },
                { label: 'Selisih (Hari)', name: 'FSelisih', index: 'FSelisih', formatter: 'integer', sorttype: 'int', align: 'right', width: (isDesktop ? sm_dekstop_1 : sm_mobile_1) },
                { label: 'Keterangan', name: 'FKeterangan', index: 'FKeterangan', width: (isDesktop ? md_dekstop_2 : md_mobile_2) }
            ],
            autowidth: true,
            shrinkToFit: false,
            height: 350,
            rowNum: rowNum,
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
                if (!e || $(e.target).closest('td').length === 0) {
                    let $selectedRow = $grid.find('tr[id="' + rowid + '"]');
                    if ($activeCell) $activeCell.removeClass('excel-active-cell');
                    $activeCell = $selectedRow.find('td').eq(activeColumnIndex);
                    while ($activeCell.length && $activeCell.css('display') === 'none') {
                        $activeCell = $activeCell.next('td');
                        if ($activeCell.length) activeColumnIndex = $activeCell.index();
                    }
                    if ($activeCell.length) $activeCell.addClass('excel-active-cell');
                }
            },
            onSortCol: function(index, iCol, sortorder) {
                this.p.sortorder = sortorder;
                this.p.sortname = index;
                var pd = $(this).jqGrid('getGridParam', 'postData');
                if (pd) { pd.sidx = index; pd.sord = sortorder; }

                var previousSelectedTh = this.grid.headers[this.p.lastsort].el;
                var newSelectedTh = this.grid.headers[iCol].el;
                $("span.s-ico", previousSelectedTh).hide();
                $("span.s-ico", newSelectedTh).show();
                var disabledClass = "ui-state-disabled";
                $("span.ui-icon-asc, span.ui-icon-desc", newSelectedTh).addClass(disabledClass);
                $("span.ui-icon-" + sortorder, newSelectedTh).removeClass(disabledClass);
                this.p.lastsort = iCol;

                if (typeof lazyStates !== 'undefined' && lazyStates["jqGrid"]) lazyStates["jqGrid"].cachedData = {};
                loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $(this).jqGrid('getGridParam', 'rowNum'), 'jump', 'reload');
                return 'stop';
            },
            loadComplete: function(res) {
                if (res && (res.records === 0 || res.records === "0")) {
                    res.userdata = {};
                    try { $(this).jqGrid('setGridParam', { userData: null }); } catch(e) {}
                    $('#jqGridInfoHandler').text('');
                }

                var $gridObj = $(this);
                var userData = res.userdata || $(this).jqGrid('getGridParam', 'userData');

                $(document).off('keydown.grid');
                setCustomBindKeys($gridObj);

                sortname = $(this).jqGrid("getGridParam", "sortname");
                sortorder = $(this).jqGrid("getGridParam", "sortorder");

                setTimeout(function() {
                    var currentGridIds = $grid.getDataIDs();
                    var currentSelection = $grid.jqGrid('getGridParam', 'selrow');
                    var state = (typeof getGridState === 'function') ? getGridState($grid) : {};
                    var minPageLoaded = state.minPageLoaded !== undefined ? state.minPageLoaded : 1;
                    if (!currentSelection && currentGridIds.length > 0 && minPageLoaded === 1) {
                        $grid.find('tr[id="' + currentGridIds[0] + '"]').click();
                    }
                }, 50);

                if (typeof initJqGridInfo === 'function') initJqGridInfo($(this));
                $grid.removeClass('table-striped');

                // Total baris yang sudah dimuat (lazy load)
                var TotalFNominal = 0;
                if (typeof lazyStates !== 'undefined' && lazyStates["jqGrid"] && lazyStates["jqGrid"].cachedData) {
                    for (var pg in lazyStates["jqGrid"].cachedData) {
                        lazyStates["jqGrid"].cachedData[pg].forEach(function(row) {
                            TotalFNominal += parseFloat(row.FNominal) || 0;
                        });
                    }
                }
                $gridObj.jqGrid('footerData', 'set', { FNInvoice: 'TOTAL :', FNominal: TotalFNominal });

                var $footer = $gridObj.closest(".ui-jqgrid-view").find(".ui-jqgrid-sdiv tr.footrow");
                $footer.find("td[aria-describedby$='_FNInvoice']").css('text-align', 'right');
                if (userData && userData.GrandTotalNominal !== undefined) {
                    $footer.find("td[aria-describedby$='_FNInvoice']").text("GRAND TOTAL :");
                    $footer.find("td[aria-describedby$='_FNominal']").text(formatMoney(userData.GrandTotalNominal)).css('text-align', 'right');
                }

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

        // Tombol clear (lingkaran merah) pada filter toolbar
        $(document).on('keyup input', '.ui-search-input input', function() {
            const $input = $(this);
            const $clearBtn = $input.closest('tr').find('.clearsearchclass');
            $clearBtn.attr('style', $input.val().length > 0 ? 'display: flex !important' : 'display: none !important');
        });
        $(document).on('click', '.clearsearchclass', function() {
            $(this).attr('style', 'display: none !important');
        });

        $('#btnFilter').on('click', reloadGrid);
        $('#jumlahHariInput').on('keydown', function(e) { if (e.which === 13) reloadGrid(); });

        $('#btnReset').on('click', function() {
            $('#jumlahHariInput').val(14);
            try { $grid[0].clearToolbar(false); } catch(e) {}
            $('#jqGridInfoHandler').html('');
            initFilters().then(reloadGrid);
        });

        // Initial load: isi dropdown berantai lalu muat grid
        initFilters().then(reloadGrid);
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