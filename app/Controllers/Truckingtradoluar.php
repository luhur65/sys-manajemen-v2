<?php

namespace App\Controllers;

use App\Libraries\Export\ExcelExporter;
use App\Libraries\Export\NullExportNotifier;
use App\Models\MtruckingtradoluarModel;

class Truckingtradoluar extends BaseController
{
    private const CABANG_NAMA = ['MDN' => 'MEDAN', 'JKT' => 'JAKARTA', 'SBY' => 'SURABAYA', 'MKS' => 'MAKASSAR'];

    /** Kolom Excel = kolom grid header, urutan, label & group header sama. */
    private const EXCEL_COLUMNS = [
        ['key' => 'FTgl',                 'label' => 'Tanggal', 'type' => 'date', 'width' => 14],
        ['key' => 'FUkuran20Muatan',      'label' => '20',   'group' => 'Muatan',    'type' => 'integer', 'width' => 9],
        ['key' => 'FUkuran2x20Muatan',    'label' => '2x20', 'group' => 'Muatan',    'type' => 'integer', 'width' => 9],
        ['key' => 'FUkuran40Muatan',      'label' => '40',   'group' => 'Muatan',    'type' => 'integer', 'width' => 9],
        ['key' => 'FUkuran20Bongkaran',   'label' => '20',   'group' => 'Bongkaran', 'type' => 'integer', 'width' => 9],
        ['key' => 'FUkuran2x20Bongkaran', 'label' => '2x20', 'group' => 'Bongkaran', 'type' => 'integer', 'width' => 9],
        ['key' => 'FUkuran40Bongkaran',   'label' => '40',   'group' => 'Bongkaran', 'type' => 'integer', 'width' => 9],
        ['key' => 'FUkuran20Import',      'label' => '20',   'group' => 'Import',    'type' => 'integer', 'width' => 9],
        ['key' => 'FUkuran2x20Import',    'label' => '2x20', 'group' => 'Import',    'type' => 'integer', 'width' => 9],
        ['key' => 'FUkuran40Import',      'label' => '40',   'group' => 'Import',    'type' => 'integer', 'width' => 9],
        ['key' => 'FUkuran20Eksport',     'label' => '20',   'group' => 'Eksport',   'type' => 'integer', 'width' => 9],
        ['key' => 'FUkuran2x20Eksport',   'label' => '2x20', 'group' => 'Eksport',   'type' => 'integer', 'width' => 9],
        ['key' => 'FUkuran40Eksport',     'label' => '40',   'group' => 'Eksport',   'type' => 'integer', 'width' => 9],
    ];

    /**
     * Whitelist kolom filter grid jqGrid (tabel TradoLuar per cabang - grid rekap harian).
     * Kolom di luar daftar ini ditolak oleh GridFilter.
     */
    protected array $filterFields = [
        'FTgl' => "FORMAT(CAST(FTgl AS DATETIME), 'dd-MMM-yyyy', 'en-US')",
        'FUkuran20Muatan',
        'FUkuran2x20Muatan',
        'FUkuran40Muatan',
        'FUkuran20Bongkaran',
        'FUkuran2x20Bongkaran',
        'FUkuran40Bongkaran',
        'FUkuran20Import',
        'FUkuran2x20Import',
        'FUkuran40Import',
        'FUkuran20Eksport',
        'FUkuran2x20Eksport',
        'FUkuran40Eksport',
    ];

    protected ?string $filterDbGroup = 'dbtruck';

    /**
     * Whitelist kolom filter untuk grid detail per tanggal.
     */
    private const FILTER_FIELDS_DETAIL = [
        'FTgl' => "FORMAT(CAST(FTgl AS DATETIME), 'dd-MMM-yyyy', 'en-US')",
        'FNTrans',
        'FNoContSeal',
        'FNShipper',
        'FNoPol',
        'FOrderan',
        'FNContainer',
        'FLokasiBongkarMuat',
        'FNominalHargaTrucking',
        'FNominalHargaTruckingPusat',
        'FKeterangan',
        'FSelisih' => ['sql' => 'FSelisih', 'numeric' => true],
    ];

