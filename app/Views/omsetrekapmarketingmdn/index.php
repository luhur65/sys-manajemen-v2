<style>
    .filter-input-group { margin-bottom: 10px; }
    .filter-label { font-weight: bold; font-size: 14px; margin-bottom: 5px; display: block;}
    .filter-btn-container { padding-top: 25px; }
    
</style>

<div class="container-fluid">
    <div class="card card-primary card-outline">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group filter-input-group">
                                <label class="filter-label">Jenis Filter</label>
                                <select id="jenisSelect" class="form-control select2">
                                    <option value="bln" selected>Per Bulan</option>
                                    <option value="thn">Per Tahun</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-3" id="wrapperBulan">
                            <div class="form-group filter-input-group">
                                <label class="filter-label">Bulan</label>
                                <input type="text" id="blnInput" class="form-control monthpicker" placeholder="Pilih Bulan (e.g. 05-2026)" autocomplete="off">
                            </div>
                        </div>
                        
                        <div class="col-md-3" id="wrapperTahun" style="display:none;">
                            <div class="form-group filter-input-group">
                                <label class="filter-label">Tahun</label>
                                <input type="text" id="thnInput" class="form-control yearpicker" placeholder="Pilih Tahun (e.g. 2026)" autocomplete="off">
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
                                <button type="button" id="btnFilter" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i> Tampilkan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grid Card -->
            <div class="card card-default">
                <div class="card-header">
                    <h3 class="card-title">DATA REKAP OMSET MARKETING - CABANG MEDAN</h3>
                </div>
                <div class="card-body p-0">
                    <table id="jqGrid"></table>
                    <!-- <div id="jqGridPager"></div> -->
            <div class="d-flex justify-content-between align-items-center p-2 mt-0">
                <div id="lastUpdateHandler">Last Update : <?= $last_update ?></div>
                <div id="jqGridInfoHandler"></div>
            </div>
                </div>
            </div>
            
</div>

<!-- Scripts -->
<script src="<?= base_url('libraries/tas-lib/js/MonthPicker.min.js') ?>"></script>
<script src="<?= base_url('libraries/tas-lib/js/YearPicker.js') ?>"></script>
<script src="<?= base_url('libraries/tas-lib/js/lazyLoadingGridMonolith.js') ?>"></script>

