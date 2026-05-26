<?php
namespace App\Controllers;

use App\Models\MprofilModel;
use App\Models\MuserModel;

use App\Controllers\BaseController;

// Migrated from CI3: application/controllers/profil.php
 class Profil extends BaseController
{
    protected MuserModel $muserModel;
    protected MprofilModel $mprofilModel;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->mprofilModel = new MprofilModel();
        $this->muserModel = new MuserModel();

		date_default_timezone_set("Asia/Jakarta");
		ini_set('memory_limit', '-1');
	}
	/**
     * Fungsi menu profil
     * @AclName menu profil
     */
	public function index(){
		$data['title'] = 'Profil';
		$data['sqlprofil'] = $this->mprofilModel->get(session()->get(SESSION_NAME.'userid'));

		return $this->render('profil/view',$data);

	}

	public function editprofil(){
		$userpk = session()->get(SESSION_NAME.'userpk');
		$userid = $this->request->getGet('userid');
		$username = $this->request->getGet('username');
		$data = array('userid' => $userid,'username' => $username);
		$data = $this->muserModel->edit("tbluser",$data,$userpk);
		session()->set(SESSION_NAME.'userid', $userid);
		session()->set(SESSION_NAME.'username', $username);
		echo "1";
	}

	public function editpassword(){
		$userpk = session()->get(SESSION_NAME.'userpk');
		$password = session()->get(SESSION_NAME.'password');
		$password1 = md5(rawurldecode($this->request->getGet('password1')));
		$password2 = md5(rawurldecode($this->request->getGet('password2')));
		$password3 = md5(rawurldecode($this->request->getGet('password3')));
		if($password!=$password1){
			echo"1";
		}
		else if($password2!=$password3){
			echo"2";
		}
		else{
			$data = array('password' => $password2 );
			$data = $this->muserModel->edit("tbluser",$data,$userpk);
			session()->set(SESSION_NAME.'password', $password2);
			$insert = [
                'userpk' => $userpk,
                'password' => $password2,
                'modifiedby' => session()->get(SESSION_NAME.'username'),
                'modifiedon' => date('Y-m-d H:i:s')
            ];

            $db = \Config\Database::connect();
            $db->table('tblhistorypassword')->insert($insert);
			echo"3";
		}
	}
}