    protected $mtruckingtradoluarModel;

    public function __construct()
    {
        $this->mtruckingtradoluarModel = new MtruckingtradoluarModel();
    }

    public function index()
    {
        $data['menuaktif'] = 'Laporan Trucking';
        return $this->render('truckingtradoluar/index', $data);
    }

    public function grid()
    {
        $page = $this->request->getPost('page') ?: 1;
        $limit = $this->request->getPost('rows') ?: 50;
        $sidx = $this->request->getPost('sidx') ?: 'FTgl';
        $sord = $this->request->getPost('sord') ?: 'desc';
        $cabang = $this->request->getPost('cabang') ?: 'MDN';
        $tgl_dari = $this->request->getPost('tgl_dari'); if(!empty($tgl_dari)) $tgl_dari = date('Y-m-d', strtotime($tgl_dari));
        $tgl_sampai = $this->request->getPost('tgl_sampai'); if(!empty($tgl_sampai)) $tgl_sampai = date('Y-m-d', strtotime($tgl_sampai));
        $where = $this->buildWhere($this->request->getPost('filters'), $tgl_dari, $tgl_sampai);

        $sql = $this->mtruckingtradoluarModel->count($where, $cabang);
        $count = $sql->getNumRows();
        
        if ($count > 0) {
            $total_pages = ceil($count / $limit);
        } else {
            $total_pages = 0;
        }
        
        if ($page > $total_pages) $page = $total_pages;
        if ($limit < 0) $limit = 0;
        $start = $limit * $page - $limit;
        if ($start < 0) $start = 0;

        $data = $this->mtruckingtradoluarModel->get($where, $sidx, $sord, $limit, $start, $cabang);
        $grandTotal = $this->mtruckingtradoluarModel->getGrandTotal($where, $cabang);
        
        $responce = new \stdClass();
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $i = 0;
        foreach ($data->getResult() as $row) {
            $responce->rows[$i] = array(
                'id'                   => $i + $start,
                'FTgl'                 => $row->FTgl ? date('d-M-Y', strtotime($row->FTgl)) : '',
                'FUkuran20Muatan'      => $row->FUkuran20Muatan,
                'FUkuran2x20Muatan'    => $row->FUkuran2x20Muatan,
                'FUkuran40Muatan'      => $row->FUkuran40Muatan,
                'FUkuran20Bongkaran'   => $row->FUkuran20Bongkaran,
                'FUkuran2x20Bongkaran' => $row->FUkuran2x20Bongkaran,
                'FUkuran40Bongkaran'   => $row->FUkuran40Bongkaran,
                'FUkuran20Import'      => $row->FUkuran20Import,
                'FUkuran2x20Import'    => $row->FUkuran2x20Import,
                'FUkuran40Import'      => $row->FUkuran40Import,
                'FUkuran20Eksport'     => $row->FUkuran20Eksport,
                'FUkuran2x20Eksport'   => $row->FUkuran2x20Eksport,
                'FUkuran40Eksport'     => $row->FUkuran40Eksport
            );
            $i++;
        }
        
        $tglUpdateData = $this->mtruckingtradoluarModel->get_tglupdate($cabang);
        $lastUpdate = (!empty($tglUpdateData) && !empty($tglUpdateData[0]->FTglUpdate)) 
            ? date("d-m-Y H:i:s", strtotime($tglUpdateData[0]->FTglUpdate)) : '';

        $responce->userdata = [
            'last_update' => $lastUpdate,
            'Total20Muatan' => $grandTotal->Total20Muatan ?? 0,
            'Total2x20Muatan' => $grandTotal->Total2x20Muatan ?? 0,
            'Total40Muatan' => $grandTotal->Total40Muatan ?? 0,
            'Total20Bongkaran' => $grandTotal->Total20Bongkaran ?? 0,
            'Total2x20Bongkaran' => $grandTotal->Total2x20Bongkaran ?? 0,
            'Total40Bongkaran' => $grandTotal->Total40Bongkaran ?? 0,
            'Total20Import' => $grandTotal->Total20Import ?? 0,
            'Total2x20Import' => $grandTotal->Total2x20Import ?? 0,
            'Total40Import' => $grandTotal->Total40Import ?? 0,
            'Total20Eksport' => $grandTotal->Total20Eksport ?? 0,
            'Total2x20Eksport' => $grandTotal->Total2x20Eksport ?? 0,
            'Total40Eksport' => $grandTotal->Total40Eksport ?? 0
        ];

        return $this->response->setJSON($responce);
    }

