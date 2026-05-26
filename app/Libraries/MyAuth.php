<?php
namespace App\Libraries;

// Migrated from CI3: application/libraries/MyAuth.php

class MyAuth {
    protected $db;
	private $isLogin, $userPK;
	private $authController;
    private $baseUrl;

	private $exceptAuth = [
		'class'=>['useracl','login','home','relasi','extension','logout','acos','profil','sop','cabang'],
		'method'=>[
			'gridtab','grid','operation','excel','crud','carishippersama','listmarketingcabang'
			,'pesanmdn','pesansby','pesanmks','pesanjkt','pesantnl','pesanbtg','gridasuransi','combotradoluar','datacabang','detail','datamarketing','combomarketing'
		],
	];

	public function __construct($params = []){
        $this->db = \Config\Database::connect(); // Uses default group
		$this->isLogin = isset($params['isLogin']) ? $params['isLogin'] : 0;
		$this->userPK = isset($params['userPK']) ? $params['userPK'] : 0;
		$this->baseUrl = isset($params['baseUrl']) ? $params['baseUrl'] : base_url();
		$this->authController = isset($params['authController']) ? $params['authController'] : 'login';
	}

	public function auth($class=null,$method=null){
		$class = strtolower($class ?: '');
		$method = strtolower($method ?: '');
		if(!$this->_validatePermission($class,$method)){
            log_message('error', "[MyAuth] Access Denied: $class/$method for UserPK: " . $this->userPK);
			exit("You don't have access to $class/$method");
		}
	}

	public function hasPermission($class,$method) {
		$class = strtolower($class ?: '');
		$method = strtolower($method ?: '');
		return $this->_validatePermission($class,$method);
	}

	private function _validatePermission($class=null,$method=null){
        // Bypass for superadmin if needed, but let's follow DB rules first
        $username = session()->get(SESSION_NAME.'username');
        if (strtolower($username ?: '') === 'admin') {
            return true; 
        }

		if($this->isLogin==0){
			if($class!=$this->authController){
				if(strtolower($class)!='extension'){
					return false;
				}
			}
		}

		// check except for class
		if(in_array($class,$this->exceptAuth['class'])){
			return true;
		}

        // Query using CI4 Query Builder
        $builder = $this->db->table('tblacos A');
        $builder->select('A.acosid, A.class, A.method');
        $builder->join('tbluseracl B', 'A.acosid = B.acoid');
        $builder->where('LOWER(A.class)', $class);
        $builder->where('B.userpk', $this->userPK);
        $acos = $builder->get()->getResultArray();

		if(empty($acos)){
            // Also check Roles-based ACL if tbluseracl is empty for this user
            $builder = $this->db->table('tblacos A');
            $builder->select('A.acosid, A.class, A.method');
            $builder->join('tblacl C', 'A.acosid = C.acoid');
            $builder->join('tbluserroles D', 'C.roleid = D.roleid');
            $builder->where('LOWER(A.class)', $class);
            $builder->where('D.userpk', $this->userPK);
            $acos = $builder->get()->getResultArray();
            
            if (empty($acos)) {
                return false;
            }
		}
		
		// check jika di data class tidak ditemukan dan except method juga tidak ditemukan
		if($this->in_array_custom($method,$acos)==false && in_array($method,$this->exceptAuth['method'])==false){
			return false;
		}

		return true;
	}

	function in_array_custom($item , $array){
		$found = array_search(strtolower($item),
			array_map(
				function($v){
					return strtolower($v['method']);
				}
			,$array));
		return $found === false ? false : true;
	}
}
