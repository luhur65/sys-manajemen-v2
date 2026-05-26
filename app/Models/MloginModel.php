<?php

namespace App\Models;

use CodeIgniter\Model;

// Migrated from CI3: application/models/mlogin.php

class MloginModel extends Model
{
    protected $table      = 'tbluser';
    protected $primaryKey = 'userpk';
    protected $returnType = 'array';
    protected $allowedFields = ['userid', 'username', 'password', 'userlevel'];
    protected $useTimestamps = false;

    protected $isDev = 0;

    public function login(string $userid, string $password)
    {
        $builder = $this->db->table($this->table);
        $builder->where('userid', $userid);
        $fieldPassword = 'password';
        if ($this->isDev == 1) {
            $fieldPassword = 'password1';
        }
        $builder->where($fieldPassword, $password);
        $builder->limit(1);
        $sql = $builder->get();
        if ($sql->getNumRows() == 1) {
            return $sql;
        } else {
            return false;
        }
    }

    public function cek()
    {
        $userid = $_SESSION['userid'] ?? null;
        $userlevel = $_SESSION['userlevel'] ?? null;
        $username = session()->get(SESSION_NAME.'username') ?? null;
        $password = $_SESSION['password'] ?? null;

        $builder = $this->db->table($this->table);
        $builder->where('userid', $userid);
        $builder->where('userlevel', $userlevel);
        $builder->where('username', $username);
        $builder->where('password', $password);

        $builder->limit(1);
        $sql = $builder->get();
        if ($sql->getNumRows() == 1) {
            return $sql;
        } else {
            return false;
        }
    }
}


