<?php
namespace App\Controllers;

use App\Models\MomsetModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Orderan extends BaseController
{
    protected $momsetModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->momsetModel = new MomsetModel();
        date_default_timezone_set("Asia/Jakarta");
        ini_set('memory_limit', '-1');
    }

    public function index()
    {
        $data['menuaktif'] = 'Laporan Orderan';
        $data['title'] = 'Laporan Orderan';
        
        $data['last_update'] = '';
        $tgllast = $this->momsetModel->get_tglupdate('MDN'); // Default MDN
        if(!empty($tgllast)) {
            $data['last_update'] = date("d-m-Y h:i:s", strtotime($tgllast[0]->FlastUpdate));
        }
        
        return $this->render('trucking/orderan', $data);
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

        $filters = $this->request->getPost('filters');
        $search = $this->request->getPost('_search');
        
        $where = "";
        
        if ($search == "true") {
            $where = " AND (" . $this->operationAll($filters) . ")";
        }
        
        if (!empty($tgl_dari) && !empty($tgl_sampai)) {
            // Usually we convert date format from dd-MM-yyyy to yyyy-MM-dd if needed,
            // but let's assume the frontend sends yyyy-MM-dd
            $where .= " AND FTgl >= '" . $tgl_dari . "' AND FTgl <= '" . $tgl_sampai . " 23:59:59'";
        }

        $sql = $this->momsetModel->count($where, $cabang);
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

        $data = $this->momsetModel->get($where, $sidx, $sord, $limit, $start, $cabang);
        $grandTotal = $this->momsetModel->getGrandTotal($where, $cabang);
        $tglUpdateData = $this->momsetModel->get_tglupdate($cabang);
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
            'GrandTotalExim' => $grandTotal->TotalExim ?? 0
        ];
        
        $i = 0;
        foreach ($data->getResult() as $row) {
            $responce->rows[$i]['id']   = $i + $start;
            $responce->rows[$i]['cell'] = array(
                $row->FTgl ? date('d-M-Y', strtotime($row->FTgl)) : '',
                $row->FJumlahMuatan,
                $row->FJumlahBongkaran,
                $row->FJumlahExim
            );
            $i++;
        }
        
        return $this->response->setJSON($responce);
    }
}
