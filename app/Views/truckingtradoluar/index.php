<style>
    #ui-datepicker-div { display: none; }
    /* Mimic the legacy look or adapt Omset's style */
    .jqgrow td { font-size: 13px !important; }
    .ui-jqgrid .ui-jqgrid-htable th { font-size: 13px !important; text-align: center; }
    .ui-jqgrid .ui-userdata { height: 35px; padding: 6px; }
    .card-filter { margin-bottom: 15px; }
    .table-subgrid { margin: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    
    /* Responsif JqGrid Pager pada Mobile */
    @media (max-width: 767px) {
        .ui-jqgrid .ui-jqgrid-pager { height: auto !important; padding: 10px 0 !important; }
        .ui-jqgrid .ui-pager-control { height: auto !important; }
        
        /* Ubah table utama pager menjadi blok bersusun */
        .ui-jqgrid .ui-pager-control > .ui-pg-table,
        .ui-jqgrid .ui-pager-control > .ui-pg-table > tbody,
        .ui-jqgrid .ui-pager-control > .ui-pg-table > tbody > tr {
            display: block;
            width: 100% !important;
        }
        
        /* Kolom kiri, tengah, kanan ditumpuk */
        .ui-jqgrid .ui-pager-control > .ui-pg-table > tbody > tr > td {
            display: block;
            width: 100% !important;
            text-align: center !important;
            margin-bottom: 8px;
        }
        
        /* Jaga agar tabel tombol/pagination di dalam kolom tetap inline/horizontal */
        .ui-jqgrid .ui-pager-control .ui-pg-table .ui-pg-table {
            display: inline-table !important;
            margin: 0 auto;
        }
    }
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
                        </select>
                    </div>
                </div>
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
            <h3 class="card-title" id="gridTitle" style="font-weight: bold; margin-bottom: 0;">DATA PENGGUNAAN TRADO LUAR - CABANG MEDAN</h3><br>
            <small id="lastUpdateHandler" class="text-muted ">Last Update : -</small>
        </div>
        <div class="card-body p-0">
            <table id="jqGrid"></table>
            
            <div class="d-flex justify-content-end align-items-center p-2 mt-0">
                <div id="jqGridInfoHandler"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal modal-fullscreen" id="modalDetailTradoLuar" tabindex="-1" role="dialog" aria-labelledby="modalDetailTitle" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="modal-title" id="modalDetailTitle" style="font-weight: bold; margin-bottom: 0;">Detail Trado Luar</h5>
                    <small id="modalLastUpdate" class="text-muted ">Last Update : -</small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <table id="jqGridDetail"></table>
                <div id="jqGridPagerDetail"></div>
            </div>
            <div class="modal-footer justify-content-start">
                <button type="button" class="btn btn-outline-primary" data-dismiss="modal"><i class="fas fa-times"></i> Close</button>
            </div>
        </div>
    </div>
</div>
<!-- Number formatter for JS -->
<script>
    $(document).ready(function() {
        let limit;
        let sortname = '';
        let sortorder = '';
        let rowNum = 50;

        let indexRow = 0;
        let triggerClick = true;
        let id = '';

        // Initialize Select2
        if($('.select2').length > 0) {
            $('.select2').select2({ theme: 'bootstrap4' });
        }

        // Initialize Datepickers
        var curdate = new Date();
        var first_day = new Date(curdate.getFullYear(), curdate.getMonth(), 1);
        var last_day = new Date(curdate.getFullYear(), curdate.getMonth() + 1, 0);

        $("#tgl_dari").datepicker({ dateFormat: 'yy-mm-dd' });
        $("#tgl_dari").datepicker('setDate', first_day);
        
        $("#tgl_sampai").datepicker({ dateFormat: 'yy-mm-dd' });
        $("#tgl_sampai").datepicker('setDate', last_day);

        const apiUrl = `<?= base_url('truckingtradoluar/grid') ?>`;
        const apiDetailUrl = `<?= base_url('truckingtradoluar/griddetail') ?>`;
        const formatMoney = (val) => new Intl.NumberFormat('en-US').format(val);
        const parseMoney = (val) => Number(String(val).replace(/[^0-9.-]+/g,""));

        // Helper widths based on device
        const isDesktop = (detectDeviceType() == "desktop");
        const colW = isDesktop ? sm_dekstop_2 : sm_mobile_2;

        $("#jqGrid").jqGrid({
            url: apiUrl,
            mtype: "POST",
            datatype: "local",
            postData: {
                cabang: function() { return $('#cabangSelect').val(); },
                tgl_dari: function() { return $('#tgl_dari').val(); },
                tgl_sampai: function() { return $('#tgl_sampai').val(); }
            },
            styleUI: 'Bootstrap4',
            iconSet: 'fontAwesome',
            colModel: [
                { label: 'Tanggal', name: 'FTgl', width: (isDesktop ? sm_dekstop_2 : sm_mobile_2), align: 'center', sorttype: 'date' },
                
                // Muatan
                { label: '20', name: 'FUkuran20Muatan', width: colW, formatter: 'integer', align: 'right' },
                { label: '2x20', name: 'FUkuran2x20Muatan', width: colW, formatter: 'integer', align: 'right' },
                { label: '40', name: 'FUkuran40Muatan', width: colW, formatter: 'integer', align: 'right' },

                // Bongkaran
                { label: '20', name: 'FUkuran20Bongkaran', width: colW, formatter: 'integer', align: 'right' },
                { label: '2x20', name: 'FUkuran2x20Bongkaran', width: colW, formatter: 'integer', align: 'right' },
                { label: '40', name: 'FUkuran40Bongkaran', width: colW, formatter: 'integer', align: 'right' },

                // Import
                { label: '20', name: 'FUkuran20Import', width: colW, formatter: 'integer', align: 'right' },
                { label: '2x20', name: 'FUkuran2x20Import', width: colW, formatter: 'integer', align: 'right' },
                { label: '40', name: 'FUkuran40Import', width: colW, formatter: 'integer', align: 'right' },

                // Eksport
                { label: '20', name: 'FUkuran20Eksport', width: colW, formatter: 'integer', align: 'right' },
                { label: '2x20', name: 'FUkuran2x20Eksport', width: colW, formatter: 'integer', align: 'right' },
                { label: '40', name: 'FUkuran40Eksport', width: colW, formatter: 'integer', align: 'right' }
            ],
            rowNum: 50,
            rowList: [50, 100, 500, 1000],
            sortname: 'FTgl',
            sortorder: 'desc',
            viewrecords: false,
            autowidth: true,
            height: 400,
            shrinkToFit: false,
            rownumbers: true,
            footerrow: true,
            userDataOnFooter: true,
            toolbar: [true, 'top'],
            onSortCol: function(index, iCol, sortorder) {
                var targetGridId = this.id || 'jqGrid'; if (typeof lazyStates !== 'undefined' && lazyStates[targetGridId]) lazyStates[targetGridId].cachedData = {};
                if(typeof loadGridData === 'function') {
                    loadGridData("#jqGrid", apiUrl, $("#jqGrid").jqGrid('getGridParam', 'postData'), 1, $("#jqGrid").jqGrid('getGridParam', 'rowNum'), 'jump', 'page');
                }
                return 'stop';
            },
            loadComplete: function(data) {
                var $gridObj = $(this);

                if (data && data.userdata) {
                    $('#lastUpdateHandler').text('Last Update : ' + (data.userdata.last_update || '-'));
                    
                    // 1. Calculate Page Total (Current View)
                    var pageTotal = {
                        FTgl: "Total:",
                        FUkuran20Muatan: $gridObj.jqGrid('getCol', 'FUkuran20Muatan', false, 'sum'),
                        FUkuran2x20Muatan: $gridObj.jqGrid('getCol', 'FUkuran2x20Muatan', false, 'sum'),
                        FUkuran40Muatan: $gridObj.jqGrid('getCol', 'FUkuran40Muatan', false, 'sum'),
                        FUkuran20Bongkaran: $gridObj.jqGrid('getCol', 'FUkuran20Bongkaran', false, 'sum'),
                        FUkuran2x20Bongkaran: $gridObj.jqGrid('getCol', 'FUkuran2x20Bongkaran', false, 'sum'),
                        FUkuran40Bongkaran: $gridObj.jqGrid('getCol', 'FUkuran40Bongkaran', false, 'sum'),
                        FUkuran20Import: $gridObj.jqGrid('getCol', 'FUkuran20Import', false, 'sum'),
                        FUkuran2x20Import: $gridObj.jqGrid('getCol', 'FUkuran2x20Import', false, 'sum'),
                        FUkuran40Import: $gridObj.jqGrid('getCol', 'FUkuran40Import', false, 'sum'),
                        FUkuran20Eksport: $gridObj.jqGrid('getCol', 'FUkuran20Eksport', false, 'sum'),
                        FUkuran2x20Eksport: $gridObj.jqGrid('getCol', 'FUkuran2x20Eksport', false, 'sum'),
                        FUkuran40Eksport: $gridObj.jqGrid('getCol', 'FUkuran40Eksport', false, 'sum')
                    };
                    $gridObj.jqGrid("footerData", "set", pageTotal);

                    // 2. Create Second Footer for Grand Total
                    var $footerRow = $gridObj.closest(".ui-jqgrid-view").find(".ui-jqgrid-sdiv tr.footrow");
                    var $secondFooter = $gridObj.closest(".ui-jqgrid-view").find(".ui-jqgrid-sdiv tr.myfootrow");
                    
                    if ($secondFooter.length === 0) {
                        $secondFooter = $footerRow.clone().removeClass("footrow").addClass("myfootrow");
                        $secondFooter.insertAfter($footerRow);
                    }
                    $footerRow.hide();

                    if (parseInt($gridObj.jqGrid("getGridParam", "records"), 10) > 0) {
                        $secondFooter.show();
                        $secondFooter.find("td[aria-describedby$='_FTgl']").text("GRAND TOTAL :").css('text-align', 'right').css('font-weight', 'bold');
                        $secondFooter.find("td[aria-describedby$='_FUkuran20Muatan']").text(data.userdata.Total20Muatan || 0).css('text-align', 'right').css('font-weight', 'bold');
                        $secondFooter.find("td[aria-describedby$='_FUkuran2x20Muatan']").text(data.userdata.Total2x20Muatan || 0).css('text-align', 'right').css('font-weight', 'bold');
                        $secondFooter.find("td[aria-describedby$='_FUkuran40Muatan']").text(data.userdata.Total40Muatan || 0).css('text-align', 'right').css('font-weight', 'bold');
                        
                        $secondFooter.find("td[aria-describedby$='_FUkuran20Bongkaran']").text(data.userdata.Total20Bongkaran || 0).css('text-align', 'right').css('font-weight', 'bold');
                        $secondFooter.find("td[aria-describedby$='_FUkuran2x20Bongkaran']").text(data.userdata.Total2x20Bongkaran || 0).css('text-align', 'right').css('font-weight', 'bold');
                        $secondFooter.find("td[aria-describedby$='_FUkuran40Bongkaran']").text(data.userdata.Total40Bongkaran || 0).css('text-align', 'right').css('font-weight', 'bold');
                        
                        $secondFooter.find("td[aria-describedby$='_FUkuran20Import']").text(data.userdata.Total20Import || 0).css('text-align', 'right').css('font-weight', 'bold');
                        $secondFooter.find("td[aria-describedby$='_FUkuran2x20Import']").text(data.userdata.Total2x20Import || 0).css('text-align', 'right').css('font-weight', 'bold');
                        $secondFooter.find("td[aria-describedby$='_FUkuran40Import']").text(data.userdata.Total40Import || 0).css('text-align', 'right').css('font-weight', 'bold');
                        
                        $secondFooter.find("td[aria-describedby$='_FUkuran20Eksport']").text(data.userdata.Total20Eksport || 0).css('text-align', 'right').css('font-weight', 'bold');
                        $secondFooter.find("td[aria-describedby$='_FUkuran2x20Eksport']").text(data.userdata.Total2x20Eksport || 0).css('text-align', 'right').css('font-weight', 'bold');
                        $secondFooter.find("td[aria-describedby$='_FUkuran40Eksport']").text(data.userdata.Total40Eksport || 0).css('text-align', 'right').css('font-weight', 'bold');
                    } else {
                        $secondFooter.hide();
                    }
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

                if(typeof setupLazyLoadScrollHandler === 'function') {
                    setupLazyLoadScrollHandler("#jqGrid", apiUrl, $("#jqGrid").jqGrid('getGridParam', 'postData'));
                }
            }
        });

        // Setup Header Grouping for Master Grid
        $("#jqGrid").jqGrid('setGroupHeaders', {
            useColSpanStyle: true, 
            groupHeaders:[
                {startColumnName: 'FUkuran20Muatan', numberOfColumns: 3, titleText: '<div style="text-align:center">Muatan</div>'},
                {startColumnName: 'FUkuran20Bongkaran', numberOfColumns: 3, titleText: '<div style="text-align:center">Bongkaran</div>'},
                {startColumnName: 'FUkuran20Import', numberOfColumns: 3, titleText: '<div style="text-align:center">Import</div>'},
                {startColumnName: 'FUkuran20Eksport', numberOfColumns: 3, titleText: '<div style="text-align:center">Eksport</div>'}
            ]
        });

        $("#jqGrid").jqGrid('filterToolbar', { 
            stringResult: true, 
            searchOnEnter: false, 
            defaultSearch: 'cn',
            beforeSearch: function() {
                var postData = $("#jqGrid").jqGrid('getGridParam', 'postData');
                if (postData.filters) {
                    var filtersObj = JSON.parse(postData.filters);
                    postData._search = (filtersObj.rules && filtersObj.rules.length > 0);
                }
                $("#jqGrid").jqGrid('setGridParam', { postData: postData });
                
                var targetGridId = this.id || 'jqGrid'; if (typeof lazyStates !== 'undefined' && lazyStates[targetGridId]) lazyStates[targetGridId].cachedData = {};
                $("#jqGrid").jqGrid('clearGridData');
                if(typeof loadGridData === 'function') {
                    loadGridData("#jqGrid", apiUrl, $("#jqGrid").jqGrid('getGridParam', 'postData'), 1, $("#jqGrid").jqGrid('getGridParam', 'rowNum'), 'jump', 'page');
                }
                return false;
            }
        });

        $("#jqGrid").customPager({
            lazyLoading: true,
            buttons: [
                {
                    id: 'detail',
                    innerHTML: '<i class="fas fa-list"></i> DETAIL',
                    class: 'btn btn-info btn-sm mr-1',
                    onClick: () => {
                        var selRowId = $("#jqGrid").jqGrid('getGridParam', 'selrow');
                        if (selRowId) {
                            var rowData = $("#jqGrid").jqGrid('getRowData', selRowId);
                            var ftglValue = rowData.FTgl;
                            var cabangValue = $('#cabangSelect').val();
                            
                            $('#modalDetailTitle').text('Detail Trado Luar: ' + ftglValue);
                            $('#modalLastUpdate').text($('#lastUpdateHandler').text());
                            
                            // Initialize or Reload detail grid
                            if (!$("#jqGridDetail").hasClass('ui-jqgrid-btable')) {
                                initDetailGrid(cabangValue, ftglValue);
                            } else {
                                $("#jqGridDetail").jqGrid('setGridParam', {
                                    postData: { cabang: cabangValue, ftgl: ftglValue }
                                });
                                var targetGridId = this.id || 'jqGrid'; if (typeof lazyStates !== 'undefined' && lazyStates[targetGridId]) lazyStates[targetGridId].cachedData = {};
                                $("#jqGridDetail").jqGrid('clearGridData');
                                if (typeof loadGridData === 'function') {
                                    loadGridData("#jqGridDetail", apiDetailUrl, $("#jqGridDetail").jqGrid('getGridParam', 'postData'), 1, 50, 'down', 'reload');
                                }
                            }
                            
                            // Fix jqGrid width when modal is fully visible
                            $('#modalDetailTradoLuar').modal('show');
                            
                        } else {
                            alert("Pilih baris data terlebih dahulu!");
                        }
                    }
                }
            ]
        });


        // Trigger initial load
        if(typeof loadGridData === 'function') {
            loadGridData("#jqGrid", apiUrl, $("#jqGrid").jqGrid('getGridParam', 'postData'), 1, 50, 'down', 'reload');
        } else {
            console.error("lazyLoadingGridMonolith.js is not loaded.");
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

        function initDetailGrid(cabangValue, ftglValue) {
            $("#jqGridDetail").jqGrid({
                url: apiDetailUrl,
                mtype: "POST",
                datatype: "local",
                postData: {
                    cabang: cabangValue,
                    ftgl: ftglValue
                },
                styleUI: 'Bootstrap4',
                iconSet: 'fontAwesome',
                colModel: [
                    { label: 'Tanggal', name: 'FTgl', width: (isDesktop ? sm_dekstop_2 : sm_mobile_2), align: 'center', sorttype: 'date' },
                    { label: 'No Job', name: 'FNTrans', width: (isDesktop ? sm_dekstop_4 : sm_mobile_4) },
                    { label: 'No Cont / Seal', name: 'FNoContSeal', width: (isDesktop ? sm_dekstop_3 : sm_mobile_3) },
                    { label: 'Nama Shipper', name: 'FNShipper', width: (isDesktop ? sm_dekstop_3 : sm_mobile_3) },
                    { label: 'No Pol', name: 'FNoPol', width: (isDesktop ? sm_dekstop_2 : sm_mobile_2) },
                    { label: 'Jns Order', name: 'FOrderan', width: (isDesktop ? sm_dekstop_2 : sm_mobile_2) },
                    { label: 'Container', name: 'FNContainer', width: (isDesktop ? sm_dekstop_2 : sm_mobile_2) },
                    { label: 'Lokasi Bongkar', name: 'FLokasiBongkarMuat', width: (isDesktop ? sm_dekstop_3 : sm_mobile_3) },
                    { label: 'Hrg Trucking', name: 'FNominalHargaTrucking', width: (isDesktop ? sm_dekstop_2 : sm_mobile_2), formatter: 'integer', align: 'right' },
                    { label: 'Hrg Pusat', name: 'FNominalHargaTruckingPusat', width: (isDesktop ? sm_dekstop_2 : sm_mobile_2), formatter: 'integer', align: 'right' },
                    { label: 'Selisih', name: 'FSelisih', width: (isDesktop ? sm_dekstop_2 : sm_mobile_2), formatter: 'integer', align: 'right' },
                    { label: 'Keterangan', name: 'FKeterangan', width: (isDesktop ? sm_dekstop_4 : sm_mobile_4) }
                ],
                rowNum: 50,
                rownumbers: true,
                sortname: 'FNTrans',
                sortorder: 'asc',
                autowidth: true,
                shrinkToFit: false,
                height: 400,
                footerrow: true,
                userDataOnFooter: true,
                toolbar: [true, 'top'],
                loadComplete: function(data) {
                    var $gridObj = $(this);
                    if (data && data.userdata) {
                        var footerData = {
                            FTgl: "Total:",
                            FNominalHargaTrucking: data.userdata.TotalHargaTrucking || 0,
                            FNominalHargaTruckingPusat: data.userdata.TotalHargaTruckingPusat || 0,
                            FSelisih: data.userdata.TotalSelisih || 0
                        };
                        $gridObj.jqGrid("footerData", "set", footerData);

                        var $footerRow = $gridObj.closest(".ui-jqgrid-view").find(".ui-jqgrid-sdiv tr.footrow");
                        
                        // Add Second Footer (Jumlah Bongkaran)
                        var $secondFooter = $gridObj.closest(".ui-jqgrid-view").find(".ui-jqgrid-sdiv tr.myfootrow1");
                        if ($secondFooter.length === 0) {
                            $secondFooter = $footerRow.clone().removeClass("footrow").addClass("myfootrow1 ui-widget-content");
                            $secondFooter.insertAfter($footerRow);
                        }
                        
                        // Add Third Footer (Jumlah Muatan)
                        var $thirdFooter = $gridObj.closest(".ui-jqgrid-view").find(".ui-jqgrid-sdiv tr.myfootrow2");
                        if ($thirdFooter.length === 0) {
                            $thirdFooter = $footerRow.clone().removeClass("footrow").addClass("myfootrow2 ui-widget-content");
                            $thirdFooter.insertAfter($secondFooter);
                        }
                        
                        // Add Fourth Footer (Jumlah Import)
                        var $fourthFooter = $gridObj.closest(".ui-jqgrid-view").find(".ui-jqgrid-sdiv tr.myfootrow3");
                        if ($fourthFooter.length === 0) {
                            $fourthFooter = $footerRow.clone().removeClass("footrow").addClass("myfootrow3 ui-widget-content");
                            $fourthFooter.insertAfter($thirdFooter);
                        }
                        
                        // Add Fifth Footer (Jumlah Eksport)
                        var $fifthFooter = $gridObj.closest(".ui-jqgrid-view").find(".ui-jqgrid-sdiv tr.myfootrow4");
                        if ($fifthFooter.length === 0) {
                            $fifthFooter = $footerRow.clone().removeClass("footrow").addClass("myfootrow4 ui-widget-content");
                            $fifthFooter.insertAfter($fourthFooter);
                        }
                        
                        // Reset text on clone
                        $secondFooter.find("td").text("");
                        $thirdFooter.find("td").text("");
                        $fourthFooter.find("td").text("");
                        $fifthFooter.find("td").text("");
                        
                        $secondFooter.find("td[aria-describedby$='_FNTrans']").text("Jumlah Bongkaran :").css('text-align', 'right').css('font-weight', 'bold');
                        $secondFooter.find("td[aria-describedby$='_FOrderan']").text(data.userdata.TotalBongkaran || 0).css('text-align', 'center').css('font-weight', 'bold');
                        
                        $thirdFooter.find("td[aria-describedby$='_FNTrans']").text("Jumlah Muatan :").css('text-align', 'right').css('font-weight', 'bold');
                        $thirdFooter.find("td[aria-describedby$='_FOrderan']").text(data.userdata.TotalMuatan || 0).css('text-align', 'center').css('font-weight', 'bold');

                        $fourthFooter.find("td[aria-describedby$='_FNTrans']").text("Jumlah Import :").css('text-align', 'right').css('font-weight', 'bold');
                        $fourthFooter.find("td[aria-describedby$='_FOrderan']").text(data.userdata.TotalImport || 0).css('text-align', 'center').css('font-weight', 'bold');

                        $fifthFooter.find("td[aria-describedby$='_FNTrans']").text("Jumlah Eksport :").css('text-align', 'right').css('font-weight', 'bold');
                        $fifthFooter.find("td[aria-describedby$='_FOrderan']").text(data.userdata.TotalEksport || 0).css('text-align', 'center').css('font-weight', 'bold');
                    }
                    
                    if(typeof setupLazyLoadScrollHandler === 'function') {
                        setupLazyLoadScrollHandler("#jqGridDetail", apiDetailUrl, $("#jqGridDetail").jqGrid('getGridParam', 'postData'));
                    }
                }
            });
            
            $("#jqGridDetail").jqGrid('filterToolbar', { 
                stringResult: true, 
                searchOnEnter: false, 
                defaultSearch: 'cn',
                beforeSearch: function() {
                    var postData = $("#jqGridDetail").jqGrid('getGridParam', 'postData');
                    if (postData.filters) {
                        var filtersObj = JSON.parse(postData.filters);
                        postData._search = (filtersObj.rules && filtersObj.rules.length > 0);
                    }
                    $("#jqGridDetail").jqGrid('setGridParam', { postData: postData });
                    
                    var targetGridId = this.id || 'jqGrid'; if (typeof lazyStates !== 'undefined' && lazyStates[targetGridId]) lazyStates[targetGridId].cachedData = {};
                    $("#jqGridDetail").jqGrid('clearGridData');
                    if(typeof loadGridData === 'function') {
                        loadGridData("#jqGridDetail", apiDetailUrl, $("#jqGridDetail").jqGrid('getGridParam', 'postData'), 1, $("#jqGridDetail").jqGrid('getGridParam', 'rowNum'), 'jump', 'page');
                    }
                    return false;
                }
            });
            
            if(typeof loadGridData === 'function') {
                loadGridData("#jqGridDetail", apiDetailUrl, $("#jqGridDetail").jqGrid('getGridParam', 'postData'), 1, 50, 'down', 'reload');
            }
        }

        // Trigger filter
        $('#btnFilter').click(function() {
            var targetGridId = this.id || 'jqGrid'; if (typeof lazyStates !== 'undefined' && lazyStates[targetGridId]) lazyStates[targetGridId].cachedData = {};
            $("#jqGrid").jqGrid('setGridParam', {
                postData: {
                    cabang: $('#cabangSelect').val(),
                    tgl_dari: $('#tgl_dari').val(),
                    tgl_sampai: $('#tgl_sampai').val()
                }
            });
            var selectedCabangText = $('#cabangSelect option:selected').text();
            $('#gridTitle').text('DATA PENGGUNAAN TRADO LUAR - CABANG ' + selectedCabangText.toUpperCase());
            
            loadGridData("#jqGrid", apiUrl, $("#jqGrid").jqGrid('getGridParam', 'postData'), 1, $("#jqGrid").jqGrid('getGridParam', 'rowNum'), 'down', 'reload');
            
        });

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
        
        // Cabang change auto-refresh
        $('#cabangSelect').on('change', function() {
            $('#btnFilter').click();
        });
    });
</script>
