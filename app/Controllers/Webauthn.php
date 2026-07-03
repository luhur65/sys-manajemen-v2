<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use lbuchs\WebAuthn\WebAuthn as LbWebAuthn;
use App\Models\MWebauthnModel;
use App\Models\MloginModel;
use App\Models\MlogModel;

class Webauthn extends BaseController
{
    private $webauthn;
    private $appname = 'Sys Modern';

    /**
     * Log detail teknis exception ke file log, lalu kembalikan respons JSON
     * dengan pesan yang ramah untuk pengguna. Detail (file/baris) hanya
     * disertakan saat environment development.
     */
    private function errorResponse(\Throwable $e, string $context, string $userMessage, int $statusCode)
    {
        log_message('error', 'WebAuthn ' . $context . ': ' . $e->getMessage() . ' di ' . $e->getFile() . ':' . $e->getLine());

        $body = [
            'error' => true,
            'message' => $userMessage,
        ];
        if (ENVIRONMENT === 'development') {
            $body['debug'] = $e->getMessage() . ' di ' . $e->getFile() . ':' . $e->getLine();
        }

        return $this->response->setStatusCode($statusCode)->setJSON($body);
    }

    private function initWebauthn()
    {
        if ($this->webauthn !== null) {
            return;
        }
        
        // Require the lbuchs webauthn
        $rpId = $_SERVER['HTTP_HOST'] ?? 'localhost';
        // Remove port if exists to prevent WebAuthn library from throwing an exception
        if (($pos = strpos($rpId, ':')) !== false) {
            $rpId = substr($rpId, 0, $pos);
        }
        
        // Formats: name, relying party id, array of supported formats
        $this->webauthn = new LbWebAuthn($this->appname, $rpId, ['apple', 'android-key', 'android-safetynet', 'fido-u2f', 'tpm', 'none']);
    }

    // --- REGISTRATION ---

    /**
     * Get registration challenge
     */
    public function getRegisterArgs()
    {
        try {
            $this->initWebauthn();
            
            // User must be logged in to register a device
            if (!session()->has(SESSION_NAME . 'logged_in')) {
                return $this->response->setJSON(['error' => true, 'message' => 'Sesi Anda telah berakhir. Harus login terlebih dahulu.'])->setStatusCode(401);
            }

            $userId = session()->get(SESSION_NAME . 'userid'); // the string ID
            $userPk = session()->get(SESSION_NAME . 'userpk');
            $username = session()->get(SESSION_NAME . 'username') ?? $userId;

            // Kirim credential yang sudah terdaftar sebagai excludeCredentials:
            // perangkat yang sudah punya credential untuk user ini akan ditolak
            // browser dengan InvalidStateError, sehingga tidak tercipta baris
            // duplikat ketika penanda localStorage di perangkat hilang
            // (clear browsing data, ganti browser, dsb).
            $excludeCredentialIds = [];
            $model = new MWebauthnModel();
            foreach ($model->where('userpk', $userPk)->findAll() as $cred) {
                $excludeCredentialIds[] = base64_decode($cred['credentialId']);
            }

            // Generate cross-platform credential
            $createArgs = $this->webauthn->getCreateArgs(
                $userPk, // userId (hex/binary or string). We use PK for unique internal id.
                $userId, // username
                $username, // displayName
                60, // timeout
                'preferred', // resident key: 'required' membuat perangkat lama (Android 8)
                             // gagal membuat credential sama sekali; 'preferred' = perangkat
                             // modern dapat passkey discoverable, perangkat lama dapat
                             // credential biasa (login via allowCredentials)
                'required', // user verification requirement
                null, // cross-platform attachment (null = both)
                $excludeCredentialIds // tolak pendaftaran ulang perangkat yang sama
            );

            // Save challenge to session as a hex string to avoid serialization issues
            $challengeData = bin2hex($this->webauthn->getChallenge()->getBinaryString());
            session()->set('webauthn_challenge', $challengeData);

            return $this->response->setJSON($createArgs);
        } catch (\Throwable $e) {
            return $this->errorResponse($e, 'getRegisterArgs', 'Gagal menyiapkan pendaftaran biometrik. Silakan coba lagi.', 500);
        }
    }

