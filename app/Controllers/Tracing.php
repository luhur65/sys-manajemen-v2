<?php

namespace App\Controllers;

use App\Models\TracingModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Tracing extends BaseController
{
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
            $where .= " AND cabang = '" . $cabang . "'";
        }

        // Terapkan filter pencarian jqGrid bawaan monolith jika diperlukan
        if ($search === "true" && !empty($filters)) {
            $where .= " AND (" . $this->operation($filters) . ")";
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

    private function operation($filters)
    {
        // Custom search operation filter equivalent to the one in BaseController/Legacy Code
        $filters = json_decode($filters);
        $where = " ";
        $whereArray = array();
        $rules = $filters->rules;
        $groupOperation = $filters->groupOp;
        foreach ($rules as $rule) {
            $field = $rule->field;
            $data = $rule->data;
            switch ($rule->op) {
                case "eq":
                    $operator = " = ";
                    break;
                case "ne":
                    $operator = " != ";
                    break;
                case "lt":
                    $operator = " < ";
                    break;
                case "le":
                    $operator = " <= ";
                    break;
                case "gt":
                    $operator = " > ";
                    break;
                case "ge":
                    $operator = " >= ";
                    break;
                case "bw":
                    $operator = " LIKE ";
                    $data .= "%";
                    break;
                case "bn":
                    $operator = " NOT LIKE ";
                    $data .= "%";
                    break;
                case "ew":
                    $operator = " LIKE ";
                    $data = "%" . $data;
                    break;
                case "en":
                    $operator = " NOT LIKE ";
                    $data = "%" . $data;
                    break;
                case "cn":
                    $operator = " LIKE ";
                    $data = "%" . $data . "%";
                    break;
                case "nc":
                    $operator = " NOT LIKE ";
                    $data = "%" . $data . "%";
                    break;
            }
            $whereArray[] = $field . $operator . "'" . $data . "'";
        }
        if (count($whereArray) > 0) {
            $where .= join(" " . $groupOperation . " ", $whereArray);
        } else {
            $where = "1=1";
        }
        return $where;
    }
}
