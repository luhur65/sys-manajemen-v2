<?php
$content = "\n\n";
$content .= "// Routes for App\Controllers\Grafikemklluar (Grafikemklluar)\n";
$content .= "\$routes->match(['GET', 'POST'], 'Grafikemklluar', 'Grafikemklluar::index');\n";
$content .= "\$routes->match(['GET', 'POST'], 'grafikemklluar', 'Grafikemklluar::index');\n";
$content .= "\$routes->match(['GET', 'POST'], 'Grafikemklluar/index', 'Grafikemklluar::index');\n";
$content .= "\$routes->match(['GET', 'POST'], 'grafikemklluar/index', 'Grafikemklluar::index');\n";

file_put_contents('D:\\php-project\\sys-modern\\app\\Config\\Routes.php', $content, FILE_APPEND);
echo "Routes appended.";
