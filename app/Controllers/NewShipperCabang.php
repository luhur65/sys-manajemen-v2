<?php

namespace App\Controllers;

use App\Models\MshippernewModel;

class NewShipperCabang extends BaseController
{
    protected $mshippernewModel;

    public function __construct()
    {
        $this->mshippernewModel = new MshippernewModel();
    }

    public function mks()
    {
        $data = [
            'menuaktif' => 'Laporan Shipper Baru'
        ];
        return $this->render('newshippercabang/mks/index', $data);
    }

    public function getGridDataMks()
    {
        $page = $this->request->getPost('page') ?: 1;
        $limit = $this->request->getPost('rows') ?: 50;
        $sidx = $this->request->getPost('sidx') ?: 'FTgl';
        $sord = $this->request->getPost('sord') ?: 'desc';
        $search = $this->request->getPost('_search');
        $filters = $this->request->getPost('filters');
        
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
        if ($search == "true" && !empty($filters)) {
            $operation = trim($this->operation($filters));
            if (!empty($operation)) {
                $where = "(" . $operation . ")";
            }
        }

        $offset = ($page - 1) * $limit;
        
        $countResult = $this->mshippernewModel->getList('MAKASSAR', $dateFilters, $where, 0, 0, $sidx, $sord, true);
        $total_pages = 0;
        if ($countResult > 0) {
            $total_pages = ceil($countResult / $limit);
        }
        if ($page > $total_pages) {
            $page = $total_pages;
        }

        $dataResult = $this->mshippernewModel->getList('MAKASSAR', $dateFilters, $where, $limit, $offset, $sidx, $sord, false);

        $response = new \stdClass();
        $response->page = $page;
        $response->total = $total_pages;
        $response->records = $countResult;
        $response->rows = [];

        $i = 0;
        foreach ($dataResult as $row) {
            $response->rows[$i]['id'] = $row['FNShipper'];
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
        $filters = str_replace('\"', '"', $filters);
        $filters = str_replace('"[', '[', $filters);
        $filters = str_replace(']"', ']', $filters);
        $filters = json_decode($filters);
        $where = " ";
        $whereArray = [];
        $rules = $filters->rules;
        $groupOperation = $filters->groupOp;
        foreach ($rules as $rule) {
            $fieldName = $rule->field;
            $fieldData = str_replace("'", "''", $rule->data); // escape string

            switch ($rule->op) {
                case "eq": $fieldOperation = " = '" . $fieldData . "'"; break;
                case "ne": $fieldOperation = " != '" . $fieldData . "'"; break;
                case "lt": $fieldOperation = " < '" . $fieldData . "'"; break;
                case "gt": $fieldOperation = " > '" . $fieldData . "'"; break;
                case "le": $fieldOperation = " <= '" . $fieldData . "'"; break;
                case "ge": $fieldOperation = " >= '" . $fieldData . "'"; break;
                case "nu": $fieldOperation = " = ''"; break;
                case "nn": $fieldOperation = " != ''"; break;
                case "in": $fieldOperation = " IN (" . $fieldData . ")"; break;
                case "ni": $fieldOperation = " NOT IN ('" . $fieldData . "')"; break;
                case "bw": $fieldOperation = " LIKE '" . $fieldData . "%'"; break;
                case "bn": $fieldOperation = " NOT LIKE '" . $fieldData . "%'"; break;
                case "ew": $fieldOperation = " LIKE '%" . $fieldData . "'"; break;
                case "en": $fieldOperation = " NOT LIKE '%" . $fieldData . "'"; break;
                case "cn": $fieldOperation = " LIKE '%" . $fieldData . "%'"; break;
                case "nc": $fieldOperation = " NOT LIKE '%" . $fieldData . "%'"; break;
                default: $fieldOperation = ""; break;
            }

            if ($fieldOperation != "") {
                if (strtoupper($fieldName) === 'FTGL') {
                    $whereArray[] = "FORMAT(CAST(" . $fieldName . " AS DATETIME), 'dd-MMM-yyyy', 'en-US')" . $fieldOperation;
                } else {
                    $whereArray[] = $fieldName . $fieldOperation;
                }
            }
        }
        if (count($whereArray) > 0) {
            $where .= join(" " . $groupOperation . " ", $whereArray);
        } else {
            $where = " ";
        }
        return $where;
    }
}
