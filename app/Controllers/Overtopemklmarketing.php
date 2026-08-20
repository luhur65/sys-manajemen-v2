<?php
namespace App\Controllers;

use App\Models\MovertopmarketingModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Overtopemklmarketing extends BaseController
{
    /**
     * Whitelist kolom filter grid jqGrid (view LapEMKL_OverDue per marketing).
     * Kolom di luar daftar ini ditolak oleh GridFilter.
     */
    protected array $filterFields = [
        'FNTrans',
        'FNInvoice',
        'FNShipper',
        'FJnsRemind',
        'FNoJob',
        'FBlnJob',
        'FThnJob',
        'FJnsJob',
        'FJnsPiutang',
        'FTglHariIni',
        'FTgl' => "FORMAT(CAST(FTgl AS DATETIME), 'dd-MMM-yyyy', 'en-US')",
        'FTglJT' => "FORMAT(CAST(FTglJT AS DATETIME), 'dd-MMM-yyyy', 'en-US')",
        'FNTgl' => "(ltrim(rtrim(str(FThnJob)))+'-'+(case when FBlnJob>=10 then '' else '0' end)+ltrim(rtrim(str(FBlnJob))))",
        'FNominal' => ['sql' => 'FNominal', 'numeric' => true],
        'FSisa' => ['sql' => 'FSisa', 'numeric' => true],
        'FSelisih' => ['sql' => 'FSelisih', 'numeric' => true],
        'FTOP' => ['sql' => 'FTOP', 'numeric' => true],
        'FNMarketing',
    ];

    protected ?string $filterDbGroup = 'dbtruck';

    protected $movertopmarketingModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->movertopmarketingModel = new MovertopmarketingModel();
        date_default_timezone_set("Asia/Jakarta");
        ini_set('memory_limit', '-1');
    }

    public function index()
    {
        $data['menuaktif'] = 'Laporan Over TOP EMKL (Marketing)';
        $data['title'] = 'Laporan Over TOP EMKL (Marketing)';
        
        $data['last_update'] = '';
        $tgllast = $this->movertopmarketingModel->get_tglupdate('MDN'); // Default MDN
        if(!empty($tgllast)) {
            $data['last_update'] = date("d-m-Y h:i:s", strtotime($tgllast[0]->FlastUpdate));
        }
        
        return $this->render('overtopemklmarketing/index', $data);
    }
    
    public function get_marketing()
    {
        $cabang = $this->request->getPost('cabang') ?: 'MDN';
        $options = $this->movertopmarketingModel->get_marketing($cabang);
        return $this->response->setJSON(['html' => $options]);
    }

    public function grid()
    {
        $page = $this->request->getPost('page') ?: 1;
        $limit = $this->request->getPost('rows') ?: 50;
        $sidx = $this->request->getPost('sidx') ?: 'FSelisih';
        $sord = $this->request->getPost('sord') ?: 'desc';
        
        $cabang = $this->request->getPost('cabang') ?: 'MDN';
        $marketing = $this->request->getPost('marketing');
        
        $filters = $this->request->getPost('filters');
        $search = $this->request->getPost('_search');
        
        $where = "";
        
        $operation = $search == "true" ? $this->operationAll($filters) : '';
        if ($operation !== '') {
            $where = " AND (" . $operation . ")";
        }
        
        if (!empty($marketing)) {
            $where .= " AND FNMarketing = " . $this->escapeFilterValue($marketing);
        }

        $sql = $this->movertopmarketingModel->count($where, $cabang);
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

        $data = $this->movertopmarketingModel->get($where, $sidx, $sord, $limit, $start, $cabang);
        $grandTotal = $this->movertopmarketingModel->getGrandTotal($where, $cabang);
        $tglUpdateData = $this->movertopmarketingModel->get_tglupdate($cabang);
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
            
            $responce->rows[$i]['cell'] = array(
                $row->FNMarketing,
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
