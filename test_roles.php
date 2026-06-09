<?php
$_POST['oper'] = 'edit';
$_POST['id'] = 1;
$_POST['rolename'] = 'ADMIN';

// Let's emulate a call to Roles::crud()
$app = \Config\Services::codeigniter(null, false);
$app->initialize();
$request = \Config\Services::request();
$request->setMethod('post');
// Actually, it's easier to just call the model's saveData function directly.
$model = new \App\Models\RolesModel();
$status = $model->saveData(['roleid' => 1, 'rolename' => 'ADMIN']);
echo "STATUS: " . ($status ? "SUCCESS" : "FAIL") . "\n";
if (!$status) {
    print_r($model->errors());
}