    /**
     * Process registration response
     */
    public function processRegister()
    {
        if (!session()->has(SESSION_NAME . 'logged_in')) {
            return $this->response->setJSON(['error' => true, 'message' => 'Sesi Anda telah berakhir. Harus login terlebih dahulu.'])->setStatusCode(401);
        }

        $clientDataJSON = base64_decode($this->request->getPost('clientDataJSON'));
        $attestationObject = base64_decode($this->request->getPost('attestationObject'));

        $challengeHex = session()->get('webauthn_challenge');
        if (!$challengeHex) {
            return $this->response->setJSON(['error' => true, 'message' => 'Sesi verifikasi telah kedaluwarsa. Silakan ulangi proses dari awal.'])->setStatusCode(400);
        }
        // Reconstruct the ByteBuffer
        $challenge = new \lbuchs\WebAuthn\Binary\ByteBuffer(hex2bin($challengeHex));
        
        $userPk = session()->get(SESSION_NAME . 'userpk');

        try {
            $this->initWebauthn();
            
            // Verify and process the registration
            $data = $this->webauthn->processCreate($clientDataJSON, $attestationObject, $challenge, 'required', true, false);

            // Store the credential in the database
            $model = new MWebauthnModel();
            
            // Check if credential ID already exists to avoid duplicates
            // SQL Server does not support '=' for TEXT columns, so we use LIKE
            $credentialIdB64 = base64_encode($data->credentialId);
            $existing = $model->like('credentialId', $credentialIdB64, 'none')->first();
            if (!$existing) {
                $model->insert([
                    'userpk' => $userPk,
                    'credentialId' => $credentialIdB64,
                    'credentialPublicKey' => $data->credentialPublicKey,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }

            session()->remove('webauthn_challenge');
            // credentialId dikembalikan agar client bisa menyimpannya sebagai
            // penanda perangkat dan memverifikasinya diam-diam via checkDevice
            return $this->response->setJSON(['success' => true, 'credentialId' => $credentialIdB64]);

        } catch (\Exception $e) {
            return $this->errorResponse($e, 'processRegister', 'Pendaftaran biometrik gagal diverifikasi. Silakan coba lagi.', 400);
        }
    }


    /**
     * Pengecekan diam-diam dari halaman home: apakah credential milik
     * perangkat ini (credid tersimpan di localStorage) masih terdaftar.
     * Tanpa credid, jatuh ke pengecekan apakah user punya credential apa pun.
     */
    public function checkDevice()
    {
        if (!session()->has(SESSION_NAME . 'logged_in')) {
            return $this->response->setJSON(['registered' => false]);
        }

        $userPk = session()->get(SESSION_NAME . 'userpk');
        $model = new MWebauthnModel();

        $credId = $this->request->getGet('credid');
        if ($credId) {
            // SQL Server does not support '=' for TEXT columns, so we use LIKE
            $cred = $model->where('userpk', $userPk)->like('credentialId', $credId, 'none')->first();
            return $this->response->setJSON(['registered' => (bool) $cred]);
        }

        $count = $model->where('userpk', $userPk)->countAllResults();
        return $this->response->setJSON(['registered' => ($count > 0)]);
    }

    /**
     * Check if user has any registered webauthn credentials
     */
    public function checkRegistered()
    {
        if (!session()->has(SESSION_NAME . 'logged_in')) {
            return $this->response->setJSON(['registered' => false]);
        }

        $userPk = session()->get(SESSION_NAME . 'userpk');
        $model = new MWebauthnModel();
        
        $count = $model->where('userpk', $userPk)->countAllResults();
        
        return $this->response->setJSON([
            'registered' => ($count > 0)
        ]);
    }

    // --- LOGIN ---

    /**
     * Get login challenge
     */
    public function getLoginArgs()
    {
        try {
            $this->initWebauthn();

            // Perangkat lama (mis. Android 8/Oreo) tidak mendukung discoverable
            // credential, sehingga allowCredentials kosong selalu berakhir
            // NotAllowedError meski perangkat sudah terdaftar. Jika client
            // mengirim userid yang tersimpan di perangkat (di-set setiap kali
            // user login), sertakan daftar credentialId milik user tersebut
            // agar credential non-discoverable tetap bisa dipakai.
            $credentialIds = [];
            $userid = $this->request->getGet('userid');
            if ($userid) {
                $db = \Config\Database::connect();
                $user = $db->table('tbluser')->where('userid', $userid)->get()->getRowArray();
                if ($user) {
                    $model = new MWebauthnModel();
                    $creds = $model->where('userpk', $user['userpk'])->findAll();
                    foreach ($creds as $cred) {
                        $credentialIds[] = base64_decode($cred['credentialId']);
                    }
                }
            }

            $getArgs = $this->webauthn->getGetArgs(
                $credentialIds, // daftar credential user ini; kosong = passkey/discoverable (perangkat modern)
                60, // timeout
                true, // allowUsb
                true, // allowNfc
                true, // allowBle
                true, // allowHybrid
                true, // allowInternal
                'required' // require user verification
            );

            // Save challenge to session as hex string
            $challengeData = bin2hex($this->webauthn->getChallenge()->getBinaryString());
            session()->set('webauthn_challenge', $challengeData);

            return $this->response->setJSON($getArgs);
        } catch (\Throwable $e) {
            return $this->errorResponse($e, 'getLoginArgs', 'Gagal menyiapkan login biometrik. Silakan coba lagi.', 500);
        }
    }

    /**
     * Process login response
     */
    public function processLogin()
    {
        $clientDataJSON = base64_decode($this->request->getPost('clientDataJSON'));
        $authenticatorData = base64_decode($this->request->getPost('authenticatorData'));
        $signature = base64_decode($this->request->getPost('signature'));
        $userHandle = base64_decode($this->request->getPost('userHandle'));
        $id = base64_decode($this->request->getPost('id')); // This is the credential ID
        
        $challengeHex = session()->get('webauthn_challenge');
        if (!$challengeHex) {
            return $this->response->setJSON(['error' => true, 'message' => 'Sesi verifikasi telah kedaluwarsa. Silakan ulangi proses dari awal.'])->setStatusCode(400);
        }
        $challenge = new \lbuchs\WebAuthn\Binary\ByteBuffer(hex2bin($challengeHex));

        try {
            $this->initWebauthn();

            // Look up the credential public key from our database
            $model = new MWebauthnModel();
            $cred = $model->like('credentialId', base64_encode($id), 'none')->first();
            if (!$cred) {
                return $this->response->setJSON(['error' => true, 'message' => 'Perangkat ini belum terdaftar. Harus Login Terlebih Dahulu menggunakan password, lalu daftarkan biometrik di menu Profil.'])->setStatusCode(400);
            }

            // [KEAMANAN LOCKSCREEN] Jika user sudah login, pastikan sidik jari milik user yang sedang aktif!
            if (session()->has(SESSION_NAME . 'logged_in')) {
                if ($cred['userpk'] != session()->get(SESSION_NAME . 'userpk')) {
                    return $this->response->setJSON(['error' => true, 'message' => 'Akses ditolak: Sidik jari bukan milik pengguna sesi ini!'])->setStatusCode(403);
                }
            }

            // Verify the login
            $this->webauthn->processGet(
                $clientDataJSON, 
                $authenticatorData, 
                $signature, 
                $cred['credentialPublicKey'], 
                $challenge, 
                null, 
                false // Allow login even if device skipped user verification
            );

            // Authentication successful!
            
            // Jika belum login (login dari halaman utama), buat sesi baru
            if (!session()->has(SESSION_NAME . 'logged_in')) {
                // Load user data using userpk
                $loginModel = new MloginModel();
                $db = \Config\Database::connect();
                $user = $db->table('tbluser')->where('userpk', $cred['userpk'])->get()->getRowArray();

                if (!$user) {
                    return $this->response->setJSON(['error' => true, 'message' => 'Data pengguna tidak ditemukan. Harus Login Terlebih Dahulu menggunakan password.'])->setStatusCode(400);
                }

                // Create session
                $sessionData = [
                    SESSION_NAME . 'userpk' => $user['userpk'],
                    SESSION_NAME . 'userid' => $user['userid'],
                    SESSION_NAME . 'username' => $user['username'],
                    SESSION_NAME . 'userlevel' => $user['userlevel'],
                    SESSION_NAME . 'password' => $user['password'],
                    SESSION_NAME . 'logged_in' => 1,
                    SESSION_NAME . 'cabangid' => $user['authorityid'],
                    'username' => $user['username']
                ];
                session()->set($sessionData);

                // Save login log
                $logModel = new MlogModel();
                $logModel->saveLog($this);
            }

            session()->remove('webauthn_challenge');
            return $this->response->setJSON(['success' => true]);

        } catch (\Exception $e) {
            return $this->errorResponse($e, 'processLogin', 'Verifikasi biometrik gagal. Silakan coba lagi atau login menggunakan password.', 400);
        }
    }
}
