<?php
$content = "\n\n";
$branches = ['jkt', 'sby', 'mks', 'smg', 'btg'];
foreach($branches as $b) {
    $content .= "// Routes for App\Controllers\Omsetrekapmarketing$b\n";
    $content .= "\$routes->match(['GET', 'POST'], 'Omsetrekapmarketing$b', 'Omsetrekapmarketing$b::index');\n";
    $content .= "\$routes->match(['GET', 'POST'], 'omsetrekapmarketing$b', 'Omsetrekapmarketing$b::index');\n";
    $content .= "\$routes->match(['GET', 'POST'], 'Omsetrekapmarketing$b/index', 'Omsetrekapmarketing$b::index');\n";
    $content .= "\$routes->match(['GET', 'POST'], 'omsetrekapmarketing$b/index', 'Omsetrekapmarketing$b::index');\n";
    $content .= "\$routes->match(['GET', 'POST'], 'Omsetrekapmarketing$b/grid', 'Omsetrekapmarketing$b::grid');\n";
    $content .= "\$routes->match(['GET', 'POST'], 'omsetrekapmarketing$b/grid', 'Omsetrekapmarketing$b::grid');\n";
    $content .= "\$routes->match(['GET', 'POST'], 'Omsetrekapmarketing$b/combomarketing', 'Omsetrekapmarketing$b::combomarketing');\n";
    $content .= "\$routes->match(['GET', 'POST'], 'omsetrekapmarketing$b/combomarketing', 'Omsetrekapmarketing$b::combomarketing');\n\n";
}
file_put_contents('D:\\php-project\\sys-modern\\app\\Config\\Routes.php', $content, FILE_APPEND);
echo "Routes appended.";
