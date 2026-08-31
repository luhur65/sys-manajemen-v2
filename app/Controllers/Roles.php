<?php

namespace App\Controllers;

use App\Models\MlogModel;
use App\Models\RolesModel;

class Roles extends BaseController
{
    /**
     * Whitelist kolom filter grid jqGrid (tabel tblroles).
     * Kolom di luar daftar ini ditolak oleh GridFilter.
     */
    protected array $filterFields = [
        'roleid',
        'rolename',
        'modifiedby',
        'modifiedon' => "FORMAT(modifiedon, 'dd-MM-yyyy hh:mm:ss')",
        'modifiedonview' => "FORMAT(modifiedon, 'dd-MM-yyyy hh:mm:ss')",
    ];

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
        $operation = $search === "true" ? $this->operationAll($filters) : '';
        if ($operation !== '') {
            $where = " WHERE (" . $operation . ")";
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

                $sebelum = null;

                if ($action == 'edit') {
                    // Nilai lama harus dibaca sebelum saveData menimpanya.
                    $sebelum        = $this->rolesModel->getByIdRoles($id);
                    $data['roleid'] = $id;
                }

                $status = $this->rolesModel->saveData($data);
                $dbError = $this->rolesModel->db->error();
                $lastErrorMsg = $this->rolesModel->lastErrorMsg ?? '';

                if ($status) {
                    // M-07: role menentukan siapa boleh membuka apa. Perubahan di
                    // sini berdampak jauh lebih luas daripada satu baris data,
                    // jadi wajib meninggalkan jejak.
                    $this->auditLog(
                        $action == 'edit' ? MlogModel::DATA_UPDATE : MlogModel::DATA_CREATE,
                        $action == 'edit' ? 'Ubah role' : 'Tambah role',
                        [
                            'tabel'      => 'tblroles',
                            'roleid'     => $id,
                            'rolename'   => $data['rolename'] ?? null,
                            'perubahan'  => $action == 'edit' ? MlogModel::changes($sebelum, $data) : $data,
                        ]
                    );
                }

                return $this->response->setJSON([
                    'status' => $status ? 'sukses' : 'gagal',
                    'message' => $status ? '' : ($lastErrorMsg ?: ($dbError['message'] ?? json_encode($this->rolesModel->errors())))
                ]);
            } elseif ($action == 'del') {
                $sebelum = $this->rolesModel->getByIdRoles($id);
                $status  = $this->rolesModel->deleteRole($id);

                if ($status) {
                    $this->auditLog(MlogModel::DATA_DELETE, 'Hapus role', [
                        'tabel'   => 'tblroles',
                        'roleid'  => $id,
                        'sebelum' => $sebelum,
                    ]);
                }

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

    public function test_roles()
    {
        $data = [
            'roleid' => 3,
            'rolename' => 'ADMIN',
            'role_permission' => [
                'acos' => [59, 20, 22, 21, 18, 23, 19, 40, 39, 47, 46, 48, 49, 51, 50, 52, 53, 44, 45, 3, 5, 4, 6, 1, 2, 43, 56, 9, 11, 10, 7, 8, 25, 30, 26, 24, 33, 32, 35, 36, 37, 38, 14, 16, 15, 12, 13, 17]
            ]
        ];
        $status = $this->rolesModel->saveData($data);
        $dbError = $this->rolesModel->db->error();
        echo "Status: " . ($status ? 'sukses' : 'gagal') . "\n";
        echo "DB Error: ";
        print_r($dbError);
    }
}
