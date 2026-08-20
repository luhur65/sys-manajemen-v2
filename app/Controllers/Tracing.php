<?php

namespace App\Controllers;

use App\Models\TracingModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Tracing extends BaseController
{
    /**
     * Whitelist kolom filter grid jqGrid (tabel tbltracing).
     * Kolom di luar daftar ini ditolak oleh GridFilter.
     */
    protected array $filterFields = [
        'UserId',
        'shipper',
        'cabang',
        'waktulogin' => "FORMAT(waktulogin, 'dd-MM-yyyy HH:mm:ss')",
    ];

    protected $tracingModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->tracingModel = new TracingModel();
        date_default_timezone_set("Asia/Jakarta");
        ini_set('memory_limit', '-1');
    }

    public function index()
    {
        $data['menuaktif'] = 'Laporan Tracing Per Cabang';
        $data['title'] = 'Laporan Tracing Per Cabang';
        $data['cabangList'] = $this->tracingModel->get_cabang();
        
        return $this->render('tracing/index', $data);
    }

    public function grid()
    {
        $page = $this->request->getPost('page') ?? 1;
        $limit = $this->request->getPost('rows') ?? 50;
        $sidx = $this->request->getPost('sidx') ?? 'waktulogin';
        $sord = $this->request->getPost('sord') ?? 'DESC';
        $search = $this->request->getPost('_search');
        $filters = $this->request->getPost('filters');

        // Filter Cabang
        $cabang = $this->request->getPost('cabang');

        $where = "";

        if (!empty($cabang) && $cabang != 'All') {
            $where .= " AND cabang = " . $this->escapeFilterValue($cabang);
        }

        // Terapkan filter pencarian jqGrid bawaan monolith jika diperlukan
        $operation = $search === "true" ? $this->operationAll($filters) : '';
        if ($operation !== '') {
            $where .= " AND (" . $operation . ")";
        }

        $count = $this->tracingModel->count_tracing($where);

        $total_pages = 0;
        if ($count > 0) {
            $total_pages = ceil($count / $limit);
        }

        if ($page > $total_pages) {
            $page = $total_pages;
        }

        $start = $limit * $page - $limit;
        if ($start < 0) {
            $start = 0;
        }

        $query = $this->tracingModel->get_tracing($start, $limit, $sidx, $sord, $where);
        $result = $query->getResult();

        $responce = new \stdClass();
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $responce->rows = [];

        $i = 0;
        foreach ($result as $row) {
            $responce->rows[$i]['id'] = $row->UserId;
            $responce->rows[$i]['cell'] = [
                $row->UserId,
                $row->shipper,
                $row->waktulogin,
                $row->cabang
            ];
            $i++;
        }

        return $this->response->setJSON($responce);
    }

}
