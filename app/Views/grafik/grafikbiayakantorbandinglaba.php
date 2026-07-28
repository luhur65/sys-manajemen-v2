<style>
    #ui-datepicker-div { display: none; }
    .card-filter { margin-bottom: 15px; }

    /* Perbaikan UX untuk highlight bulan yang aktif di DatePicker/MonthPicker (terutama Dark Mode) */
    .ui-datepicker .ui-state-active,
    .month-picker-month-table a.ui-state-active,
    body.dark-mode .ui-datepicker .ui-state-active,
    body.dark-mode .ui-datepicker .ui-state-highlight,
    body.dark-mode .month-picker-month-table a.ui-state-active,
    body.dark-mode .month-picker-month-table a.ui-state-highlight {
        background-color: #007bff !important; /* Warna biru primer Bootstrap */
        color: #ffffff !important;
        border-color: #007bff !important;
        border-radius: 4px;
        font-weight: bold;
    }
</style>

<div class="container-fluid">
    <!-- Filter Card -->
    <div class="card card-primary card-outline card-filter">
        <div class="card-body">
            <form id="formFilter">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group filter-input-group">
                            <label class="filter-label">Cabang</label>
                            <select name="cabang" id="cabangSelect" class="form-control select2">
                                <option value="MDN" <?= ($selectedCabang == 'MDN') ? 'selected' : '' ?>>MEDAN</option>
                                <option value="JKT" <?= ($selectedCabang == 'JKT') ? 'selected' : '' ?>>JAKARTA</option>
                                <option value="SBY" <?= ($selectedCabang == 'SBY') ? 'selected' : '' ?>>SURABAYA</option>
                                <option value="MKS" <?= ($selectedCabang == 'MKS') ? 'selected' : '' ?>>MAKASSAR</option>
                                <option value="SMG" <?= ($selectedCabang == 'SMG') ? 'selected' : '' ?>>SEMARANG</option>
                                <option value="BTG" <?= ($selectedCabang == 'BTG') ? 'selected' : '' ?>>BITUNG</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group filter-input-group">
                            <label class="filter-label">Bulan dari</label>
                            <input type="text" class="form-control monthpicker" name="tgl_dari" id="tgl_dari" value="<?= esc($tgl_dari) ?>" autocomplete="off" placeholder="MM-YYYY">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group filter-input-group">
                            <label class="filter-label">Bulan sampai</label>
                            <input type="text" class="form-control monthpicker" name="tgl_sampai" id="tgl_sampai" value="<?= esc($tgl_sampai) ?>" autocomplete="off" placeholder="MM-YYYY">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group filter-input-group w-100">
                            <label class="filter-label d-none d-md-block">&nbsp;</label>
                            <div class="d-flex w-100">
                                <button type="button" id="btnFilter" class="btn btn-primary w-50 mr-1">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                                <button type="button" id="btnReset" class="btn btn-secondary w-50 ml-1">
                                    <i class="fas fa-undo"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Chart Card -->
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card card-primary card-outline">
                <div class="card-body">
                    <div id="grafikCabang" style="width:100%; height:400px;"></div>
                    <div class="mt-2">
                        <small class="text-muted" id="textLastUpdate">Last Update : <?= esc($LastUpdateCABANG ?? '-') ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dialog Containers untuk alert showDialog dari mains.js -->
