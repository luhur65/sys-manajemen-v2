<?php
$db = new PDO("sqlsrv:Server=192.168.1.189;Database=dbtruck", "sa", "123");
$stmt = $db->query("SELECT TOP 1 * FROM TradoLuarDetailMdn");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
