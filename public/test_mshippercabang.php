<?php
$conn = sqlsrv_connect("localhost", array("Database"=>"dbTruckWeb", "UID"=>"sa", "PWD"=>""));
if($conn) {
    $sql = "SELECT TOP 1 * FROM MShipperCabang";
    $stmt = sqlsrv_query($conn, $sql);
    if($stmt) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        print_r($row);
    } else {
        die(print_r(sqlsrv_errors(), true));
    }
} else {
    die(print_r(sqlsrv_errors(), true));
}
