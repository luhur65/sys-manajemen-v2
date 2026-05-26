<?php
namespace App\Libraries;

// Migrated from CI3: application/libraries/mydb.php

class mydb {
    protected $db;
	private $Data, $mysqli, $ResultSet;

	public function __construct() {
        $this->db = \Config\Database::connect();
	    $this->Data = '';
	    $this->ResultSet = array();
	    $this->mysqli = $this->db->conn_id;
	}

    public function GetMultiResults($SqlCommand) {
	    /* execute multi query */
	    if (mysqli_multi_query($this->mysqli, $SqlCommand)) {
	        $i = 0;
	        do {
	            if ($result = mysqli_store_result($this->mysqli)) {
	                while ($row = mysqli_fetch_assoc($result)) {
	                    $this->ResultSet[$i][] = $row;
	                }
	                mysqli_free_result($result);
	            }
	            $i++;
	        } while (mysqli_more_results($this->mysqli) && mysqli_next_result($this->mysqli));
	    }
	    return $this->ResultSet;
	}
}
