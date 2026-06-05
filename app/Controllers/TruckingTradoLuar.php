<?php

namespace App\Controllers;

use App\Models\MtruckingtradoluarModel;

class TruckingTradoLuar extends BaseController
{
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
        $tgl_dari = $this->request->getPost('tgl_dari');
        $tgl_sampai = $this->request->getPost('tgl_sampai');
        $search = $this->request->getPost('_search');
        $filters = $this->request->getPost('filters');

        $where = " ";

        $operation = trim($this->operationAll($filters));
        if ($operation != '') {
            $where = " AND ($operation)";
        }

        if (!empty($tgl_dari) && !empty($tgl_sampai)) {
            $where .= " AND FTgl >= '" . date('Y-m-d', strtotime($tgl_dari)) . " 00:00:00' AND FTgl <= '" . date('Y-m-d', strtotime($tgl_sampai)) . " 23:59:59'";
        }

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
            $responce->rows[$i]['id']   = $i + $start;
            $responce->rows[$i]['cell'] = array(
                $row->FTgl ? date('d-M-Y', strtotime($row->FTgl)) : '',
                $row->FUkuran20Muatan,
                $row->FUkuran2x20Muatan,
                $row->FUkuran40Muatan,
                $row->FUkuran20Bongkaran,
                $row->FUkuran2x20Bongkaran,
                $row->FUkuran40Bongkaran,
                $row->FUkuran20Import,
                $row->FUkuran2x20Import,
                $row->FUkuran40Import,
                $row->FUkuran20Eksport,
                $row->FUkuran2x20Eksport,
                $row->FUkuran40Eksport
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

        $operation = trim($this->operationAll($filters));
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
            $responce->rows[$i]['id']   = $i + $start;
            $responce->rows[$i]['cell'] = array(
                $row->FTgl ? date('d-M-Y', strtotime($row->FTgl)) : '',
                $row->FNTrans,
                $row->FNoContSeal,
                $row->FNShipper,
                $row->FNoPol,
                $row->FOrderan,
                $row->FNContainer,
                $row->FLokasiBongkarMuat,
                $row->FNominalHargaTrucking,
                $row->FNominalHargaTruckingPusat,
                $row->FSelisih,
                $row->FKeterangan ?? $row->Fketerangan // Depending on the actual case sensitivity in DB
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
