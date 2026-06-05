<?php
namespace App\Controllers;

use App\Models\MomsetrekapmarketingmdnModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Omsetrekapmarketingmdn extends BaseController
{
    protected $momsetrekapmarketingmdnModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->momsetrekapmarketingmdnModel = new MomsetrekapmarketingmdnModel();
        date_default_timezone_set("Asia/Jakarta");
        ini_set('memory_limit', '-1');
    }

    public function index()
    {
        $data['menuaktif'] = 'Laporan Rekap Omset Marketing Medan';
        $data['title'] = 'Laporan Rekap Omset Marketing Medan';
        
        $data['last_update'] = '';
        $tgllast = $this->momsetrekapmarketingmdnModel->get_tglupdate();
        if(!empty($tgllast)) {
            $data['last_update'] = date("d-m-Y h:i:s", strtotime($tgllast[0]->FlastUpdate));
        }
        
        return $this->render('omsetrekapmarketingmdn/index', $data);
    }
    
    public function combomarketing()
    {
        $jenis = $this->request->getGet('jenis');
        $nilai = $this->request->getGet('nilai'); // either bulan or tahun
        
        $dataArr = [];
        if ($jenis == 'bln' && !empty($nilai)) {
            $res = $this->momsetrekapmarketingmdnModel->getMarketingByBulan($nilai);
            foreach ($res as $row) {
                $dataArr[] = ['FNMarketing' => $row->FNMarketing];
            }
        } else if ($jenis == 'thn' && !empty($nilai)) {
            $res = $this->momsetrekapmarketingmdnModel->getMarketingByTahun($nilai);
            foreach ($res as $row) {
                $dataArr[] = ['FNMarketing' => $row->FNMarketing];
            }
        }
        
        return $this->response->setJSON(['data' => $dataArr]);
    }

    public function grid()
    {
        $page = $this->request->getPost('page') ?: 1;
        $limit = $this->request->getPost('rows') ?: 50;
        $sidx = $this->request->getPost('sidx') ?: '';
        $sord = $this->request->getPost('sord') ?: 'desc';
        
        $jenis = $this->request->getPost('jenis'); // bln or thn
        $bln = $this->request->getPost('bln');
        $thn = $this->request->getPost('thn');
        $marketing = $this->request->getPost('marketing');

        $filters = $this->request->getPost('filters');
        $search = $this->request->getPost('_search');
        
        $where = "";
        
        if ($search == "true") {
            $where = " AND (" . $this->operationAll($filters) . ")";
        }
        
        if ($jenis == 'bln' && !empty($bln)) {
            $where .= " AND FBulan = '" . addslashes($bln) . "'";
        } else if ($jenis == 'thn' && !empty($thn)) {
            $where .= " AND FBulan LIKE '%" . addslashes($thn) . "'";
        }
        
        if (!empty($marketing) && $marketing != 'ALL') {
            $where .= " AND FNMarketing = '" . addslashes($marketing) . "'";
        }

        $sql = $this->momsetrekapmarketingmdnModel->count($where);
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

        $data = $this->momsetrekapmarketingmdnModel->get($where, $sidx, $sord, $limit, $start);
        $grandTotal = $this->momsetrekapmarketingmdnModel->getGrandTotal($where);
        $tglUpdateData = $this->momsetrekapmarketingmdnModel->get_tglupdate();
        $lastUpdate = (!empty($tglUpdateData) && !empty($tglUpdateData[0]->FlastUpdate)) 
            ? date("d-m-Y h:i:s", strtotime($tglUpdateData[0]->FlastUpdate)) : '';

        $responce = new \stdClass();
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        
        $responce->userdata = [
            'last_update' => $lastUpdate,
            'GrandTotalMuatan' => $grandTotal->TotalMuatan ?? 0,
            'GrandTotalBongkaran' => $grandTotal->TotalBongkaran ?? 0,
            'GrandTotalExim' => $grandTotal->TotalExim ?? 0,
            'GrandTotalOmset' => $grandTotal->TotalOmset ?? 0,
            'GrandTotalBiayaLapangan' => $grandTotal->TotalBiayaLapangan ?? 0,
            'GrandTotalPph23' => $grandTotal->TotalPph23 ?? 0,
            'GrandTotalProfit' => $grandTotal->TotalProfit ?? 0
        ];
        
        $responce->rows = [];
        $i = 0;
        foreach ($data->getResult() as $row) {
            $responce->rows[$i]['id']   = $i + $start; 
            
            $margin = 0;
            if (floatval($row->FOmset) != 0) {
                $margin = (floatval($row->FProfit) / floatval($row->FOmset)) * 100;
            }

            $responce->rows[$i]['cell'] = array(
                $row->FBulan,
                $row->FNMarketing,
                $row->FJumlahMuatan,
                $row->FJumlahBongkaran,
                $row->FJumlahExim,
                $row->FOmset,
                $row->FBiayaLapangan,
                $row->FNomPph23,
                $row->FProfit,
                $margin
            );
            $i++;
        }
        
        return $this->response->setJSON($responce);
    }
}
