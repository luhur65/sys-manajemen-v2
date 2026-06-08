<?php

namespace App\Controllers;

use App\Models\MshippernewModel;

class NewShipper extends BaseController
{
    protected $mshippernewModel;

    public function __construct()
    {
        $this->mshippernewModel = new MshippernewModel();
    }

    public function index()
    {
        $data = [
            'menuaktif' => 'Laporan Shipper Baru'
        ];

        return $this->render('newshipper/index', $data);
    }

    public function getGridData()
    {
        $page = $this->request->getPost('page') ?? 1;
        $limit = $this->request->getPost('rows') ?? 50;
        $sidx = $this->request->getPost('sidx') ?? 'FTgl';
        $sord = $this->request->getPost('sord') ?? 'desc';
        $search = $this->request->getPost('_search');
        $filters = $this->request->getPost('filters');

        $cabang = $this->request->getPost('cabang');
        $datefrom = $this->request->getPost('datefrom');
        $dateto = $this->request->getPost('dateto');

        $dateFilters = [];
        if(!empty($datefrom)) {
            $dateFilters['datefrom'] = date('Y-m-d', strtotime($datefrom));
        }
        if(!empty($dateto)) {
            $dateFilters['dateto'] = date('Y-m-d', strtotime($dateto));
        }

        $where = "";
        if ($search == 'true' && !empty($filters)) {
            $operation = trim($this->operation($filters));
            if (!empty($operation)) {
                $where = "(" . $operation . ")";
            }
        }

        $offset = ($page - 1) * $limit;
        
        $countResult = $this->mshippernewModel->getList($cabang, $dateFilters, $where, 0, 0, $sidx, $sord, true);
        $total_pages = 0;
        if ($countResult > 0) {
            $total_pages = ceil($countResult / $limit);
        }
        if ($page > $total_pages) {
            $page = $total_pages;
        }

        $dataResult = $this->mshippernewModel->getList($cabang, $dateFilters, $where, $limit, $offset, $sidx, $sord, false);

        $response = new \stdClass();
        $response->page = $page;
        $response->total = $total_pages;
        $response->records = $countResult;
        $response->rows = [];

        $i = 0;
        foreach ($dataResult as $row) {
            $response->rows[$i]['id'] = $row['FNCabang'].'_'.$row['FNShipper'];
            $response->rows[$i]['cell'] = [
                $row['FNCabang'],
                $row['FNShipper'],
                $row['FTgl'] ? date('d-M-Y', strtotime($row['FTgl'])) : '',
                $row['FNMarketing']
            ];
            $i++;
        }

        return $this->response->setJSON($response);
    }

    private function operation($filters)
    {
        $filters = json_decode($filters);
        $where = "";
        $whereArray = [];
        $rules = $filters->rules;
        $groupOperation = $filters->groupOp;

        foreach ($rules as $rule) {
            $field = $rule->field;
            $data = $rule->data;
            
            // Map jqGrid operations to SQL Server operators
            switch ($rule->op) {
                case "eq": $fieldOperation = " = '" . $data . "'"; break;
                case "ne": $fieldOperation = " != '" . $data . "'"; break;
                case "lt": $fieldOperation = " < '" . $data . "'"; break;
                case "le": $fieldOperation = " <= '" . $data . "'"; break;
                case "gt": $fieldOperation = " > '" . $data . "'"; break;
                case "ge": $fieldOperation = " >= '" . $data . "'"; break;
                case "nu": $fieldOperation = " = ''"; break;
                case "nn": $fieldOperation = " != ''"; break;
                case "in": $fieldOperation = " IN (" . $data . ")"; break;
                case "ni": $fieldOperation = " NOT IN ('" . $data . "')"; break;
                case "bw": $fieldOperation = " LIKE '" . $data . "%'"; break;
                case "bn": $fieldOperation = " NOT LIKE '" . $data . "%'"; break;
                case "ew": $fieldOperation = " LIKE '%" . $data . "'"; break;
                case "en": $fieldOperation = " NOT LIKE '%" . $data . "'"; break;
                case "cn": $fieldOperation = " LIKE '%" . $data . "%'"; break;
                case "nc": $fieldOperation = " NOT LIKE '%" . $data . "%'"; break;
                default: $fieldOperation = ""; break;
            }

            if ($fieldOperation != "") {
                if (strtoupper($field) === 'FTGL') {
                    $whereArray[] = "FORMAT(CAST(" . $field . " AS DATETIME), 'dd-MMM-yyyy', 'en-US')" . $fieldOperation;
                } else {
                    $whereArray[] = $field . $fieldOperation;
                }
            }
        }

        if (count($whereArray) > 0) {
            $where .= join(" $groupOperation ", $whereArray);
        }

        return $where;
    }
}
