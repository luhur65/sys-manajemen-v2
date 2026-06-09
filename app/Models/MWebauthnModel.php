<?php

namespace App\Models;

use CodeIgniter\Model;

class MWebauthnModel extends Model
{
    protected $table      = 'tbluser_webauthn';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['userpk', 'credentialId', 'credentialPublicKey', 'created_at'];
    protected $useTimestamps = false;
}