<div id="dialog-message" title="Pesan" class="text-center" style="display: none;"></div>
<div id="dialog-warning-message" title="Peringatan" class="text-center" style="display: none;"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/highcharts/11.4.3/highcharts.js"></script>
<script type="text/javascript">
    $(function () {
        <?php if (session()->getFlashdata('error_grafik')) : ?>
        showDialog('<?= session()->getFlashdata('error_grafik') ?>');
        <?php endif; ?>
        
        // Inisialisasi Monthpicker jika fungsinya tersedia
        if($.fn.select2) {
            $('.select2').select2();
        }
        
        // Initialize Monthpicker if available
        if (typeof initMonthpicker === 'function') {
            initMonthpicker('monthpicker');
        }

        // Fungsi pembantu untuk memformat nominal uang ala Indonesia
        function formatRupiah(number) {
            if (number >= 1e12) return (number / 1e12).toFixed(1).replace(/\.0$/, '') + 'T';
            if (number >= 1e9) return (number / 1e9).toFixed(1).replace(/\.0$/, '') + 'M';
            if (number >= 1e6) return (number / 1e6).toFixed(1).replace(/\.0$/, '') + 'jt';
            if (number >= 1e3) return (number / 1e3).toFixed(1).replace(/\.0$/, '') + 'rb';
            return number;
        }

        // Helper function to safely join arrays or return empty array string if empty
        function getArrayData(phpData) {
            return (typeof phpData === 'string' && phpData === '[]') ? [] : phpData;
        }

        // INIT CHART
        const getChartTheme = () => {
            const isDark = $('body').hasClass('dark-mode');
            return {
                chart: { backgroundColor: 'transparent' },
                title: { style: { color: isDark ? '#ffffff' : '#333333' } },
                subtitle: { style: { color: isDark ? '#cccccc' : '#666666' } },
                xAxis: { labels: { style: { color: isDark ? '#cccccc' : '#666666' } } },
                yAxis: {
                    title: { style: { color: isDark ? '#cccccc' : '#666666' } },
                    labels: { style: { color: isDark ? '#cccccc' : '#666666' } },
                    gridLineColor: isDark ? '#444444' : '#e6e6e6'
                },
                legend: {
                    itemStyle: { color: isDark ? '#cccccc' : '#333333' },
                    itemHoverStyle: { color: isDark ? '#ffffff' : '#000000' }
                },
                plotOptions: {
                    series: {
                        dataLabels: {
                            enabled: true,
                            allowOverlap: true,
                            color: isDark ? '#ffffff' : '#333333',
                            textOutline: isDark ? '1px contrast' : 'none'
                        }
                    }
                }
            };
        };

        var myChart = Highcharts.chart('grafikCabang', {
            chart: { type: 'line' },
            title: { text: 'Grafik Biaya Kantor vs Laba Bersih - Cabang <?= strtoupper($cabangCABANG ?? '') ?>' },
            subtitle: { text: 'Per <?= $jlhblnCABANG ?? 0 ?> Bulan, Tahun <?= $TahunCABANG ?? "" ?>' },
            xAxis: { categories: [<?= isset($FTglCABANG) && is_array($FTglCABANG) ? implode(',', $FTglCABANG) : (isset($FTglCABANG) ? $FTglCABANG : '[]') ?>] },
            yAxis: {
                title: { text: 'Nominal (Rp)' },
                plotLines: [{ value: 0, width: 1, color: '#808080' }],
                labels: {
                    formatter: function () {
                        return formatRupiah(this.value);
                    }
                }
            },
            plotOptions: {
                series: {
                    dataLabels: {
                        enabled: true,
                        allowOverlap: true,
                        formatter: function () {
                            return formatRupiah(this.y);
                        }
                    }
                }
            },
            tooltip: {
                formatter: function () {
                    return '<b>' + this.series.name + '</b><br/>' +
                           this.x + ': Rp ' + Highcharts.numberFormat(this.y, 2, ',', '.');
                }
            },
            credits: { enabled: false },
            legend: { layout: 'vertical', align: 'right', verticalAlign: 'middle', borderWidth: 0 },
            responsive: {
                rules: [{
                    condition: { maxWidth: 500 },
                    chartOptions: { legend: { layout: 'horizontal', align: 'center', verticalAlign: 'bottom' } }
                }]
            },
            series: [{
                name: 'Biaya Kantor',
                color: '#dc3545',
                data: getArrayData([<?= isset($TotalBiayaCABANG) && is_array($TotalBiayaCABANG) ? implode(',', $TotalBiayaCABANG) : (isset($TotalBiayaCABANG) ? $TotalBiayaCABANG : '[]') ?>])
            }, {
                name: 'Laba Bersih',
                color: '#28a745',
                data: getArrayData([<?= isset($TotalLabaCABANG) && is_array($TotalLabaCABANG) ? implode(',', $TotalLabaCABANG) : (isset($TotalLabaCABANG) ? $TotalLabaCABANG : '[]') ?>])
            }]
        });

        // Menyimpan status filter terakhir agar tidak ter-trigger ganda jika value belum berubah
        var lastFetchedData = {
            cabang: '<?= esc($selectedCabang ?? '') ?>',
            tgl_dari: '<?= esc($tgl_dari ?? '') ?>',
            tgl_sampai: '<?= esc($tgl_sampai ?? '') ?>'
        };

        var currentAjaxReq = null;
        var lastChangedInput = 'tgl_dari'; // Default

        // Track last modified input for dynamic error placement
        $('#tgl_dari').on('change keyup', function() { lastChangedInput = 'tgl_dari'; });
        $('#tgl_sampai').on('change keyup', function() { lastChangedInput = 'tgl_sampai'; });

        // AJAX Chart Update Function
        function fetchAndUpdateChart() {
            var cabang = $('#cabangSelect').val();
            var tgl_dari = $('#tgl_dari').val();
            var tgl_sampai = $('#tgl_sampai').val();

            // Reset error validation UI
            $('#formFilter .is-invalid').removeClass('is-invalid');
            $('#formFilter .invalid-feedback').remove();

            // Cegah pemanggilan AJAX jika filter sama persis dengan yang terakhir di-request
            if (lastFetchedData.cabang === cabang && 
                lastFetchedData.tgl_dari === tgl_dari && 
                lastFetchedData.tgl_sampai === tgl_sampai) {
                return;
            }

            // Update memori filter terbaru
            lastFetchedData = {
                cabang: cabang,
                tgl_dari: tgl_dari,
                tgl_sampai: tgl_sampai
            };

            myChart.showLoading('Memuat data...');
            
            if (currentAjaxReq !== null) {
                currentAjaxReq.abort();
            }
            
            currentAjaxReq = $.ajax({
                url: '<?= site_url('grafikbiayakantorbandinglaba') ?>',
                type: 'GET',
                dataType: 'json',
                data: {
                    cabang: cabang,
                    tgl_dari: tgl_dari,
                    tgl_sampai: tgl_sampai,
                    last_changed: lastChangedInput
                },
                success: function(res) {
                    myChart.hideLoading();
                    
                    // Error validasi (HTTP 422) akan ditangkap oleh block error: di bawah, persis seperti Trucking (Laravel)

                    if (res.error) {
                        showDialog(res.error);
                        return;
                    }
                    
                    var cabangName = res.cabangCABANG ? res.cabangCABANG.toUpperCase() : '';
                    myChart.setTitle({ text: 'Grafik Biaya Kantor vs Laba Bersih - Cabang ' + cabangName }, { text: 'Per ' + (res.jlhblnCABANG || 0) + ' Bulan, Tahun ' + (res.TahunCABANG || "") });
                    
                    // Kembalikan nilai tanggal dari backend (misal jika reset, backend akan mengirimkan min/max bulan)
                    if (res.tgl_dari) {
                        $('#tgl_dari').val(res.tgl_dari);
                        lastFetchedData.tgl_dari = res.tgl_dari;
                    }
                    if (res.tgl_sampai) {
                        $('#tgl_sampai').val(res.tgl_sampai);
                        lastFetchedData.tgl_sampai = res.tgl_sampai;
                    }

                    // Bersihkan single quote dari PHP pada kategori
                    var categories = getArrayData(res.FTglCABANG).map(function(val) {
                        return typeof val === 'string' ? val.replace(/'/g, '') : val;
                    });
                    
                    // Pastikan data yang masuk adalah float/angka, bukan string
                    var dataBiaya = getArrayData(res.TotalBiayaCABANG).map(function(val) {
                        return parseFloat(val) || 0;
                    });
                    
                    var dataLaba = getArrayData(res.TotalLabaCABANG).map(function(val) {
                        return parseFloat(val) || 0;
                    });

                    myChart.xAxis[0].setCategories(categories);
                    myChart.series[0].setData(dataBiaya);
                    myChart.series[1].setData(dataLaba);
                    
                    $('#textLastUpdate').text('Last Update : ' + (res.LastUpdateCABANG || '-'));
                    
                    // Update batas MonthPicker jika ada data
                    if (res.minBulan && res.maxBulan) {
                        try {
                            var minParts = res.minBulan.split('-');
                            var maxParts = res.maxBulan.split('-');
                            var minDate = new Date(minParts[1], parseInt(minParts[0]) - 1);
                            var maxDate = new Date(maxParts[1], parseInt(maxParts[0]) - 1);
                            
                            $('#tgl_dari, #tgl_sampai').MonthPicker('option', 'MinMonth', minDate);
                            $('#tgl_dari, #tgl_sampai').MonthPicker('option', 'MaxMonth', maxDate);
                        } catch(e) {}
                    }
                },
                error: function(jqXHR, textStatus) {
                    if (textStatus !== 'abort') {
                        myChart.hideLoading();
                        
                        // Menangani response HTTP 422 seperti Trucking (Laravel FormRequest)
                        if (jqXHR.status === 422) {
                            var res = jqXHR.responseJSON;
                            setErrorMessages($('#formFilter'), res.errors);
                        } else {
                            showDialog('Terjadi kesalahan saat mengambil data grafik.');
                        }
                    }
                },
                complete: function() {
                    currentAjaxReq = null;
                }
            });
        }

        // Bind events
        $('#cabangSelect, #tgl_dari, #tgl_sampai').on('change', function() {
            // Auto reload grafik jika tanggal valid, jika invalid hanya muncul tulisan merah
            fetchAndUpdateChart();
        });

        // Trigger pencarian juga saat tombol filter diklik
        $('#btnFilter').click(function(e) {
            e.preventDefault();
            // Force fetch dengan mengosongkan lastFetchedData agar check tidak return awal
            lastFetchedData.cabang = null;
            fetchAndUpdateChart();
        });

        // Event Reset tanpa reload halaman
        $('#btnReset').click(function(e) {
            e.preventDefault();
            $('#cabangSelect').val('MDN');
            if($.fn.select2) {
                $('#cabangSelect').trigger('change.select2');
            }
            $('#tgl_dari').val('');
            $('#tgl_sampai').val('');
            
            // Hapus status is-invalid jika ada
            $('#formFilter .is-invalid').removeClass('is-invalid');
            $('#formFilter .invalid-feedback').remove();
            
            lastFetchedData.cabang = null; // force reload
            fetchAndUpdateChart();
        });

        // Event untuk input teks manual (jika user mengetik manual di field tanggal)
        var filterTimeout;
        $('#tgl_dari, #tgl_sampai').on('keyup', function() {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(fetchAndUpdateChart, 300);
        });

        // Khusus untuk plugin MonthPicker saat user memilih dari popup kalender
        try {
            $('#tgl_dari, #tgl_sampai').MonthPicker('option', 'OnAfterChooseMonth', function() {
                fetchAndUpdateChart();
            });
            
            // Set batas awal MonthPicker saat halaman pertama kali dimuat
            var initMin = '<?= esc($minBulan ?? '') ?>';
            var initMax = '<?= esc($maxBulan ?? '') ?>';
            if (initMin && initMax) {
                var minP = initMin.split('-');
                var maxP = initMax.split('-');
                $('#tgl_dari, #tgl_sampai').MonthPicker('option', 'MinMonth', new Date(minP[1], parseInt(minP[0]) - 1));
                $('#tgl_dari, #tgl_sampai').MonthPicker('option', 'MaxMonth', new Date(maxP[1], parseInt(maxP[0]) - 1));
            }
        } catch(e) {}

        // Apply initial theme
        myChart.update(getChartTheme());

        // Observe body class changes for dynamic dark mode switching
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === "class") {
                    myChart.update(getChartTheme());
                }
            });
        });
        observer.observe(document.body, { attributes: true });
    });
</script>
