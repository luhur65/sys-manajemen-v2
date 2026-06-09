<?php
$content = "\n\n";
$content .= "// Routes for App\Controllers\Supirpercabang (Supirpercabang)\n";
$content .= "\$routes->match(['GET', 'POST'], 'Supirpercabang', 'Supirpercabang::index');\n";
$content .= "\$routes->match(['GET', 'POST'], 'supirpercabang', 'Supirpercabang::index');\n";
$content .= "\$routes->match(['GET', 'POST'], 'Supirpercabang/index', 'Supirpercabang::index');\n";
$content .= "\$routes->match(['GET', 'POST'], 'supirpercabang/index', 'Supirpercabang::index');\n";
$content .= "\$routes->match(['GET', 'POST'], 'Supirpercabang/grid', 'Supirpercabang::grid');\n";
$content .= "\$routes->match(['GET', 'POST'], 'supirpercabang/grid', 'Supirpercabang::grid');\n";
$content .= "\$routes->match(['GET', 'POST'], 'Supirpercabang/detail', 'Supirpercabang::detail');\n";
$content .= "\$routes->match(['GET', 'POST'], 'supirpercabang/detail', 'Supirpercabang::detail');\n";

file_put_contents('D:\\php-project\\sys-modern\\app\\Config\\Routes.php', $content, FILE_APPEND);
echo "Routes appended.";
