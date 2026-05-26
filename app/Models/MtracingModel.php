<?php
namespace App\Models;

use CodeIgniter\Model;

// Migrated from CI3: application/models/mtracing.php

class MtracingModel extends Model
{
    protected $table      = 'tbltracing';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = []; // TODO: Add allowed fields
    protected $useTimestamps = false;

    public function count($where){
		$sql = $this->db->query("SELECT userid FROM tbltracing " . $where);
        return $sql;
	}
    public function get($where, $sidx, $sord, $limit, $start){
		$sql = $this->db->query("SELECT UserId , shipper ,
		FORMAT(waktulogin,'dd-MM-yyyy hh:mm:ss') waktulogin, cabang
		FROM tbltracing " . $where . " ORDER BY 3 desc, $sidx $sord
		OFFSET $start ROWS FETCH NEXT $limit ROWS ONLY");
		return $sql;
	}
    public function get_where($where){
		$sql = $this->db->query("SELECT UserId , shipper ,FORMAT(waktulogin,'dd-MM-yyyy hh:mm:ss')waktulogin, cabang FROM tbltracing " . $where);
		return $sql;
	}
    public function laptracingpertahun() {
		$sql = $this->db->query("call usp_Lap_Pemakaian_Tracing_PerBulan()");
		return $sql->getResult();
	}
    public function getallpercabang($cabang,$bulan,$tahun){
		$sql = $this->db->query("SELECT UserId , shipper ,FORMAT(waktulogin,'dd-MM-yyyy hh:mm:ss') waktulogin, cabang FROM tbltracing WHERE cabang =".$cabang." And year(waktulogin)=".$tahun." And
				Month(waktulogin)=".$bulan);
		return $sql->getResult();
	}

	//controller
    public function get_karyawan(){
		$sql = $this->db->query('SELECT * FROM tbltracing ORDER BY cabang');
		return $sql;
	}
    public function get_combo(){
		$cabang=":All;";
		$sqlcabang = $this->db->query('SELECT * FROM tbltracing ORDER BY cabang');
		foreach ($sqlcabang->getResult() as $key) {
			$cabang=$cabang.$key->cabangid.":".$key->cabangname.";";
		}
		$cabang=strrev($cabang);
		$cabang=substr($cabang,1);
		$cabang=strrev($cabang);
		return $cabang;
	}
}
