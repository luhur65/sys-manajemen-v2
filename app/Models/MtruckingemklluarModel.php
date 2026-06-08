<?php

namespace App\Models;

use CodeIgniter\Model;

class MtruckingemklluarModel extends Model
{
    protected $dbtruck;

    public function __construct()
    {
        parent::__construct();
        $this->dbtruck = \Config\Database::connect('dbtruck');
    }

    public function get_whereMDN($where = "")
    {
        $whereClause = trim($where) !== "" ? "WHERE 1=1 " . $where : "";
        $sql = $this->dbtruck->query("SELECT * FROM EmklLainMdn $whereClause");
        return $sql;
    }

    public function get_whereJKT($where = "")
    {
        $whereClause = trim($where) !== "" ? "WHERE 1=1 " . $where : "";
        $sql = $this->dbtruck->query("SELECT * FROM EmklLainJkt $whereClause");
        return $sql;
    }

    public function get_whereSBY($where = "")
    {
        $whereClause = trim($where) !== "" ? "WHERE 1=1 " . $where : "";
        $sql = $this->dbtruck->query("SELECT * FROM EmklLainSby $whereClause");
        return $sql;
    }

    public function get_whereMKS($where = "")
    {
        $whereClause = trim($where) !== "" ? "WHERE 1=1 " . $where : "";
        $sql = $this->dbtruck->query("SELECT * FROM EmklLainMks $whereClause");
        return $sql;
    }
}
