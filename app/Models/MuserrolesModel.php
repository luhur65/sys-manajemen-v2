<?php

namespace App\Models;

use CodeIgniter\Model;

class MuserrolesModel extends Model
{
    protected $table = 'tbluserroles';
    protected $primaryKey = 'userpk'; // Composite key technically, but this is fine for insertion
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $allowedFields = [
        'userpk', 'roleid', 'modifiedby', 'modifiedon'
    ];

    public function getByUserID($user_id)
    {
        return $this->where('userpk', $user_id)->findAll();
    }

    public function deleteByRole($role_id)
    {
        return $this->where('roleid', $role_id)->delete();
    }

    public function saveBatchRoles($data)
    {
        if (isset($data['roles'])) {
            $userpk = $data['userpk'];
            $currentRoles = $this->where('userpk', $userpk)->findAll();
            $existingRoles = array_column($currentRoles, 'roleid');

            $newRoles = $data['roles'];

            $toInsert = array_diff($newRoles, $existingRoles);
            $toDelete = array_diff($existingRoles, $newRoles);

            $db = \Config\Database::connect();
            $db->transStart();

            if (!empty($toDelete)) {
                $this->where('userpk', $userpk)->whereIn('roleid', $toDelete)->delete();
            }

            if (!empty($toInsert)) {
                $insertData = [];
                $modifiedby = strtoupper(session()->get('USERNAME') ?? 'SYSTEM');
                $modifiedon = date("Y-m-d H:i:s");
                
                foreach ($toInsert as $role) {
                    $insertData[] = [
                        'userpk' => $userpk,
                        'roleid' => $role,
                        'modifiedby' => $modifiedby,
                        'modifiedon' => $modifiedon
                    ];
                }
                $this->insertBatch($insertData);
            }

            $db->transComplete();
            return $db->transStatus();
        }
        return false;
    }
}
