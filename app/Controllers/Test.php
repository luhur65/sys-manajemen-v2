<?php

namespace App\Controllers;

use App\Models\RolesModel;

class Test extends BaseController
{
    public function index()
    {
        $model = new RolesModel();
        $data = ['roleid' => 1, 'rolename' => 'ADMIN'];
        $status = $model->saveData($data);

        if (!$status) {
            echo "FAILED.\n";
            print_r($model->errors());
        } else {
            echo "SUCCESS.\n";
        }
    }
}
