<?php
$serverName = "localhost"; // Adjust if needed
$connectionOptions = array(
    "Database" => "transpor_tasmanagement",
    "Uid" => "sa",
    "PWD" => "Admin123" // Guessing from common setup, but wait, CI4 has database config.
);
// Let's just use CI4 to connect
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';
$db = \Config\Database::connect();
$query = $db->query("SELECT TOP 1 parameter_key FROM tblparameter");
echo json_encode($query->getRow());
