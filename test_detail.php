<?php
$cabang = "MDN";
$ftgl = "2026-05-25";

$db = \Config\Database::connect('dbtruck');

try {
    $tableName = 'TradoLuarDetail' . ucfirst(strtolower($cabang));
    $whereClause = " WHERE FTgl >= '2026-05-25 00:00:00' AND FTgl <= '2026-05-25 23:59:59'";
    $sql = $db->query("SELECT 
        SUM(ISNULL(TRY_CAST(REPLACE(FNominalHargaTrucking, ',', '') AS FLOAT), 0)) as TotalHargaTrucking,
        SUM(ISNULL(TRY_CAST(REPLACE(FNominalHargaTruckingPusat, ',', '') AS FLOAT), 0)) as TotalHargaTruckingPusat,
        SUM(ISNULL(TRY_CAST(REPLACE(FSelisih, ',', '') AS FLOAT), 0)) as TotalSelisih,
        SUM(CASE WHEN LOWER(FOrderan) LIKE '%muat%' THEN 1 ELSE 0 END) as TotalMuatan,
        SUM(CASE WHEN LOWER(FOrderan) LIKE '%bongkar%' THEN 1 ELSE 0 END) as TotalBongkaran,
        SUM(CASE WHEN LOWER(FOrderan) LIKE '%import%' THEN 1 ELSE 0 END) as TotalImport,
        SUM(CASE WHEN LOWER(FOrderan) LIKE '%ekspor%' THEN 1 ELSE 0 END) as TotalEksport
        FROM $tableName $whereClause");
    
    print_r($sql->getRow());
    echo "\nSuccess\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
