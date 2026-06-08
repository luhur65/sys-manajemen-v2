<div class="container-fluid">
    <div class="row">
        <!-- JAKARTA -->
        <div class="col-md-12 mb-4">
            <div class="card card-primary card-outline">
                <div class="card-body">
                    <div id="emklluarJKT" style="width:100%; height:400px;"></div>
                </div>
            </div>
        </div>

        <!-- MEDAN -->
        <div class="col-md-12 mb-4">
            <div class="card card-primary card-outline">
                <div class="card-body">
                    <div id="emklluarMDN" style="width:100%; height:400px;"></div>
                </div>
            </div>
        </div>

        <!-- SURABAYA -->
        <div class="col-md-12 mb-4">
            <div class="card card-primary card-outline">
                <div class="card-body">
                    <div id="emklluarSBY" style="width:100%; height:400px;"></div>
                </div>
            </div>
        </div>

        <!-- MAKASSAR -->
        <div class="col-md-12 mb-4">
            <div class="card card-primary card-outline">
                <div class="card-body">
                    <div id="emklluarMKS" style="width:100%; height:400px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.highcharts.com/highcharts.js"></script>
<script type="text/javascript">
    $(function () {
        
        // JAKARTA
        $('#emklluarJKT').highcharts({
            chart: { type: 'line' },
            title: { text: 'GRAFIK Pengunaan EMKL Luar <?= $cabangJKT ?>' },
            subtitle: { text: 'Per <?= $jlhblnJKT ?> Bulan (<?= $TahunJKT ?>)' },
            xAxis: { categories: [<?= is_array($FTglJKT) ? implode(',', $FTglJKT) : $FTglJKT ?>] },
            yAxis: {
                title: { text: 'Jumlah Job' },
                plotLines: [{ value: 0, width: 1, color: '#808080' }]
            },
            tooltip: { valueSuffix: ' job' },
            credits: { enabled: false },
            legend: { layout: 'vertical', align: 'right', verticalAlign: 'middle', borderWidth: 0 },
            responsive: {
                rules: [{
                    condition: { maxWidth: 768 },
                    chartOptions: {
                        legend: { layout: 'horizontal', align: 'center', verticalAlign: 'bottom' }
                    }
                }]
            },
            series: [{
                name: 'EMKL Luar <?= $cabangJKT ?>',
                data: [<?= is_array($TotalEmklluarJKT) ? implode(',', $TotalEmklluarJKT) : $TotalEmklluarJKT ?>]
            }]
        });

        // MEDAN
        $('#emklluarMDN').highcharts({
            chart: { type: 'line' },
            title: { text: 'GRAFIK Pengunaan EMKL Luar <?= $cabangMDN ?>' },
            subtitle: { text: 'Per <?= $jlhblnMDN ?> Bulan (<?= $TahunMDN ?>)' },
            xAxis: { categories: [<?= is_array($FTglMDN) ? implode(',', $FTglMDN) : $FTglMDN ?>] },
            yAxis: {
                title: { text: 'Jumlah Job' },
                plotLines: [{ value: 0, width: 1, color: '#808080' }]
            },
            tooltip: { valueSuffix: ' job' },
            credits: { enabled: false },
            legend: { layout: 'vertical', align: 'right', verticalAlign: 'middle', borderWidth: 0 },
            responsive: {
                rules: [{
                    condition: { maxWidth: 768 },
                    chartOptions: {
                        legend: { layout: 'horizontal', align: 'center', verticalAlign: 'bottom' }
                    }
                }]
            },
            series: [{
                name: 'EMKL Luar <?= $cabangMDN ?>',
                data: [<?= is_array($TotalEmklluarMDN) ? implode(',', $TotalEmklluarMDN) : $TotalEmklluarMDN ?>]
            }]
        });

        // SURABAYA
        $('#emklluarSBY').highcharts({
            chart: { type: 'line' },
            title: { text: 'GRAFIK Pengunaan EMKL Luar <?= $cabangSBY ?>' },
            subtitle: { text: 'Per <?= $jlhblnSBY ?> Bulan (<?= $TahunSBY ?>)' },
            xAxis: { categories: [<?= is_array($FTglSBY) ? implode(',', $FTglSBY) : $FTglSBY ?>] },
            yAxis: {
                title: { text: 'Jumlah Job' },
                plotLines: [{ value: 0, width: 1, color: '#808080' }]
            },
            tooltip: { valueSuffix: ' job' },
            credits: { enabled: false },
            legend: { layout: 'vertical', align: 'right', verticalAlign: 'middle', borderWidth: 0 },
            responsive: {
                rules: [{
                    condition: { maxWidth: 768 },
                    chartOptions: {
                        legend: { layout: 'horizontal', align: 'center', verticalAlign: 'bottom' }
                    }
                }]
            },
            series: [{
                name: 'EMKL Luar <?= $cabangSBY ?>',
                data: [<?= is_array($TotalEmklluarSBY) ? implode(',', $TotalEmklluarSBY) : $TotalEmklluarSBY ?>]
            }]
        });

        // MAKASSAR
        $('#emklluarMKS').highcharts({
            chart: { type: 'line' },
            title: { text: 'GRAFIK Pengunaan EMKL Luar <?= $cabangMKS ?>' },
            subtitle: { text: 'Per <?= $jlhblnMKS ?> Bulan (<?= $TahunMKS ?>)' },
            xAxis: { categories: [<?= is_array($FTglMKS) ? implode(',', $FTglMKS) : $FTglMKS ?>] },
            yAxis: {
                title: { text: 'Jumlah Job' },
                plotLines: [{ value: 0, width: 1, color: '#808080' }]
            },
            tooltip: { valueSuffix: ' job' },
            credits: { enabled: false },
            legend: { layout: 'vertical', align: 'right', verticalAlign: 'middle', borderWidth: 0 },
            responsive: {
                rules: [{
                    condition: { maxWidth: 768 },
                    chartOptions: {
                        legend: { layout: 'horizontal', align: 'center', verticalAlign: 'bottom' }
                    }
                }]
            },
            series: [{
                name: 'EMKL Luar <?= $cabangMKS ?>',
                data: [<?= is_array($TotalEmklluarMKS) ? implode(',', $TotalEmklluarMKS) : $TotalEmklluarMKS ?>]
            }]
        });

    });
</script>
