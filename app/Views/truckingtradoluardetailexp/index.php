<style>
    #ui-datepicker-div { display: none; z-index: 9999 !important; }
    .ui-jqgrid tr.myfootrow td {
		font-weight: normal !important;
		overflow: hidden;
		white-space:nowrap;
		/* height: 21px; */
		padding: 10px !important;
    }
</style>
<div class="container-fluid">
    <!-- Filter Card -->
    <div class="card card-primary card-outline">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <div class="form-group filter-input-group mb-0">
                        <label class="filter-label">Cabang</label>
                        <select id="cabangSelect" class="form-control select2">
                            <option value="MDN">MEDAN</option>
                            <option value="JKT">JAKARTA</option>
                            <option value="SBY">SURABAYA</option>
                            <option value="MKS">MAKASSAR</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group filter-input-group mb-0">
                        <label class="filter-label">Tanggal Dari</label>
                        <input type="text" class="form-control datepicker" id="tgl_dari" autocomplete="off">
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group filter-input-group mb-0">
                        <label class="filter-label">Tanggal Sampai</label>
                        <input type="text" class="form-control datepicker" id="tgl_sampai" autocomplete="off">
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
            <h3 class="card-title">LAPORAN PEMAKAIAN TRADO LUAR DETAIL</h3>
        </div>
        <div class="card-body p-0">
            <table id="jqGrid"></table>
            <div class="d-flex justify-content-between align-items-center p-2 mt-0">
                <div id="lastUpdateHandler"></div>
                <div id="jqGridInfoHandler"></div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        let limit;
        let sortname = '';
        let sortorder = '';
        let rowNum = 50;

        let indexRow = 0;
        let triggerClick = true;
        let id = '';

        const apiUrl = `<?= base_url('truckingtradoluardetailexp/grid') ?>`;
        const $grid = $("#jqGrid");

        if($('.select2').length > 0) {
            $('.select2').select2({ theme: 'bootstrap4' });
        }

        let curdate = new Date();
        let first_day = new Date(curdate.getFullYear(), curdate.getMonth(), 1);
        let last_day = new Date(curdate.getFullYear(), curdate.getMonth() + 1, 0);

        initDatepicker();
        $("#tgl_dari").datepicker('setDate', first_day);
        
        
        $("#tgl_sampai").datepicker('setDate', last_day);

        var nullstringFormatter = function(cellvalue, options, rowObject) {
            if (cellvalue === null || typeof(cellvalue) === 'object' || cellvalue === '') {
                return '-';
            }
            return cellvalue;
        };

        var dateTimeFormatter = function(cellvalue, options, rowObject) {
            if (cellvalue) {
                if(cellvalue.length >= 10) {
                    var parts = cellvalue.substr(0, 10).split('-');
                    if(parts.length === 3) return parts[2] + '-' + parts[1] + '-' + parts[0];
                }
            }
            return '-';
        };

        $grid.jqGrid({
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
            colNames: ['Tgl', 'No Job', 'No Cont / Seal', 'Nama Shipper', 'Nama EMKL', 'No Pol', 'Jns Order', 'Container', 'Lokasi Bongkar', 'Hrg trucking', 'Hrg Pusat', 'Selisih', 'Keterangan'],
            colModel: [
                { name: 'FTgl', index: 'FTgl', width: 100, formatter: dateTimeFormatter, searchoptions: { sopt: ['cn'] } },
                { name: 'FNTrans', index: 'FNTrans', width: 150, searchoptions: { sopt: ['cn'] } },
                { name: 'FNoContSeal', index: 'FNoContSeal', width: 120, searchoptions: { sopt: ['cn'] } },
                { name: 'FNShipper', index: 'FNShipper', width: 150, searchoptions: { sopt: ['cn'] } },
                { name: 'FNEmklLainTradoLuar', index: 'FNEmklLainTradoLuar', width: 150, searchoptions: { sopt: ['cn'] } },
                { name: 'FNoPol', index: 'FNoPol', width: 100, formatter: nullstringFormatter, searchoptions: { sopt: ['cn'] } },
                { name: 'FOrderan', index: 'FOrderan', width: 100, searchoptions: { sopt: ['cn'] } },
                { name: 'FNContainer', index: 'FNContainer', width: 100, searchoptions: { sopt: ['cn'] } },
                { name: 'FLokasiBongkarMuat', index: 'FLokasiBongkarMuat', width: 150, formatter: nullstringFormatter, searchoptions: { sopt: ['cn'] } },
                { name: 'FNominalHargaTrucking', index: 'FNominalHargaTrucking', width: 120, formatter: 'integer', align: 'right', searchoptions: { sopt: ['eq','ne','lt','le','gt','ge'] } },
                { name: 'FNominalHargaTruckingPusat', index: 'FNominalHargaTruckingPusat', width: 120, formatter: 'integer', align: 'right', searchoptions: { sopt: ['eq','ne','lt','le','gt','ge'] } },
                { name: 'FSelisih', index: 'FSelisih', width: 120, formatter: 'integer', align: 'right', searchoptions: { sopt: ['eq','ne','lt','le','gt','ge'] } },
                { name: 'Fketerangan', index: 'Fketerangan', width: 150, formatter: nullstringFormatter, searchoptions: { sopt: ['cn'] } }
            ],
            sortname: "FTgl",
            sortorder: "DESC",
            rowNum: 50,
            rowList: [50, 100, 200],
            viewrecords: true,
            toolbar: [true, "top"],
            autowidth: true,
            shrinkToFit: false,
            height: 400,
            rownumbers: true,
            rownumWidth: 35,
            gridview: true,
            footerrow: true,
            onSortCol: function(index, iCol, sortorder) {
                var targetGridId = this.id || 'jqGrid'; if (typeof lazyStates !== 'undefined' && lazyStates[targetGridId]) lazyStates[targetGridId].cachedData = {};
                if (typeof loadGridData === 'function') {
                    loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $(this).jqGrid('getGridParam', 'rowNum'), 'jump', 'page');
                }
                return 'stop';
            },
            loadComplete: function(res) {
                // Initialize custom bind keys
                $(document).off('keydown.grid');
                setCustomBindKeys($grid);

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

                if (typeof initJqGridInfo === 'function') {
                    initJqGridInfo($(this));
                }
                
                var userData = res.userdata || $(this).jqGrid('getGridParam', 'userData');
                
                if (userData) {
                    var $footerRow = $(this.grid.sDiv).find("tr.footrow");
                    
                    // Main Footer (Total Nominal)
                    $(this).jqGrid("footerData", "set", {
                        FNTrans: "Total",
                        FNominalHargaTrucking: userData.TotalHargaTrucking || 0,
                        FNominalHargaTruckingPusat: userData.TotalHargaTruckingPusat || 0,
                        FSelisih: userData.TotalSelisih || 0
                    });

                    // Row 1: Bongkaran
                    var $newFooterRow = $(this.grid.sDiv).find("tr.myfootrow");
                    if ($newFooterRow.length === 0) {
                        $newFooterRow = $footerRow.clone().removeClass("footrow").addClass("myfootrow ui-widget-content");
                        $newFooterRow.children("td").each(function () { this.style.width = ""; });
                        $newFooterRow.insertAfter($footerRow);
                    }
                    $newFooterRow.find(">td[aria-describedby=" + this.id + "_FNTrans]").text("Jumlah Bongkaran ");
                    $newFooterRow.find(">td[aria-describedby=" + this.id + "_FNominalHargaTrucking]").text("");
                    $newFooterRow.find(">td[aria-describedby=" + this.id + "_FNominalHargaTruckingPusat]").text("");
                    $newFooterRow.find(">td[aria-describedby=" + this.id + "_FSelisih]").text("");
                    $newFooterRow.find(">td[aria-describedby=" + this.id + "_FOrderan]").text(userData.JumlahBongkaran || 0).css('text-align', 'right');

                    // Row 2: Muatan
                    var $newFooterRow1 = $(this.grid.sDiv).find("tr.myfootrow1");
                    if ($newFooterRow1.length === 0) {
                        $newFooterRow1 = $footerRow.clone().removeClass("footrow").addClass("myfootrow1 ui-widget-content");
                        $newFooterRow1.children("td").each(function () { this.style.width = ""; });
                        $newFooterRow1.insertAfter($newFooterRow);
                    }
                    $newFooterRow1.find(">td[aria-describedby=" + this.id + "_FNTrans]").text("Jumlah Muatan ");
                    $newFooterRow1.find(">td[aria-describedby=" + this.id + "_FNominalHargaTrucking]").text("");
                    $newFooterRow1.find(">td[aria-describedby=" + this.id + "_FNominalHargaTruckingPusat]").text("");
                    $newFooterRow1.find(">td[aria-describedby=" + this.id + "_FSelisih]").text("");
                    $newFooterRow1.find(">td[aria-describedby=" + this.id + "_FOrderan]").text(userData.JumlahMuatan || 0).css('text-align', 'right');

                    // Row 3: Import
                    var $newFooterRow2 = $(this.grid.sDiv).find("tr.myfootrow2");
                    if ($newFooterRow2.length === 0) {
                        $newFooterRow2 = $footerRow.clone().removeClass("footrow").addClass("myfootrow2 ui-widget-content");
                        $newFooterRow2.children("td").each(function () { this.style.width = ""; });
                        $newFooterRow2.insertAfter($newFooterRow1);
                    }
                    $newFooterRow2.find(">td[aria-describedby=" + this.id + "_FNTrans]").text("Jumlah Import ");
                    $newFooterRow2.find(">td[aria-describedby=" + this.id + "_FNominalHargaTrucking]").text("");
                    $newFooterRow2.find(">td[aria-describedby=" + this.id + "_FNominalHargaTruckingPusat]").text("");
                    $newFooterRow2.find(">td[aria-describedby=" + this.id + "_FSelisih]").text("");
                    $newFooterRow2.find(">td[aria-describedby=" + this.id + "_FOrderan]").text(userData.JumlahImport || 0).css('text-align', 'right');

                    // Row 4: Eksport
                    var $newFooterRow3 = $(this.grid.sDiv).find("tr.myfootrow3");
                    if ($newFooterRow3.length === 0) {
                        $newFooterRow3 = $footerRow.clone().removeClass("footrow").addClass("myfootrow3 ui-widget-content");
                        $newFooterRow3.children("td").each(function () { this.style.width = ""; });
                        $newFooterRow3.insertAfter($newFooterRow2);
                    }
                    $newFooterRow3.find(">td[aria-describedby=" + this.id + "_FNTrans]").text("Jumlah Eksport ");
                    $newFooterRow3.find(">td[aria-describedby=" + this.id + "_FNominalHargaTrucking]").text("");
                    $newFooterRow3.find(">td[aria-describedby=" + this.id + "_FNominalHargaTruckingPusat]").text("");
                    $newFooterRow3.find(">td[aria-describedby=" + this.id + "_FSelisih]").text("");
                    $newFooterRow3.find(">td[aria-describedby=" + this.id + "_FOrderan]").text(userData.JumlahEksport || 0).css('text-align', 'right');
                }
                
                if (userData && userData.last_update) {
                    $('#lastUpdateHandler').text('Last Update : ' + userData.last_update);
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
            triggerClick = true;
            
            var targetGridId = this.id || 'jqGrid'; if (typeof lazyStates !== 'undefined' && lazyStates[targetGridId]) lazyStates[targetGridId].cachedData = {};
            $grid.jqGrid("setGridParam", {
                postData: {
                    cabang: $('#cabangSelect').val(),
                    tgl_dari: $('#tgl_dari').val(),
                    tgl_sampai: $('#tgl_sampai').val()
                }
            });

            if (typeof loadGridData === 'function') {
                loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $grid.jqGrid('getGridParam', 'rowNum'), 'down', 'reload');
            } else {
                $grid.jqGrid('setGridParam', { page: 1 }).trigger("reloadGrid");
            }
        });


        $('#btnReset').click(function() {
            $("#tgl_dari").datepicker('setDate', first_day);
            $("#tgl_sampai").datepicker('setDate', last_day);
            $('#cabangSelect').val('MDN').trigger('change.select2'); // refresh tampilan select2
            try { $('#jqGrid')[0].clearToolbar(false); } catch(e) {}

            // Reset main footer
            $grid.jqGrid("footerData", "set", {
                FNTrans: "Total",
                FNominalHargaTrucking: 0,
                FNominalHargaTruckingPusat: 0,
                FSelisih: 0
            });

            // ✅ Cara yang benar untuk akses sDiv di luar loadComplete
            var gridObj = $grid[0].grid; // ambil object grid internal
            var $sDiv = $(gridObj.sDiv);  // sDiv adalah property dari grid object

            // Reset custom footer rows
            var resetMap = {
                'myfootrow':  { label: 'Jumlah Bongkaran' },
                'myfootrow1': { label: 'Jumlah Muatan' },
                'myfootrow2': { label: 'Jumlah Import' },
                'myfootrow3': { label: 'Jumlah Eksport' }
            };

            $.each(resetMap, function(cls, cfg) {
                var $row = $sDiv.find("tr." + cls);
                if ($row.length > 0) {
                    $row.find(">td[aria-describedby=jqGrid_FNTrans]").text(cfg.label + ' ');
                    $row.find(">td[aria-describedby=jqGrid_FNominalHargaTrucking]").text('');
                    $row.find(">td[aria-describedby=jqGrid_FNominalHargaTruckingPusat]").text('');
                    $row.find(">td[aria-describedby=jqGrid_FSelisih]").text('');
                    $row.find(">td[aria-describedby=jqGrid_FOrderan]").text(0).css('text-align', 'right');
                }
            });

            $('#lastUpdateHandler, #jqGridInfoHandler').html('');
            
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

        $('#cabangSelect').on('change', function() {
            $('#btnFilter').trigger('click');
        });
        
        $(window).on("resize", function () {
            var newWidth = $grid.closest(".ui-jqgrid").parent().width();
            $grid.jqGrid("setGridWidth", newWidth, true);
        });

        setTimeout(function() {
            $('#btnFilter').trigger('click');
        }, 100);
    });
</script>
