<?php

namespace App\Models;

use CodeIgniter\Model;

class MtruckingtradoluartasModel extends Model
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect('dbtruck');
    }

    public function getGridData($cabang = 'MDN', $params = [])
    {
        // For Trucking Luar Tas Medan, we query LaporanTradoLuarTasMdn
        // If needed to support other branches, we can dynamically change the table
        $tableName = 'LaporanTradoLuarTas' . ucfirst(strtolower($cabang));
        
        $builder = $this->db->table($tableName);

        // Filters
        if (!empty($params['bulan']) && $params['bulan'] !== 'ALL') {
            $builder->where('FBulan', $params['bulan']);
        }

        if (!empty($params['jenis_trado']) && $params['jenis_trado'] !== 'All') {
            $builder->where('FJenisTrado', $params['jenis_trado']);
        }

        // Searching from jqGrid filterToolbar
        if (isset($params['_search']) && $params['_search'] === 'true' && isset($params['filters'])) {
            $filters = json_decode($params['filters'], true);
            if ($filters && isset($filters['rules'])) {
                $builder->groupStart();
                foreach ($filters['rules'] as $rule) {
                    $field = $rule['field'];
                    $data = $rule['data'];
                    // Ignore formatted numbers in search, or use like
                    $builder->like($field, $data);
                }
                $builder->groupEnd();
            }
        }

        // Total Records
        $totalRecords = $builder->countAllResults(false);

        // Calculate Grand Totals for Footer
        $builderTotals = clone $builder;
        $builderTotals->select("
            SUM(ISNULL(TRY_CAST(REPLACE(FNominalMuatan, ',', '') AS FLOAT), 0)) as TotalFNominalMuatan,
            SUM(ISNULL(TRY_CAST(REPLACE(FJumlahMuatan, ',', '') AS FLOAT), 0)) as TotalFJumlahMuatan,
            SUM(ISNULL(TRY_CAST(REPLACE(FNominalBongkaran, ',', '') AS FLOAT), 0)) as TotalFNominalBongkaran,
            SUM(ISNULL(TRY_CAST(REPLACE(FJumlahBongkaran, ',', '') AS FLOAT), 0)) as TotalFJumlahBongkaran,
            SUM(ISNULL(TRY_CAST(REPLACE(FNominalImport, ',', '') AS FLOAT), 0)) as TotalFNominalImport,
            SUM(ISNULL(TRY_CAST(REPLACE(FJumlahImport, ',', '') AS FLOAT), 0)) as TotalFJumlahImport,
            SUM(ISNULL(TRY_CAST(REPLACE(FNominalEksport, ',', '') AS FLOAT), 0)) as TotalFNominalEksport,
            SUM(ISNULL(TRY_CAST(REPLACE(FJumlahEksport, ',', '') AS FLOAT), 0)) as TotalFJumlahEksport,
            SUM(
                ISNULL(TRY_CAST(REPLACE(FNominalMuatan, ',', '') AS FLOAT), 0) + 
                ISNULL(TRY_CAST(REPLACE(FNominalBongkaran, ',', '') AS FLOAT), 0) + 
                ISNULL(TRY_CAST(REPLACE(FNominalImport, ',', '') AS FLOAT), 0) +
                ISNULL(TRY_CAST(REPLACE(FNominalEksport, ',', '') AS FLOAT), 0)
            ) as GrandTotal
        ");
        
        $totals = $builderTotals->get()->getRowArray();

        // Select all columns and compute Total for the main grid query
        $builder->select('
            FBulan, 
            FNoPol, 
            FJenisTrado, 
            FNominalMuatan, 
            FJumlahMuatan, 
            FNominalBongkaran, 
            FJumlahBongkaran, 
            FNominalImport, 
            FJumlahImport, 
            FNominalEksport, 
            FJumlahEksport, 
            FTglUpdate, 
            (ISNULL(TRY_CAST(REPLACE(FNominalMuatan, \',\', \'\') AS FLOAT), 0) + 
             ISNULL(TRY_CAST(REPLACE(FNominalBongkaran, \',\', \'\') AS FLOAT), 0) + 
             ISNULL(TRY_CAST(REPLACE(FNominalImport, \',\', \'\') AS FLOAT), 0) +
             ISNULL(TRY_CAST(REPLACE(FNominalEksport, \',\', \'\') AS FLOAT), 0)) AS Total
        ');

        // Sorting
        $sidx = isset($params['sidx']) && !empty($params['sidx']) ? $params['sidx'] : 'FBulan';
        $sord = isset($params['sord']) && !empty($params['sord']) ? $params['sord'] : 'DESC';
        $builder->orderBy($sidx, $sord);

        // Pagination
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['rows']) ? (int)$params['rows'] : 10;
        
        if ($limit > 0) {
            $totalPages = ceil($totalRecords / $limit);
        } else {
            $totalPages = 0;
        }
        
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        
        $offset = 0;
        if ($limit > 0 && $page > 0) {
            $offset = $limit * $page - $limit;
            $builder->limit($limit, $offset);
        }

        $query = $builder->get();
        
        $rows = [];
        if ($query) {
            $results = $query->getResultArray();
            foreach ($results as $i => $row) {
                // Add unique ID for jqGrid based on pagination offset
                $row['id'] = $offset + $i + 1;
                $rows[] = $row;
            }
        }

        return [
            'page' => $page,
            'total' => $totalPages,
            'records' => $totalRecords,
            'rows' => $rows,
            'userdata' => $totals
        ];
    }

    public function getComboBulan($cabang = 'MDN')
    {
        $tableName = 'LaporanTradoLuarTas' . ucfirst(strtolower($cabang));
        $sql = "SELECT FBulan FROM (SELECT DISTINCT FBulan FROM $tableName) A ORDER BY substring(A.FBulan,4,4) DESC, A.FBulan DESC";
        $query = $this->db->query($sql);
        return $query ? $query->getResultArray() : [];
    }

    public function getComboJenisTrado($cabang = 'MDN')
    {
        $tableName = 'LaporanTradoLuarTas' . ucfirst(strtolower($cabang));
        $sql = "SELECT DISTINCT FJenisTrado FROM $tableName";
        $query = $this->db->query($sql);
        return $query ? $query->getResultArray() : [];
    }
    
    public function getLastUpdate($cabang = 'MDN')
    {
        $tableName = 'LaporanTradoLuarTas' . ucfirst(strtolower($cabang));
        $builder = $this->db->table($tableName);
        $builder->select('FTglUpdate');
        $builder->orderBy('FTglUpdate', 'DESC');
        $builder->limit(1);
        $query = $builder->get();
        if ($query && $query->getNumRows() > 0) {
            $row = $query->getRowArray();
            return date("d-m-Y H:i:s", strtotime($row['FTglUpdate']));
        }
        return '';
    }
}
