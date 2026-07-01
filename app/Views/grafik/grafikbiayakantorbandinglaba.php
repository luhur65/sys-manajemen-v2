<style>
    #ui-datepicker-div { display: none; }
    .card-filter { margin-bottom: 15px; }
</style>

<div class="container-fluid">
    <!-- Filter Card -->
    <div class="card card-primary card-outline card-filter">
        <div class="card-body">
            <form method="GET" action="<?= site_url('grafikbiayakantorbandinglaba') ?>" id="formFilter">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group filter-input-group">
                            <label class="filter-label">Cabang</label>
                            <select name="cabang" id="cabangSelect" class="form-control select2">
                                <option value="JKT" <?= ($selectedCabang == 'JKT') ? 'selected' : '' ?>>JAKARTA</option>
                                <option value="MDN" <?= ($selectedCabang == 'MDN') ? 'selected' : '' ?>>MEDAN</option>
                                <option value="SBY" <?= ($selectedCabang == 'SBY') ? 'selected' : '' ?>>SURABAYA</option>
                                <option value="MKS" <?= ($selectedCabang == 'MKS') ? 'selected' : '' ?>>MAKASSAR</option>
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
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-group filter-input-group w-100">
                            <div class="d-flex w-100">
                                <button type="submit" id="btnFilter" class="btn btn-primary w-50 mr-1">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                                <button type="button" id="btnReset" class="btn btn-secondary w-50 ml-1" onclick="window.location.href='<?= site_url('grafikbiayakantorbandinglaba') ?>'">
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/highcharts/11.4.3/highcharts.js"></script>
<script type="text/javascript">
    $(function () {
        
        // Initialize Select2 if available
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
                           this.x + ': Rp ' + Highcharts.numberFormat(this.y, 0, ',', '.');
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
