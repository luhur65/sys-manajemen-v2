<?php

namespace App\Models;

use CodeIgniter\Model;

class MtruckingtradoluardetailexpModel extends Model
{
    protected $DBGroup = 'dbtruck';
    
    public function getGridData($cabang, $params)
    {
        $tableMap = [
            'JKT' => 'TradoLuarDetailJkt',
            'MDN' => 'TradoLuarDetailMdn',
            'SBY' => 'TradoLuarDetailSby',
            'MKS' => 'TradoLuarDetailMks'
        ];
        
        $table = isset($tableMap[$cabang]) ? $tableMap[$cabang] : 'TradoLuarDetailMdn';
        
        $builder = $this->db->table($table);

        if (isset($params['tgl_dari']) && !empty($params['tgl_dari'])) {
            $builder->where('FTgl >=', $params['tgl_dari'] . ' 00:00:00');
        }
        
        if (isset($params['tgl_sampai']) && !empty($params['tgl_sampai'])) {
            $builder->where('FTgl <=', $params['tgl_sampai'] . ' 23:59:59');
        }


        // Searching
        if (isset($params['_search']) && $params['_search'] === 'true' && isset($params['filters'])) {
            $filters = json_decode($params['filters'], true);
            if ($filters && isset($filters['rules'])) {
                $builder->groupStart();
                foreach ($filters['rules'] as $rule) {
                    $field = $rule['field'];
                    $data = $rule['data'];
                    $builder->like($field, $data);
                }
                $builder->groupEnd();
            }
        }
        
        // Total Records
        $totalRecords = $builder->countAllResults(false);
        
        $builderTotals = clone $builder;
        $builderTotals->select("
            SUM(TRY_CAST(REPLACE(FNominalHargaTrucking, ',', '') AS FLOAT)) as TotalHargaTrucking,
            SUM(TRY_CAST(REPLACE(FNominalHargaTruckingPusat, ',', '') AS FLOAT)) as TotalHargaTruckingPusat,
            SUM(TRY_CAST(REPLACE(FSelisih, ',', '') AS FLOAT)) as TotalSelisih,
            SUM(CASE WHEN FOrderan = 'Eksport' THEN 1 ELSE 0 END) as JumlahEksport,
            SUM(CASE WHEN FOrderan = 'Import' THEN 1 ELSE 0 END) as JumlahImport,
            SUM(CASE WHEN FOrderan = 'Muatan' THEN 1 ELSE 0 END) as JumlahMuatan,
            SUM(CASE WHEN FOrderan = 'Bongkaran' THEN 1 ELSE 0 END) as JumlahBongkaran
        ");
        $totals = $builderTotals->get()->getRowArray();

        // Sorting
        $sidx = isset($params['sidx']) && !empty($params['sidx']) ? $params['sidx'] : 'FTgl';
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
        
        if ($page > $totalPages) $page = $totalPages;
        $start = $limit * $page - $limit;
        if ($start < 0) $start = 0;
        
        $builder->limit($limit, $start);
        
        $query = $builder->get();
        $rows = $query->getResultArray();
        
        // Inject id for jqgrid / lazyLoadingGridMonolith
        foreach ($rows as $index => &$row) {
            $row['id'] = $start + $index + 1;
        }
        
        return [
            'page' => $page,
            'total' => $totalPages,
            'records' => $totalRecords,
            'rows' => $rows,
            'userdata' => $totals
        ];
    }
    
    public function getLastUpdate($cabang)
    {
        $tableMap = [
            'JKT' => 'TradoLuarDetailJkt',
            'MDN' => 'TradoLuarDetailMdn',
            'SBY' => 'TradoLuarDetailSby',
            'MKS' => 'TradoLuarDetailMks'
        ];
        
        $table = isset($tableMap[$cabang]) ? $tableMap[$cabang] : 'TradoLuarDetailMdn';
        
        $builder = $this->db->table($table);
        $builder->selectMax('FTglUpdate');
        $query = $builder->get();
        $row = $query->getRow();
        
        if ($row && $row->FTglUpdate) {
            return date("d-m-Y H:i:s", strtotime($row->FTglUpdate));
        }
        return '-';
    }
}
