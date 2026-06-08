<?php
namespace App\Controllers;

use App\Models\MparameterModel;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Parameter extends BaseController
{
    protected MparameterModel $mparameterModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->mparameterModel = new MparameterModel();
    }

    public function index()
    {
        $data['title'] = 'Master Parameter';
        return $this->render('parameter/index', $data);
    }

    public function grid()
    {
        $page = $this->request->getPost('page') ?: 1;
        $limit = $this->request->getPost('rows') ?: 10;
        $sidx = $this->request->getPost('sidx') ?: 'parameter_key';
        $sord = $this->request->getPost('sord') ?: 'asc';

        $totalrows = $this->request->getPost('totalrows');
        if ($totalrows) {
           $limit = $totalrows;
        }

        $filters = $this->request->getPost('filters');
        $search = $this->request->getPost('_search');
        $where = " WHERE 1=1 ";

        if ($search == "true") {
            $operation = $this->operation($filters);
            if (!empty($operation)) {
                $where .= " AND (" . $operation . ")";
            }
        }

        if (!empty($filters)) {
            $filterDecoded = json_decode($filters);
            if (!empty($filterDecoded->rules)) {
                $where .= " AND (" . $this->operation($filters) . ")";
            }
        }

        $sql = $this->mparameterModel->count($where);
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

        $data = $this->mparameterModel->get($where, $sidx, $sord, $limit, $start);
        
        $responce = new \stdClass();
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $i = 0;
        foreach ($data->getResult() as $row) {
            $responce->rows[$i]['id']   = $row->parameter_key;
            $responce->rows[$i]['cell'] = array(
                $row->parameter_key,
                $row->parametergrpid,
                $row->parameterid,
                $row->parametertext,
                $row->parametermemo,
                $row->modifiedby,
                $row->modifiedonview
            );
            $i++;
        }
        return $this->response->setJSON($responce);
    }

    public function crud()
    {
        $action = $this->request->getPost('action');
        $id = $this->request->getPost('parameter_key');

        $data = [
            'parametergrpid' => strtoupper($this->request->getPost('parametergrpid')),
            'parameterid'    => strtoupper($this->request->getPost('parameterid')),
            'parametertext'  => strtoupper($this->request->getPost('parametertext')),
            'parametermemo'  => strtoupper($this->request->getPost('parametermemo')),
            'modifiedby'     => session()->get('USERNAME') ?? 'SYSTEM',
            'modifiedon'     => date('Y-m-d H:i:s')
        ];

        if ($action == 'add') {
            $maxQuery = $this->mparameterModel->db->query("SELECT ISNULL(MAX(parameter_key), 0) as max_id FROM tblparameter");
            $data['parameter_key'] = $maxQuery->getRow()->max_id + 1;
            $status = $this->mparameterModel->insert($data);
            $id = $data['parameter_key'];
        } elseif ($action == 'edit') {
            $status = $this->mparameterModel->update($id, $data);
        } elseif ($action == 'delete') {
            $status = $this->mparameterModel->delete($id);
        }

        return $this->response->setJSON([
            'status' => $status ? 'sukses' : 'gagal',
            'id' => $id
        ]);
        // if ($this->request->isAJAX()) {
        // }
    }

    public function getById()
    {
        $id = $this->request->getPost('id');
        $data = $this->mparameterModel->getById($id);
        return $this->response->setJSON($data);
    }

    protected function operation($filters)
    {
        if (empty($filters)) return " 1=1 ";
        $filters = str_replace('\"', '"', $filters);
        $filters = str_replace('"[', '[', $filters);
        $filters = str_replace(']"', ']', $filters);
        $filters = json_decode($filters);
        
        $whereArray = array();
        
        if (!isset($filters->rules)) return " 1=1 ";
        
        $rules = $filters->rules;
        $groupOperation = $filters->groupOp;
        
        foreach ($rules as $rule) {
            $fieldName = $rule->field;
            $fieldData = addslashes($rule->data);

            switch ($rule->op) {
                case "eq": $fieldOperation = " = '".$fieldData."'"; break;
                case "ne": $fieldOperation = " != '".$fieldData."'"; break;
                case "lt": $fieldOperation = " < '".$fieldData."'"; break;
                case "gt": $fieldOperation = " > '".$fieldData."'"; break;
                case "le": $fieldOperation = " <= '".$fieldData."'"; break;
                case "ge": $fieldOperation = " >= '".$fieldData."'"; break;
                case "nu": $fieldOperation = " = ''"; break;
                case "nn": $fieldOperation = " != ''"; break;
                case "in": $fieldOperation = " IN (".$fieldData.")"; break;
                case "ni": $fieldOperation = " NOT IN '".$fieldData."'"; break;
                case "bw": $fieldOperation = " LIKE '".$fieldData."%'"; break;
                case "bn": $fieldOperation = " NOT LIKE '".$fieldData."%'"; break;
                case "ew": $fieldOperation = " LIKE '%".$fieldData."'"; break;
                case "en": $fieldOperation = " NOT LIKE '%".$fieldData."'"; break;
                case "cn": $fieldOperation = " LIKE '%".$fieldData."%'"; break;
                case "nc": $fieldOperation = " NOT LIKE '%".$fieldData."%'"; break;
                default: $fieldOperation = ""; break;
            }
            
            if ($fieldOperation != "") {
                if ($fieldName == "modifiedon") {
                    $whereArray[] = "FORMAT(modifiedon,'dd-MM-yyyy HH:mm:ss')".$fieldOperation;
                } else {
                    $whereArray[] = $fieldName.$fieldOperation;
                }
            }
        }

        if (count($whereArray) > 0) {
            return join(" ".$groupOperation." ", $whereArray);
        } else {
            return " 1=1 ";
        }
    }
}
