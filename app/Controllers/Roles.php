<?php

namespace App\Controllers;

use App\Models\RolesModel;

class Roles extends BaseController
{
    protected $rolesModel;

    public function __construct()
    {
        $this->rolesModel = new RolesModel();
    }

    public function index()
    {
        $db = \Config\Database::connect();
        // Fetch all acos for the checkboxes
        $acos = $db->table('tblacos')->orderBy('class', 'ASC')->orderBy('method', 'ASC')->get()->getResult();

        $data = [
            'title' => 'Master Roles',
            'acos' => $acos
        ];

        return $this->render('roles/index', $data);
    }

    public function grid()
    {
        $page = $this->request->getPost('page') ?? 1;
        $limit = $this->request->getPost('rows') ?? 10;
        $sidx = $this->request->getPost('sidx') ?? 1;
        $sord = $this->request->getPost('sord') ?? 'asc';
        
        $filters = $this->request->getPost('filters');
        $search = $this->request->getPost('_search');
        
        $where = " WHERE 1=1 ";
        if ($search === "true" && !empty($filters)) {
            $parsedFilters = json_decode($filters);
            if (!empty($parsedFilters->rules)) {
                $where = " WHERE (" . $this->operation($filters) . ")";
            }
        }

        $countQuery = $this->rolesModel->count($where);
        $count = $countQuery->getNumRows();

        if ($count > 0) {
            $total_pages = ceil($count / $limit);
        } else {
            $total_pages = 0;
        }

        if ($page > $total_pages) {
            $page = $total_pages;
        }
        if ($limit < 0) {
            $limit = 0;
        }
        $start = $limit * $page - $limit;
        if ($start < 0) {
            $start = 0;
        }

        $data = $this->rolesModel->get($where, $sidx, $sord, $limit, $start);
        
        $responce = new \stdClass();
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $responce->rows = [];

        $i = 0;
        foreach ($data->getResult() as $row) {
            $responce->rows[$i]['id'] = $row->roleid;
            $responce->rows[$i]['cell'] = array(
                $row->roleid, // placeholder aksi
                $row->rolename,
                $row->modifiedby,
                $row->modifiedonview
            );
            $i++;
        }
        return $this->response->setJSON($responce);
    }

    public function crud()
    {
        $action = $this->request->getPost('oper');
        $id = $this->request->getPost('id');

        try {
            if ($action == 'add' || $action == 'edit') {
                $data = $this->request->getPost();
                
                // Validate duplicate role name
                if ($this->rolesModel->isNameExists($data['rolename'], $id)) {
                    return $this->response->setJSON([
                        'status' => 'gagal',
                        'message' => "Role name '{$data['rolename']}' is already exists."
                    ]);
                }

                if ($action == 'edit') {
                    $data['roleid'] = $id;
                }
                
                $status = $this->rolesModel->saveData($data);
                return $this->response->setJSON([
                    'status' => $status ? 'sukses' : 'gagal',
                    'message' => $status ? '' : json_encode($this->rolesModel->errors())
                ]);
            } elseif ($action == 'del') {
                $status = $this->rolesModel->deleteRole($id);
                return $this->response->setJSON([
                    'status' => $status ? 'sukses' : 'gagal'
                ]);
            }

            return $this->response->setJSON(['status' => 'gagal']);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'gagal',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getById($id)
    {
        $data = $this->rolesModel->getByIdRoles($id);
        
        if (!empty($data)) {
            $data->role_permission = strpos($data->acos, ',') === false ? [$data->acos] : explode(',', $data->acos);
        }

        return $this->response->setJSON($data);
    }

    protected function operation($filters)
    {
        $filters = str_replace('\"', '"', $filters);
        $filters = str_replace('"[', '[', $filters);
        $filters = str_replace(']"', ']', $filters);
        $filters = json_decode($filters);
        $where = " ";
        $whereArray = array();
        $rules = $filters->rules;
        $groupOperation = $filters->groupOp;
        $db = \Config\Database::connect();
        
        foreach ($rules as $rule) {
            $fieldName = $rule->field;
            $fieldData = $db->escapeString($rule->data);
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
                if ($fieldName == "modifiedon") {
                    $whereArray[] = "FORMAT(modifiedon,'dd-MM-yyyy hh:mm:ss')" . $fieldOperation;
                } else if ($fieldName == "modifiedby") {
                    $whereArray[] = "modifiedby" . $fieldOperation;
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
