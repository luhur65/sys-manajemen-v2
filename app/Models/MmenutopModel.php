<?php
namespace App\Models;

use CodeIgniter\Model;

// Migrated from CI3: application/models/mmenutop.php

class MmenutopModel extends Model
{
    protected $table      = 'mmenutop';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = []; // TODO: Add allowed fields
    protected $useTimestamps = false;

	public function get_data($induk=0)
	{
		$userpk = session()->get(SESSION_NAME.'userpk');
		$data = array();
		$query="SELECT  t1.menuid,t1.menuname,t1.menuicon,t3.class,t3.method,t1.link,t1.menuparent
			FROM tblmenu t1 left join tblacos t3 on t1.acoid = t3.acosid
						WHERE t1.menuparent='$induk'     ORDER BY menuseq ASC";
						// and (t1.acoid = t3.acosid or t1.menuparent=0 or t1.link!='')
		$result = $this->db->query($query);

		foreach($result->getResult() as $row)
		{
			if($row->menuparent==0 and count($this->get_data($row->menuid))==0 and ($row->menuid!=1) and $row->menuid!=10){
				continue;
			}else if(strtolower($row->menuname)=="report design" and strtolower(strtoupper(session()->get(SESSION_NAME.'username')))!='admin'){
				continue;
			}else{
				$check =hasPermission($row->class,$row->method);
				if($check==true || $row->class==""){
					if($row->class=="" && count($this->get_data($row->menuid))==0 && $row->menuparent!=0){

					}else{
						$data[] = array(
								'menuid'	=>$row->menuid,
								'menuname'	=>$row->menuname,
								'menuicon'	=>$row->menuicon,
								'link' => $row->link,
								'menuexe'	=>$row->class."/".$row->method,
								'menuparent'=>$row->menuparent,
								// recursive
								'child'	=>$this->get_data($row->menuid)
							);
					}

				}
			}
		}
		return $data;
	}
	public function get_child($menuid)
	{
		$data = array();
		$result = $this->db->table('tblmenu')
			->where('menuparent',$menuid)
			->orderBy("menuseq", "asc")
			->get();
		foreach($result->getResult() as $row)
		{
			$data[$row->menuid] = $row->menuname;
		}
		return $data;
	}
    public function get_menuid($menuexe){
		$userpk = session()->get(SESSION_NAME.'userpk');
		$query = "SELECT t2.acl
			FROM tblmenu t1, tblusermenu t2
			WHERE t1.menuexe='$menuexe' AND t1.menuid=t2.menuid AND t2.userpk='$userpk'";
		$result = $this->db->query($query);
		$data = "";
		foreach($result->getResult() as $row)
		{
			$data = $row->acl;
		}
		return $data;
	}
}

