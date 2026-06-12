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
                return $this->response->setJSON(['error' => 'Not logged in'])->setStatusCode(401);
            }

            $userId = session()->get(SESSION_NAME . 'userid'); // the string ID
            $userPk = session()->get(SESSION_NAME . 'userpk');
            $username = session()->get(SESSION_NAME . 'username') ?? $userId;
            
            // Generate cross-platform credential
            $createArgs = $this->webauthn->getCreateArgs(
                $userPk, // userId (hex/binary or string). We use PK for unique internal id.
                $userId, // username
                $username, // displayName
                60, // timeout
                true, // require resident key (for passwordless login usually)
                'required', // user verification requirement
                null // cross-platform attachment (null = both)
            );

            // Save challenge to session as a hex string to avoid serialization issues
            $challengeData = bin2hex($this->webauthn->getChallenge()->getBinaryString());
            session()->set('webauthn_challenge', $challengeData);

            return $this->response->setJSON($createArgs);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'error' => true,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    /**
     * Process registration response
     */
    public function processRegister()
    {
        if (!session()->has(SESSION_NAME . 'logged_in')) {
            return $this->response->setJSON(['error' => 'Not logged in'])->setStatusCode(401);
        }

        $clientDataJSON = base64_decode($this->request->getPost('clientDataJSON'));
        $attestationObject = base64_decode($this->request->getPost('attestationObject'));
        
        $challengeHex = session()->get('webauthn_challenge');
        if (!$challengeHex) {
            return $this->response->setJSON(['error' => 'No challenge found in session'])->setStatusCode(400);
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
            $existing = $model->like('credentialId', base64_encode($data->credentialId), 'none')->first();
            if (!$existing) {
                $model->insert([
                    'userpk' => $userPk,
                    'credentialId' => base64_encode($data->credentialId),
                    'credentialPublicKey' => $data->credentialPublicKey,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }

            session()->remove('webauthn_challenge');
            return $this->response->setJSON(['success' => true]);

        } catch (\Exception $e) {
            return $this->response->setJSON(['error' => $e->getMessage()])->setStatusCode(400);
        }
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
            
            // For passwordless, we do not require userid up front. 
            // We just get the challenge, and the authenticator returns the credentialId, which we look up.
            
            $getArgs = $this->webauthn->getGetArgs(
                [], // allowed credentials (empty = allow any registered passwordless credential)
                60,
                true, // require user verification
                true, // user presence
                true, // allow cross-platform
                true  // allow platform
            );

            // Save challenge to session as hex string
            $challengeData = bin2hex($this->webauthn->getChallenge()->getBinaryString());
            session()->set('webauthn_challenge', $challengeData);

            return $this->response->setJSON($getArgs);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'error' => true,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
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
            return $this->response->setJSON(['error' => 'No challenge found in session'])->setStatusCode(400);
        }
        $challenge = new \lbuchs\WebAuthn\Binary\ByteBuffer(hex2bin($challengeHex));

        try {
            $this->initWebauthn();
            
            // Look up the credential public key from our database
            $model = new MWebauthnModel();
            $cred = $model->like('credentialId', base64_encode($id), 'none')->first();
            if (!$cred) {
                return $this->response->setJSON(['error' => 'Credential not found in database'])->setStatusCode(400);
            }

            // Verify the login
            $this->webauthn->processGet(
                $clientDataJSON, 
                $authenticatorData, 
                $signature, 
                $cred['credentialPublicKey'], 
                $challenge, 
                null, 
                'required'
            );

            // Authentication successful!
            // Load user data using userpk
            $loginModel = new MloginModel();
            $db = \Config\Database::connect();
            $user = $db->table('tbluser')->where('userpk', $cred['userpk'])->get()->getRowArray();

            if (!$user) {
                throw new \Exception('User not found.');
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

            session()->remove('webauthn_challenge');
            return $this->response->setJSON(['success' => true]);

        } catch (\Exception $e) {
            return $this->response->setJSON(['error' => $e->getMessage()])->setStatusCode(400);
        }
    }
}
