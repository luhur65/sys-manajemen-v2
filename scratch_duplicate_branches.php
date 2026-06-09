<?php
$branches = [
    ['code' => 'jkt', 'Code' => 'Jkt', 'name' => 'JAKARTA', 'Name' => 'Jakarta'],
    ['code' => 'sby', 'Code' => 'Sby', 'name' => 'SURABAYA', 'Name' => 'Surabaya'],
    ['code' => 'mks', 'Code' => 'Mks', 'name' => 'MAKASSAR', 'Name' => 'Makassar'],
    ['code' => 'smg', 'Code' => 'Smg', 'name' => 'SEMARANG', 'Name' => 'Semarang'],
    ['code' => 'btg', 'Code' => 'Btg', 'name' => 'BITUNG', 'Name' => 'Bitung']
];

$basePath = "D:\\php-project\\sys-modern\\app";

$controllerSource = file_get_contents("$basePath\\Controllers\\Omsetrekapmarketingmdn.php");
$modelSource = file_get_contents("$basePath\\Models\\MomsetrekapmarketingmdnModel.php");
$viewSource = file_get_contents("$basePath\\Views\\omsetrekapmarketingmdn\\index.php");

foreach ($branches as $branch) {
    // Replace in Controller
    $cContent = $controllerSource;
    $cContent = str_replace('omsetrekapmarketingmdn', 'omsetrekapmarketing' . $branch['code'], $cContent);
    $cContent = str_replace('Omsetrekapmarketingmdn', 'Omsetrekapmarketing' . $branch['code'], $cContent);
    $cContent = str_replace('MomsetrekapmarketingmdnModel', 'Momsetrekapmarketing' . $branch['code'] . 'Model', $cContent);
    $cContent = str_replace('Medan', $branch['Name'], $cContent);
    $cContent = str_replace('MEDAN', $branch['name'], $cContent);
    file_put_contents("$basePath\\Controllers\\Omsetrekapmarketing" . $branch['code'] . ".php", $cContent);

    // Replace in Model
    $mContent = $modelSource;
    $mContent = str_replace('MomsetrekapmarketingmdnModel', 'Momsetrekapmarketing' . $branch['code'] . 'Model', $mContent);
    $mContent = str_replace('vRekapOmsetMarketing', 'vRekapOmsetMarketing' . $branch['Code'], $mContent);
    file_put_contents("$basePath\\Models\\Momsetrekapmarketing" . $branch['code'] . "Model.php", $mContent);

    // Replace in View
    $vContent = $viewSource;
    $vContent = str_replace('omsetrekapmarketingmdn', 'omsetrekapmarketing' . $branch['code'], $vContent);
    $vContent = str_replace('MEDAN', $branch['name'], $vContent);
    $vContent = str_replace('Medan', $branch['Name'], $vContent);
    
    $viewDir = "$basePath\\Views\\omsetrekapmarketing" . $branch['code'];
    if (!is_dir($viewDir)) {
        mkdir($viewDir, 0777, true);
    }
    file_put_contents($viewDir . "\\index.php", $vContent);
}

echo "Duplication and replacement successful.";
