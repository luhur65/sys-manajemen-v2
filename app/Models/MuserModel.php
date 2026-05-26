<?php

namespace App\Models;

use CodeIgniter\Model;

// Migrated from CI3: application/models/Muser.php

class MuserModel extends Model
{
    protected $table = 'tbluser';
    protected $primaryKey = 'userpk';
    protected $returnType = 'object';
    protected $allowedFields = ['userid', 'username', 'password', 'dashboard', 'modifiedon', 'modifiedby', 'aktif'];
    protected $useTimestamps = false;

    protected $alias = 'u';
    protected $dbtruck;

    public function __construct()
    {
        parent::__construct();
        $this->dbtruck = \Config\Database::connect('dbtruck');
    }

    public function getList($conditions = [], $count = false, $limit = 0, $offset = 0)
    {
        $table = $this->table;
        $alias = $this->alias;
        $builder = $this->db->table($table . ' as ' . $alias);
        $select = "$alias.userpk, $alias.username, $alias.userid, $alias.password,$alias.dashboard, '' as roles,'' as roles_name";
        if (!empty($conditions)) {
            $builder->where($conditions);
        }
        if (!empty($limit)) {
            $builder->limit($limit, $offset);
        }
        if ($count === true) {
            return $builder->get()->getNumRows();
        } else {
            $builder->select($select);
            return $builder->get()->getResult();
        }
    }

    public function getByIdUser($id)
    {
        $conditions = ['userpk' => $id];
        $user = $this->getList($conditions);
        if (!empty($user)) {
            $user = $user[0];
            return $user;
        }
        return [];
    }

    public function getcabang($userid)
    {
        $cabang = "";
        $sql = $this->dbtruck->query("SELECT FKCabang FROM MMarketingWeb where FKMarketing='" . $userid . "'");
        foreach ($sql->getResult() as $key) {
            $cabang .= $key->FKCabang;
        }
        return $cabang;
    }

    public function delete($id = null, bool $purge = false)
    {
        if ($id) {
            $this->db->table('tbluserroles')->where(['userpk' => $id])->delete();
        }
        return parent::delete($id, $purge);
    }

    public function saveUserData($data)
    {
        $this->db->transStart();
        $data['modifiedon'] = date("Y-m-d H:i:s");
        $data['modifiedby'] = strtoupper(session()->get(SESSION_NAME.'username') ?? '');

        // encrypt the password
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = $this->_hashPassword($data['password']);
        }
        $data['userid'] = strtoupper($data['userid'] ?? '');
        $data['username'] = strtoupper($data['username'] ?? '');

        if (isset($data['userpk']) && !empty($data['userpk'])) {
            $id = $data['userpk'];
            unset($data['userpk']);
            $save = $this->_preFormat($data); // format the fields

            $result = $this->update($id, $save);
            if ($result) {
                if (isset($data['user_roles'])) {
                    $this->saveUserRoles($id, $data['user_roles']);
                }
            } else {
                $this->db->transRollback();
                return false;
            }
        } else {
            $save = $this->_preFormat($data); // format untuk field
            $save['aktif'] = 0;
            $id = $this->insert($save, true);
            if ($id) {
                if (isset($data['user_roles'])) {
                    $this->saveUserRoles($id, $data['user_roles']);
                }
            } else {
                $this->db->transRollback();
                return false;
            }
        }

        if ($this->db->transStatus() === false) {
            $this->db->transRollback();
            return false;
        } else {
            $this->db->transCommit();
            return true;
        }
    }

    public function saveUserRoles($userpk, $roles)
    {
        $muserroles = new \App\Models\MuserrolesModel();
        // Assuming saveBatch is a custom method in MuserrolesModel or we use builder
        $result = $muserroles->db->table('tbluserroles')->insertBatch(array_map(function($role) use ($userpk) {
            return ['userpk' => $userpk, 'roleid' => $role];
        }, (array)$roles));
        return $result;
    }

    private function _preFormat($data)
    {
        $fields = ['userid', 'username', 'password', 'dashboard', 'modifiedon', 'modifiedby', 'aktif'];
        $save = [];
        foreach ($fields as $val) {
            if (isset($data[$val])) {
                $save[$val] = $data[$val];
            }
        }
        return $save;
    }

    public function _hashPassword($password)
    {
        return md5($password);
    }

    public function count($where)
    {
        $sql = "SELECT tbluser.userpk FROM tbluser left join tbluserroles ur on ur.userpk = tbluser.userpk left join tblroles r on r.roleid=ur.roleid  " . $where . " group by tbluser.userpk";
        return $this->db->query($sql);
    }

    public function get($where, $sidx, $sord, $limit, $start)
    {
        $sort = " userid asc ";
        if ($sidx != "1") {
            $sort = " $sidx $sord ";
        }
        $sql = "SELECT tbluser.*,
        FORMAT(tbluser.modifiedon,'dd-MM-yyyy hh:mm:ss') modifiedonview
        FROM tbluser  " . $where . "  ORDER BY $sort
            OFFSET $start ROWS FETCH NEXT $limit ROWS ONLY
        ";
        return $this->db->query($sql);
    }

    public function add($tabel, $data)
    {
        $this->db->table($tabel)->insert($data);
        $query = "SELECT TOP 1 userpk FROM tbluser ORDER BY userpk DESC";
        $sql = $this->db->query($query);
        $userpk = "";
        foreach ($sql->getResult() as $key) {
            $userpk = $key->userpk;
        }
        return $userpk;
    }

    public function edit($tabel, $data, $id)
    {
        return $this->db->table($tabel)->where("userpk", $id)->update($data);
    }

    public function getwhere($userpk)
    {
        $sql = "SELECT TOP 1 * FROM tbluser WHERE userpk ='$userpk'";
        return $this->db->query($sql);
    }

    public function del($tabel, $id)
    {
        return $this->db->table($tabel)->where("userpk", $id)->delete();
    }

    public function getuserpk($userid)
    {
        $userpk = "";
        $sql = $this->db->query("SELECT userpk FROM tbluser where userid='" . $userid . "'");
        foreach ($sql->getResult() as $key) {
            $userpk = $key->userpk;
        }
        return $userpk;
    }
}


