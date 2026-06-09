<?php
$content = "\n\n";
$content .= "// Routes for App\Controllers\Tracing (Tracing)\n";
$content .= "\$routes->match(['GET', 'POST'], 'Tracing', 'Tracing::index');\n";
$content .= "\$routes->match(['GET', 'POST'], 'tracing', 'Tracing::index');\n";
$content .= "\$routes->match(['GET', 'POST'], 'Tracing/index', 'Tracing::index');\n";
$content .= "\$routes->match(['GET', 'POST'], 'tracing/index', 'Tracing::index');\n";
$content .= "\$routes->match(['GET', 'POST'], 'Tracing/grid', 'Tracing::grid');\n";
$content .= "\$routes->match(['GET', 'POST'], 'tracing/grid', 'Tracing::grid');\n";

file_put_contents('D:\\php-project\\sys-modern\\app\\Config\\Routes.php', $content, FILE_APPEND);
echo "Routes appended.";
