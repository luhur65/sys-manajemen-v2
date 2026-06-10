<div class="container-fluid">
    <!-- Filter Card -->
    <div class="card card card-primary card-outline mb-3">
        <div class="card-body p-2">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <div class="form-group filter-input-group mb-0">
                        <label class="filter-label">Bulan</label>
                        <input type="text" id="bulan" class="form-control monthpicker" placeholder="Pilih Bulan (e.g. 05-2026)" autocomplete="off">
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group filter-input-group mb-0">
                        <label class="filter-label">Jenis Trado</label>
                        <select id="jenis_trado" class="form-control select2">
                            <?= $comboJenisTrado ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group filter-input-group mb-0">
                        <button type="button" id="btnFilter" class="btn btn-primary btn-md mr-2">
                            <i class="fas fa-search"></i> Tampilkan
                        </button>
                        <button type="button" id="btnReset" class="btn btn-danger btn-md">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grid Card -->
    <div class="card card-default">
        <div class="card-header">
            <h3 class="card-title">PENGGUNAAN TRADO LUAR TAS PER BULAN (MDN)</h3>
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
        let indexRow = 0;
        let triggerClick = true;
        let limit;
        let postData;
        var activeGrid;
        let sortname = 'FBulan';
        let sortorder = 'desc';
        let rowNum = 50;
        const apiUrl = `<?= base_url('truckingtradoluartas/grid') ?>`;
        const $grid = $("#jqGrid");
        
        var formatMoney = function(val) {
            if (!val) return '0';
            return new Intl.NumberFormat('en-US').format(val);
        };

        if ($('.select2').length > 0) {
            $('.select2').select2({ theme: 'bootstrap4' });
        }
        
        let curdate = new Date();
        let curMonth = ("0" + (curdate.getMonth() + 1)).slice(-2) + '-' + curdate.getFullYear();
        $('#bulan').val(curMonth);

        if (typeof initMonthpicker === 'function') initMonthpicker('monthpicker');

        const isDesktop = (detectDeviceType() == "desktop");
        const sm_dekstop_2 = 80, sm_dekstop_3 = 100, sm_dekstop_4 = 150;
        const sm_mobile_2 = 60, sm_mobile_3 = 80, sm_mobile_4 = 100;

        $grid.jqGrid({
            url: apiUrl,
            mtype: "POST",
            datatype: "local",
            postData: {
                bulan: function() { return $('#bulan').val(); },
                jenis_trado: function() { return $('#jenis_trado').val(); }
            },
            styleUI: 'Bootstrap4',
            iconSet: 'fontAwesome',
            colModel: [
                { label: 'Bulan', name: 'FBulan', index: 'FBulan', width: 80, searchoptions: { sopt: ['cn'] } },
                { label: 'No Polisi', name: 'FNoPol', index: 'FNoPol', width: 100, searchoptions: { sopt: ['cn'] } },
                { label: 'Jenis Trado', name: 'FJenisTrado', index: 'FJenisTrado', width: 120, searchoptions: { sopt: ['cn'] } },
                { label: 'Nom. Muatan', name: 'FNominalMuatan', index: 'FNominalMuatan', width: 120, formatter: 'integer', align: 'right', searchoptions: { sopt: ['eq','ne','lt','le','gt','ge'] } },
                { label: 'Jlh. Muatan', name: 'FJumlahMuatan', index: 'FJumlahMuatan', width: 100, formatter: 'integer', align: 'right', searchoptions: { sopt: ['eq','ne','lt','le','gt','ge'] } },
                { label: 'Nom. Bongkaran', name: 'FNominalBongkaran', index: 'FNominalBongkaran', width: 130, formatter: 'integer', align: 'right', searchoptions: { sopt: ['eq','ne','lt','le','gt','ge'] } },
                { label: 'Jlh. Bongkaran', name: 'FJumlahBongkaran', index: 'FJumlahBongkaran', width: 120, formatter: 'integer', align: 'right', searchoptions: { sopt: ['eq','ne','lt','le','gt','ge'] } },
                { label: 'Nom. Import', name: 'FNominalImport', index: 'FNominalImport', width: 120, formatter: 'integer', align: 'right', searchoptions: { sopt: ['eq','ne','lt','le','gt','ge'] } },
                { label: 'Jlh. Import', name: 'FJumlahImport', index: 'FJumlahImport', width: 100, formatter: 'integer', align: 'right', searchoptions: { sopt: ['eq','ne','lt','le','gt','ge'] } },
                { label: 'Nom. Eksport', name: 'FNominalEksport', index: 'FNominalEksport', width: 120, formatter: 'integer', align: 'right', searchoptions: { sopt: ['eq','ne','lt','le','gt','ge'] } },
                { label: 'Jlh. Eksport', name: 'FJumlahEksport', index: 'FJumlahEksport', width: 100, formatter: 'integer', align: 'right', searchoptions: { sopt: ['eq','ne','lt','le','gt','ge'] } },
                { label: 'Total', name: 'Total', index: 'Total', width: 120, formatter: 'integer', align: 'right', searchoptions: { sopt: ['eq','ne','lt','le','gt','ge'] } }
            ],
            sortname: sortname,
            sortorder: sortorder,
            rowNum: rowNum,
            rowList: [50, 100, 500, 2000],
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
                if (typeof cachedData !== 'undefined') cachedData = {};
                if (typeof loadGridData === 'function') {
                    loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $(this).jqGrid('getGridParam', 'rowNum'), 'jump', 'page');
                }
                return 'stop';
            },
            loadComplete: function(res) {
                $(document).off('keydown.grid');
                if (typeof setCustomBindKeys === 'function') setCustomBindKeys($grid);

                sortname = $(this).jqGrid("getGridParam", "sortname");
                sortorder = $(this).jqGrid("getGridParam", "sortorder");
                limit = $(this).jqGrid('getGridParam', 'postData').limit;
                postData = $(this).jqGrid('getGridParam', 'postData');
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
                    $(this).jqGrid("footerData", "set", {
                        FJenisTrado: "Total",
                        FNominalMuatan: userData.TotalFNominalMuatan || 0,
                        FJumlahMuatan: userData.TotalFJumlahMuatan || 0,
                        FNominalBongkaran: userData.TotalFNominalBongkaran || 0,
                        FJumlahBongkaran: userData.TotalFJumlahBongkaran || 0,
                        FNominalImport: userData.TotalFNominalImport || 0,
                        FJumlahImport: userData.TotalFJumlahImport || 0,
                        FNominalEksport: userData.TotalFNominalEksport || 0,
                        FJumlahEksport: userData.TotalFJumlahEksport || 0,
                        Total: userData.GrandTotal || 0
                    });
                }
                
                if (userData && userData.last_update) {
                    $('#lastUpdateHandler').text('Last Update : ' + userData.last_update);
                }
                
                if (typeof setupLazyLoadScrollHandler === 'function') {
                    setupLazyLoadScrollHandler("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'));
                }
                if (typeof setHighlight === 'function') {
                    setHighlight($grid);
                }
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
                
                if (typeof cachedData !== 'undefined') cachedData = {};
                $grid.jqGrid('clearGridData');
                $grid.jqGrid("footerData", "set", {
                    FJenisTrado: "Total", FNominalMuatan: 0, FJumlahMuatan: 0, FNominalBongkaran: 0, 
                    FJumlahBongkaran: 0, FNominalImport: 0, FJumlahImport: 0, FNominalEksport: 0, FJumlahEksport: 0, Total: 0
                });
                $('#jqGridInfoHandler').empty();
                
                if (typeof loadGridData === 'function') {
                    loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $grid.jqGrid('getGridParam', 'rowNum'), 'jump', 'page');
                }
                return false;
            }
        });

        $('#btnFilter').click(function() {
            triggerClick = true;
            
            if (typeof cachedData !== 'undefined') cachedData = {};
            $grid.jqGrid("setGridParam", {
                postData: {
                    bulan: $('#bulan').val(),
                    jenis_trado: $('#jenis_trado').val()
                }
            });
            $grid.jqGrid('clearGridData');
            $grid.jqGrid("footerData", "set", {
                FJenisTrado: "Total", FNominalMuatan: 0, FJumlahMuatan: 0, FNominalBongkaran: 0, 
                FJumlahBongkaran: 0, FNominalImport: 0, FJumlahImport: 0, FNominalEksport: 0, FJumlahEksport: 0, Total: 0
            });
            $('#jqGridInfoHandler').empty();
            
            if (typeof loadGridData === 'function') {
                loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $grid.jqGrid('getGridParam', 'rowNum'), 'jump', 'page');
            } else {
                $grid.jqGrid('setGridParam', { datatype: 'json' }).trigger('reloadGrid');
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

        $('#btnReset').click(function() {
            let curdate = new Date();
            let curMonth = ("0" + (curdate.getMonth() + 1)).slice(-2) + '-' + curdate.getFullYear();
            $('#bulan').val(curMonth);
            
            $('#jenis_trado').val('All').trigger('change');
            
            var postData = $grid.jqGrid('getGridParam', 'postData');
            delete postData.filters;
            postData._search = false;
            
            $grid.jqGrid('setGridParam', { postData: postData });
            $grid[0].clearToolbar();
            
            $('#btnFilter').click();
        });

        // Trigger initial load
        if (typeof loadGridData === 'function') {
            loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, rowNum, 'down', 'reload');
        } else {
            $grid.jqGrid('setGridParam', { datatype: 'json' }).trigger('reloadGrid');
        }
    });
</script>
<script src="<?= base_url('libraries/tas-lib/js/MonthPicker.min.js') ?>"></script>
<script src="<?= base_url('libraries/tas-lib/js/YearPicker.js') ?>"></script>