<script>
    $(document).ready(function() {
        let indexRow = 0;
        let triggerClick = true;
        let limit;
        let postData;
        let activeGrid;
        let id = '';
        let selectedId = '';
        let page = 1;
        let sortname = 'FBulan';
        let sortorder = 'desc';
        let rowNum = 50;
        const apiUrl = `<?= base_url('omsetrekapmarketingmdn/grid') ?>`;
        const comboUrl = `<?= base_url('omsetrekapmarketingmdn/combomarketing') ?>`;
        const $grid = $("#jqGrid");
        const formatMoney = (val) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val);

        if($('.select2').length > 0) {
            $('.select2').select2({ theme: 'bootstrap4' });
        }
        
        let curdate = new Date();
        let curMonth = ("0" + (curdate.getMonth() + 1)).slice(-2) + '-' + curdate.getFullYear();
        let curYear = curdate.getFullYear().toString();
        
        $('#blnInput').val(curMonth);
        $('#thnInput').val(curYear);

        // Initialize pickers from tas-lib mains.js
        if (typeof initMonthpicker === 'function') initMonthpicker('monthpicker');
        if (typeof initYearpicker === 'function') initYearpicker('yearpicker');

        // Hook up MonthPicker callback since it doesn't trigger 'change' automatically
        if ($.fn.MonthPicker) {
            $('#blnInput').MonthPicker('option', 'OnAfterChooseMonth', function() {
                loadMarketingCombo();
            });
        }

        $('#jenisSelect').on('change', function() {
            if($(this).val() == 'bln') {
                $('#wrapperBulan').show();
                $('#wrapperTahun').hide();
            } else {
                $('#wrapperBulan').hide();
                $('#wrapperTahun').show();
            }
            loadMarketingCombo();
        });
        
        function loadMarketingCombo() {
            let jenis = $('#jenisSelect').val();
            let nilai = jenis == 'bln' ? $('#blnInput').val() : $('#thnInput').val();
            
            $.ajax({
                url: comboUrl,
                type: 'GET',
                data: { jenis: jenis, nilai: nilai },
                dataType: 'json',
                success: function(res) {
                    let options = '<option value="ALL">ALL</option>';
                    if(res.data) {
                        res.data.forEach(item => {
                            options += '<option value="' + item.FNMarketing + '">' + item.FNMarketing + '</option>';
                        });
                    }
                    
                    $('#marketingSelect').html(options);
                    
                    if($('.select2').length > 0) {
                        $('#marketingSelect').select2({ theme: 'bootstrap4' });
                    }
                }
            });
        }
        
        $('#blnInput, #thnInput').on('change blur input', function() {
            loadMarketingCombo();
        });
        
        // Initial combo load
        loadMarketingCombo();

        const isDesktop = (detectDeviceType() == "desktop");
        const sm_dekstop_3 = 100, sm_dekstop_4 = 150, sm_dekstop_5 = 200, sm_dekstop_6 = 250;
        const sm_mobile_3 = 80, sm_mobile_4 = 100, sm_mobile_5 = 120, sm_mobile_6 = 150;

        $grid.jqGrid({
            url: apiUrl,
            mtype: "POST", 
            datatype: "local",
            postData: {
                jenis: function() { return $('#jenisSelect').val(); },
                bln: function() { return $('#blnInput').val(); },
                thn: function() { return $('#thnInput').val(); },
                marketing: function() { return $('#marketingSelect').val(); }
            },
            styleUI: 'Bootstrap4',
            iconSet: 'fontAwesome',
            colModel: [
                {
                    label: 'Bulan',
                    name: 'FBulan',
                    index: 'FBulan',
                    width: (isDesktop ? sm_dekstop_4 : sm_mobile_4)
                },
                {
                    label: 'Marketing',
                    name: 'FNMarketing',
                    index: 'FNMarketing',
                    width: (isDesktop ? sm_dekstop_4 : sm_mobile_4)
                },
                {
                    label: 'Jlh Muatan',
                    name: 'FJumlahMuatan',
                    index: 'FJumlahMuatan',
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_3),
                    align: 'right',
                    formatter: 'integer',
                    formatoptions: { thousandsSeparator: "," }
                },
                {
                    label: 'Jlh Bongkaran',
                    name: 'FJumlahBongkaran',
                    index: 'FJumlahBongkaran',
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_3),
                    align: 'right',
                    formatter: 'integer',
                    formatoptions: { thousandsSeparator: "," }
                },
                {
                    label: 'Jlh Exim',
                    name: 'FJumlahExim',
                    index: 'FJumlahExim',
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_3),
                    align: 'right',
                    formatter: 'integer',
                    formatoptions: { thousandsSeparator: "," }
                },
                {
                    label: 'Omset',
                    name: 'FOmset',
                    index: 'FOmset',
                    width: (isDesktop ? sm_dekstop_4 : sm_mobile_4),
                    align: 'right',
                    formatter: 'integer',
                    formatoptions: { thousandsSeparator: "," }
                },
                {
                    label: 'Biaya Lapangan',
                    name: 'FBiayaLapangan',
                    index: 'FBiayaLapangan',
                    width: (isDesktop ? sm_dekstop_4 : sm_mobile_4),
                    align: 'right',
                    formatter: 'integer',
                    formatoptions: { thousandsSeparator: "," }
                },
                {
                    label: 'Nom Pph23',
                    name: 'FNomPph23',
                    index: 'FNomPph23',
                    width: (isDesktop ? sm_dekstop_4 : sm_mobile_4),
                    align: 'right',
                    formatter: 'integer',
                    formatoptions: { thousandsSeparator: "," }
                },
                {
                    label: 'Profit',
                    name: 'FProfit',
                    index: 'FProfit',
                    width: (isDesktop ? sm_dekstop_4 : sm_mobile_4),
                    align: 'right',
                    formatter: 'integer',
                    formatoptions: { thousandsSeparator: "," }
                },
                {
                    label: 'Margin %',
                    name: 'FMargin',
                    index: 'FMargin',
                    width: (isDesktop ? sm_dekstop_3 : sm_mobile_3),
                    align: 'right',
                    formatter: function(cellvalue, options, rowObject) {
                        return formatMoney(cellvalue) + ' %';
                    }
                }
            ],
            autowidth: true,
            shrinkToFit: false,
            height: 400,
            rowNum: rowNum,
            toolbar: [true, "top"],
            rowList: [50, 100, 500, 1000],
            pager: "#jqGridPager",
            sortname: sortname,
            sortorder: sortorder,
            viewrecords: false,
            rownumbers: true,
            rownumWidth: 45,
            gridview: true,
            ignoreCase: true,
            altRows: true,
            altclass: 'myAltRowClass',
            footerrow: true,
            sortable: true,
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
                var targetGridId = this.id ? '#' + this.id : '#jqGrid'; if (typeof gridState !== 'undefined' && gridState[targetGridId]) gridState[targetGridId].cachedData = {};
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

                setTimeout(function() {
                    if (indexRow > $('#jqGrid').getDataIDs().length - 1) {
                        indexRow = $('#jqGrid').getDataIDs().length - 1;
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
                            $(`#jqGrid [id="` + $('#jqGrid').getDataIDs()[0] + `"]`).click();
                        }

                        triggerClick = false
                    } else {
                        $('#jqGrid').setSelection($('#jqGrid').getDataIDs()[indexRow]);
                    }
                }, 100);

                if (userData) {
                    var $footer = $gridObj.closest(".ui-jqgrid-bdiv").next(".ui-jqgrid-sdiv").find(".footrow");
                    var $secondFooter = $footer.next(".myfootrow");
                    if ($secondFooter.length === 0) {
                        $secondFooter = $footer.clone().removeClass("footrow").addClass("myfootrow").insertAfter($footer);
                    }
                    
                    $secondFooter.find("td").empty();
                    
                    $secondFooter.find("td[aria-describedby$='_FBulan']").text("GRAND TOTAL :").css('text-align', 'right').css('font-weight', 'bold');
                    $secondFooter.find("td[aria-describedby$='_FJumlahMuatan']").text(userData.GrandTotalMuatan || 0).css('text-align', 'right').css('font-weight', 'bold');
                    $secondFooter.find("td[aria-describedby$='_FJumlahBongkaran']").text(userData.GrandTotalBongkaran || 0).css('text-align', 'right').css('font-weight', 'bold');
                    $secondFooter.find("td[aria-describedby$='_FJumlahExim']").text(userData.GrandTotalExim || 0).css('text-align', 'right').css('font-weight', 'bold');
                    $secondFooter.find("td[aria-describedby$='_FOmset']").text(userData.GrandTotalOmset || 0).css('text-align', 'right').css('font-weight', 'bold');
                    $secondFooter.find("td[aria-describedby$='_FBiayaLapangan']").text(userData.GrandTotalBiayaLapangan || 0).css('text-align', 'right').css('font-weight', 'bold');
                    $secondFooter.find("td[aria-describedby$='_FNomPph23']").text(userData.GrandTotalPph23 || 0).css('text-align', 'right').css('font-weight', 'bold');
                    $secondFooter.find("td[aria-describedby$='_FProfit']").text(userData.GrandTotalProfit || 0).css('text-align', 'right').css('font-weight', 'bold');
                    
                    let grandMargin = 0;
                    if(parseFloat(userData.GrandTotalOmset) > 0) {
                        grandMargin = (parseFloat(userData.GrandTotalProfit) / parseFloat(userData.GrandTotalOmset)) * 100;
                    }
                    $secondFooter.find("td[aria-describedby$='_FMargin']").text(formatMoney(grandMargin) + ' %').css('text-align', 'right').css('font-weight', 'bold');

                    $secondFooter.find("td").each(function() {
                        var val = $(this).text();
                        if (val && !isNaN(val.replace(/,/g, '')) && !val.includes('%') && val !== "0") {
                            $(this).text(new Intl.NumberFormat('en-US').format(val.replace(/,/g, '')));
                        }
                    });
                }
                
                if(typeof setupLazyLoadScrollHandler === 'function') {
                    setupLazyLoadScrollHandler("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'));
                }
                if(typeof setHighlight === 'function') {
                    setHighlight($grid);
                }

                $grid.removeClass('table-striped');
            }
        });

        // Filter toolbar
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
                
                var targetGridId = this.id ? '#' + this.id : '#jqGrid'; if (typeof gridState !== 'undefined' && gridState[targetGridId]) gridState[targetGridId].cachedData = {};
                $grid.jqGrid('clearGridData');
                if(typeof loadGridData === 'function') {
                    loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $grid.jqGrid('getGridParam', 'rowNum'), 'jump', 'page');
                }
                return false;
            }
        });

        // Filter Action
        $('#btnFilter').click(function() {
            var targetGridId = this.id ? '#' + this.id : '#jqGrid'; if (typeof gridState !== 'undefined' && gridState[targetGridId]) gridState[targetGridId].cachedData = {};
            $grid.jqGrid('setGridParam', {
                postData: {
                    jenis: $('#jenisSelect').val(),
                    bln: $('#blnInput').val(),
                    thn: $('#thnInput').val(),
                    marketing: $('#marketingSelect').val()
                }
            });
            
            if(typeof loadGridData === 'function') {
                loadGridData("#jqGrid", apiUrl, $grid.jqGrid('getGridParam', 'postData'), 1, $grid.jqGrid('getGridParam', 'rowNum'), 'down', 'reload');
            } else {
                $grid.trigger("reloadGrid", [{page: 1}]);
            }
        });
        
        // Auto trigger initial load after a tiny delay
        setTimeout(function(){
            $('#btnFilter').click();
        }, 500);

    });

    // Dummy detectDeviceType if not present
    if (typeof detectDeviceType !== "function") {
        function detectDeviceType() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ? 'mobile' : 'desktop';
        }
    }
</script>
