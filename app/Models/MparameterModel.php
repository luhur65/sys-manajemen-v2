<?php
namespace App\Models;

use CodeIgniter\Model;

class MparameterModel extends Model
{
    protected $table      = 'tblparameter';
    protected $primaryKey = 'parameter_key';
    protected $returnType = 'object';
    protected $useAutoIncrement = false;
    protected $allowedFields = [
        'parameter_key',
        'parametergrpid',
        'parameterid',
        'parametertext',
        'parametermemo',
        'modifiedby',
        'modifiedon'
    ];
    protected $useTimestamps = false;

    public function count($where)
    {
        $sql = $this->db->query("SELECT parameter_key FROM tblparameter " . $where);
        return $sql;
    }

    public function get($where, $sidx, $sord, $limit, $start)
    {
        $sql = $this->db->query("SELECT *,
        FORMAT(modifiedon,'dd-MM-yyyy HH:mm:ss') as modifiedonview
        FROM tblparameter " . $where . " ORDER BY $sidx $sord OFFSET $start ROWS FETCH NEXT $limit ROWS ONLY");
        return $sql;
    }

    public function getById($id)
    {
        $sql = $this->db->query("SELECT *,
        FORMAT(modifiedon,'dd-MM-yyyy HH:mm:ss') as modifiedonview
        FROM tblparameter WHERE parameter_key = ?", [$id]);
        return $sql->getRow();
    }
}
