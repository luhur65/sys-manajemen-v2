<?php
namespace App\Controllers;
use CodeIgniter\Controller;
class TestDB extends Controller
{
    public function index()
    {
        $db = \Config\Database::connect();
        $fields = $db->getFieldData('tblmenu');
        echo "<pre>";
        print_r($fields);
        echo "</pre>";
    }
}
