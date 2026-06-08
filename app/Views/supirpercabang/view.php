<div class="row">
    <div class="col-md-6">
        <table class="table table-bordered table-sm">
            <tr>
                <th width="35%">ID Supir</th>
                <td><?= htmlspecialchars($FKSupir) ?></td>
            </tr>
            <tr>
                <th>Nama Supir</th>
                <td><?= htmlspecialchars($FNSupir) ?></td>
            </tr>
            <tr>
                <th>Alamat</th>
                <td><?= htmlspecialchars($FAlamat) ?></td>
            </tr>
            <tr>
                <th>Kota</th>
                <td><?= htmlspecialchars($FKota) ?></td>
            </tr>
            <tr>
                <th>No. Telp</th>
                <td><?= htmlspecialchars($FTelp) ?></td>
            </tr>
            <tr>
                <th>Status</th>
                <td><span class="badge badge-<?= $FStatus == 'Aktif' ? 'success' : 'danger' ?>"><?= htmlspecialchars($FStatus) ?></span></td>
            </tr>
        </table>
    </div>
</div>

<hr>
<h5 class="text-primary mb-3">Dokumen Supir</h5>
<div class="row">
    <div class="col-md-3 text-center mb-3">
        <h6>Foto Supir</h6>
        <img src="<?= $FotoSupir ?>" alt="Foto Supir" class="img-thumbnail" style="max-height: 250px;">
    </div>
    <div class="col-md-3 text-center mb-3">
        <h6>Foto SIM</h6>
        <img src="<?= $FotoSim ?>" alt="Foto SIM" class="img-thumbnail" style="max-height: 250px;">
    </div>
    <div class="col-md-3 text-center mb-3">
        <h6>Foto KTP</h6>
        <img src="<?= $FotoKtp ?>" alt="Foto KTP" class="img-thumbnail" style="max-height: 250px;">
    </div>
    <div class="col-md-3 text-center mb-3">
        <h6>Foto KK</h6>
        <img src="<?= $FotoKK ?>" alt="Foto KK" class="img-thumbnail" style="max-height: 250px;">
    </div>
</div>
