<?php
namespace App\Controllers;
use App\Models\RolesModel;

class Test2 extends BaseController
{
    public function index()
    {
        $model = new RolesModel();
        $data = [
            'roleid' => 3,
            'rolename' => 'ADMIN',
            'role_permission' => [
                'acos' => [59,20,22,21,18,23,19,40,63,64,61,62,39,47,46,48,49,51,50,52,53,44,45,3,5,4,6,1,2,43,56,9,11,10,7,8,25,30,26,24,33,32,35,36,37,38,14,16,15,12,13,17]
            ]
        ];
        
        $model->db->transStart();
        $save = [
            'rolename' => 'ADMIN',
            'modifiedby' => 'SYSTEM',
            'modifiedon' => date("Y-m-d H:i:s")
        ];
        
        $log = "";
        $log .= "Updating role...\n";
        $result = $model->update(3, $save);
        if (!$result) {
            $log .= "Update failed: " . print_r($model->db->error(), true);
        } else {
            $log .= "Update succeeded.\n";
            $log .= "Saving permissions...\n";
            $res = $model->saveRolePermission(3, $data['role_permission']['acos']);
            if (!$res) {
                $log .= "saveRolePermission failed.\n";
            } else {
                $log .= "saveRolePermission succeeded.\n";
            }
        }
        
        if ($model->db->transStatus() === false) {
            $log .= "transStatus is FALSE. DB Error: " . print_r($model->db->error(), true);
            $model->db->transRollback();
        } else {
            $log .= "transStatus is TRUE.\n";
            $model->db->transCommit();
        }
        file_put_contents('test2_log.txt', $log);
    }
}
