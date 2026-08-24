<?php

namespace App\Models;

use App\Libraries\GridSort;
use CodeIgniter\Model;

class MmenuModel extends Model
{
    /** Kolom yang boleh diurutkan; sesuai colModel di app/Views/menu/index.php. */
    private const SORTABLE = [
        'menuid', 'menuname', 'menuseq', 'menuparent', 'menuicon', 'acoid',
        'link', 'menuexe', 'modifiedby', 'modifiedonview', 'routeid',
    ];

    /** Kolom yang boleh diurutkan pada grid katalog ACO. */
    private const SORTABLE_ACOS = [
        'acosid', 'class', 'method', 'displayname', 'modifiedby', 'modifiedonview',
    ];

    protected $table = 'tblmenu';
    protected $primaryKey = 'menuid';
    protected $useAutoIncrement = false;
    protected $returnType = 'object';
    protected $allowedFields = [
        'menuid', 'menuname', 'menuseq', 'menuparent', 'menuicon', 'acoid', 'modifiedon', 'modifiedby', 'link', 'menuexe'
    ];

    public function get($where, $sidx, $sord, $limit, $start)
    {
        // H-03: sidx/sord dari client divalidasi terhadap whitelist kolom grid.
        // sidx yang tidak lolos menjadi string kosong sehingga jatuh ke default.
        $sidx  = GridSort::column($sidx, '', self::SORTABLE);
        $sord  = GridSort::direction($sord, 'ASC');
        $limit = GridSort::limit($limit);
        $start = GridSort::offset($start);

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
        // H-03: "class, method" adalah default majemuk tulisan developer, jadi
        // divalidasi dulu sebelum cabang di bawah menggantinya.
        $sidx  = GridSort::column($sidx, 'class, method', self::SORTABLE_ACOS);
        $sord  = GridSort::direction($sord, 'ASC');
        $limit = GridSort::limit($limit);
        $start = GridSort::offset($start);

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
