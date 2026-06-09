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

$sql = "INSERT INTO tblmenu (menuname, menuseq, menuparent, menuicon, acoid, link, modifiedby, modifiedon) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$params = array('test', 0, 1, 'fas fa-code', 0, '', 'SYSTEM', date('Y-m-d H:i:s'));

$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

echo "Success";
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
