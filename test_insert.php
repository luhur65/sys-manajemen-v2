<?php

// Actually CI4 is easier to bootstrap
require 'vendor/autoload.php';
$app = \Config\Services::codeigniter(null, false);
$app->initialize();

$db = \Config\Database::connect();
try {
    $db->table('tblacl')->insert([
        'roleid' => 3,
        'acoid' => 63,
        'modifiedby' => 'SYSTEM',
        'modifiedon' => date('Y-m-d H:i:s')
    ]);
    echo "Success!\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
