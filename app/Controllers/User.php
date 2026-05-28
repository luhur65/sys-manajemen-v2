<?php

namespace App\Controllers;

use App\Models\MuserModel;
use App\Models\MuserrolesModel;
use CodeIgniter\Controller;

class User extends BaseController
{
    protected $muserModel;

    public function __construct()
    {
        $this->muserModel = new MuserModel();
    }

    public function index()
    {
        $data = [
            'title' => 'System -> User'
        ];
        return $this->render('user/index', $data);
    }

    public function getRoles()
    {
        $db = \Config\Database::connect();
        $roles = $db->table('tblroles')->orderBy('rolename', 'asc')->get()->getResult();
        return $this->response->setJSON($roles);
    }

    public function grid()
    {
        $page = $this->request->getPost('page') ?? 1;
        $limit = $this->request->getPost('rows') ?? 10;
        $sidx = $this->request->getPost('sidx') ?? 1;
        $sord = $this->request->getPost('sord') ?? 'asc';
        $search = $this->request->getPost('_search');
        $filters = $this->request->getPost('filters');

        $where1 = " WHERE 1=1 ";
        $where2 = "";

        if ($search === "true" && !empty($filters)) {
            $parsedFilters = json_decode($filters);
            if (!empty($parsedFilters->rules)) {
                $where2 = " AND (" . $this->operation($filters) . ")";
            }
        }
        $where = $where1 . " " . $where2;

        $countQuery = $this->muserModel->count($where);
        $count = $countQuery->getNumRows();

        if ($count > 0) {
            $total_pages = ceil($count / $limit);
        } else {
            $total_pages = 0;
        }

        if ($page > $total_pages) $page = $total_pages;
        $start = $limit * $page - $limit;
        if ($start < 0) $start = 0;
        if ($limit < 0) $limit = 0;

        $dataQuery = $this->muserModel->get($where, $sidx, $sord, $limit, $start);
        $data = $dataQuery->getResult();

        // Get userpks to fetch roles in bulk
        $userPks = array_map(function($row) { return $row->userpk; }, $data);

        $userRolesMap = [];
        if (!empty($userPks)) {
            $db = \Config\Database::connect();
            $rolesQuery = $db->table('tbluserroles ur')
                             ->select('ur.userpk, r.rolename')
                             ->join('tblroles r', 'ur.roleid = r.roleid', 'left')
                             ->whereIn('ur.userpk', $userPks)
                             ->get()
                             ->getResult();
            foreach ($rolesQuery as $r) {
                if (!isset($userRolesMap[$r->userpk])) {
                    $userRolesMap[$r->userpk] = [];
                }
                $userRolesMap[$r->userpk][] = $r->rolename;
            }
        }

        $response = new \stdClass();
        $response->page = $page;
        $response->total = $total_pages;
        $response->records = $count;
        $response->rows = [];

        $i = 0;
        foreach ($data as $row) {
            // CI3 specific hack
            $rolesForThisUser = isset($userRolesMap[$row->userpk]) ? implode(', ', $userRolesMap[$row->userpk]) : '';
            $row->rolename = $rolesForThisUser;

            if (strtoupper(session()->get('USERNAME') ?? '') != 'ADMIN') {
                if ($row->rolename == 'SUPERADMIN') {
                    continue;
                }
            }

            // We let frontend handle the buttons rendering (as requested in modern grid approaches)
            // But we pass the data needed
            $response->rows[$i]['id'] = $row->userpk;
            $response->rows[$i]['cell'] = [
                $row->userpk, // placeholder for aksi in frontend
                $row->userid,
                $row->username,
                $row->dashboard,
                $row->rolename,
                $row->modifiedby,
                $row->modifiedonview
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
            
            // Map column names for filtering
            if ($fieldName == 'rolename') {
                $fieldName = "r.rolename";
            } else {
                $fieldName = "tbluser." . $fieldName;
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
                if (strpos($fieldName, 'modifiedon') !== false) {
                    $whereArray[] = "FORMAT(tbluser.modifiedon,'dd-MM-yyyy HH:mm:ss')" . $fieldOperation;
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

    public function crud()
    {
        $action = $this->request->getPost('oper');
        $id = $this->request->getPost('id');

        $data = [
            'userid'     => $this->request->getPost('userid'),
            'username'   => $this->request->getPost('username'),
            'password'   => $this->request->getPost('password'),
            'dashboard'  => $this->request->getPost('dashboard'),
            'user_roles' => $this->request->getPost('user_roles')
        ];

        try {
            if ($action == 'add') {
                $status = $this->muserModel->saveUserData($data);
                // We cannot easily return ID because saveUserData doesn't return ID.
                // But wait, the grid will reload anyway.
            } elseif ($action == 'edit') {
                $data['userpk'] = $id;
                if ($data['password'] == '') {
                    unset($data['password']);
                }
                $status = $this->muserModel->saveUserData($data);
            } elseif ($action == 'del') {
                $status = $this->muserModel->delete($id);
            }

            return $this->response->setJSON([
                'status' => $status ? 'sukses' : 'gagal',
                'message' => $status ? '' : json_encode($this->muserModel->errors()) . ' | ' . json_encode($this->muserModel->db->error())
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'gagal',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getById($id)
    {
        $user = $this->muserModel->getByIdUser($id);
        
        if (!empty($user)) {
            $muserroles = new MuserrolesModel();
            $roles = $muserroles->getByUserID($id);
            $user->user_roles = array_column($roles, 'roleid');
        }

        return $this->response->setJSON($user);
    }
}
