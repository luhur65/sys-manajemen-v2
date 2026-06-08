<?php

namespace App\Models;

use CodeIgniter\Model;

class MmenuModel extends Model
{
    protected $table = 'tblmenu';
    protected $primaryKey = 'menuid';
    protected $useAutoIncrement = false;
    protected $returnType = 'object';
    protected $allowedFields = [
        'menuid', 'menuname', 'menuseq', 'menuparent', 'menuicon', 'acoid', 'modifiedon', 'modifiedby', 'link', 'menuexe'
    ];

    public function get($where, $sidx, $sord, $limit, $start)
    {
        $sort = " menuname asc ";
        if ($sidx != "1" && $sidx != "") {
            $sort = " $sidx $sord ";
        }
        
        $sql = "SELECT *, FORMAT(modifiedon,'dd-MM-yyyy HH:mm:ss') as modifiedonview
                FROM tblmenu $where 
                ORDER BY $sort , menuseq ASC 
                OFFSET $start ROWS FETCH NEXT $limit ROWS ONLY";
                
        return $this->db->query($sql);
    }

    public function count($where)
    {
        $sql = "SELECT menuid FROM tblmenu $where";
        return $this->db->query($sql);
    }
    
    public function getAcos($where, $sidx, $sord, $limit, $start)
    {
        if($sidx == "class" || $sidx == "1" || $sidx == "") {
            $sidx = "class, method";
        }
        $sql = "SELECT * FROM tblacos $where 
                ORDER BY $sidx $sord 
                OFFSET $start ROWS FETCH NEXT $limit ROWS ONLY";
                
        return $this->db->query($sql);
    }

    public function countAcos($where)
    {
        $sql = "SELECT acosid FROM tblacos $where";
        return $this->db->query($sql);
    }
}
