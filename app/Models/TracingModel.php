<?php

namespace App\Models;

use App\Libraries\GridSort;
use CodeIgniter\Model;

class TracingModel extends Model
{
    protected $table      = 'tbltracing';
    protected $primaryKey = 'UserId';
    protected $returnType     = 'object';
    protected $useSoftDeletes = false;

    public function get_tracing($start, $limit, $sidx, $sord, $where)
    {
        // H-03: sidx/sord dari client divalidasi sebelum masuk ke ORDER BY.
        // sidx yang tidak lolos menjadi string kosong sehingga jatuh ke default
        // yang sudah ada di bawah.
        $sidx  = GridSort::column($sidx, '');
        $sord  = GridSort::direction($sord, 'DESC');
        $limit = GridSort::limit($limit);
        $start = GridSort::offset($start);

        $start = $start + 1;
        $sampai = $limit + $start - 1;

        $surut = !empty($sidx) ? $sidx : "waktulogin";
        $order = !empty($sord) ? $sord : "DESC";

        $whereClause = trim($where) !== "" ? "WHERE 1=1 " . $where : "";

        // Paging using ROW_NUMBER()
        $sql = $this->db->query("SELECT * FROM (
            SELECT UserId, shipper, FORMAT(waktulogin, 'dd-MM-yyyy HH:mm:ss') as waktulogin, cabang,
            ROW_NUMBER() OVER (ORDER BY $surut $order) AS RowNum 
            FROM tbltracing 
            $whereClause
        ) AS GD WHERE RowNum BETWEEN $start AND $sampai ORDER BY RowNum");

        return $sql;
    }

    public function count_tracing($where)
    {
        $whereClause = trim($where) !== "" ? "WHERE 1=1 " . $where : "";
        $sql = $this->db->query("SELECT count(UserId) as total FROM tbltracing $whereClause");
        return $sql->getRow()->total;
    }

    public function get_cabang()
    {
        $sql = $this->db->query("SELECT DISTINCT cabang FROM tbltracing WHERE cabang IS NOT NULL ORDER BY cabang ASC");
        return $sql->getResult();
    }
}
