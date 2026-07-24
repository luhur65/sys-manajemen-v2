<?php

namespace App\Models;

use CodeIgniter\Model;

class MgrafikbiayakantorbandinglabaModel extends Model
{
    // Kosongkan karena kita akan menarik data lintas database (cross-database query)
    protected $table = ''; 

    /**
     * Helper internal untuk menarik data laporan laba rugi dari database cabang tertentu.
     * Asumsi tabel bernama `laporanlabarugi` pada skema `dbo`.
     */
    private function _get_where_cabang($dbName, $where = '')
    {
        $db = \Config\Database::connect();
        
        // Membaca langsung dari database lain di server yang sama
        $builder = $db->table($dbName . '.dbo.laporanlabarugi');
        
        // Asumsi field: bulan (format MM-YYYY), biayakantorcabang, lababersih, ftglinput
        $builder->select('bulan, SUM(biayakantorcabang) as FBiaya, SUM(lababersih) as FLaba, MAX(ftglinput) as ftglinput');
        
        if ($where != '') {
            $builder->where($where);
        }
        
        $builder->groupBy('bulan');
        // Urutkan berdasarkan tahun (4 karakter terakhir) lalu bulan (2 karakter awal)
        $builder->orderBy('RIGHT(bulan, 4)', 'ASC', false);
        $builder->orderBy('LEFT(bulan, 2)', 'ASC', false);

        return $builder->get();
    }

    public function get_whereJKT($where = '')
    {
        return $this->_get_where_cabang('emkljakarta', $where);
    }

    public function get_whereMDN($where = '')
    {
        return $this->_get_where_cabang('emklmedan', $where);
    }

    public function get_whereSBY($where = '')
    {
        return $this->_get_where_cabang('emklsurabaya', $where);
    }

    public function get_whereMKS($where = '')
    {
        return $this->_get_where_cabang('emklmakassar', $where);
    }

    public function get_whereBTG($where = '')
    {
        return $this->_get_where_cabang('emklbitung', $where);
    }

    public function get_whereSMG($where = '')
    {
        return $this->_get_where_cabang('emklsemarang', $where);
    }
}
