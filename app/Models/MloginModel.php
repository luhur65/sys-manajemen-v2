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
        $builder->limit(1);
        $sql = $builder->get();
        if ($sql->getNumRows() == 1) {
            $row = $sql->getRow();
            $fieldPassword = 'password';
            if ($this->isDev == 1) {
                $fieldPassword = 'password1';
            }
            $hash = (string)$row->$fieldPassword;
            $hashTrim = trim($hash);

            // Cek apakah hash adalah bcrypt/argon2 (berawalan $2y$, $2a$, $2b$, $argon2)
            if (str_starts_with($hashTrim, '$2y$') || str_starts_with($hashTrim, '$2a$') || str_starts_with($hashTrim, '$2b$') || str_starts_with($hashTrim, '$argon2')) {
                if (password_verify($password, $hashTrim)) {
                    return $sql;
                }
            } else {
                // Belum dimigrasi, anggap hash adalah MD5
                if (strcasecmp(md5($password), $hashTrim) === 0) {
                    // Update ke bcrypt on-the-fly
                    $newHash = password_hash($password, PASSWORD_BCRYPT);
                    $this->db->table($this->table)
                             ->where('userid', $userid)
                             ->update([$fieldPassword => $newHash]);
                    
                    // Kembalikan query baru agar objek session mendapatkan hash yang terbaru
                    return $this->db->table($this->table)->where('userid', $userid)->get();
                }
            }
        }
        return false;
    }

    /**
     * Ambil ulang baris user milik sesi yang sedang berjalan.
     *
     * H-05: sebelumnya method ini mencocokkan `WHERE password = <hash dari sesi>`
     * — itulah yang memaksa hash bcrypt ikut disimpan di session file. Kunci
     * primer `userpk` sudah cukup untuk mengidentifikasi baris, jadi hash tidak
     * perlu dibawa-bawa. Sekaligus memperbaiki pembacaan `$_SESSION['userid']`
     * yang mengabaikan prefiks SESSION_NAME sehingga selalu bernilai null.
     *
     * Catatan: tidak ada pemanggil di codebase saat ini (sisa migrasi CI3).
     */
    public function cek()
    {
        $userpk = session()->get(SESSION_NAME . 'userpk');
        if (! $userpk) {
            return false;
        }

        $sql = $this->db->table($this->table)
                        ->where('userpk', $userpk)
                        ->limit(1)
                        ->get();

        return $sql->getNumRows() === 1 ? $sql : false;
    }
}


