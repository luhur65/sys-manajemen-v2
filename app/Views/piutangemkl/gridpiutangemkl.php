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
            onSelectRow: onSelectRowFunction = function(id) {
                activeGrid = $grid
                selectedId = $grid.jqGrid('getCell', id, 'id')
                indexRow = $grid.jqGrid('getCell', id, 'rn') - 1
                page = $grid.jqGrid('getGridParam', 'page')
                let limit = $grid.jqGrid('getGridParam', 'postData').limit
                if (indexRow >= limit) indexRow = (indexRow - limit * (page - 1))

            },
            onSortCol: function(index, iCol, sortorder) {
                if (typeof lazyStates !== 'undefined' && lazyStates["jqGrid"]) lazyStates["jqGrid"].cachedData = {};
                loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $(this).jqGrid('getGridParam', 'rowNum'), 'jump', 'reload');
                return 'stop';
            },
            loadComplete: function(res) {
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
                var $footerRow = $gridObj.closest(".ui-jqgrid-view").find(".ui-jqgrid-sdiv tr.footrow");
                var $secondFooter = $gridObj.closest(".ui-jqgrid-view").find(".ui-jqgrid-sdiv tr.myfootrow");

                if ($secondFooter.length === 0) {
                    $secondFooter = $footerRow.clone().removeClass("footrow").addClass("myfootrow");
                    $secondFooter.insertAfter($footerRow);
                }

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
</script>