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
            title: { text: 'GRAFIK Pengunaan EMKL Luar <?= esc((string) ($cabangJKT ?? ''), 'js') ?>' },
            subtitle: { text: 'Per <?= (int) ($jlhblnJKT ?? 0) ?> Bulan (<?= esc((string) ($TahunJKT ?? ''), 'js') ?>)' },
            xAxis: { categories: <?= json_encode(array_values((array) ($FTglJKT ?? [])), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?> },
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
                name: 'EMKL Luar <?= esc((string) ($cabangJKT ?? ''), 'js') ?>',
                data: <?= json_encode(array_values((array) ($TotalEmklluarJKT ?? []))) ?>
            }]
        });

        // MEDAN
        $('#emklluarMDN').highcharts({
            chart: { type: 'line' },
            title: { text: 'GRAFIK Pengunaan EMKL Luar <?= esc((string) ($cabangMDN ?? ''), 'js') ?>' },
            subtitle: { text: 'Per <?= (int) ($jlhblnMDN ?? 0) ?> Bulan (<?= esc((string) ($TahunMDN ?? ''), 'js') ?>)' },
            xAxis: { categories: <?= json_encode(array_values((array) ($FTglMDN ?? [])), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?> },
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
                name: 'EMKL Luar <?= esc((string) ($cabangMDN ?? ''), 'js') ?>',
                data: <?= json_encode(array_values((array) ($TotalEmklluarMDN ?? []))) ?>
            }]
        });

        // SURABAYA
        $('#emklluarSBY').highcharts({
            chart: { type: 'line' },
            title: { text: 'GRAFIK Pengunaan EMKL Luar <?= esc((string) ($cabangSBY ?? ''), 'js') ?>' },
            subtitle: { text: 'Per <?= (int) ($jlhblnSBY ?? 0) ?> Bulan (<?= esc((string) ($TahunSBY ?? ''), 'js') ?>)' },
            xAxis: { categories: <?= json_encode(array_values((array) ($FTglSBY ?? [])), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?> },
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
                name: 'EMKL Luar <?= esc((string) ($cabangSBY ?? ''), 'js') ?>',
                data: <?= json_encode(array_values((array) ($TotalEmklluarSBY ?? []))) ?>
            }]
        });

        // MAKASSAR
        $('#emklluarMKS').highcharts({
            chart: { type: 'line' },
            title: { text: 'GRAFIK Pengunaan EMKL Luar <?= esc((string) ($cabangMKS ?? ''), 'js') ?>' },
            subtitle: { text: 'Per <?= (int) ($jlhblnMKS ?? 0) ?> Bulan (<?= esc((string) ($TahunMKS ?? ''), 'js') ?>)' },
            xAxis: { categories: <?= json_encode(array_values((array) ($FTglMKS ?? [])), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?> },
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
                name: 'EMKL Luar <?= esc((string) ($cabangMKS ?? ''), 'js') ?>',
                data: <?= json_encode(array_values((array) ($TotalEmklluarMKS ?? []))) ?>
            }]
        });

    });
</script>
