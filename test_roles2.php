<?php
$db = new \mysqli("127.0.0.1", "root", "", "dbTruckWeb");
// wait, sql server is used! So I must use sqlsrv or CI4.
require 'vendor/autoload.php';
$app = \Config\Services::codeigniter(null, false);
$app->initialize();

$model = new \App\Models\RolesModel();
$data = ['roleid' => 1, 'rolename' => 'ADMIN'];
$status = $model->saveData($data);

if (!$status) {
    echo "FAILED.\n";
    print_r($model->errors());
} else {
    echo "SUCCESS.\n";
}
