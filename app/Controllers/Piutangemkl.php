<?php

namespace App\Controllers;

use App\Models\MpiutangemklModel;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

// Migrated from CI3: application/controllers/piutangemkl.php

class Piutangemkl extends BaseController
{
    /**
     * Whitelist kolom filter grid jqGrid (view LapEMKL_Piutang).
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
        'FTgl' => "UPPER(FORMAT(FTgl, 'dd-MMM-yyyy'))",
        'FTglJT' => "UPPER(FORMAT(FTglJT, 'dd-MMM-yyyy'))",
        'FNTgl' => "(ltrim(rtrim(str(FThnJob)))+'-'+(case when FBlnJob>=10 then '' else '0' end)+ltrim(rtrim(str(FBlnJob))))",
        'FNominal' => ['sql' => 'CAST(FNominal AS VARCHAR)', 'numeric' => true],
        'FSisa' => ['sql' => 'CAST(FSisa AS VARCHAR)', 'numeric' => true],
        'FSelisih' => ['sql' => 'FSelisih', 'numeric' => true],
        'FTOP' => ['sql' => 'FTOP', 'numeric' => true],
    ];

    protected ?string $filterDbGroup = 'dbtruck';

    protected $mpiutangemklModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->mpiutangemklModel = new MpiutangemklModel();

        date_default_timezone_set("Asia/Jakarta");
        ini_set('memory_limit', '-1');
    }

    public function index()
    {
        $data['title'] = 'Laporan Piutang EMKL';
        
        // Initial last update for Medan (Default)
        $data['last_update'] = date("d-m-Y H:i:s", strtotime($this->getlastupdate('MDN')));
        
        return $this->render('piutangemkl/gridpiutangemkl', $data);
    }

    public function grid()
    {
        $page = $this->request->getPost('page') ?: 1;
        $limit = $this->request->getPost('rows') ?: 10;
        $sidx = $this->request->getPost('sidx') ?: 'FTgl';
        $sord = $this->request->getPost('sord') ?: 'desc';
        $cabang = $this->request->getPost('cabang') ?: 'MDN';

        $totalrows = $this->request->getPost('totalrows');
        if ($totalrows) {
            $limit = $totalrows;
        }
        $filters = $this->request->getPost('filters');
        // $search = $this->request->getPost('_search');
        $where = " ";

        $operation = $this->operationAll($filters);

        if ($operation != '') {
            $where = " AND ($operation)";
        }

        // Logic filter tambahan dari view (Jenis Job & Titipan)
        $jnsjob = $this->request->getPost('jnsjob');
        $jnstitipan = $this->request->getPost('isTitipan');

        if ($jnsjob && $jnsjob != 'A') {
            $where .= " AND FJnsJob LIKE " . $this->escapeFilterValue($jnsjob . '%');
        }
        if ($jnstitipan == '1') {
            $where .= " AND FJnsPiutang = 'TITIPAN'";
        } else if ($jnstitipan == '2') {
            $where .= " AND (FJnsPiutang = '' OR FJnsPiutang IS NULL)";
        }

        $sql = $this->mpiutangemklModel->count($where, $cabang);
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

        $data = $this->mpiutangemklModel->get($where, $sidx, $sord, $limit, $start, $cabang);
        $grandTotal = $this->mpiutangemklModel->getGrandTotal($where, $cabang);
        
        $responce = new \stdClass();
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $i = 0;
        foreach ($data->getResult() as $row) {
            $responce->rows[$i] = array(
                'id'         => $row->FNTrans,
                'FTgl'       => $row->FTgl ? date('d-M-Y', strtotime($row->FTgl)) : '',
                'FNTrans'    => $row->FNTrans,
                'FNInvoice'  => $row->FNInvoice,
                'FNShipper'  => $row->FNShipper,
                'FNominal'   => $row->FNominal,
                'FSisa'      => $row->FSisa,
                'FTOP'       => $row->FTOP,
                'FTglJT'     => $row->FTglJT ? date('d-M-Y', strtotime($row->FTglJT)) : '',
                'FSelisih'   => $row->FSelisih,
                'FJnsRemind' => $row->FJnsRemind,
                'FNoJob'     => $row->FNoJob,
                'FBlnJob'    => $row->FBlnJob,
                'FThnJob'    => $row->FThnJob,
                'FNTgl'      => $row->FNTgl ? date('d-M-Y', strtotime($row->FNTgl)) : '',
                'FJnsJob'    => $row->FJnsJob,
                'FJnsPiutang'=> $row->FJnsPiutang
            );
            $i++;
        }
        
        // Add metadata for last update and grand totals
        $responce->userdata = [
            'last_update' => date("d-m-Y H:i:s", strtotime($this->getlastupdate($cabang))),
            'GrandTotalNominal' => $grandTotal->TotalNominal ?? 0,
            'GrandTotalSisa' => $grandTotal->TotalSisa ?? 0
        ];

        return $this->response->setJSON($responce);
    }

    public function getlastupdate($cabangid)
    {
        $tgllast = $this->mpiutangemklModel->get_tglupdate($cabangid);
        $hasil = "";
        foreach ($tgllast as $key) {
            $hasil = $key->FlastUpdate;
        }
        return $hasil;
    }
}
