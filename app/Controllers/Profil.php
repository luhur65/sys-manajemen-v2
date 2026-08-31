<?php
namespace App\Controllers;

use App\Models\MlogModel;
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

		// Userid adalah identitas yang dipakai seluruh log lain. Mengubahnya
		// tanpa jejak membuat baris log lama sulit dihubungkan ke orangnya.
		$sebelum = [
			'userid'   => session()->get(SESSION_NAME.'userid'),
			'username' => session()->get(SESSION_NAME.'username'),
		];

		$data = $this->muserModel->edit("tbluser",$data,$userpk);
		session()->set(SESSION_NAME.'userid', $userid);
		session()->set(SESSION_NAME.'username', $username);

		$this->auditLog(MlogModel::DATA_UPDATE, 'Ubah profil sendiri', [
			'tabel'     => 'tbluser',
			'userpk'    => $userpk,
			'perubahan' => MlogModel::changes($sebelum, ['userid' => $userid, 'username' => $username]),
		]);

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
                // Riwayat ganti password harus mencatat siapa yang BENAR-BENAR
                // mengubahnya. Pada sesi Panel Casting, membaca username sesi
                // akan mencatat nama orang yang sedang ditiru — persis orang
                // yang tidak melakukannya.
                'modifiedby' => \App\Libraries\AuditUser::modifiedBy(),
                'modifiedon' => date('Y-m-d H:i:s')
            ];

            $db = \Config\Database::connect();
            $db->table('tblhistorypassword')->insert($insert);

			// M-07: pergantian password adalah peristiwa keamanan. Nilainya
			// tidak ikut tercatat — yang perlu diketahui hanya kapan, oleh
			// siapa, dan dari IP mana.
			$this->auditLog(MlogModel::DATA_UPDATE, 'Ganti password sendiri', [
				'tabel'  => 'tbluser',
				'userpk' => $userpk,
			]);

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

            // M-07: perangkat biometrik adalah faktor otentikasi. Pencabutannya
            // dicatat supaya "kenapa saya tidak bisa login lagi" punya jawaban.
            $this->auditLog(MlogModel::DATA_DELETE, 'Hapus perangkat biometrik (WebAuthn)', [
                'tabel'  => 'tbluser_webauthn',
                'id'     => $id,
                'userpk' => $userpk,
            ]);

            return $this->response->setJSON(['success' => true]);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Device not found or not authorized']);
    }
}





