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
            title: { text: 'GRAFIK Pengunaan Trado Luar <?= $cabangJKT ?>' },
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
                name: 'Muatan <?= $cabangJKT ?>',
                data: [<?= is_array($TotalMuatanJKT) ? implode(',', $TotalMuatanJKT) : $TotalMuatanJKT ?>]
            }, {
                name: 'Bongkaran <?= $cabangJKT ?>',
                data: [<?= is_array($TotalBongkaranJKT) ? implode(',', $TotalBongkaranJKT) : $TotalBongkaranJKT ?>]  
            }]
        });

        // MEDAN
        $('#tradoluarMDN').highcharts({
            chart: { type: 'line' },
            title: { text: 'GRAFIK Pengunaan Trado Luar <?= $cabangMDN ?>' },
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
                name: 'Muatan <?= $cabangMDN ?>',
                data: [<?= is_array($TotalMuatanMDN) ? implode(',', $TotalMuatanMDN) : $TotalMuatanMDN ?>]
            }, {
                name: 'Bongkaran <?= $cabangMDN ?>',
                data: [<?= is_array($TotalBongkaranMDN) ? implode(',', $TotalBongkaranMDN) : $TotalBongkaranMDN ?>]  
            }]
        });

        // SURABAYA
        $('#tradoluarSBY').highcharts({
            chart: { type: 'line' },
            title: { text: 'GRAFIK Pengunaan Trado Luar <?= $cabangSBY ?>' },
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
                name: 'Muatan <?= $cabangSBY ?>',
                data: [<?= is_array($TotalMuatanSBY) ? implode(',', $TotalMuatanSBY) : $TotalMuatanSBY ?>]
            }, {
                name: 'Bongkaran <?= $cabangSBY ?>',
                data: [<?= is_array($TotalBongkaranSBY) ? implode(',', $TotalBongkaranSBY) : $TotalBongkaranSBY ?>]  
            }]
        });

        // MAKASSAR
        $('#tradoluarMKS').highcharts({
            chart: { type: 'line' },
            title: { text: 'GRAFIK Pengunaan Trado Luar <?= $cabangMKS ?>' },
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
                name: 'Muatan <?= $cabangMKS ?>',
                data: [<?= is_array($TotalMuatanMKS) ? implode(',', $TotalMuatanMKS) : $TotalMuatanMKS ?>]
            }, {
                name: 'Bongkaran <?= $cabangMKS ?>',
                data: [<?= is_array($TotalBongkaranMKS) ? implode(',', $TotalBongkaranMKS) : $TotalBongkaranMKS ?>]  
            }]
        });

    });
</script>
