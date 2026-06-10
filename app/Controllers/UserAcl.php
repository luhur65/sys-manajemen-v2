<?php

namespace App\Controllers;

use App\Models\UserAclModel;

class UserAcl extends BaseController
{
    protected $userAclModel;

    public function __construct()
    {
        $this->userAclModel = new UserAclModel();
    }

    public function index()
    {
        $userpk = $this->request->getGet('userpk');
        if (empty($userpk)) {
            return "Empty userpk";
        }
        
        $data['userpk'] = $userpk;
        return view('useracl/index', $data);
    }

    public function userroles($userpk)
    {
        $db = \Config\Database::connect();
        
        if ($this->request->getMethod() == 'post' || $this->request->getMethod() == 'POST') {
            $postData = $this->request->getPost();
            $postData['userpk'] = $userpk;
            
            $save = $this->userAclModel->saveData($postData);
            $status = "batal";
            if ($save) {
                $status = "sukses";
            }
            return $this->response->setJSON(['status' => $status]);
        } else {
            $acos = $db->table('tblacos')->orderBy('class', 'ASC')->orderBy('method', 'ASC')->get()->getResult();
            
            $builder = $db->table('tblroles as r');
            $builder->select("r.roleid, r.rolename, STUFF((
                SELECT ',' + CONVERT(VARCHAR(12), (tblacl.acoid))
                FROM tblacl 
                WHERE tblacl.roleid = r.roleid
                FOR XML PATH('')), 1, 1, '') as acos");
            $roles = $builder->get()->getResult();
            
            // Get user's current acls
            $data = $this->userAclModel->getByIdUser($userpk);
            
            return view('useracl/form', [
                'data' => $data, 
                'acos' => $acos, 
                'roles' => $roles,
                'userpk' => $userpk
            ]);
        }
    }

    public function grid($userpk)
    {
        $page = $this->request->getPost('page') ?? 1;
        $limit = $this->request->getPost('rows') ?? 10;
        $sidx = $this->request->getPost('sidx') ?? 1;
        $sord = $this->request->getPost('sord') ?? 'asc';
        
        $filters = $this->request->getPost('filters');
        $search = $this->request->getPost('_search');
        
        $db = \Config\Database::connect();
        $where1 = " WHERE userpk = " . $db->escapeString($userpk);
        $where2 = "";

        if ($search === "true" && !empty($filters)) {
            $parsedFilters = json_decode($filters);
            if (!empty($parsedFilters->rules)) {
                $where2 = " AND (" . $this->operation($filters) . ")";
            }
        }
        $where = $where1 . " " . $where2;

        $countQuery = $this->userAclModel->count($where);
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

        $data = $this->userAclModel->get($where, $sidx, $sord, $limit, $start);
        
        $responce = new \stdClass();
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $responce->allData = $this->userAclModel->get($where, $sidx, $sord, $limit, $start)->getResult();
        $responce->rows = [];

        $i = 0;
        foreach ($data->getResult() as $row) {
            $db = \Config\Database::connect();
            $routeQuery = $db->table('tblacos')->where('acosid', $row->acoid)->get()->getResult();
            $route = count($routeQuery) > 0 ? $routeQuery[0] : '-';
            
            $row->acoid = $route != '-' ? $route->class . "/" . $route->method : $route;
            
            $responce->rows[$i]['id'] = $row->useraclid;
            $responce->rows[$i]['cell'] = array(
                $row->acoid,
                $row->modifiedby,
                $row->modifiedonview
            );
            $i++;
        }
        return $this->response->setJSON($responce);
    }

    public function getAcos()
    {
        $db = \Config\Database::connect();
        $acos = $db->table('tblacos')->orderBy('class', 'ASC')->orderBy('method', 'ASC')->get()->getResult();
        
        $responce = new \stdClass();
        $responce->page = 1;
        $responce->total = 1;
        $responce->records = count($acos);
        $responce->rows = [];
        
        $i = 0;
        foreach ($acos as $aco) {
            $className = trim($aco->class ?? '');
            $methodName = trim($aco->method ?? '');
            $displayName = trim($aco->display_name ?? '');
            
            if ($className === '') {
                $className = $displayName !== '' ? '[MENU] ' . $displayName : '[PARENT MENU / SEPARATOR]';
            }
            if ($methodName === '') {
                $methodName = '-';
            }
            
            $responce->rows[$i]['id'] = $aco->acosid;
            $responce->rows[$i]['cell'] = array(
                $aco->acosid,
                $className,
                $methodName,
                $displayName
            );
            $i++;
        }
        
        return $this->response->setJSON($responce);
    }
    
    // Adapted from old CI3 operation
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
