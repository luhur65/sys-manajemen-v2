<?php

namespace App\Models;

use App\Libraries\GridSort;
use CodeIgniter\Model;

class MshippernewModel extends Model
{
    /** Kolom yang boleh diurutkan; sama dengan daftar select() di getList(). */
    private const SORTABLE = ['FNCabang', 'FNShipper', 'FTgl', 'FNMarketing'];

    protected $DBGroup          = 'dbtruck';
    protected $table            = 'MShipperCabang';
    protected $primaryKey       = 'FNShipper'; // Assuming FNShipper is the closest thing to PK, or we can just omit if no PK operations are needed
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    public function getList($cabang, $filters = [], $whereString = '', $limit = 0, $offset = 0, $sortName = 'FTgl', $sortOrder = 'desc', $count = false)
    {
        $builder = $this->db->table($this->table);
        
        $builder->select('FNCabang, FNShipper, FTgl, FNMarketing');
        if (!empty($cabang) && strtoupper($cabang) !== 'ALL') {
            $builder->where('FNCabang', $cabang);
        }

        // Date Filtering
        if (!empty($filters['datefrom']) && !empty($filters['dateto'])) {
            $builder->where("FTgl >=", $filters['datefrom'] . ' 00:00:00');
            $builder->where("FTgl <=", $filters['dateto'] . ' 23:59:59');
        }

        // Global Search
        if (!empty($whereString) && trim($whereString) != "") {
            $builder->where($whereString, null, false);
        }

        // Column Sorting
        //
        // H-03: Query Builder TIDAK mengamankan ORDER BY. protectIdentifiers
        // meloloskan subquery dan ekspresi apa adanya — `(SELECT TOP 1 password
        // FROM tbluser)` keluar utuh sebagai klausa ORDER BY. Jadi nama kolom
        // tetap harus divalidasi sendiri, sama seperti model yang memakai query
        // string mentah.
        $sortName  = GridSort::column($sortName, 'FTgl', self::SORTABLE);
        $sortOrder = GridSort::direction($sortOrder);

        if (!empty($sortName)) {
            $builder->orderBy($sortName, $sortOrder);
        }

        if ($count) {
            return $builder->countAllResults();
        }

        if ($limit > 0) {
            $builder->limit($limit, $offset);
        }

        return $builder->get()->getResultArray();
    }
}
