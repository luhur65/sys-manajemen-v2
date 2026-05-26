<?php
$hostname = "127.0.0.1";
$username = "sa";
$password = "Aa123456"; // Assuming from my_helper.php
$database = "dbTas";

// Connect to SQL Server
$connectionInfo = array("Database" => $database, "UID" => $username, "PWD" => $password);
$conn = sqlsrv_connect($hostname, $connectionInfo);

if ($conn === false) {
    echo "Connection could not be established.\n";
    die(print_r(sqlsrv_errors(), true));
}

$sql = "SELECT menuid, menuname, class, method FROM tblmenu WHERE menuname LIKE '%Omset%' OR menuname LIKE '%Emkl%'";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    echo "Error in executing query.\n";
    die(print_r(sqlsrv_errors(), true));
}

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo $row['menuname'] . " -> " . $row['class'] . "/" . $row['method'] . "\n";
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
