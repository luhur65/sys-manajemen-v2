<?php

namespace App\Controllers;

use App\Models\MlogModel;
use App\Models\UserAclModel;

class UserAcl extends BaseController
{
    /**
     * Whitelist kolom filter grid jqGrid (tabel tbluseracl).
     * Kolom di luar daftar ini ditolak oleh GridFilter.
     */
    protected array $filterFields = [
        'useraclid',
        'userpk',
        'acoid',
        'modifiedby',
        'modifiedon' => "FORMAT(modifiedon, 'dd-MM-yyyy hh:mm:ss')",
        'modifiedonview' => "FORMAT(modifiedon, 'dd-MM-yyyy hh:mm:ss')",
    ];

    /**
     * Whitelist kolom filter untuk grid daftar ACO (tabel tblacos).
     */
    private const FILTER_FIELDS_ACOS = [
        'acosid',
        'class',
        'method',
        'displayname',
    ];

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

            // Daftar ACO lama dibaca lebih dulu: saveRolePermission menghapus
            // seluruh baris milik user ini sebelum menulis yang baru, jadi
            // sesudahnya tidak ada lagi cara mengetahui hak apa yang dicabut.
            $sebelum = $this->userAclModel->getByIdUser($userpk);

            $save = $this->userAclModel->saveData($postData);
            $status = "batal";
            if ($save) {
                $status = "sukses";

                // M-07: ini perubahan hak akses per-user — persis jenis perubahan
                // yang paling perlu bisa ditelusuri belakangan.
                $this->auditLog(MlogModel::DATA_UPDATE, 'Ubah hak akses (ACL) user', [
                    'tabel'  => 'tbluseracl',
                    'userpk' => $userpk,
                    'acos'   => [
                        'dari' => is_object($sebelum) ? ($sebelum->acos ?? null) : null,
                        'ke'   => $postData['role_permission']['acos'] ?? [],
                    ],
                ]);
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

        $operation = $search === "true" ? $this->operationAll($filters) : '';
        if ($operation !== '') {
            $where2 = " AND (" . $operation . ")";
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
        $page = $this->request->getPost('page') ?? $this->request->getGet('page') ?? 1;
        $limit = $this->request->getPost('rows') ?? $this->request->getGet('rows') ?? 50;
        $sidx = $this->request->getPost('sidx') ?? $this->request->getGet('sidx');
        $sord = $this->request->getPost('sord') ?? $this->request->getGet('sord') ?? 'asc';
        
        $filters = $this->request->getPost('filters') ?? $this->request->getGet('filters');
        $search = $this->request->getPost('_search') ?? $this->request->getGet('_search');
        
        $db = \Config\Database::connect();
        $builder = $db->table('tblacos');

        $whereCondition = $search === "true" ? $this->operationAll($filters, self::FILTER_FIELDS_ACOS) : '';
        if ($whereCondition !== '') {
            // Kondisi sudah lolos whitelist dan di-escape oleh GridFilter.
            $builder->where($whereCondition, null, false);
        }

        $countBuilder = clone $builder;
        $count = $countBuilder->countAllResults(false);

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

        if (!empty($sidx)) {
            $builder->orderBy($sidx, $sord);
        } else {
            $builder->orderBy('class', 'ASC')->orderBy('method', 'ASC');
        }

        $acos = $builder->limit($limit, $start)->get()->getResult();
        
        $responce = new \stdClass();
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $responce->rows = [];
        
        $i = 0;
        foreach ($acos as $aco) {
            $className = trim($aco->class ?? '');
            $methodName = trim($aco->method ?? '');
            $displayName = trim($aco->displayname ?? '');
            
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
}
