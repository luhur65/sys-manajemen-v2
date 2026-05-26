<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class TestParam extends Controller
{
    public function index()
    {
        $db = \Config\Database::connect();
        
        $query = $db->query("SELECT TOP 1 parameter_key FROM tblparameter ORDER BY parameter_key DESC");
        echo json_encode($query->getRow());
        
        echo "\n";
        
        $query2 = $db->query("SELECT column_name, data_type, is_identity, column_default 
                     FROM information_schema.columns 
                     JOIN sys.columns ON sys.columns.name = information_schema.columns.column_name 
                                     AND sys.columns.object_id = object_id('tblparameter')
                     WHERE table_name = 'tblparameter' AND column_name = 'parameter_key'");
        echo json_encode($query2->getRow());
    }
}
