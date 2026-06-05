<?php

namespace App\Models;

use CodeIgniter\Model;

class RolesModel extends Model
{
    protected $table = 'tblroles';
    protected $primaryKey = 'roleid';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $allowedFields = ['roleid', 'rolename', 'modifiedon', 'modifiedby'];
    protected $useTimestamps = false;

    protected $alias = 'r';

    public function count($where)
    {
        $sql = "SELECT roleid FROM tblroles " . $where;
        return $this->db->query($sql);
    }

    public function get($where, $sidx, $sord, $limit, $start)
    {
        if (trim($where) == '' || trim($where) == 'where 1=1') {
            $cond = " WHERE (rolename <> 'GUEST' AND rolename <> 'SUPERADMIN')";
        } else {
            // Because where might already start with WHERE
            if (stripos(trim($where), 'WHERE') === 0) {
                $cond = " AND (rolename <> 'GUEST' AND rolename <> 'SUPERADMIN') ";
            } else {
                $cond = " WHERE (rolename <> 'GUEST' AND rolename <> 'SUPERADMIN') ";
            }
        }
        $sort = " rolename asc ";
        if ($sidx != "1") {
            $sort = " $sidx $sord ";
        }
        $query = "SELECT *, FORMAT(modifiedon,'dd-MM-yyyy HH:mm:ss') as modifiedonview FROM tblroles " . $where . $cond . " ORDER BY $sort OFFSET $start ROWS FETCH NEXT $limit ROWS ONLY";
        return $this->db->query($query);
    }

