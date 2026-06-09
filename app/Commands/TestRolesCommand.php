<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class TestRolesCommand extends BaseCommand
{
    protected $group       = 'Custom';
    protected $name        = 'test:roles';
    protected $description = 'Test roles update';

    public function run(array $params)
    {
        $db = \Config\Database::connect('dbtruck');
        $query1 = $db->query("SELECT COUNT(*) as cnt FROM TradoLuarMdn");
        CLI::write("TradoLuarMdn: " . $query1->getRow()->cnt);
        
        $query2 = $db->query("SELECT COUNT(*) as cnt FROM LaporanTradoLuarTasMdn");
        CLI::write("LaporanTradoLuarTasMdn: " . $query2->getRow()->cnt);
    }
}
