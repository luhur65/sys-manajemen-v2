<?php
namespace App\Controllers;

use App\Models\MovertopModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Overtopemkl extends BaseController
{
    protected $movertopModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->movertopModel = new MovertopModel();
        date_default_timezone_set("Asia/Jakarta");
        ini_set('memory_limit', '-1');
    }

    public function index()
    {
        $data['menuaktif'] = 'Laporan Over TOP EMKL';
        $data['title'] = 'Laporan Over TOP EMKL';
        
        $data['last_update'] = '';
        $tgllast = $this->movertopModel->get_tglupdate('MDN'); // Default MDN
        if(!empty($tgllast)) {
            $data['last_update'] = date("d-m-Y h:i:s", strtotime($tgllast[0]->FlastUpdate));
        }
        
        return $this->render('overtopemkl/index', $data);
    }

    public function grid()
    {
        $page = $this->request->getPost('page') ?: 1;
        $limit = $this->request->getPost('rows') ?: 50;
        $sidx = $this->request->getPost('sidx') ?: 'FSelisih';
        $sord = $this->request->getPost('sord') ?: 'desc';
        
        $cabang = $this->request->getPost('cabang') ?: 'MDN';
        
        $filters = $this->request->getPost('filters');
        $search = $this->request->getPost('_search');
        
        $where = "";
        
        if ($search == "true") {
            $where = " AND (" . $this->operationAll($filters) . ")";
        }

        $sql = $this->movertopModel->count($where, $cabang);
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

        $data = $this->movertopModel->get($where, $sidx, $sord, $limit, $start, $cabang);
        $grandTotal = $this->movertopModel->getGrandTotal($where, $cabang);
        $tglUpdateData = $this->movertopModel->get_tglupdate($cabang);
        $lastUpdate = (!empty($tglUpdateData) && !empty($tglUpdateData[0]->FlastUpdate)) 
            ? date("d-m-Y h:i:s", strtotime($tglUpdateData[0]->FlastUpdate)) : '';

        $responce = new \stdClass();
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        
        $responce->userdata = [
            'last_update' => $lastUpdate,
            'TotalNominal' => $grandTotal->TotalNominal ?? 0,
            'TotalSisa' => $grandTotal->TotalSisa ?? 0
        ];
        
        $i = 0;
        foreach ($data->getResult() as $row) {
            $responce->rows[$i]['id']   = $row->FNTrans; // Uses FNTrans as ID instead of index
            
            // colNames: 'Tanggal', 'No EPE', 'No Invoice', 'Nama Shipper', 'Nilai Invoice', 'Sisa (Blm dilunasi)', 'TOP (Hari)', 'Tgl Jth Tempo', 'OverDue (Hari)', 'Remind', 'No Job', 'Bln', 'Thn', 'Thn-Bln Job', 'Jns Job', 'Jns Piutang'
            $responce->rows[$i]['cell'] = array(
                $row->FTgl ? date('d-M-Y', strtotime($row->FTgl)) : '',
                $row->FNTrans,
                $row->FNInvoice,
                $row->FNShipper,
                $row->FNominal,
                $row->FSisa,
                $row->FTOP,
                $row->FTglJT ? date('d-M-Y', strtotime($row->FTglJT)) : '',
                $row->FSelisih,
                $row->FJnsRemind,
                $row->FNoJob,
                $row->FBlnJob,
                $row->FThnJob,
                $row->FNTgl,
                $row->FJnsJob,
                $row->FJnsPiutang
            );
            $i++;
        }
        
        return $this->response->setJSON($responce);
    }
}