    public function getList($conditions = [], $count = false, $limit = 0, $offset = 0)
    {
        $table = $this->table;
        $alias = $this->alias;
        $builder = $this->db->table($table . ' as ' . $alias);
        $select = "$alias.roleid, $alias.rolename, STUFF((
          SELECT ',' + CONVERT(VARCHAR(12),(tblacl.acoid))
          FROM tblacl 
          WHERE tblacl.roleid = $alias.roleid
          FOR XML PATH('')), 1, 1, '') as acos";
          
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

    public function getByIdRoles($id)
    {
        $conditions = ['roleid' => $id];
        $roles = $this->getList($conditions);
        if (!empty($roles)) {
            return $roles[0];
        }
        return [];
    }

    public function isNameExists($name, $id = null)
    {
        $conditions = [$this->alias . '.rolename' => $name];
        if (!empty($id) && is_numeric($id)) {
            $conditions[$this->alias . '.roleid !='] = $id;
        }
        $count = $this->getList($conditions, true);
        if ($count > 0) return true;
        return false;
    }

    public function saveData($data)
    {
        $this->db->transStart();
        $save = [
            'rolename' => strtoupper($data['rolename'] ?? ''),
            'modifiedby' => strtoupper(session()->get('USERNAME') ?? 'SYSTEM'),
            'modifiedon' => date("Y-m-d H:i:s")
        ];

        if (isset($data['roleid']) && !empty($data['roleid'])) {
            $id = $data['roleid'];
            $result = $this->update($id, $save);
            if ($result) {
                $acos = [];
                if (isset($data['role_permission']) && isset($data['role_permission']['acos'])) {
                    $acos = $data['role_permission']['acos'];
                }
                $this->saveRolePermission($id, $acos);
            } else {
                $error = $this->db->error();
                file_put_contents(WRITEPATH . 'logs/roles_update_error.txt', date('Y-m-d H:i:s') . ' - Update Error: ' . print_r($error, true) . "\n", FILE_APPEND);
                $this->db->transRollback();
                return false;
            }
        } else {
            $id = $this->insert($save, true);
            if ($id !== false) {
                $acos = [];
                if (isset($data['role_permission']) && isset($data['role_permission']['acos'])) {
                    $acos = $data['role_permission']['acos'];
                }
                $this->saveRolePermission($id, $acos);
            } else {
                $this->db->transRollback();
                return false;
            }
        }

        if ($this->db->transStatus() === false) {
            $error = $this->db->error();
            file_put_contents(WRITEPATH . 'logs/roles_db_error.txt', date('Y-m-d H:i:s') . ' - DB Error: ' . print_r($error, true) . "\n", FILE_APPEND);
            $this->db->transRollback();
            return false;
        } else {
            $this->db->transCommit();
            return true;
        }
    }

    public function deleteRole($id)
    {
        $this->db->transStart();
        // Delete related
        $this->db->table('tbluserroles')->where(['roleid' => $id])->delete();
        $this->db->table('tblacl')->where(['roleid' => $id])->delete();
        $this->delete($id);
        
        $this->db->transComplete();
        return $this->db->transStatus();
    }

    public function saveRolePermission($roleid, $acos)
    {
        return $this->saveBatchData(['roleid' => $roleid, 'acos' => $acos]);
    }

    protected function getListAll($table, $conditions, $count = false)
    {
        $builder = $this->db->table($table);
        $builder->where($conditions);
        if ($count) {
            return $builder->get()->getResultArray();
        }
        return $builder->get()->getResult();
    }

    protected function getListByGroup($conditions)
    {
        $builder = $this->db->table('tblroles as r');
        $builder->select(
            "DISTINCT(STUFF((
              SELECT ',' + CONVERT(VARCHAR(12), (tblacl.acoid))
              FROM tblacl
              WHERE tblacl.roleid = r.roleid
              FOR XML PATH('')), 1, 1, '')) as acos"
        );
        $builder->where($conditions);
        return $builder->get()->getResultArray();
    }

    public function saveBatchData($data)
    {
        $insert = [];
        if (isset($data['acos'])) {
            $data['acos'] = array_unique($data['acos']);
            $records = $this->getListAll('tblacl', ['roleid' => $data['roleid']], true);
            if (empty($records)) {
                $maxQuery = $this->db->query("SELECT ISNULL(MAX(aclid), 0) as max_id FROM tblacl");
                $maxId = (int)$maxQuery->getRow()->max_id;
                foreach ($data['acos'] as $aco) {
                    $maxId++;
                    try {
                        $this->db->table('tblacl')->insert([
                            'aclid' => $maxId,
                            'roleid' => $data['roleid'],
                            'acoid' => $aco,
                            'modifiedby' => strtoupper(session()->get('USERNAME') ?? 'SYSTEM'),
                            'modifiedon' => date("Y-m-d H:i:s")
                        ]);
                    } catch (\Exception $e) {
                        file_put_contents(WRITEPATH . 'logs/roles_db_error.txt', date('Y-m-d H:i:s') . ' - Insert Error (New): ' . $e->getMessage() . "\n", FILE_APPEND);
                    }
                }
                return true;
            } else {
                $existingAcos = array_column($records, 'acoid');
                
                $inserts = array_diff($data['acos'], $existingAcos);
                $removes = array_diff($existingAcos, $data['acos']);
                
                if (!empty($inserts)) {
                    $maxQuery = $this->db->query("SELECT ISNULL(MAX(aclid), 0) as max_id FROM tblacl");
                    $maxId = (int)$maxQuery->getRow()->max_id;
                    foreach ($inserts as $val) {
                        $maxId++;
                        try {
                            $this->db->table('tblacl')->insert([
                                'aclid' => $maxId,
                                'roleid' => $data['roleid'],
                                'acoid' => $val,
                                'modifiedby' => strtoupper(session()->get('USERNAME') ?? 'SYSTEM'),
                                'modifiedon' => date("Y-m-d H:i:s")
                            ]);
                        } catch (\Exception $e) {
                            file_put_contents(WRITEPATH . 'logs/roles_db_error.txt', date('Y-m-d H:i:s') . ' - Insert Error: ' . $e->getMessage() . "\n", FILE_APPEND);
                        }
                    }
                }
                
                if (!empty($removes)) {
                    foreach ($removes as $val) {
                        $this->db->table('tblacl')
                                 ->where(['roleid' => $data['roleid'], 'acoid' => $val])
                                 ->delete();
                    }
                }
               
                return true;
            }
        }
        return false;
    }
}