    /**
     * Export Excel grid header (rekap harian): isi sama dengan grid
     * (cabang, periode, filter toolbar dan sort aktif), tanpa paging.
     */
    public function excel()
    {
        $cabang = strtoupper((string) ($this->request->getGet('cabang') ?: 'MDN'));
        if (!isset(self::CABANG_NAMA[$cabang])) {
            $cabang = 'MDN';
        }
        $tglDari    = (string) $this->request->getGet('tgl_dari');
        $tglSampai  = (string) $this->request->getGet('tgl_sampai');
        $where      = $this->buildWhere($this->request->getGet('filters'), $tglDari, $tglSampai);
        $sidx       = $this->request->getGet('sidx') ?: 'FTgl';
        $sord       = $this->request->getGet('sord') ?: 'desc';

        try {
            $rows = $this->mtruckingtradoluarModel->getAll($where, $sidx, $sord, $cabang);
        } catch (\Throwable $e) {
            log_message('error', 'Truckingtradoluar::excel: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setBody('Gagal memuat data laporan');
        }

        $toNum = static fn($v) => ($v === null || trim((string) $v) === '') ? null : (float) str_replace(',', '', (string) $v);
        $data  = [];
        $total = ['FTgl' => 'GRAND TOTAL'];
        foreach ($rows as $r) {
            $item = ['FTgl' => $r['FTgl'] ? date('d-M-Y', strtotime($r['FTgl'])) : ''];
            foreach (self::EXCEL_COLUMNS as $col) {
                if ($col['key'] === 'FTgl') {
                    continue;
                }
                $item[$col['key']]  = $toNum($r[$col['key']] ?? null);
                $total[$col['key']] = ($total[$col['key']] ?? 0) + (float) $item[$col['key']];
            }
            $data[] = $item;
        }

        $periode = trim($tglDari . ' s/d ' . $tglSampai);
        $exporter = new ExcelExporter(self::EXCEL_COLUMNS, new NullExportNotifier());
        $exporter->setTitle('LAPORAN PENGGUNAAN TRADO LUAR', [
            'Cabang'  => self::CABANG_NAMA[$cabang],
            'Periode' => $periode === 's/d' ? 'SEMUA' : $periode,
            'Dicetak' => ExcelExporter::tanggalCetak(),
        ]);

        $fileName = 'Laporan_Trado_Luar_' . self::CABANG_NAMA[$cabang] . '_' . date('Ymd_His');

        try {
            $path = $exporter->export(ExcelExporter::chunk($data), count($data), $fileName, $total);
        } catch (\Throwable $e) {
            log_message('error', 'Truckingtradoluar::excel write: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setBody('Gagal membuat file Excel');
        }

        return ExcelExporter::download($path, preg_replace('/[^A-Za-z0-9_\-]+/', '_', $fileName) . '.xlsx');
    }

    /** WHERE grid header: filter toolbar + rentang tanggal. Dipakai grid() dan excel(). */
    private function buildWhere($filters, $tglDari, $tglSampai): string
    {
        $where = " ";

        $operation = $this->operationAll($filters);
        if ($operation != '') {
            $where = " AND ($operation)";
        }

        if (!empty($tglDari) && !empty($tglSampai)) {
            $where .= " AND FTgl >= '" . date('Y-m-d', strtotime($tglDari)) . " 00:00:00' AND FTgl <= '" . date('Y-m-d', strtotime($tglSampai)) . " 23:59:59'";
        }

        return $where;
    }

    public function griddetail()
    {
        $page = $this->request->getPost('page') ?: 1;
        $limit = $this->request->getPost('rows') ?: 50;
        $sidx = $this->request->getPost('sidx') ?: 'FNTrans';
        $sord = $this->request->getPost('sord') ?: 'asc';
        $cabang = $this->request->getPost('cabang') ?: 'MDN';
        $ftgl = $this->request->getPost('ftgl');
        $search = $this->request->getPost('_search');
        $filters = $this->request->getPost('filters');

        $where = " ";

        $operation = $this->operationAll($filters, self::FILTER_FIELDS_DETAIL);
        if ($operation != '') {
            $where = " AND ($operation)";
        }

        if (!empty($ftgl)) {
            // Because $ftgl from the grid cell is likely "25-May-2026", convert it back to Y-m-d
            $formattedTgl = date('Y-m-d', strtotime($ftgl));
            $where .= " AND FTgl >= '" . $formattedTgl . " 00:00:00' AND FTgl <= '" . $formattedTgl . " 23:59:59'";
        }

        $sql = $this->mtruckingtradoluarModel->countDetail($where, $cabang);
        $count = $sql->getNumRows();
        
        if ($count > 0) {
            $total_pages = ceil($count / $limit);
        } else {
            $total_pages = 0;
        }
        
        if ($page > $total_pages) $page = $total_pages;
        if ($limit < 0) $limit = 0;
        $start = $limit * $page - $limit;
        if ($start < 0) $start = 0;

        $data = $this->mtruckingtradoluarModel->getDetail($where, $sidx, $sord, $limit, $start, $cabang);
        $grandTotal = $this->mtruckingtradoluarModel->getGrandTotalDetail($where, $cabang);
        
        $responce = new \stdClass();
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $i = 0;
        foreach ($data->getResult() as $row) {
            $responce->rows[$i] = array(
                'id'                         => $i + $start,
                'FTgl'                       => $row->FTgl ? date('d-M-Y', strtotime($row->FTgl)) : '',
                'FNTrans'                    => $row->FNTrans,
                'FNoContSeal'                => $row->FNoContSeal,
                'FNShipper'                  => $row->FNShipper,
                'FNoPol'                     => $row->FNoPol,
                'FOrderan'                   => $row->FOrderan,
                'FNContainer'                => $row->FNContainer,
                'FLokasiBongkarMuat'         => $row->FLokasiBongkarMuat,
                'FNominalHargaTrucking'      => $row->FNominalHargaTrucking,
                'FNominalHargaTruckingPusat' => $row->FNominalHargaTruckingPusat,
                'FSelisih'                   => $row->FSelisih,
                'FKeterangan'                => $row->FKeterangan ?? $row->Fketerangan // Depending on the actual case sensitivity in DB
            );
            $i++;
        }
        
        $responce->userdata = [
            'TotalHargaTrucking' => $grandTotal->TotalHargaTrucking ?? 0,
            'TotalHargaTruckingPusat' => $grandTotal->TotalHargaTruckingPusat ?? 0,
            'TotalSelisih' => $grandTotal->TotalSelisih ?? 0,
            'TotalMuatan' => $grandTotal->TotalMuatan ?? 0,
            'TotalBongkaran' => $grandTotal->TotalBongkaran ?? 0,
            'TotalImport' => $grandTotal->TotalImport ?? 0,
            'TotalEksport' => $grandTotal->TotalEksport ?? 0
        ];

        return $this->response->setJSON($responce);
    }
}
