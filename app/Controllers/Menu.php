<?php
namespace App\Controllers;

use App\Models\MmenuModel;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Menu extends BaseController
{
    protected MmenuModel $mmenuModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->mmenuModel = new MmenuModel();
    }

    public function index()
    {
        $data['title'] = 'Master Menu';
        return $this->render('menu/index', $data);
    }

    public function grid()
    {
        $page = $this->request->getPost('page') ?: 1;
        $limit = $this->request->getPost('rows') ?: 10;
        $sidx = $this->request->getPost('sidx') ?: 'menuid';
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

        $sql = $this->mmenuModel->count($where);
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

        $data = $this->mmenuModel->get($where, $sidx, $sord, $limit, $start);
        
        $responce = new \stdClass();
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $i = 0;
        foreach ($data->getResult() as $row) {
            $route = '-';
            if ($row->acoid != 0) {
                // lookup route
                $acoQuery = $this->mmenuModel->db->query("SELECT * FROM tblacos WHERE acosid = " . $this->mmenuModel->db->escape($row->acoid));
                if ($acoQuery->getNumRows() > 0) {
                    $aco = $acoQuery->getRow();
                    $route = $aco->class . "/" . $aco->method;
                }
            }

            $responce->rows[$i]['id']   = $row->menuid;
            $responce->rows[$i]['cell'] = array(
                $row->menuid,
                $row->menuname,
                $row->menuseq,
                $row->menuparent,
                $row->menuicon,
                $route,
                $row->link,
                $row->modifiedby,
                $row->modifiedonview
            );
            $i++;
        }
        return $this->response->setJSON($responce);
    }

    public function lookupAco()
    {
        $page = $this->request->getPost('page') ?: 1;
        $limit = $this->request->getPost('rows') ?: 10;
        $sidx = $this->request->getPost('sidx') ?: 'acosid';
        $sord = $this->request->getPost('sord') ?: 'asc';

        $filters = $this->request->getPost('filters');
        $search = $this->request->getPost('_search');
        $where = " WHERE 1=1 ";

        if ($search == "true" && !empty($filters)) {
            $operation = $this->operation($filters);
            if (!empty($operation)) {
                $where .= " AND (" . $operation . ")";
            }
        }

        $sql = $this->mmenuModel->countAcos($where);
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

        $data = $this->mmenuModel->getAcos($where, $sidx, $sord, $limit, $start);
        
        $responce = new \stdClass();
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $i = 0;
        foreach ($data->getResult() as $row) {
            $responce->rows[$i]['id']   = $row->acosid;
            $responce->rows[$i]['cell'] = array(
                $row->acosid,
                $row->class,
                $row->method,
                $row->displayname
            );
            $i++;
        }
        return $this->response->setJSON($responce);
    }

    public function crud()
    {
        $action = $this->request->getPost('action');
        $id = $this->request->getPost('menuid');

        $data = [
            'menuname'   => $this->request->getPost('menuname'),
            'menuseq'    => $this->request->getPost('menuseq'),
            'menuparent' => $this->request->getPost('menuparent'),
            'menuicon'   => $this->request->getPost('menuicon'),
            'acoid'      => empty($this->request->getPost('acoid')) ? 0 : $this->request->getPost('acoid'),
            'link'       => empty($this->request->getPost('link')) ? '' : $this->request->getPost('link'),
            'menuexe'    => '',
            'modifiedby' => session()->get('USERNAME') ?? 'SYSTEM',
            'modifiedon' => date('Y-m-d H:i:s')
        ];

        try {
            if ($action == 'add') {
                $maxIdQuery = $this->mmenuModel->db->query("SELECT ISNULL(MAX(menuid), 0) + 1 as new_id FROM tblmenu");
                $data['menuid'] = $maxIdQuery->getRow()->new_id;
                $status = $this->mmenuModel->insert($data);
                $id = $data['menuid'];
            } elseif ($action == 'edit') {
                $status = $this->mmenuModel->update($id, $data);
            } elseif ($action == 'delete') {
                $status = $this->mmenuModel->delete($id);
            }

            return $this->response->setJSON([
                'status' => $status ? 'sukses' : 'gagal',
                'id' => $id
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
        $data = $this->mmenuModel->find($id);
        
        $route = '';
        if ($data && $data->acoid != 0) {
            $acoQuery = $this->mmenuModel->db->query("SELECT * FROM tblacos WHERE acosid = " . $this->mmenuModel->db->escape($data->acoid));
            if ($acoQuery->getNumRows() > 0) {
                $aco = $acoQuery->getRow();
                $route = $aco->class . "/" . $aco->method;
            }
        }

        return $this->response->setJSON([
            'status' => 'sukses',
            'data'   => $data,
            'route'  => $route
        ]);
    }

    private function operation($filters)
    {
        if (empty($filters)) return "";
        $filters = str_replace('\"', '"', $filters);
        $filters = str_replace('"[', '[', $filters);
        $filters = str_replace(']"', ']', $filters);
        $filters = json_decode($filters);
        
        $whereArray = array();
        if (empty($filters->rules)) return "";
        
        $rules = $filters->rules;
        $groupOperation = $filters->groupOp;
        
        foreach ($rules as $rule) {
            $fieldName = $rule->field;
            $fieldData = $this->mmenuModel->escapeString($rule->data);
            $fieldOperation = "";
            
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
            }
            if ($fieldOperation != "") {
                if ($fieldName == "modifiedon") {
                    $whereArray[] = "FORMAT(modifiedon,'dd-MM-yyyy HH:mm:ss')" . $fieldOperation;
                } else {
                    $whereArray[] = $fieldName . $fieldOperation;
                }
            }
        }

        if (count($whereArray) > 0) {
            return join(" " . $groupOperation . " ", $whereArray);
        } else {
            return "";
        }
    }

    public function reseq()
    {
        try {
            $query = "SELECT DISTINCT(menuparent) FROM tblmenu ORDER BY menuparent ASC";
            $sql = $this->mmenuModel->db->query($query);
            foreach ($sql->getResult() as $key) {
                $query2="SELECT menuid FROM tblmenu WHERE menuparent=" . $this->mmenuModel->db->escape($key->menuparent) . " ORDER BY menuseq ASC";
                $sql2 = $this->mmenuModel->db->query($query2);
                $i=0;
                foreach ($sql2->getResult() as $row) {
                    $i += 10;
                    $query3 = "UPDATE tblmenu SET menuseq=$i WHERE menuid=" . $this->mmenuModel->db->escape($row->menuid);
                    $this->mmenuModel->db->query($query3);
                }
            }
            return $this->response->setJSON(['status' => 'sukses']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'gagal', 'message' => $e->getMessage()]);
        }
    }
}
