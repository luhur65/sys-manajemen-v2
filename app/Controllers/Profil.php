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

        $mwebauthnModel = new \App\Models\MWebauthnModel();
        $data['webauthn_devices'] = $mwebauthnModel->where('userpk', session()->get(SESSION_NAME.'userpk'))->findAll();

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
		$password1 = rawurldecode((string)$this->request->getGet('password1'));
		$password2 = rawurldecode((string)$this->request->getGet('password2'));
		$password3 = rawurldecode((string)$this->request->getGet('password3'));
        
        $isValid = false;
        $hashTrim = trim((string)$password);
        if (str_starts_with($hashTrim, '$2y$') || str_starts_with($hashTrim, '$2a$') || str_starts_with($hashTrim, '$2b$') || str_starts_with($hashTrim, '$argon2')) {
            $isValid = password_verify($password1, $hashTrim);
        } else {
            $isValid = (strcasecmp(md5($password1), $hashTrim) === 0);
        }

		if(!$isValid){
			echo"1";
		}
		else if($password2!=$password3){
			echo"2";
		}
		else{
            $newHash = password_hash($password2, PASSWORD_BCRYPT);
			$data = array('password' => $newHash );
			$data = $this->muserModel->edit("tbluser",$data,$userpk);
			session()->set(SESSION_NAME.'password', $newHash);
			$insert = [
                'userpk' => $userpk,
                'password' => $newHash,
                'modifiedby' => session()->get(SESSION_NAME.'username'),
                'modifiedon' => date('Y-m-d H:i:s')
            ];

            $db = \Config\Database::connect();
            $db->table('tblhistorypassword')->insert($insert);
			echo"3";
		}
	}

    public function deleteWebauthnDevice()
    {
        $id = $this->request->getPost('id');
        $userpk = session()->get(SESSION_NAME . 'userpk');

        if (!$id || !$userpk) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        $mwebauthnModel = new \App\Models\MWebauthnModel();
        // Pastikan device tersebut milik user yang sedang login
        $device = $mwebauthnModel->where('id', $id)->where('userpk', $userpk)->first();
        
        if ($device) {
            $mwebauthnModel->delete($id);
            return $this->response->setJSON(['success' => true]);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Device not found or not authorized']);
    }
}





