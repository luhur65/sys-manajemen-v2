<?php
namespace App\Libraries;

// Migrated from CI3: application/libraries/MyAuth.php

class MyAuth {
    protected $db = null;
	private $isLogin, $userPK;
	private $authController;
    private $baseUrl;

	/**
	 * Cache keputusan per instance supaya render menu (yang memanggil
	 * hasPermission puluhan kali) tidak menembak database berulang kali.
	 */
	private $permissionCache = [];
	private $acosCache = [];

	/**
	 * Class yang izinnya menumpang pada class lain (izin diberikan bila user
	 * punya hak pada SALAH SATU class induk).
	 *
	 * 'useracl' tidak pernah muncul di menu — ia hanya dicapai dari dalam layar
	 * admin User (modal Manage User Roles) dan layar admin Roles (grid katalog
	 * aco), sehingga tidak punya baris sendiri di tblacos. Sebelumnya class ini
	 * masuk daftar except sehingga SIAPA PUN yang login bisa memanggil
	 * POST /useracl/userroles/<userpk-nya-sendiri> dan mengangkat dirinya
	 * menjadi admin. Sekarang izinnya mengikuti class induknya.
	 */
	private $classAliases = [
		'useracl' => ['user','roles'],
	];

	/**
	 * exceptAuth['class']  : class yang boleh diakses semua user yang sudah login.
	 *                        Isinya hanya halaman milik-sendiri (profil), landing
	 *                        page (home), alur autentikasi, dan endpoint infrastruktur.
	 * exceptAuth['method'] : method pendamping yang diizinkan SETELAH user terbukti
	 *                        punya minimal satu hak pada class tersebut. Daftar ini
	 *                        tidak melonggarkan class — hanya menghindari keharusan
	 *                        mendaftarkan setiap endpoint grid/lookup ke tblacos.
	 */
	private $exceptAuth = [
		'class'=>[
			// alur autentikasi & halaman milik sendiri
			'login','logout','home','profil','extension',
			// endpoint infrastruktur (tidak membaca data bisnis)
			'errors','webauthn','gridpreference','harilibur',
			// class warisan CI3 yang controllernya sudah tidak ada
			'relasi','acos','sop','cabang',
		],
		'method'=>[
			// grid / data pendamping
			'gridtab','grid','griddetail','gridasuransi','getgriddata','getgriddatamks','operation','excel','crud'
			// lookup & combo
			,'carishippersama','listmarketingcabang','combotradoluar','combomarketing','datacabang','datamarketing'
			,'getbyid','getroles','getacos','lookupaco','get_marketing','getlastupdate','detail','reseq','userroles'
			// varian per cabang
			,'pesanmdn','pesansby','pesanmks','pesanjkt','pesantnl','pesanbtg','mks'
		],
	];

	public function __construct($params = []){
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
			throw new \CodeIgniter\Exceptions\PageNotFoundException("You don't have access to $class/$method");
		}
	}

	public function hasPermission($class,$method) {
		$class = strtolower($class ?: '');
		$method = strtolower($method ?: '');

		$key = $class . '|' . $method;
		if (!array_key_exists($key, $this->permissionCache)) {
			$this->permissionCache[$key] = $this->_validatePermission($class,$method);
		}

		return $this->permissionCache[$key];
	}

	private function _validatePermission($class=null,$method=null){
        // Bypass removed to enforce DB ACL for admin
		if($this->isLogin==0){
			if($class!=$this->authController){
				if(strtolower($class)!='extension'){
					return false;
				}
			}
		}

		// class yang izinnya menumpang class lain (mis. useracl -> user/roles)
		if(isset($this->classAliases[$class])){
			foreach((array) $this->classAliases[$class] as $parent){
				if($this->_validatePermission($parent,$method)){
					return true;
				}
			}

			return false;
		}

		// check except for class
		if(in_array($class,$this->exceptAuth['class'])){
			return true;
		}

		$acos = $this->getAcosForClass($class);

		if(empty($acos)){
			return false;
		}

		// check jika di data class tidak ditemukan dan except method juga tidak ditemukan
		if($this->in_array_custom($method,$acos)==false && in_array($method,$this->exceptAuth['method'])==false){
			return false;
		}

		return true;
	}

	/**
	 * Koneksi database dibuat saat benar-benar dibutuhkan, bukan di constructor,
	 * supaya request yang keputusannya sudah selesai lewat daftar except / alias
	 * (dan unit test yang memalsukan data ACL) tidak perlu menyentuh database.
	 */
	protected function db(){
		if ($this->db === null) {
			$this->db = \Config\Database::connect(); // Uses default group
		}

		return $this->db;
	}

	/**
	 * Ambil seluruh aco pada satu class yang benar-benar dimiliki user,
	 * baik lewat hak langsung (tbluseracl) maupun lewat role (tblacl + tbluserroles).
	 */
	protected function getAcosForClass($class){
		if (array_key_exists($class, $this->acosCache)) {
			return $this->acosCache[$class];
		}

        $db = $this->db();

        // Query using CI4 Query Builder
        $builder = $db->table('tblacos A');
        $builder->select('A.acosid, A.class, A.method');
        $builder->join('tbluseracl B', 'A.acosid = B.acoid');
        $builder->where('LOWER(A.class)', $class);
        $builder->where('B.userpk', $this->userPK);
        $acos = $builder->get()->getResultArray();

		if(empty($acos)){
            // Also check Roles-based ACL if tbluseracl is empty for this user
            $builder = $db->table('tblacos A');
            $builder->select('A.acosid, A.class, A.method');
            $builder->join('tblacl C', 'A.acosid = C.acoid');
            $builder->join('tbluserroles D', 'C.roleid = D.roleid');
            $builder->where('LOWER(A.class)', $class);
            $builder->where('D.userpk', $this->userPK);
            $acos = $builder->get()->getResultArray();
		}

		return $this->acosCache[$class] = $acos;
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
