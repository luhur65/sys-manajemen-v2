<?php
$serverName = "localhost";
$connectionOptions = array(
    "Database" => "transpor_tasmanagement",
    "Uid" => "sa",
    "PWD" => "123"
);

//Establishes the connection
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// check if menuid is identity
$tsql = "SELECT column_name, is_identity FROM sys.columns WHERE object_id = object_id('tblmenu')";
$stmt = sqlsrv_query($conn, $tsql);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo $row['column_name'] . ": " . $row['is_identity'] . "\n";
}
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
