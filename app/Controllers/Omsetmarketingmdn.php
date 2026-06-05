<?php
namespace App\Controllers;

use App\Models\MomsetmarketingmdnModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Omsetmarketingmdn extends BaseController
{
    protected $momsetmarketingmdnModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->momsetmarketingmdnModel = new MomsetmarketingmdnModel();
        date_default_timezone_set("Asia/Jakarta");
        ini_set('memory_limit', '-1');
    }

    public function index()
    {
        $data['menuaktif'] = 'Laporan Omset Marketing Medan';
        $data['title'] = 'Laporan Omset Marketing Medan';
        
        $data['last_update'] = '';
        $tgllast = $this->momsetmarketingmdnModel->get_tglupdate();
        if(!empty($tgllast)) {
            $data['last_update'] = date("d-m-Y h:i:s", strtotime($tgllast[0]->FlastUpdate));
        }
        
        return $this->render('omsetmarketingmdn/index', $data);
    }

    public function grid()
    {
        $page = $this->request->getPost('page') ?: 1;
        $limit = $this->request->getPost('rows') ?: 50;
        $sidx = $this->request->getPost('sidx') ?: 'FTgl';
        $sord = $this->request->getPost('sord') ?: 'desc';
        
        $tgl_dari = $this->request->getPost('tgl_dari');
        $tgl_sampai = $this->request->getPost('tgl_sampai');

        $filters = $this->request->getPost('filters');
        $search = $this->request->getPost('_search');
        
        $where = "";
        
        if ($search == "true") {
            $where = " AND (" . $this->operationAll($filters) . ")";
        }
        
        if (!empty($tgl_dari) && !empty($tgl_sampai)) {
            $where .= " AND FTgl >= '" . $tgl_dari . "' AND FTgl <= '" . $tgl_sampai . " 23:59:59'";
        }

        $sql = $this->momsetmarketingmdnModel->count($where);
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

        $data = $this->momsetmarketingmdnModel->get($where, $sidx, $sord, $limit, $start);
        $grandTotal = $this->momsetmarketingmdnModel->getGrandTotal($where);
        $tglUpdateData = $this->momsetmarketingmdnModel->get_tglupdate();
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
        
        $i = 0;
        foreach ($data->getResult() as $row) {
            $responce->rows[$i]['id']   = $i + $start; 
            
            $margin = 0;
            if (floatval($row->FOmset) != 0) {
                $margin = (floatval($row->FProfit) / floatval($row->FOmset)) * 100;
            }

            $responce->rows[$i]['cell'] = array(
                $row->FNMarketing,
                $row->FTgl ? date('d-M-Y', strtotime($row->FTgl)) : '',
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
