<?php
namespace App\Models;

use CodeIgniter\Model;

// Migrated from CI3: application/models/mprofil.php

class MprofilModel extends Model
{
    protected $table      = 'tbluser';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = []; // TODO: Add allowed fields
    protected $useTimestamps = false;
    public function __construct() {
        parent::__construct();
    }
    public function get($userid){
		$sql = $this->db->query("SELECT * FROM tbluser WHERE userid='$userid'");
        return $sql;
	}
}

