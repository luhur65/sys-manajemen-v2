<?php

namespace App\Controllers;

use App\Models\MovertopModel;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

// Migrated from CI3: application/controllers/piutangemkl.php

class Piutangemkl extends BaseController
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

        $operation = trim($this->operationAll($filters));

        if ($operation != '') {
            $where = " AND ($operation)";
        }

        // Logic filter tambahan dari view (Jenis Job & Titipan)
        $jnsjob = $this->request->getPost('jnsjob');
        $jnstitipan = $this->request->getPost('isTitipan');

        if ($jnsjob && $jnsjob != 'A') {
            $where .= " AND FJnsJob LIKE '$jnsjob%'";
        }
        if ($jnstitipan == '1') {
            $where .= " AND FJnsPiutang = 'TITIPAN'";
        } else if ($jnstitipan == '2') {
            $where .= " AND (FJnsPiutang = '' OR FJnsPiutang IS NULL)";
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
        
        $responce = new \stdClass();
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $i = 0;
        foreach ($data->getResult() as $row) {
            $responce->rows[$i]['id']   = $row->FNTrans;
            $responce->rows[$i]['cell'] = array(
                $row->FTgl,
                $row->FNTrans,
                $row->FNInvoice,
                $row->FNShipper,
                $row->FNominal,
                $row->FSisa,
                $row->FTOP,
                $row->FTglJT,
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
        $tgllast = $this->movertopModel->get_tglupdate($cabangid);
        $hasil = "";
        foreach ($tgllast as $key) {
            $hasil = $key->FlastUpdate;
        }
        return $hasil;
    }

    protected function operationAll($filters)
    {
        if (empty($filters)) return " ";

        $filters = str_replace('\"', '"', $filters);
        $filters = str_replace('"[', '[', $filters);
        $filters = str_replace(']"', ']', $filters);
        $filters = json_decode($filters);

        if (!$filters) return '';

        if (
            !isset($filters->rules) ||
            empty($filters->rules)
        ) {
            return '';
        }

        $where = " ";
        $whereArray = array();
        $rules = $filters->rules;
        $groupOperation = $filters->groupOp;

        foreach ($rules as $rule) {
            $fieldName = $rule->field;
            $fieldData = $rule->data;

            // Handle date fields to match UI format (dd-MMM-yyyy)
            if ($fieldName == 'FTgl' || $fieldName == 'FTglJT') {
                $fieldName = "UPPER(FORMAT($fieldName, 'dd-MMM-yyyy'))";
            }

            // Handle numeric fields: Strip commas from input and cast column to string for LIKE search
            if ($fieldName == 'FNominal' || $fieldName == 'FSisa') {
                $fieldData = str_replace(',', '', $fieldData);
                $fieldName = "CAST($fieldName AS VARCHAR)";
            }

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
                case "ni": $fieldOperation = " NOT IN '" . $fieldData . "'"; break;
                case "bw": $fieldOperation = " LIKE '" . $fieldData . "%'"; break;
                case "bn": $fieldOperation = " NOT LIKE '" . $fieldData . "%'"; break;
                case "ew": $fieldOperation = " LIKE '%" . $fieldData . "'"; break;
                case "en": $fieldOperation = " NOT LIKE '%" . $fieldData . "'"; break;
                case "cn": $fieldOperation = " LIKE '%" . $fieldData . "%'"; break;
                case "nc": $fieldOperation = " NOT LIKE '%" . $fieldData . "%'"; break;
                default: $fieldOperation = ""; break;
            }

            if ($fieldOperation != "") {
                $whereArray[] = $fieldName . $fieldOperation;
            }
        }

        if (count($whereArray) > 0) {
            return join(" " . $groupOperation . " ", $whereArray);
        }

        return '';
    }
}
