<?php

namespace App\Models;

use CodeIgniter\Model;

class UserAclModel extends Model
{
    protected $table = 'tbluseracl';
    protected $primaryKey = 'useraclid';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $allowedFields = ['useraclid', 'userpk', 'acoid', 'modifiedby', 'modifiedon'];
    protected $useTimestamps = false;
    protected $alias = 'ua';

    public function count($where)
    {
        $db = \Config\Database::connect();
        $query = $db->query("SELECT * FROM tbluseracl " . $where);
        return $query;
    }

    public function getList($conditions = [], $count = false, $limit = 0, $offset = 0)
    {
        $table = "tbluser";
        $alias = "u";
        $builder = $this->db->table($table . ' as ' . $alias);
        
        $select = "$alias.userpk, STUFF((
          SELECT ',' + CONVERT(VARCHAR(12), (tbluseracl.acoid))
          FROM tbluseracl 
          WHERE tbluseracl.userpk = $alias.userpk
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

    public function getByUserPK($userpk)
    {
        if (empty($userpk)) {
            return [];
        }
        
        $builder = $this->db->table($this->table . ' as ' . $this->alias);
        $builder->distinct();
        $builder->select('acos.class, acos.method, acos.display_name');
        
        if (is_array($userpk)) {
            $builder->whereIn('userpk', $userpk);
        } else {
            $builder->where('userpk', $userpk);
        }
        
        $builder->join('tblacos as acos', 'acos.acosid = ' . $this->alias . '.acoid', 'inner');
        return $builder->get()->getResultArray();
    }

    public function getByIdUser($id)
    {
        $conditions = [
            'u.userpk' => $id
        ];
        $data = $this->getList($conditions);
        if (!empty($data)) {
            $data = $data[0];
        }
        return $data;
    }

    public function saveData($data)
    {
        $this->db->transStart();
        if (isset($data['userpk']) && !empty($data['userpk'])) {
            $id = $data['userpk'];
            $acos = [];
            if (isset($data['role_permission']) && isset($data['role_permission']['acos'])) {
                $acos = $data['role_permission']['acos'];
            }
            $this->saveRolePermission($id, $acos);
        }
        
        if ($this->db->transStatus() === false) {
            $this->db->transRollback();
            return false;
        } else {
            $this->db->transCommit();
            return true;
        }
    }

    public function saveRolePermission($userpk, $acos)
    {
        $result = $this->saveBatchData(['userpk' => $userpk, 'acos' => $acos]);
        return $result;
    }

    public function getListAll($table, $conditions, $count = false)
    {
        $builder = $this->db->table($table);
        $builder->where($conditions);
        if($count) {
            return $builder->get()->getResultArray();
        }
        return $builder->get()->getResult();
    }

    public function saveBatchData($data)
    {
        if (isset($data['acos']) && is_array($data['acos'])) {
            // Delete all existing ACLs for this user
            $this->db->table('tbluseracl')->where('userpk', $data['userpk'])->delete();
            
            $insert = [];
            foreach ($data['acos'] as $aco) {
                if (!empty($aco)) {
                    $insert[] = [
                        'userpk' => $data['userpk'],
                        'acoid' => $aco,
                        'modifiedby' => strtoupper(session()->get('username') ?? 'SYSTEM'),
                        'modifiedon' => date("Y-m-d H:i:s")
                    ];
                }
            }
            
            if (count($insert) > 0) {
                $this->db->table('tbluseracl')->insertBatch($insert);
            }
            return true;
        }
        return false;
    }

    protected function getListByGroup($conditions)
    {
        $builder = $this->db->table($this->table . ' as ' . $this->alias);
        $builder->select(
            "DISTINCT(STUFF((
              SELECT ',' + CONVERT(VARCHAR(12), ({$this->table}.acoid))
              FROM {$this->table}
              WHERE {$this->table}.userpk = {$this->alias}.userpk
              FOR XML PATH('')), 1, 1, '')) as acos"
        );
        $builder->where($conditions);
        return $builder->get()->getResultArray();
    }

    public function get($where, $sidx, $sord, $limit, $start)
    {
        $query = "SELECT * ,FORMAT(modifiedon,'dd-MM-yyyy hh:mm:ss') as modifiedonview 
                  FROM tbluseracl " . $where . " 
                  ORDER BY $sidx $sord 
                  OFFSET $start ROWS FETCH NEXT $limit ROWS ONLY";
        $db = \Config\Database::connect();
        return $db->query($query);
    }
}
