<?php

namespace App\Controllers;

use App\Models\MlogModel;
use App\Models\MloginModel;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

// Migrated from CI3: application/controllers/Login.php

class Login extends BaseController
{
    protected MlogModel $mlogModel;
    protected MloginModel $mloginModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->mloginModel = new MloginModel();
        $this->mlogModel = new MlogModel();
    }

    public function index()
    {
        if (session()->has(SESSION_NAME . 'logged_in')) {
            return redirect()->to(base_url('home'));
        }
        
        $time = microtime();
        $time = explode(' ', $time);
        $time = $time[1] + $time[0];

        $data['start'] = $time;
        $data['versi'] = CONS_VERSI;
        $data['error'] = session()->getFlashdata(SESSION_NAME . 'message');

        return view('login', $data);
    }

    public function proses()
    {
        // Validate input fields first
        if (!$this->validate([
            'userid'   => 'required',
            'password' => 'required',
        ], [
            'userid'   => ['required' => 'User ID harus diisi!'],
            'password' => ['required' => 'Password harus diisi!'],
        ])) {
            return redirect()->to(base_url('login'))->withInput()->with('errors', $this->validator->getErrors());
        }

        $time = microtime();
        $time = explode(' ', $time);
        $time = $time[1] + $time[0];
        $data['start'] = $time;
        $data['versi'] = CONS_VERSI;

        $this->response->setHeader("Cache-Control", "no-cache, must-revalidate");
        $userid = $this->request->getPost('userid');
        $password = md5($this->request->getPost('password'));

        $cek = $this->mloginModel->login($userid, $password);

        if ($cek != "" && $cek->getNumRows() > 0) {
            $row = $cek->getRow();
            $sessionData = [
                SESSION_NAME . 'userpk' => $row->userpk,
                SESSION_NAME . 'userid' => $row->userid,
                SESSION_NAME . 'username' => $row->username,
                SESSION_NAME . 'userlevel' => $row->userlevel,
                SESSION_NAME . 'password' => $row->password,
                SESSION_NAME . 'logged_in' => 1,
                SESSION_NAME . 'cabangid' => $row->authorityid,
                'username' => $row->username // For compatibility with some controllers using session()->get('username')
            ];
            session()->set($sessionData);

            $this->mlogModel->saveLog($this);
            return redirect()->to(base_url("home"));
        }
        
        return redirect()->to(base_url('login'))
            ->with(SESSION_NAME . 'message', 'Kombinasi userid Atau Password Salah');
    }

    public function logout()
    {
        $this->response->setHeader("Cache-Control", "no-cache, must-revalidate");
        session()->destroy();
        return redirect()->to(base_url("login"));
    }
}
