<div class="container-fluid">
    <div class="row">
        <!-- JAKARTA -->
        <div class="col-md-12 mb-4">
            <div class="card card-primary card-outline">
                <div class="card-body">
                    <div id="tradoluarJKT" style="width:100%; height:400px;"></div>
                </div>
            </div>
        </div>

        <!-- MEDAN -->
        <div class="col-md-12 mb-4">
            <div class="card card-primary card-outline">
                <div class="card-body">
                    <div id="tradoluarMDN" style="width:100%; height:400px;"></div>
                </div>
            </div>
        </div>

        <!-- SURABAYA -->
        <div class="col-md-12 mb-4">
            <div class="card card-primary card-outline">
                <div class="card-body">
                    <div id="tradoluarSBY" style="width:100%; height:400px;"></div>
                </div>
            </div>
        </div>

        <!-- MAKASSAR -->
        <div class="col-md-12 mb-4">
            <div class="card card-primary card-outline">
                <div class="card-body">
                    <div id="tradoluarMKS" style="width:100%; height:400px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.highcharts.com/highcharts.js"></script>
<script type="text/javascript">
    $(function () {
        
        // Helper function to safely join arrays or return empty array string if empty
        function getArrayData(phpData) {
            return (typeof phpData === 'string' && phpData === '[]') ? [] : phpData;
        }

        // JAKARTA
        $('#tradoluarJKT').highcharts({
            chart: { type: 'line' },
            title: { text: 'GRAFIK Pengunaan Trado Luar <?= esc((string) ($cabangJKT ?? ''), 'js') ?>' },
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
                name: 'Muatan <?= esc((string) ($cabangJKT ?? ''), 'js') ?>',
                data: <?= json_encode(array_values((array) ($TotalMuatanJKT ?? []))) ?>
            }, {
                name: 'Bongkaran <?= esc((string) ($cabangJKT ?? ''), 'js') ?>',
                data: <?= json_encode(array_values((array) ($TotalBongkaranJKT ?? []))) ?>  
            }]
        });

        // MEDAN
        $('#tradoluarMDN').highcharts({
            chart: { type: 'line' },
            title: { text: 'GRAFIK Pengunaan Trado Luar <?= esc((string) ($cabangMDN ?? ''), 'js') ?>' },
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
                name: 'Muatan <?= esc((string) ($cabangMDN ?? ''), 'js') ?>',
                data: <?= json_encode(array_values((array) ($TotalMuatanMDN ?? []))) ?>
            }, {
                name: 'Bongkaran <?= esc((string) ($cabangMDN ?? ''), 'js') ?>',
                data: <?= json_encode(array_values((array) ($TotalBongkaranMDN ?? []))) ?>  
            }]
        });

        // SURABAYA
        $('#tradoluarSBY').highcharts({
            chart: { type: 'line' },
            title: { text: 'GRAFIK Pengunaan Trado Luar <?= esc((string) ($cabangSBY ?? ''), 'js') ?>' },
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
                name: 'Muatan <?= esc((string) ($cabangSBY ?? ''), 'js') ?>',
                data: <?= json_encode(array_values((array) ($TotalMuatanSBY ?? []))) ?>
            }, {
                name: 'Bongkaran <?= esc((string) ($cabangSBY ?? ''), 'js') ?>',
                data: <?= json_encode(array_values((array) ($TotalBongkaranSBY ?? []))) ?>  
            }]
        });

        // MAKASSAR
        $('#tradoluarMKS').highcharts({
            chart: { type: 'line' },
            title: { text: 'GRAFIK Pengunaan Trado Luar <?= esc((string) ($cabangMKS ?? ''), 'js') ?>' },
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
                name: 'Muatan <?= esc((string) ($cabangMKS ?? ''), 'js') ?>',
                data: <?= json_encode(array_values((array) ($TotalMuatanMKS ?? []))) ?>
            }, {
                name: 'Bongkaran <?= esc((string) ($cabangMKS ?? ''), 'js') ?>',
                data: <?= json_encode(array_values((array) ($TotalBongkaranMKS ?? []))) ?>  
            }]
        });

    });
</script>
