<?php

namespace App\Models;

use CodeIgniter\Model;

class MsupirpercabangModel extends Model
{
    protected $dbtruck;

    public function __construct()
    {
        parent::__construct();
        $this->dbtruck = \Config\Database::connect('dbtruck');
    }

    public function get_supir($start, $limit, $sidx, $sord, $where, $cabang)
    {
        $start = $start + 1;
        $sampai = $limit + $start - 1;

        $surut = !empty($sidx) ? $sidx : "FKSupir";
        $order = !empty($sord) ? $sord : "ASC";

        $whereClause = trim($where) !== "" ? "AND 1=1 " . $where : "";

        // Ensure pagination logic properly fetches the subset
        $sql = $this->dbtruck->query("SELECT * FROM (
            SELECT FKSupir, FNSupir, FAlamat, FKota, FTelp, FAktif, 
            ROW_NUMBER() OVER (ORDER BY $surut $order) AS RowNum 
            FROM MSupir 
            WHERE FKCABANG = ? AND FIsKaryawan = 0 $whereClause
        ) AS GD WHERE RowNum BETWEEN ? AND ? ORDER BY RowNum", [$cabang, $start, $sampai]);

        return $sql;
    }

    public function count_supir($where, $cabang)
    {
        $whereClause = trim($where) !== "" ? "AND 1=1 " . $where : "";
        $sql = $this->dbtruck->query("SELECT COUNT(*) as total FROM MSupir WHERE FKCABANG = ? AND FIsKaryawan = 0 $whereClause", [$cabang]);
        return $sql->getRow()->total;
    }

    public function detail_supir($fksupir, $cabang)
    {
        $sql = $this->dbtruck->query("SELECT * FROM MSupir WHERE FKCABANG = ? AND FIsKaryawan = 0 AND FKSupir = ?", [$cabang, $fksupir]);
        return $sql->getRowArray();
    }
}
