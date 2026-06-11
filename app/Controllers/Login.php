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

    public function forgotPassword()
    {
        $username = $this->request->getPost('user');
        $check = $this->request->getPost('check');
        
        $muserModel = new \App\Models\MuserModel();
        $userRow = $muserModel->where('userid', $username)->first();

        if (!$userRow) {
            return $this->response->setStatusCode(400)->setJSON(['errors' => ['user' => 'Username tidak ditemukan']]);
        }

        if ($check) {
            return $this->response->setJSON(['status' => 200, 'message' => 'Jika username ada, link reset akan dikirim ke email/WhatsApp.']);
        }

        $email = $userRow->email;
        $nowhatsapp = $userRow->nowhatsapp;

        if (empty($email) && empty($nowhatsapp)) {
            return $this->response->setStatusCode(400)->setJSON(['errors' => ['user' => 'Akun ini tidak memiliki email atau nomor WhatsApp terdaftar.']]);
        }

        $resetModel = new \App\Models\PasswordResetModel();
        $resetModel->where('username', $username)->delete();

        $rawToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $rawToken);

        $resetModel->insert([
            'username' => $username,
            'token' => $hashedToken,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
        ]);

        $resetLink = base_url("reset-password?token={$rawToken}&user=" . urlencode($username));

        // Send Email
        if (!empty($email)) {
            $emailService = \Config\Services::email();
            $emailService->setTo($email);
            $emailService->setSubject('Reset Password Sys-Modern');
            $emailService->setMessage("Kami menerima permintaan reset password untuk akun Anda.<br><br>Klik link berikut untuk mereset password Anda: <a href='{$resetLink}'>Reset Password</a><br><br>Jika Anda tidak meminta ini, abaikan email ini.");
            $emailService->send();
        }

        // Send WA
        if (!empty($nowhatsapp)) {
            // TODO: Implement WA API Call Here
        }

        return $this->response->setJSON(['status' => 200, 'message' => 'Link reset sudah dikirim ke email / WhatsApp Anda.']);
    }

    public function resetPasswordForm()
    {
        $token = $this->request->getGet('token');
        $user = $this->request->getGet('user');

        if (!$token || !$user) {
            return redirect()->to('login')->with(SESSION_NAME . 'message', 'Link tidak valid.');
        }

        $resetModel = new \App\Models\PasswordResetModel();
        $row = $resetModel->where('username', $user)->first();

        if (!$row || !hash_equals($row->token, hash('sha256', $token))) {
            return redirect()->to('login')->with(SESSION_NAME . 'message', 'Link reset tidak valid atau sudah tidak berlaku.');
        }

        if (strtotime($row->expires_at) < time()) {
            $resetModel->where('username', $user)->delete();
            return redirect()->to('login')->with(SESSION_NAME . 'message', 'Link reset sudah kedaluwarsa.');
        }

        $siteConfig = config('Site');
        return view('auth/reset_password', [
            'token' => $token,
            'user' => $user,
            'siteConfig' => $siteConfig
        ]);
    }

    public function resetPasswordSubmit()
    {
        $token = $this->request->getPost('token');
        $user = $this->request->getPost('user');
        $password = $this->request->getPost('password');

        if (!$this->validate(['password' => 'required|min_length[5]'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $resetModel = new \App\Models\PasswordResetModel();
        $row = $resetModel->where('username', $user)->first();

        if (!$row || !hash_equals($row->token, hash('sha256', $token))) {
            return redirect()->to('login')->with(SESSION_NAME . 'message', 'Link reset tidak valid atau sudah tidak berlaku.');
        }

        if (strtotime($row->expires_at) < time()) {
            $resetModel->where('username', $user)->delete();
            return redirect()->to('login')->with(SESSION_NAME . 'message', 'Link reset sudah kedaluwarsa.');
        }

        $muserModel = new \App\Models\MuserModel();
        $userRow = $muserModel->where('userid', $user)->first();
        
        if ($userRow) {
            $muserModel->update($userRow->userpk, [
                'password' => md5($password)
            ]);
        }

        $resetModel->where('username', $user)->delete();

        return redirect()->to('login')->with(SESSION_NAME . 'message', 'Password berhasil direset. Silakan login dengan password baru.');
    }
}
